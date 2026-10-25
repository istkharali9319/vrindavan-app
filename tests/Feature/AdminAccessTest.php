<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_guests_are_sent_to_login(): void
    {
        $this->get('/admin')->assertRedirect(route('admin.login'));
        $this->get('/admin/users')->assertRedirect(route('admin.login'));
    }

    public function test_customers_are_forbidden(): void
    {
        $this->actingAs(User::factory()->customer()->withTwoFactor()->create())
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_staff_without_two_factor_open_the_admin_directly(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole(Role::Manager->value);

        $this->actingAs($manager)->get('/admin')->assertOk();
        $this->actingAs($manager)->get('/admin/users')->assertOk();
    }

    /**
     * @return array<string, array{Role, bool, bool, bool}>
     */
    public static function roleAccess(): array
    {
        // role => [dashboard, users list, staff management]
        return [
            'super admin' => [Role::SuperAdmin, true, true, true],
            'manager' => [Role::Manager, true, true, false],
            'front desk' => [Role::FrontDesk, true, true, false],
            'accounts' => [Role::Accounts, true, true, false],
            'content editor' => [Role::ContentEditor, true, false, false],
        ];
    }

    #[DataProvider('roleAccess')]
    public function test_each_role_reaches_only_its_modules(Role $role, bool $dashboard, bool $users, bool $staff): void
    {
        $member = User::factory()->staff($role)->create();

        $this->actingAs($member)->get('/admin')->assertStatus($dashboard ? 200 : 403);
        $this->actingAs($member)->get('/admin/users')->assertStatus($users ? 200 : 403);
        $this->actingAs($member)->get('/admin/staff')->assertStatus($staff ? 200 : 403);
    }

    public function test_sidebar_only_lists_permitted_modules(): void
    {
        $editor = User::factory()->staff(Role::ContentEditor)->create();

        $this->actingAs($editor)->get('/admin')
            ->assertOk()
            ->assertDontSee('Users &amp; customers', false)
            ->assertDontSee('Staff &amp; roles', false);

        $superAdmin = User::factory()->staff(Role::SuperAdmin)->create();

        $this->actingAs($superAdmin)->get('/admin')
            ->assertSee('Users &amp; customers', false)
            ->assertSee('Staff &amp; roles', false);
    }

    public function test_dashboard_shows_customer_counts(): void
    {
        User::factory()->customer()->count(3)->create();
        User::factory()->customer()->unverified()->create();

        $this->actingAs(User::factory()->staff(Role::Manager)->create())
            ->get('/admin')
            ->assertOk()
            ->assertSeeInOrder(['Customers', '4', 'New customers (30 days)', '4', 'Awaiting email verification', '1']);
    }
}
