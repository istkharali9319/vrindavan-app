<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_users_can_be_searched_and_filtered(): void
    {
        User::factory()->customer()->create(['name' => 'Priya Malhotra', 'phone' => '+919432064521']);
        User::factory()->customer()->create(['name' => 'Arjun Mehta']);
        $manager = User::factory()->staff(Role::Manager)->create(['name' => 'Pema Lepcha']);

        $this->actingAs($manager)->get('/admin/users?search=priya')
            ->assertOk()->assertSee('Priya Malhotra')->assertDontSee('Arjun Mehta');

        $this->actingAs($manager)->get('/admin/users?search=64521')
            ->assertSee('Priya Malhotra')->assertDontSee('Arjun Mehta');

        $this->actingAs($manager)->get('/admin/users?search=zz')
            ->assertDontSee('Priya Malhotra')->assertSee('No users match');

        $this->actingAs($manager)->get('/admin/users?type=staff')
            ->assertSee('Pema Lepcha')->assertDontSee('Priya Malhotra');
    }

    public function test_manager_can_suspend_and_reactivate_a_customer(): void
    {
        $customer = User::factory()->customer()->create();
        $manager = User::factory()->staff(Role::Manager)->create();

        $this->actingAs($manager)->post(route('admin.users.suspend', $customer))->assertRedirect();
        $this->assertSame(UserStatus::Suspended, $customer->fresh()->status);

        $this->actingAs($manager)->post(route('admin.users.reactivate', $customer))->assertRedirect();
        $this->assertSame(UserStatus::Active, $customer->fresh()->status);
    }

    public function test_front_desk_can_view_but_not_suspend(): void
    {
        $customer = User::factory()->customer()->create();
        $frontDesk = User::factory()->staff(Role::FrontDesk)->create();

        $this->actingAs($frontDesk)->get(route('admin.users.show', $customer))->assertOk()->assertDontSee('Suspend account');
        $this->actingAs($frontDesk)->post(route('admin.users.suspend', $customer))->assertForbidden();

        $this->assertTrue($customer->fresh()->isActive());
    }

    public function test_nobody_can_suspend_themselves(): void
    {
        $superAdmin = User::factory()->staff(Role::SuperAdmin)->create();

        $this->actingAs($superAdmin)->post(route('admin.users.suspend', $superAdmin))->assertForbidden();
        $this->assertTrue($superAdmin->fresh()->isActive());
    }

    public function test_manager_cannot_suspend_a_super_admin(): void
    {
        $superAdmin = User::factory()->staff(Role::SuperAdmin)->create();
        $manager = User::factory()->staff(Role::Manager)->create();

        $this->actingAs($manager)->post(route('admin.users.suspend', $superAdmin))->assertForbidden();
        $this->assertTrue($superAdmin->fresh()->isActive());
    }

    public function test_deleted_accounts_are_listed_under_the_deleted_filter(): void
    {
        $customer = User::factory()->customer()->create(['name' => 'Gone Guest']);
        $customer->delete();
        $manager = User::factory()->staff(Role::Manager)->create();

        $this->actingAs($manager)->get('/admin/users')->assertDontSee('Gone Guest');
        $this->actingAs($manager)->get('/admin/users?status=deleted')->assertSee('Gone Guest');
        $this->actingAs($manager)->get(route('admin.users.show', $customer))->assertOk()->assertSee('Deleted');
    }

    public function test_super_admin_adds_staff_who_receive_a_set_password_link(): void
    {
        Notification::fake();
        $superAdmin = User::factory()->staff(Role::SuperAdmin)->create();

        $this->actingAs($superAdmin)
            ->post(route('admin.staff.store'), [
                'name' => 'Pema Lepcha',
                'email' => 'Pema@Vrindavan.test',
                'phone' => '94320 11111',
                'role' => Role::FrontDesk->value,
            ])
            ->assertRedirect(route('admin.staff.index'));

        $member = User::firstWhere('email', 'pema@vrindavan.test');
        $this->assertTrue($member->hasRole(Role::FrontDesk->value));
        $this->assertSame('+919432011111', $member->phone);
        $this->assertTrue($member->hasVerifiedEmail());
        Notification::assertSentTo($member, ResetPassword::class);
    }

    public function test_staff_cannot_be_given_the_customer_role(): void
    {
        $superAdmin = User::factory()->staff(Role::SuperAdmin)->create();

        $this->actingAs($superAdmin)
            ->post(route('admin.staff.store'), [
                'name' => 'Someone',
                'email' => 'someone@example.com',
                'role' => Role::Customer->value,
            ])
            ->assertSessionHasErrors('role');
    }

    public function test_super_admin_can_change_a_staff_role(): void
    {
        $superAdmin = User::factory()->staff(Role::SuperAdmin)->create();
        $member = User::factory()->staff(Role::FrontDesk)->create();

        $this->actingAs($superAdmin)
            ->put(route('admin.staff.update', $member), ['name' => $member->name, 'role' => Role::Accounts->value])
            ->assertRedirect(route('admin.staff.index'));

        $member->refresh();
        $this->assertTrue($member->hasRole(Role::Accounts->value));
        $this->assertFalse($member->hasRole(Role::FrontDesk->value));
    }

    public function test_super_admin_cannot_remove_their_own_super_admin_role(): void
    {
        $superAdmin = User::factory()->staff(Role::SuperAdmin)->create();

        $this->actingAs($superAdmin)
            ->put(route('admin.staff.update', $superAdmin), ['name' => $superAdmin->name, 'role' => Role::Manager->value])
            ->assertSessionHasErrors('role');

        $this->assertTrue($superAdmin->fresh()->hasRole(Role::SuperAdmin->value));
    }

    public function test_customer_accounts_cannot_be_edited_as_staff(): void
    {
        $superAdmin = User::factory()->staff(Role::SuperAdmin)->create();
        $customer = User::factory()->customer()->create();

        $this->actingAs($superAdmin)->get(route('admin.staff.edit', $customer))->assertNotFound();
    }

    public function test_manager_cannot_manage_staff(): void
    {
        $manager = User::factory()->staff(Role::Manager)->create();

        $this->actingAs($manager)->post(route('admin.staff.store'), [
            'name' => 'Sneaky',
            'email' => 'sneaky@example.com',
            'role' => Role::SuperAdmin->value,
        ])->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'sneaky@example.com']);
    }
}
