<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class UniquePhoneTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(now(config('booking.timezone'))->setDate(2026, 11, 2)->setTime(10, 0));
        Notification::fake();
        User::factory()->customer()->create(['email' => 'arjun@example.com', 'phone' => '+919830012345', 'email_verified_at' => now()]);
    }

    /**
     * @return array<string, mixed>
     */
    private function bookingForm(array $overrides = []): array
    {
        return [
            'check_in' => '2026-11-10', 'check_out' => '2026-11-12', 'adults' => 2, 'children' => 0,
            'title' => 'Ms', 'first_name' => 'Priya', 'last_name' => 'Malhotra',
            'contact_email' => 'priya@example.com', 'country_code' => '+91', 'mobile' => '98300 12345', 'terms' => '1',
            ...$overrides,
        ];
    }

    public function test_registering_with_a_mobile_already_on_another_account_is_refused(): void
    {
        $this->post(route('register'), [
            'name' => 'Priya Malhotra', 'email' => 'priya@example.com', 'phone' => '+91 98300-12345',
            'password' => 'pine-forest-99', 'password_confirmation' => 'pine-forest-99', 'terms' => '1',
        ])->assertSessionHasErrors(['phone' => 'This mobile number is already registered with another account. Please sign in, or use a different number.']);

        $this->assertDatabaseMissing('users', ['email' => 'priya@example.com']);

        $this->post(route('register'), [
            'name' => 'Priya Malhotra', 'email' => 'priya@example.com', 'phone' => '98300 99999',
            'password' => 'pine-forest-99', 'password_confirmation' => 'pine-forest-99', 'terms' => '1',
        ])->assertSessionHasNoErrors();
    }

    public function test_a_guest_cannot_book_with_a_mobile_that_belongs_to_someone_elses_account(): void
    {
        $this->post(route('booking.store', 'deluxe-room'), $this->bookingForm())
            ->assertSessionHasErrors('contact_phone');

        $this->assertSame(0, Booking::count());
        $this->assertSame(0, User::where('email', 'priya@example.com')->count());
        $this->assertGuest();
    }

    public function test_a_guest_can_book_with_their_own_accounts_mobile_and_with_a_new_number(): void
    {
        // Same email as the account that owns the number: it's their own number.
        $this->post(route('booking.store', 'deluxe-room'), $this->bookingForm(['contact_email' => 'arjun@example.com']))
            ->assertSessionHasNoErrors();

        // A number nobody has yet.
        $this->post(route('booking.store', 'standard-room'), $this->bookingForm(['mobile' => '98300 77777']))
            ->assertSessionHasNoErrors();

        $this->assertSame(2, Booking::count());
        $this->assertSame('+919830077777', User::where('email', 'priya@example.com')->value('phone'));
    }

    public function test_signed_in_customers_can_book_with_any_contact_number(): void
    {
        $customer = User::factory()->customer()->create(['phone' => '+919830055555']);

        $this->actingAs($customer)->post(route('booking.store', 'deluxe-room'), $this->bookingForm())
            ->assertSessionHasNoErrors();

        $this->assertSame($customer->id, Booking::sole()->user_id);
    }

    public function test_profile_and_staff_phones_must_be_unique_too(): void
    {
        $customer = User::factory()->customer()->create(['phone' => '+919830066666', 'email_verified_at' => now()]);

        $this->actingAs($customer)->put('/user/profile-information', [
            'name' => $customer->name, 'email' => $customer->email, 'phone' => '9830012345',
        ])->assertSessionHasErrorsIn('updateProfileInformation', 'phone');

        $this->actingAs($customer)->put('/user/profile-information', [
            'name' => $customer->name, 'email' => $customer->email, 'phone' => '9830066666',
        ])->assertSessionHasNoErrors();

        $admin = User::factory()->staff(Role::SuperAdmin)->create();
        $this->actingAs($admin)->post(route('admin.staff.store'), [
            'name' => 'New Staff', 'email' => 'staff@example.com', 'phone' => '9830012345', 'role' => Role::FrontDesk->value,
        ])->assertSessionHasErrors(['phone' => 'This mobile number is already used by another account.']);
    }
}
