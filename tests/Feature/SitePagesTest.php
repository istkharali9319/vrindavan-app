<?php

namespace Tests\Feature;

use App\Models\SiteContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SitePagesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function pages(): array
    {
        return [
            'home' => ['home', '/', 'Where the Himalayas teach you to breathe slow.'],
            'accommodation' => ['accommodation', '/accommodation', 'Rooms Nestled Among'],
            'experience' => ['experience', '/experiences', 'Days Shaped by the Mountain Rhythm'],
            'dining' => ['dining', '/dining', 'Cooked slow'],
            'gallery' => ['gallery', '/gallery', 'The valley'],
            'explore' => ['explore', '/explore', 'An hour from everywhere'],
            'contact' => ['contact', '/contact', 'Contact Us'],
        ];
    }

    #[DataProvider('pages')]
    public function test_page_renders_with_its_heading(string $routeName, string $path, string $heading): void
    {
        $this->assertSame(url($path), route($routeName));

        $this->get($path)
            ->assertOk()
            ->assertSee($heading, false)
            ->assertSee('<a href="'.route($routeName).'" class="active" aria-current="page">', false);
    }

    #[DataProvider('pages')]
    public function test_every_image_on_the_page_exists(string $routeName, string $path): void
    {
        $html = $this->get($path)->getContent();

        preg_match_all('#'.preg_quote(asset('images').'/', '#').'([^"\')\s]+)["\')]#', $html, $matches);

        if ($routeName === 'contact') {
            $this->assertStringContainsString('maps.google.com', $html, 'Contact page shows a map instead of photos');
        } else {
            $this->assertNotEmpty($matches[1], "No images found on {$path}");
        }

        foreach (array_unique($matches[1]) as $file) {
            $this->assertFileExists(public_path("images/{$file}"), "Missing image on {$path}");
        }
    }

    #[DataProvider('pages')]
    public function test_page_has_no_links_to_old_html_files(string $routeName, string $path): void
    {
        $this->get($path)->assertDontSee('.html"', false);
    }

    public function test_legacy_html_urls_redirect_permanently(): void
    {
        $this->get('/index.html')->assertStatus(301)->assertRedirect('/');
        $this->get('/experience.html')->assertStatus(301)->assertRedirect('/experiences');
        $this->get('/contact.html')->assertStatus(301)->assertRedirect('/contact');
    }

    public function test_footer_and_contact_page_show_contact_details_from_site_settings(): void
    {
        SiteContent::create(['page' => 'site', 'key' => 'contact.email', 'value' => 'bookings@example.test']);
        SiteContent::create(['page' => 'site', 'key' => 'contact.phones', 'value' => '+91 90000 00000']);

        $this->get('/contact')
            ->assertOk()
            ->assertSee('mailto:bookings@example.test', false)
            ->assertSee('tel:+919000000000', false);
    }

    public function test_uploaded_photos_are_served_without_the_storage_symlink(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('room-types/1/photo.jpg', 'jpeg-bytes');

        $this->get('/storage/room-types/1/photo.jpg')->assertOk();
        $this->get('/storage/room-types/1/missing.jpg')->assertNotFound();
        $this->get('/storage/room-types/../../.env')->assertNotFound();
    }

    public function test_explore_page_loads_the_display_font(): void
    {
        $this->get('/explore')
            ->assertSee('Playfair+Display', false)
            ->assertSee('class="page-explore"', false);

        $this->get('/')->assertDontSee('Playfair+Display', false);
    }
}
