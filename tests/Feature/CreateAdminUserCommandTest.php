<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateAdminUserCommandTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_it_creates_a_verified_super_admin(): void
    {
        $this->artisan('admin:create', ['email' => 'Owner@Vrindavan.test', '--name' => 'Resort Owner', '--phone' => '94320 64521'])
            ->expectsQuestion('Password (min. 8 characters, letters and numbers)', 'kanchenjunga-8586')
            ->expectsQuestion('Confirm password', 'kanchenjunga-8586')
            ->assertSuccessful();

        $owner = User::firstWhere('email', 'owner@vrindavan.test');

        $this->assertNotNull($owner);
        $this->assertTrue($owner->hasRole(Role::SuperAdmin->value));
        $this->assertTrue($owner->hasVerifiedEmail());
        $this->assertSame('+919432064521', $owner->phone);
        $this->assertTrue(Hash::check('kanchenjunga-8586', $owner->password));
    }

    public function test_it_refuses_mismatched_passwords(): void
    {
        $this->artisan('admin:create', ['email' => 'owner@vrindavan.test', '--name' => 'Resort Owner'])
            ->expectsQuestion('Password (min. 8 characters, letters and numbers)', 'kanchenjunga-8586')
            ->expectsQuestion('Confirm password', 'something-else-1')
            ->assertFailed();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_it_refuses_an_email_that_already_exists(): void
    {
        User::factory()->create(['email' => 'owner@vrindavan.test']);

        $this->artisan('admin:create', ['email' => 'owner@vrindavan.test', '--name' => 'Resort Owner'])
            ->expectsQuestion('Password (min. 8 characters, letters and numbers)', 'kanchenjunga-8586')
            ->expectsQuestion('Confirm password', 'kanchenjunga-8586')
            ->assertFailed();
    }
}
