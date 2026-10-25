<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Http\Controllers\Booking\BookingController;
use App\Models\Booking;
use App\Models\User;
use App\Notifications\AccountSetupNotification;
use App\Support\GuestDevice;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class GuestAccountTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(now(config('booking.timezone'))->setDate(2026, 11, 2)->setTime(10, 0));
        Notification::fake();
    }

    /**
     * @return array<string, mixed>
     */
    private function bookingForm(array $overrides = []): array
    {
        return [
            'check_in' => '2026-11-10', 'check_out' => '2026-11-12', 'adults' => 2, 'children' => 0,
            'title' => 'Ms', 'first_name' => 'Priya', 'last_name' => 'Malhotra',
            'contact_email' => 'Priya@Example.com', 'country_code' => '+91', 'mobile' => '98300 12345', 'terms' => '1',
            ...$overrides,
        ];
    }

    public function test_a_guest_booking_creates_an_account_and_emails_a_set_password_link(): void
    {
        $this->post(route('booking.store', 'deluxe-room'), $this->bookingForm());

        $booking = Booking::sole();
        $user = User::where('email', 'priya@example.com')->sole();

        $this->assertSame($user->id, $booking->user_id);
        $this->assertSame('Priya Malhotra', $user->name);
        $this->assertSame('+919830012345', $user->phone);
        $this->assertTrue($user->hasRole(Role::Customer->value));
        $this->assertNotNull($user->created_from_booking_at);
        $this->assertFalse($user->hasVerifiedEmail());

        Notification::assertSentTo($user, AccountSetupNotification::class, function (AccountSetupNotification $notification) use ($user, $booking) {
            $mail = $notification->toMail($user);

            return str_contains($mail->subject, 'account is ready')
                && str_contains(implode(' ', $mail->introLines), $booking->reference)
                && str_contains($mail->actionUrl, '/account/setup/'.$user->id);
        });

        $this->get(BookingController::confirmationUrl($booking))->assertSee("You're signed in to your new account", false);
        $this->assertAuthenticatedAs($user);
    }

    public function test_the_guest_is_signed_in_after_booking_but_cannot_change_the_account_until_the_password_is_set(): void
    {
        $this->post(route('booking.store', 'deluxe-room'), $this->bookingForm())
            ->assertRedirect(BookingController::confirmationUrl(Booking::sole()));
        $this->get(BookingController::confirmationUrl(Booking::sole()))->assertOk();

        $user = User::where('email', 'priya@example.com')->sole();
        $this->assertAuthenticatedAs($user);

        $this->get(route('account.dashboard'))->assertOk()->assertSee(Booking::sole()->reference)->assertSee('Set a password to keep your account');
        $this->get(route('account.bookings.show', Booking::sole()->reference))->assertOk();

        $this->get(route('account.profile'))->assertRedirect(route('account.dashboard'));
        $this->get(route('account.security'))->assertRedirect(route('account.dashboard'));
        $this->put('/user/password', ['current_password' => 'x', 'password' => 'new-pass-123', 'password_confirmation' => 'new-pass-123'])->assertRedirect(route('account.dashboard'));
        $this->post('/user/two-factor-authentication')->assertRedirect(route('account.dashboard'));
        $this->delete(route('account.destroy'))->assertRedirect(route('account.dashboard'));
        $this->assertModelExists($user);

        $this->post(route('account.setup.resend'))->assertSessionHas('status');
        Notification::assertSentToTimes($user, AccountSetupNotification::class, 2);
    }

    public function test_only_the_browser_that_booked_is_signed_in(): void
    {
        $this->post(route('booking.store', 'deluxe-room'), $this->bookingForm());
        $url = BookingController::confirmationUrl(Booking::sole());

        $this->flushSession();
        Auth::logout();

        $this->get($url)->assertOk()->assertSee("We've created an account for you", false);
        $this->assertGuest();
    }

    public function test_booking_with_an_existing_accounts_email_never_signs_into_it(): void
    {
        $owner = User::factory()->customer()->create(['email' => 'priya@example.com', 'email_verified_at' => now()]);

        $this->post(route('booking.store', 'deluxe-room'), $this->bookingForm());
        $this->get(BookingController::confirmationUrl(Booking::sole()))->assertOk()->assertSee('Added to your account');

        $this->assertGuest();
        $this->assertSame($owner->id, Booking::sole()->user_id);
    }

    public function test_the_guest_sets_a_password_and_then_sees_the_booking_in_my_stays(): void
    {
        $this->post(route('booking.store', 'deluxe-room'), $this->bookingForm());
        $user = User::where('email', 'priya@example.com')->sole();
        $booking = Booking::sole();

        $setup = $this->get(AccountSetupNotification::setupUrl($user));
        $setup->assertRedirectContains('/reset-password/');
        $resetUrl = $setup->headers->get('Location');
        $this->get($resetUrl)->assertOk()->assertSee('Set your password');

        $token = str($resetUrl)->after('/reset-password/')->before('?')->toString();
        $this->post(route('password.update'), [
            'token' => $token, 'email' => 'priya@example.com',
            'password' => 'pine-forest-99', 'password_confirmation' => 'pine-forest-99',
        ])->assertRedirect(route('login'));

        $this->assertTrue($user->fresh()->hasVerifiedEmail());

        $this->post(route('login'), ['email' => 'priya@example.com', 'password' => 'pine-forest-99']);
        $this->get(route('account.dashboard'))->assertOk()->assertSee($booking->reference);

        // Used once: the setup link now just points to sign-in.
        $this->get(AccountSetupNotification::setupUrl($user->fresh()))->assertRedirect(route('login'));
    }

    public function test_a_second_guest_booking_goes_into_the_same_verified_account_without_another_setup_email(): void
    {
        $user = User::factory()->customer()->create(['email' => 'priya@example.com', 'email_verified_at' => now()]);

        $this->post(route('booking.store', 'deluxe-room'), $this->bookingForm());

        $this->assertSame($user->id, Booking::sole()->user_id);
        $this->assertSame(1, User::where('email', 'priya@example.com')->count());
        Notification::assertNotSentTo($user, AccountSetupNotification::class);
    }

    public function test_bookings_never_go_into_unverified_or_staff_accounts(): void
    {
        $unverified = User::factory()->customer()->create(['email' => 'priya@example.com', 'email_verified_at' => null]);
        User::factory()->staff(Role::FrontDesk)->create(['email' => 'desk@example.com']);

        $this->post(route('booking.store', 'deluxe-room'), $this->bookingForm());
        $this->post(route('booking.store', 'standard-room'), $this->bookingForm(['contact_email' => 'desk@example.com']));

        $this->assertSame(2, Booking::whereNull('user_id')->count());

        // Once the owner verifies the address, their guest booking appears in the account.
        $unverified->markEmailAsVerified();
        event(new Verified($unverified));
        $this->assertSame($unverified->id, Booking::where('contact_email', 'priya@example.com')->sole()->user_id);
    }

    public function test_paying_online_the_account_is_created_and_signed_in_before_the_booking_is_made(): void
    {
        config(['services.razorpay' => ['key_id' => 'rzp_test_x', 'key_secret' => 's', 'webhook_secret' => 'w']]);

        $this->post(route('booking.store', 'deluxe-room'), $this->bookingForm(['payment_method' => 'online']));

        $user = User::where('email', 'priya@example.com')->sole();
        $booking = Booking::sole();
        $this->assertAuthenticatedAs($user);
        $this->assertSame($user->id, $booking->user_id, 'The booking is saved with the new user id');
        $this->assertTrue($booking->isAwaitingPayment());
        Notification::assertNotSentTo($user, AccountSetupNotification::class);
    }

    public function test_the_account_exists_and_is_signed_in_when_the_booking_row_is_created(): void
    {
        $seen = null;
        Booking::creating(function (Booking $booking) use (&$seen): void {
            $seen = ['user_id' => $booking->user_id, 'signed_in_as' => auth()->id()];
        });

        $this->post(route('booking.store', 'deluxe-room'), $this->bookingForm());

        $user = User::where('email', 'priya@example.com')->sole();
        $this->assertSame(['user_id' => $user->id, 'signed_in_as' => $user->id], $seen);
    }

    public function test_a_leftover_account_whose_bookings_were_removed_is_replaced_and_the_guest_signed_in(): void
    {
        $this->post(route('booking.store', 'deluxe-room'), $this->bookingForm());
        $old = User::where('email', 'priya@example.com')->sole();
        Booking::query()->delete();
        $this->flushSession();
        Auth::logout();

        $this->post(route('booking.store', 'deluxe-room'), $this->bookingForm());
        $this->get(BookingController::confirmationUrl(Booking::sole()))->assertSee("You're signed in to your new account", false);

        $new = User::where('email', 'priya@example.com')->sole();
        $this->assertNotSame($old->id, $new->id);
        $this->assertSame($new->id, Booking::sole()->user_id);
        $this->assertAuthenticatedAs($new);
    }

    public function test_a_second_booking_for_an_account_still_waiting_for_its_password_joins_it_once_claimed(): void
    {
        $this->post(route('booking.store', 'deluxe-room'), $this->bookingForm());
        $user = User::where('email', 'priya@example.com')->sole();
        $this->flushSession();
        Auth::logout();

        $this->post(route('booking.store', 'standard-room'), $this->bookingForm());
        $second = Booking::latest('id')->first();

        $this->assertNull($second->user_id);
        $this->get(BookingController::confirmationUrl($second))->assertSee('Set your password to see this booking in your account');
        $this->assertGuest();
        Notification::assertSentToTimes($user, AccountSetupNotification::class, 2);

        $setup = $this->get(AccountSetupNotification::setupUrl($user));
        $token = str($setup->headers->get('Location'))->after('/reset-password/')->before('?')->toString();
        $this->post(route('password.update'), [
            'token' => $token, 'email' => 'priya@example.com',
            'password' => 'pine-forest-99', 'password_confirmation' => 'pine-forest-99',
        ]);

        $this->assertSame(2, $user->bookings()->count());
    }

    public function test_the_same_browser_booking_again_after_signing_out_joins_the_account_and_is_signed_in(): void
    {
        $this->post(route('booking.store', 'deluxe-room'), $this->bookingForm());
        $first = $this->get(BookingController::confirmationUrl(Booking::sole()));
        $first->assertCookie(GuestDevice::COOKIE);
        $user = User::where('email', 'priya@example.com')->sole();

        $this->post(route('logout'));
        $this->assertGuest();

        $this->withCookie(GuestDevice::COOKIE, (string) $user->id)
            ->post(route('booking.store', 'standard-room'), $this->bookingForm());
        $second = Booking::latest('id')->first();

        $this->assertSame($user->id, $second->user_id);
        $this->get(BookingController::confirmationUrl($second))->assertSee("You're signed in to your new account", false);
        $this->assertAuthenticatedAs($user);
        $this->assertSame(2, $user->bookings()->count());
    }
}
