<?php

namespace Tests\Feature;

use App\Actions\Booking\SendBookingConfirmation;
use App\Enums\Role;
use App\Http\Controllers\Booking\BookingController;
use App\Models\Booking;
use App\Models\User;
use App\Notifications\BookingConfirmedNotification;
use App\Notifications\NewBookingNotification;
use App\Notifications\WelcomeNotification;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(now(config('booking.timezone'))->setDate(2026, 11, 2)->setTime(10, 0));
    }

    /**
     * @return array<string, mixed>
     */
    private function bookingForm(array $overrides = []): array
    {
        return [
            'check_in' => '2026-11-10', 'check_out' => '2026-11-13', 'adults' => 2, 'children' => 0,
            'title' => 'Ms', 'first_name' => 'Priya', 'last_name' => 'Malhotra',
            'contact_email' => 'priya@example.com', 'country_code' => '+91', 'mobile' => '98300 12345', 'terms' => '1',
            ...$overrides,
        ];
    }

    public function test_new_customers_get_a_welcome_email_but_staff_accounts_do_not(): void
    {
        Notification::fake();

        $this->post(route('register'), [
            'name' => 'Arjun Mehta', 'email' => 'arjun@example.com', 'phone' => '9830012345',
            'password' => 'pine-forest-99', 'password_confirmation' => 'pine-forest-99', 'terms' => '1',
        ]);

        $arjun = User::where('email', 'arjun@example.com')->sole();
        Notification::assertSentTo($arjun, WelcomeNotification::class, function (WelcomeNotification $notification) use ($arjun) {
            $mail = $notification->toMail($arjun);

            return str_contains($mail->subject, 'Welcome to') && $mail->actionUrl === route('login');
        });

        $superAdmin = User::factory()->staff(Role::SuperAdmin)->create();
        $this->actingAs($superAdmin)->post(route('admin.staff.store'), ['name' => 'New Staff', 'email' => 'staff@example.com', 'role' => Role::FrontDesk->value]);
        Notification::assertNotSentTo(User::where('email', 'staff@example.com')->sole(), WelcomeNotification::class);
    }

    public function test_forgot_password_sends_a_time_limited_reset_link(): void
    {
        Notification::fake();
        $customer = User::factory()->customer()->create();

        $this->get(route('login'))->assertSee(route('password.request'), false);
        $this->post(route('password.email'), ['email' => $customer->email])->assertSessionHasNoErrors();

        Notification::assertSentTo($customer, ResetPassword::class, function (ResetPassword $notification) use ($customer) {
            $mail = $notification->toMail($customer);

            return str_contains($mail->actionUrl, '/reset-password/'.$notification->token)
                && collect($mail->outroLines)->contains(fn ($line) => str_contains($line, 'expires in 60 minutes'));
        });
    }

    public function test_a_guest_booking_emails_the_guest_with_an_invoice_and_notifies_the_team_once(): void
    {
        Notification::fake();
        $manager = User::factory()->staff(Role::Manager)->create();
        $contentEditor = User::factory()->staff(Role::ContentEditor)->create();

        $this->post(route('booking.store', 'deluxe-room'), $this->bookingForm());
        $booking = Booking::sole();

        Notification::assertSentOnDemand(BookingConfirmedNotification::class, function ($notification, $channels, AnonymousNotifiable $notifiable) use ($booking) {
            $mail = $notification->toMail($notifiable);
            $attachment = $mail->rawAttachments[0] ?? null;

            return $notifiable->routes['mail'] === ['priya@example.com' => 'Ms Priya Malhotra']
                && $channels === ['mail']
                && str_contains($mail->subject, $booking->reference)
                && $attachment !== null
                && $attachment['name'] === 'INV-'.$booking->reference.'.pdf'
                && str_starts_with($attachment['data'], '%PDF');
        });

        Notification::assertSentTo($manager, NewBookingNotification::class, function (NewBookingNotification $notification) use ($manager, $booking) {
            $data = $notification->toArray($manager);

            return str_contains($data['title'], $booking->reference)
                && str_contains($data['body'], 'Ms Priya Malhotra')
                && str_contains($data['body'], 'Pay at the resort')
                && $data['amount'] === $booking->total_amount;
        });
        Notification::assertNotSentTo($contentEditor, NewBookingNotification::class);

        $this->assertNotNull($booking->fresh()->confirmation_sent_at);
        $this->assertFalse(app(SendBookingConfirmation::class)->handle($booking->fresh()));
        Notification::assertSentOnDemandTimes(BookingConfirmedNotification::class, 1);
        Notification::assertSentToTimes($manager, NewBookingNotification::class, 1);
    }

    public function test_customers_with_an_account_also_see_the_booking_in_their_notifications(): void
    {
        $customer = User::factory()->customer()->create();
        $manager = User::factory()->staff(Role::Manager)->create();

        $this->actingAs($customer)->post(route('booking.store', 'deluxe-room'), $this->bookingForm());
        $booking = Booking::sole();

        $this->assertSame(1, $customer->unreadNotifications()->count());
        $this->assertSame(1, $manager->unreadNotifications()->count());

        $this->actingAs($customer)->get(route('account.notifications.index'))
            ->assertOk()
            ->assertSee("Your booking {$booking->reference} is confirmed")
            ->assertSee('nav-count', false);

        $notification = $customer->notifications()->sole();
        $this->actingAs($customer)->post(route('account.notifications.read', $notification->id))
            ->assertRedirect(route('account.bookings.show', $booking->reference));
        $this->assertSame(0, $customer->fresh()->unreadNotifications()->count());

        $this->actingAs($manager)->get(route('admin.dashboard'))->assertSee('bell-count', false);
        $this->actingAs($manager)->get(route('admin.notifications.index'))->assertSee("New booking {$booking->reference}");
        $this->actingAs($manager)->post(route('admin.notifications.read-all'))->assertRedirect();
        $this->assertSame(0, $manager->fresh()->unreadNotifications()->count());

        // Nobody can open someone else's notification.
        $this->actingAs($manager)->post(route('account.notifications.read', $notification->id))->assertRedirect(route('admin.dashboard'));
        $this->actingAs(User::factory()->customer()->create())->post(route('account.notifications.read', $notification->id))->assertNotFound();
    }

    public function test_invoices_can_be_downloaded_by_the_right_people_only(): void
    {
        $customer = User::factory()->customer()->create();
        $this->actingAs($customer)->post(route('booking.store', 'deluxe-room'), $this->bookingForm());
        $booking = Booking::sole();

        $download = $this->actingAs($customer)->get(route('account.bookings.invoice', $booking->reference));
        $download->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $download->getContent());
        $this->assertStringContainsString('INV-'.$booking->reference.'.pdf', $download->headers->get('content-disposition'));

        $this->actingAs(User::factory()->customer()->create())->get(route('account.bookings.invoice', $booking->reference))->assertNotFound();

        auth()->logout();
        $this->get(BookingController::invoiceUrl($booking))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->get(route('booking.invoice', $booking))->assertForbidden();

        $this->actingAs(User::factory()->staff(Role::FrontDesk)->create())->get(route('admin.bookings.invoice', $booking))->assertOk();
        $this->actingAs(User::factory()->staff(Role::ContentEditor)->create())->get(route('admin.bookings.invoice', $booking))->assertForbidden();
    }

    public function test_emails_use_the_resort_template_with_the_logo_inside(): void
    {
        $user = User::factory()->create(['name' => 'Priya Malhotra']);

        $user->notify(new WelcomeNotification);

        $email = app('mailer')->getSymfonyTransport()->messages()->last()->getOriginalMessage();
        $html = $email->getHtmlBody();

        $this->assertStringContainsString('cid:resort-logo', $html);
        $this->assertStringContainsString('Rhenock', $html);
        $this->assertStringNotContainsString('laravel.com', $html);
        $this->assertCount(1, $email->getAttachments());
        $this->assertSame('resort-logo', $email->getAttachments()[0]->getName());
    }
}
