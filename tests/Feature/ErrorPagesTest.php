<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_a_customer_opening_the_admin_sees_an_access_denied_page(): void
    {
        $customer = User::factory()->create(['email' => 'guest@example.com']);

        $this->actingAs($customer)->get('/admin')
            ->assertForbidden()
            ->assertSee('Access denied')
            ->assertSee("You don't have permission to access this page.")
            ->assertSee('Sign in with another account')
            ->assertSee('class="site-header"', false)
            ->assertDontSee('User does not have the right roles');
    }

    public function test_missing_pages_use_the_website_design(): void
    {
        $this->get('/no-such-page')
            ->assertNotFound()
            ->assertSee('Page not found')
            ->assertSee('class="site-header"', false);
    }

    public function test_the_bare_reset_password_address_goes_to_forgot_password(): void
    {
        $this->get('/reset-password')
            ->assertRedirect(route('password.request'));

        $this->followingRedirects()->get('/reset-password')
            ->assertOk()
            ->assertSee('Forgot your password?')
            ->assertSee('That reset link is incomplete or has expired');
    }

    public function test_a_wrong_request_method_shows_a_branded_page_not_a_crash(): void
    {
        config(['app.debug' => false]);

        $this->get('/logout')
            ->assertStatus(405)
            ->assertSee('This link can’t be opened')
            ->assertSee('class="site-header"', false);
    }

    public function test_other_error_codes_fall_back_to_branded_pages(): void
    {
        $clientError = view('errors.4xx', ['exception' => new HttpException(410)])->render();
        $this->assertStringContainsString('This page can’t be shown', $clientError);
        $this->assertStringContainsString('410', $clientError);

        $serverError = view('errors.5xx', ['exception' => new HttpException(502)])->render();
        $this->assertStringContainsString('Something went wrong on our side', $serverError);
        $this->assertStringContainsString('Error 502', $serverError);
    }

    public function test_server_errors_have_a_standalone_branded_page(): void
    {
        $html = view('errors.500')->render();

        $this->assertStringContainsString('Something went wrong on our side', $html);
        $this->assertStringContainsString('images/brand/logo.png', $html);
    }
}
