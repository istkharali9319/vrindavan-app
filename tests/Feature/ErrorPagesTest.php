<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_server_errors_have_a_standalone_branded_page(): void
    {
        $html = view('errors.500')->render();

        $this->assertStringContainsString('Something went wrong on our side', $html);
        $this->assertStringContainsString('images/brand/logo.png', $html);
    }
}
