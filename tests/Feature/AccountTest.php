<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_guests_are_sent_to_login(): void
    {
        $this->get('/account')->assertRedirect(route('login'));
        $this->get('/account/profile')->assertRedirect(route('login'));
    }

    public function test_verified_customer_sees_their_dashboard(): void
    {
        $customer = User::factory()->customer()->create(['name' => 'Arjun Mehta']);

        $this->actingAs($customer)->get('/account')
            ->assertOk()
            ->assertSee('Namaste, Arjun')
            ->assertSee('No stays booked yet');
    }

    public function test_unverified_customer_can_still_edit_their_profile(): void
    {
        $customer = User::factory()->customer()->unverified()->create();

        $this->actingAs($customer)->get('/account')->assertRedirect(route('verification.notice'));
        $this->actingAs($customer)->get('/account/profile')->assertOk()->assertSee("isn't verified yet", false);
    }

    public function test_profile_update_normalises_phone(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->put(route('user-profile-information.update'), [
                'name' => 'Sunita Devraj',
                'email' => $customer->email,
                'phone' => '098300 12345',
            ])
            ->assertSessionHasNoErrors();

        $customer->refresh();
        $this->assertSame('Sunita Devraj', $customer->name);
        $this->assertSame('+919830012345', $customer->phone);
        $this->assertTrue($customer->hasVerifiedEmail());
    }

    public function test_changing_email_requires_verifying_the_new_address(): void
    {
        Notification::fake();
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)->put(route('user-profile-information.update'), [
            'name' => $customer->name,
            'email' => 'new-address@example.com',
            'phone' => $customer->phone,
        ]);

        $customer->refresh();
        $this->assertSame('new-address@example.com', $customer->email);
        $this->assertFalse($customer->hasVerifiedEmail());
        Notification::assertSentTo($customer, VerifyEmail::class);
    }

    public function test_password_can_be_changed(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->put(route('user-password.update'), [
                'current_password' => 'password',
                'password' => 'pine-forest-99',
                'password_confirmation' => 'pine-forest-99',
            ])
            ->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('pine-forest-99', $customer->fresh()->password));
    }

    public function test_security_page_opens_without_asking_for_the_password_again(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)->get('/account/security')
            ->assertOk()
            ->assertSee('Turn on two-factor authentication');
    }

    public function test_customer_can_turn_on_two_factor_and_sees_qr_code(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->post(route('two-factor.enable'))
            ->assertRedirect();

        $this->assertNotNull($customer->fresh()->two_factor_secret);

        $this->actingAs($customer->fresh())
            ->get('/account/security')
            ->assertSee('Setup not finished')
            ->assertSee('<svg', false);
    }

    public function test_customer_can_delete_their_account_and_personal_data_is_removed(): void
    {
        $customer = User::factory()->customer()->create([
            'name' => 'Priya Malhotra',
            'email' => 'priya@example.com',
        ]);

        $this->actingAs($customer)
            ->delete(route('account.destroy'), ['password' => 'password'])
            ->assertRedirect(route('home'));

        $this->assertGuest();

        $deleted = User::withTrashed()->find($customer->id);
        $this->assertTrue($deleted->trashed());
        $this->assertSame('Deleted user', $deleted->name);
        $this->assertNull($deleted->phone);
        $this->assertStringEndsWith('@deleted.invalid', $deleted->email);
        $this->assertFalse($deleted->hasRole(Role::Customer->value));
        $this->assertDatabaseMissing('users', ['email' => 'priya@example.com']);
        $this->assertSame(0, DB::table('sessions')->where('user_id', $customer->id)->count());
    }

    public function test_account_deletion_requires_the_correct_password(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->delete(route('account.destroy'), ['password' => 'not-my-password'])
            ->assertSessionHasErrorsIn('deleteAccount', 'password');

        $this->assertFalse($customer->fresh()->trashed());
    }

    public function test_staff_are_kept_out_of_the_customer_area(): void
    {
        $manager = User::factory()->staff(Role::Manager)->create();

        $this->actingAs($manager)->get('/account')->assertRedirect(route('admin.dashboard'));
        $this->actingAs($manager)->get('/account/profile')->assertRedirect(route('admin.dashboard'));
        $this->actingAs($manager)
            ->delete(route('account.destroy'), ['password' => 'password'])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertFalse($manager->fresh()->trashed());
    }
}
