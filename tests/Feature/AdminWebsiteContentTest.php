<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\ContentItem;
use App\Models\SiteContent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminWebsiteContentTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_every_page_editor_and_list_opens(): void
    {
        $editor = User::factory()->staff(Role::ContentEditor)->create();

        $this->actingAs($editor)->get(route('admin.website.index'))->assertOk()->assertSee('Site settings')->assertSee('Gallery');

        foreach (array_keys(config('site-content.pages')) as $page) {
            $this->actingAs($editor)->get(route('admin.website.edit', $page))->assertOk();
        }

        foreach (config('site-content.collections') as $key => $collection) {
            $this->actingAs($editor)->get(route('admin.website.items.index', [$collection['page'], $key]))->assertOk();
            $this->actingAs($editor)->get(route('admin.website.items.create', [$collection['page'], $key]))->assertOk();
        }
    }

    public function test_page_text_and_photo_can_be_changed(): void
    {
        Storage::fake('public');
        $editor = User::factory()->staff(Role::ContentEditor)->create();

        $this->actingAs($editor)->put(route('admin.website.update', 'home'), [
            'fields' => [
                'hero' => ['title' => 'Breathe *slow* in the hills.', 'primary_button' => 'Reserve your stay'],
            ],
            'images' => ['hero' => ['image' => UploadedFile::fake()->image('hero.jpg', 1600, 900)]],
        ])->assertRedirect(route('admin.website.edit', 'home'));

        $image = SiteContent::where(['page' => 'home', 'key' => 'hero.image'])->value('value');
        Storage::disk('public')->assertExists($image);

        $this->assertDatabaseMissing('site_contents', ['page' => 'home', 'key' => 'hero.primary_button']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Breathe <em>slow</em> in the hills.', false)
            ->assertSee('storage/'.$image, false);
    }

    public function test_site_settings_change_contact_details_everywhere(): void
    {
        $editor = User::factory()->staff(Role::ContentEditor)->create();

        $this->actingAs($editor)->put(route('admin.website.update', 'site'), [
            'fields' => [
                'identity' => ['name' => 'Vrindavan Resort', 'tagline' => 'Rhenock • East Sikkim', 'altitude' => '1,450m'],
                'contact' => ['address' => 'Reshi Road, Rhenock', 'email' => 'hello@vrindavan.test', 'phones' => "+91 70000 11111\n"],
            ],
        ])->assertRedirect();

        $this->get(route('contact'))
            ->assertOk()
            ->assertSee('hello@vrindavan.test')
            ->assertSee('tel:+917000011111', false)
            ->assertDontSee('+91 94320 64521');
    }

    public function test_gallery_photos_can_be_added_hidden_reordered_and_removed(): void
    {
        Storage::fake('public');
        $editor = User::factory()->staff(Role::ContentEditor)->create();

        $this->actingAs($editor)->post(route('admin.website.items.store', ['gallery', 'gallery']), [
            'data' => ['title' => 'Snow on the deodars', 'category' => 'nature'],
            'images' => ['image' => UploadedFile::fake()->image('snow.jpg')],
            'is_active' => '1',
        ])->assertRedirect(route('admin.website.items.index', ['gallery', 'gallery']));

        $photo = ContentItem::where('collection', 'gallery')->get()->first(fn ($item) => $item->get('title') === 'Snow on the deodars');
        Storage::disk('public')->assertExists($photo->data['image']);
        $this->get(route('gallery'))->assertSee('Snow on the deodars');

        $this->actingAs($editor)->post(route('admin.website.items.move', ['gallery', 'gallery', $photo]), ['direction' => 'up']);
        $this->assertSame($photo->id, ContentItem::query()->inCollection('gallery')->get()->reverse()->values()[1]->id);

        $this->actingAs($editor)->put(route('admin.website.items.update', ['gallery', 'gallery', $photo]), [
            'data' => ['title' => 'Snow on the deodars', 'category' => 'nature'],
            'is_active' => '0',
        ])->assertRedirect();
        $this->get(route('gallery'))->assertDontSee('Snow on the deodars');

        config(['admin.deletes_enabled' => true]);
        $this->actingAs($editor)->delete(route('admin.website.items.destroy', ['gallery', 'gallery', $photo]))->assertRedirect();
        $this->assertModelMissing($photo);
        Storage::disk('public')->assertMissing($photo->data['image']);
    }

    public function test_home_page_follows_the_original_design_with_editable_lists(): void
    {
        $editor = User::factory()->staff(Role::ContentEditor)->create();
        $tile = ContentItem::query()->inCollection('home_experiences')->first();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Sikkimese Cooking Masterclass')
            ->assertSee('class="booking-bar"', false)
            ->assertSee('Reviews from our Guests')
            ->assertSee('images/near-lampokhari.jpg', false)
            ->assertDontSee('home-highlight', false);

        $this->actingAs($editor)->put(route('admin.website.items.update', ['home', 'home_experiences', $tile]), [
            'data' => ['title' => 'Momo Making by the Hearth'],
            'is_active' => '1',
        ])->assertRedirect();

        $this->get(route('home'))->assertSee('Momo Making by the Hearth')->assertDontSee('Sikkimese Cooking Masterclass');
    }

    public function test_invalid_input_is_rejected(): void
    {
        $editor = User::factory()->staff(Role::ContentEditor)->create();
        $review = ContentItem::query()->inCollection('reviews')->first();

        $this->actingAs($editor)->put(route('admin.website.items.update', ['home', 'reviews', $review]), [
            'data' => ['name' => ''],
        ])->assertSessionHasErrors(['data.name']);

        $this->actingAs($editor)->post(route('admin.website.items.store', ['gallery', 'gallery']), [
            'data' => ['title' => 'No photo'],
        ])->assertSessionHasErrors('images.image');

        $this->actingAs($editor)->get(route('admin.website.items.index', ['home', 'gallery']))->assertNotFound();
        $this->actingAs($editor)->get(route('admin.website.edit', 'nope'))->assertNotFound();
    }

    public function test_staff_without_content_permission_cannot_edit_the_website(): void
    {
        $frontDesk = User::factory()->staff(Role::FrontDesk)->create();

        $this->actingAs($frontDesk)->get(route('admin.website.index'))->assertForbidden();
        $this->actingAs($frontDesk)->put(route('admin.website.update', 'home'), [])->assertForbidden();
    }

    public function test_staff_can_send_themselves_a_test_email(): void
    {
        $editor = User::factory()->staff(Role::ContentEditor)->create();

        $this->actingAs($editor)->get(route('admin.website.edit', 'site'))->assertSee('Send test email to me');

        $this->actingAs($editor)->post(route('admin.website.test-email'))
            ->assertRedirect(route('admin.website.edit', 'site'))
            ->assertSessionHas('status', 'Test email sent to '.$editor->email.'. Check the inbox and the spam folder.');

        config(['mail.default' => 'smtp', 'mail.mailers.smtp.password' => '']);
        $this->actingAs($editor)->post(route('admin.website.test-email'))
            ->assertSessionHas('mail_error', 'MAIL_PASSWORD is empty in .env. Type the mailbox password there first.');
    }
}
