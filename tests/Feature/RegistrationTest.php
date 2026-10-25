<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    /**
     * @return array<string, string>
     */
    private function validInput(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Priya Malhotra',
            'email' => 'Priya@Example.com',
            'phone' => '94320 64521',
            'password' => 'mountain-air-2026',
            'password_confirmation' => 'mountain-air-2026',
        ], $overrides);
    }

    public function test_registration_page_renders(): void
    {
        $this->get('/register')->assertOk()->assertSee('Create an account');
    }

    public function test_guest_can_register_as_a_customer(): void
    {
        Notification::fake();

        $this->post('/register', $this->validInput())
            ->assertRedirect(route('account.dashboard'));

        $user = User::firstWhere('email', 'priya@example.com');

        $this->assertNotNull($user, 'Email should be stored lowercased');
        $this->assertSame('+919432064521', $user->phone);
        $this->assertTrue($user->hasRole(Role::Customer->value));
        $this->assertFalse($user->isStaff());
        $this->assertAuthenticatedAs($user);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_new_customer_must_verify_email_before_seeing_the_dashboard(): void
    {
        Notification::fake();

        $this->post('/register', $this->validInput());

        $this->get('/account')->assertRedirect(route('verification.notice'));
    }

    public function test_phone_is_required_and_must_be_valid(): void
    {
        $this->post('/register', $this->validInput(['phone' => '']))->assertSessionHasErrors('phone');
        $this->post('/register', $this->validInput(['phone' => '12345']))->assertSessionHasErrors('phone');

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_password_must_contain_letters_and_numbers(): void
    {
        $this->post('/register', $this->validInput([
            'password' => 'onlyletters',
            'password_confirmation' => 'onlyletters',
        ]))->assertSessionHasErrors('password');
    }

    public function test_email_must_be_unique(): void
    {
        User::factory()->create(['email' => 'priya@example.com']);

        $this->post('/register', $this->validInput())->assertSessionHasErrors('email');
    }

    public function test_registrations_are_rate_limited_per_ip(): void
    {
        Notification::fake();

        for ($i = 1; $i <= 5; $i++) {
            $this->post('/register', $this->validInput(['email' => "guest{$i}@example.com"]));
            $this->post('/logout');
        }

        $this->post('/register', $this->validInput(['email' => 'guest6@example.com']))
            ->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('users', ['email' => 'guest6@example.com']);
    }
}
