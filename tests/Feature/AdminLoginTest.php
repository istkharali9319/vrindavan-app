<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    /**
     * @return array<string, string>
     */
    private function credentials(User $user): array
    {
        return ['email' => $user->email, 'password' => 'password', 'portal' => 'admin'];
    }

    public function test_admin_redirects_guests_to_the_staff_sign_in_page(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');

        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('Staff sign in')
            ->assertSee('admin-assets/css/style.css', false)
            ->assertDontSee('class="site-header"', false)
            ->assertSee('name="portal" value="admin"', false);
    }

    public function test_staff_sign_in_goes_straight_to_the_dashboard(): void
    {
        $frontDesk = User::factory()->staff(Role::FrontDesk)->create();

        $this->from('/admin/login')->post('/login', $this->credentials($frontDesk))
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($frontDesk);
        $this->get('/admin')->assertOk()->assertSee('pc-sidebar', false);
    }

    public function test_staff_who_turned_on_two_factor_get_the_admin_styled_challenge(): void
    {
        $manager = User::factory()->staff(Role::Manager)->withTwoFactor()->create();

        $this->from('/admin/login')->post('/login', $this->credentials($manager))
            ->assertRedirect(route('two-factor.login'));

        $this->assertGuest();
        $this->get(route('two-factor.login'))
            ->assertOk()
            ->assertSee('admin-assets/css/style.css', false)
            ->assertSee('One more step');
    }

    public function test_password_confirmation_and_admin_security_pages_no_longer_exist(): void
    {
        $this->actingAs(User::factory()->staff(Role::Manager)->create());

        $this->get('/user/confirm-password')->assertNotFound();
        $this->post('/user/confirm-password', ['password' => 'password'])->assertNotFound();
        $this->get('/admin/security')->assertNotFound();
    }

    public function test_customers_are_refused_on_the_staff_sign_in(): void
    {
        $customer = User::factory()->customer()->create();

        $this->from('/admin/login')->post('/login', $this->credentials($customer))
            ->assertRedirect('/admin/login')
            ->assertSessionHasErrors(['email' => 'This sign-in is for resort staff. Guests can sign in on the website.']);

        $this->assertGuest();
    }

    public function test_wrong_password_returns_to_the_staff_sign_in(): void
    {
        $manager = User::factory()->staff(Role::Manager)->create();

        $this->from('/admin/login')
            ->post('/login', ['email' => $manager->email, 'password' => 'nope', 'portal' => 'admin'])
            ->assertRedirect('/admin/login')
            ->assertSessionHasErrors('email');
    }

    public function test_signed_in_staff_visiting_the_login_page_go_to_the_dashboard(): void
    {
        $this->actingAs(User::factory()->staff(Role::Manager)->create())
            ->get('/admin/login')
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_signing_out_of_the_admin_returns_to_the_staff_sign_in(): void
    {
        $this->actingAs(User::factory()->staff(Role::Manager)->create())
            ->post('/logout', ['portal' => 'admin'])
            ->assertRedirect(route('admin.login'));

        $this->assertGuest();
    }

    public function test_admin_profile_page_uses_the_admin_layout(): void
    {
        $this->actingAs(User::factory()->staff(Role::Manager)->create())
            ->get('/admin/profile')
            ->assertOk()
            ->assertSee('pc-sidebar', false)
            ->assertSee('Change password')
            ->assertDontSee('Two-factor authentication');
    }

    public function test_staff_password_reset_ends_on_the_staff_sign_in(): void
    {
        $manager = User::factory()->staff(Role::Manager)->create();

        $this->get(route('password.request', ['portal' => 'admin']))->assertSee('admin-assets/css/style.css', false);

        $token = Password::broker()->createToken($manager);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $manager->email,
            'password' => 'new-secret-2026',
            'password_confirmation' => 'new-secret-2026',
        ])->assertRedirect(route('admin.login'));
    }
}
