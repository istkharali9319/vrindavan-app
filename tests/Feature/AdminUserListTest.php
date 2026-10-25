<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserListTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = User::factory()->staff(Role::Manager)->create(['name' => 'Zara Manager', 'created_at' => now()->subYear()]);
    }

    public function test_results_are_paginated_on_the_server(): void
    {
        User::factory()->customer()->count(30)->create();

        $firstPage = $this->actingAs($this->manager)->get('/admin/users');
        $firstPage->assertOk()
            ->assertSee('Showing <strong>1–25</strong>', false)
            ->assertSee('of <strong>31</strong> users', false)
            ->assertSee('page=2', false);
        $this->assertCount(25, $firstPage->viewData('users')->items());

        $secondPage = $this->actingAs($this->manager)->get('/admin/users?page=2');
        $secondPage->assertSee('Showing <strong>26–31</strong>', false);
        $this->assertCount(6, $secondPage->viewData('users')->items());
    }

    public function test_page_size_can_be_changed_and_is_kept_in_page_links(): void
    {
        User::factory()->customer()->count(15)->create();

        $response = $this->actingAs($this->manager)->get('/admin/users?per_page=10');

        $this->assertCount(10, $response->viewData('users')->items());
        $response->assertSee('per_page=10&amp;page=2', false);
    }

    public function test_unsupported_page_size_is_rejected(): void
    {
        $this->actingAs($this->manager)->get('/admin/users?per_page=1000')->assertSessionHasErrors('per_page');
    }

    public function test_users_can_be_sorted_by_name_in_both_directions(): void
    {
        User::factory()->customer()->create(['name' => 'Arjun Mehta']);
        User::factory()->customer()->create(['name' => 'Meera Rai']);

        $this->actingAs($this->manager)->get('/admin/users?sort=name&direction=asc')
            ->assertSeeInOrder(['Arjun Mehta', 'Meera Rai', 'Zara Manager']);

        $this->actingAs($this->manager)->get('/admin/users?sort=name&direction=desc')
            ->assertSeeInOrder(['Zara Manager', 'Meera Rai', 'Arjun Mehta']);
    }

    public function test_newest_accounts_come_first_by_default(): void
    {
        User::factory()->customer()->create(['name' => 'Older Guest', 'created_at' => now()->subMonth()]);
        User::factory()->customer()->create(['name' => 'Newest Guest', 'created_at' => now()]);

        $this->actingAs($this->manager)->get('/admin/users')
            ->assertSeeInOrder(['Newest Guest', 'Older Guest', 'Zara Manager']);
    }

    public function test_unknown_sort_column_is_rejected(): void
    {
        $this->actingAs($this->manager)->get('/admin/users?sort=password')->assertSessionHasErrors('sort');
    }

    public function test_search_treats_underscores_and_percent_signs_literally(): void
    {
        User::factory()->customer()->create(['name' => 'Underscore Guest', 'email' => 'first_last@example.com']);
        User::factory()->customer()->create(['name' => 'Lookalike Guest', 'email' => 'firstxlast@example.com']);

        $this->actingAs($this->manager)->get('/admin/users?search=first_last')
            ->assertSee('Underscore Guest')
            ->assertDontSee('Lookalike Guest');

        $this->actingAs($this->manager)->get('/admin/users?search=%25')
            ->assertSee('No users match');
    }

    public function test_search_combines_with_filters_and_pagination(): void
    {
        User::factory()->customer()->count(12)->sequence(fn ($sequence) => ['name' => "Sharma Guest {$sequence->index}"])->create();
        User::factory()->customer()->suspended()->create(['name' => 'Sharma Suspended']);
        User::factory()->customer()->create(['name' => 'Other Person']);

        $response = $this->actingAs($this->manager)->get('/admin/users?search=sharma&status=active&per_page=10');

        $response->assertSee('of <strong>12</strong> users', false)
            ->assertSee('for “sharma”', false)
            ->assertDontSee('Sharma Suspended')
            ->assertDontSee('Other Person')
            ->assertSee('search=sharma', false);
        $this->assertCount(10, $response->viewData('users')->items());
    }

    public function test_sort_links_keep_the_current_search(): void
    {
        $this->actingAs($this->manager)->get('/admin/users?search=zara')
            ->assertSee('search=zara&amp;sort=name&amp;direction=asc', false);
    }
}
