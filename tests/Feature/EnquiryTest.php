<?php

namespace Tests\Feature;

use App\Enums\EnquiryStatus;
use App\Enums\Role;
use App\Models\ContentItem;
use App\Models\Enquiry;
use App\Models\Experience;
use App\Models\SiteContent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnquiryTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    /**
     * @return array<string, string>
     */
    private function form(array $overrides = []): array
    {
        return [
            'name' => 'Priya Malhotra',
            'email' => 'Priya@Example.com',
            'phone' => '+91 94320 64521',
            'message' => 'We would like to stay from 12 to 15 December, two adults and a child.',
            ...$overrides,
        ];
    }

    public function test_a_guest_can_send_an_enquiry_from_the_contact_page(): void
    {
        $this->post(route('contact.store'), $this->form())
            ->assertRedirect(route('contact').'#contactForm')
            ->assertSessionHas('status', 'enquiry-sent');

        $enquiry = Enquiry::sole();
        $this->assertSame('priya@example.com', $enquiry->email);
        $this->assertSame(EnquiryStatus::New, $enquiry->status);
        $this->assertTrue($enquiry->isUnread());

        $this->followingRedirects()->post(route('contact.store'), $this->form())
            ->assertSee('Your message has reached us');
    }

    public function test_invalid_and_spam_enquiries_are_rejected(): void
    {
        $this->post(route('contact.store'), $this->form(['email' => 'not-an-email', 'message' => 'Hi']))
            ->assertSessionHasErrors(['email', 'message']);

        $this->post(route('contact.store'), $this->form(['website' => 'http://spam.example']))
            ->assertSessionHasErrors('website');

        $this->assertSame(0, Enquiry::count());
    }

    public function test_contact_details_and_whatsapp_come_from_site_settings(): void
    {
        SiteContent::create(['page' => 'site', 'key' => 'contact.whatsapp', 'value' => '+91 70000 11111']);
        SiteContent::create(['page' => 'site', 'key' => 'contact.address', 'value' => 'Reshi Road, Rhenock']);

        $this->get(route('contact'))
            ->assertOk()
            ->assertSee('https://wa.me/917000011111', false)
            ->assertSee('Reshi Road, Rhenock')
            ->assertSee('action="'.route('contact.store').'"', false);
    }

    public function test_staff_can_read_follow_up_and_delete_enquiries(): void
    {
        $enquiry = Enquiry::factory()->create(['name' => 'Arjun Mehta', 'message' => 'Do you have parking for two cars?']);
        Enquiry::factory()->create(['name' => 'Someone Else']);
        $frontDesk = User::factory()->staff(Role::FrontDesk)->create();

        $this->actingAs($frontDesk)->get(route('admin.enquiries.index', ['search' => 'parking']))
            ->assertOk()
            ->assertSee('Arjun Mehta')
            ->assertDontSee('Someone Else');

        $this->actingAs($frontDesk)->get(route('admin.dashboard'))->assertSee('pc-badge-count', false);

        $this->actingAs($frontDesk)->get(route('admin.enquiries.show', $enquiry))
            ->assertOk()
            ->assertSee('Do you have parking for two cars?');
        $this->assertFalse($enquiry->fresh()->isUnread());

        $this->actingAs($frontDesk)->post(route('admin.enquiries.messages.store', $enquiry), [
            'type' => 'reply',
            'channel' => 'email',
            'body' => 'Yes, there is parking for two cars right by the cottages.',
            'status' => EnquiryStatus::Replied->value,
        ])->assertRedirect(route('admin.enquiries.show', $enquiry).'#conversation');

        $this->actingAs($frontDesk)->post(route('admin.enquiries.messages.store', $enquiry), [
            'type' => 'guest',
            'channel' => 'whatsapp',
            'body' => 'Great, we will book for 12 December.',
            'status' => EnquiryStatus::Replied->value,
        ]);

        $this->actingAs($frontDesk)->post(route('admin.enquiries.messages.store', $enquiry), [
            'type' => 'note',
            'channel' => 'phone',
            'body' => 'Waiting for their booking.',
            'status' => EnquiryStatus::Closed->value,
        ]);

        $enquiry->refresh();
        $this->assertSame(EnquiryStatus::Closed, $enquiry->status);
        $this->assertSame($frontDesk->id, $enquiry->handled_by);
        $this->assertSame(['reply', 'status', 'guest', 'note', 'status'], $enquiry->messages->pluck('type')->map->value->all());
        $this->assertNull($enquiry->messages->firstWhere('type.value', 'note')->channel);

        $this->actingAs($frontDesk)->get(route('admin.enquiries.show', $enquiry))
            ->assertSeeInOrder(['Do you have parking for two cars?', 'Yes, there is parking', 'Great, we will book', 'Waiting for their booking.']);

        $this->actingAs($frontDesk)->get(route('admin.enquiries.index'))
            ->assertSee('Waiting for their booking.');

        $this->actingAs($frontDesk)->post(route('admin.enquiries.messages.store', $enquiry), [
            'type' => 'reply',
            'body' => '',
            'status' => EnquiryStatus::Closed->value,
        ])->assertSessionHasErrors('body');

        config(['admin.deletes_enabled' => true]);
        $this->actingAs($frontDesk)->delete(route('admin.enquiries.destroy', $enquiry))->assertRedirect(route('admin.enquiries.index'));
        $this->assertModelMissing($enquiry);
    }

    public function test_staff_without_the_enquiries_permission_are_kept_out(): void
    {
        $accounts = User::factory()->staff(Role::Accounts)->create();
        $enquiry = Enquiry::factory()->create();

        $this->actingAs($accounts)->get(route('admin.enquiries.index'))->assertForbidden();
        $this->actingAs($accounts)->get(route('admin.enquiries.show', $enquiry))->assertForbidden();
    }

    public function test_the_logo_can_be_switched_off_for_the_resort_name(): void
    {
        $this->get(route('home'))->assertSee('class="brand-logo"', false)->assertSee('images/brand/logo.png', false);

        SiteContent::create(['page' => 'site', 'key' => 'identity.brand_style', 'value' => 'text']);

        $this->get(route('home'))->assertDontSee('class="brand-logo"', false)->assertSee('VRINDAVAN');
    }

    public function test_deleting_is_hidden_and_refused_while_switched_off(): void
    {
        $manager = User::factory()->staff(Role::Manager)->create();
        $enquiry = Enquiry::factory()->create();
        $experience = Experience::query()->first();
        $review = ContentItem::query()->inCollection('reviews')->first();

        $this->actingAs($manager)->get(route('admin.enquiries.show', $enquiry))->assertOk()->assertDontSee('Delete enquiry');
        $this->actingAs($manager)->get(route('admin.experiences.edit', $experience))->assertOk()->assertDontSee('btn-outline-danger', false);
        $this->actingAs($manager)->get(route('admin.website.items.edit', ['home', 'reviews', $review]))->assertOk()->assertDontSee('Remove review');

        $this->actingAs($manager)->delete(route('admin.enquiries.destroy', $enquiry))->assertForbidden();
        $this->actingAs($manager)->delete(route('admin.experiences.destroy', $experience))->assertForbidden();
        $this->actingAs($manager)->delete(route('admin.website.items.destroy', ['home', 'reviews', $review]))->assertForbidden();

        $this->assertModelExists($enquiry);
        $this->assertModelExists($experience);
        $this->assertModelExists($review);
    }
}
