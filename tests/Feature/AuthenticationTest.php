<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_login_page_renders_with_site_layout(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Welcome back')
            ->assertSee('class="site-header"', false);
    }

    public function test_customer_signs_in_and_lands_on_their_account(): void
    {
        $customer = User::factory()->customer()->create(['email' => 'guest@example.com']);

        $this->post('/login', ['email' => 'GUEST@example.com', 'password' => 'password'])
            ->assertRedirect(route('account.dashboard'));

        $this->assertAuthenticatedAs($customer);
        $this->assertNotNull($customer->fresh()->last_login_at);
    }

    public function test_customer_returns_to_the_page_they_were_heading_for(): void
    {
        $customer = User::factory()->customer()->create();

        $this->get(route('account.profile'))->assertRedirect(route('login'));

        $this->post('/login', ['email' => $customer->email, 'password' => 'password'])
            ->assertRedirect(route('account.profile'));
    }

    public function test_wrong_password_is_rejected(): void
    {
        $customer = User::factory()->customer()->create();

        $this->post('/login', ['email' => $customer->email, 'password' => 'wrong-password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_suspended_user_cannot_sign_in(): void
    {
        $customer = User::factory()->customer()->suspended()->create();

        $this->post('/login', ['email' => $customer->email, 'password' => 'password'])
            ->assertSessionHasErrors(['email' => 'This account has been suspended. Please contact us if you think this is a mistake.']);

        $this->assertGuest();
    }

    public function test_user_suspended_mid_session_is_signed_out_on_next_request(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)->get('/account')->assertOk();

        $customer->forceFill(['status' => UserStatus::Suspended])->save();

        $this->get('/account')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_login_is_rate_limited_after_five_attempts(): void
    {
        $customer = User::factory()->customer()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $customer->email, 'password' => 'wrong-password']);
        }

        $this->post('/login', ['email' => $customer->email, 'password' => 'password'])->assertStatus(429);
        $this->assertGuest();
    }

    public function test_staff_cannot_use_the_guest_login(): void
    {
        $manager = User::factory()->staff(Role::Manager)->create();

        $this->post('/login', ['email' => $manager->email, 'password' => 'password'])
            ->assertSessionHasErrors(['email' => 'Staff accounts sign in at '.route('admin.login').'.']);

        $this->assertGuest();
    }

    public function test_user_can_sign_out(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)->post('/logout')->assertRedirect('/');

        $this->assertGuest();
    }

    public function test_header_shows_sign_in_for_guests_and_account_link_for_customers(): void
    {
        $this->get('/')->assertSee('>Sign in</a>', false);

        $this->actingAs(User::factory()->customer()->create())
            ->get('/')
            ->assertSee('href="'.route('account.dashboard').'" class="nav-account">My account</a>', false);
    }
}
