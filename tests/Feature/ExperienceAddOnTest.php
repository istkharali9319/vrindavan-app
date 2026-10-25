<?php

namespace Tests\Feature;

use App\Enums\PriceUnit;
use App\Enums\Role;
use App\Http\Controllers\Booking\BookingController;
use App\Models\Booking;
use App\Models\Experience;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExperienceAddOnTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(now(config('booking.timezone'))->setDate(2026, 11, 2)->setTime(10, 0));
    }

    /**
     * Two adults and a child for three nights in a Deluxe Room (₹4,500 a night).
     *
     * @return array<string, mixed>
     */
    private function bookingForm(array $overrides = []): array
    {
        return [
            'check_in' => '2026-11-10',
            'check_out' => '2026-11-13',
            'adults' => 2,
            'children' => 1,
            'title' => 'Ms',
            'first_name' => 'Priya',
            'last_name' => 'Malhotra',
            'contact_email' => 'priya@example.com',
            'country_code' => '+91',
            'mobile' => '98300 12345',
            'terms' => '1',
            ...$overrides,
        ];
    }

    public function test_the_experiences_page_lists_experiences_with_their_prices(): void
    {
        Experience::factory()->create(['title' => 'Rhododendron Trek', 'price' => 180000, 'price_unit' => PriceUnit::PerGuest, 'sort_order' => 0]);
        Experience::factory()->create(['title' => 'Hidden Walk', 'is_active' => false]);
        Experience::factory()->addOn()->create(['title' => 'Airport Pickup', 'show_on_page' => false]);

        $this->get(route('experience'))
            ->assertOk()
            ->assertSee('Rhododendron Trek')
            ->assertSee('₹1,800 per guest')
            ->assertSee('Evenings by the Embers: Bonfire &amp; Lepcha Folk Songs', false)
            ->assertSee('Complimentary')
            ->assertDontSee('Hidden Walk')
            ->assertDontSee('Airport Pickup');
    }

    public function test_managers_can_add_and_price_experiences(): void
    {
        Storage::fake('public');
        $manager = User::factory()->staff(Role::Manager)->create();

        $this->actingAs($manager)->post(route('admin.experiences.store'), [
            'title' => 'Airport Pickup from Bagdogra',
            'description' => 'A private car waiting at arrivals.',
            'highlights' => "Meet and greet\nBottled water",
            'layout' => 'card',
            'categories' => ['nature'],
            'price' => '3,500',
            'price_unit' => 'per_booking',
            'show_on_page' => '0',
            'offer_as_addon' => '1',
            'is_active' => '1',
            'image' => UploadedFile::fake()->image('car.jpg'),
        ])->assertRedirect(route('admin.experiences.index'));

        $pickup = Experience::where('slug', 'airport-pickup-from-bagdogra')->firstOrFail();
        $this->assertSame(350000, $pickup->price);
        $this->assertSame(PriceUnit::PerBooking, $pickup->price_unit);
        $this->assertSame(['Meet and greet', 'Bottled water'], $pickup->highlights);
        Storage::disk('public')->assertExists($pickup->image_path);

        $momo = Experience::where('slug', 'artisan-momo-thukpa-masterclass')->firstOrFail();
        $this->actingAs($manager)->put(route('admin.experiences.update', $momo), [
            ...$momo->only(['title', 'slug', 'layout', 'description']),
            'price' => '1200',
            'price_unit' => 'per_guest',
            'show_on_page' => '1',
            'offer_as_addon' => '0',
            'is_active' => '1',
        ])->assertRedirect();

        $this->get(route('experience'))->assertSee('₹1,200 per guest');
    }

    public function test_an_add_on_needs_a_price_and_only_managers_can_edit(): void
    {
        $manager = User::factory()->staff(Role::Manager)->create();

        $this->actingAs($manager)->post(route('admin.experiences.store'), [
            'title' => 'Picnic Hamper',
            'layout' => 'feature',
            'price_unit' => 'per_booking',
            'offer_as_addon' => '1',
        ])->assertSessionHasErrors('price');

        foreach ([Role::FrontDesk, Role::ContentEditor] as $role) {
            $this->actingAs(User::factory()->staff($role)->create())->get(route('admin.experiences.index'))->assertForbidden();
        }
    }

    public function test_guests_can_add_extras_and_the_server_works_out_the_total(): void
    {
        $pickup = Experience::factory()->addOn(350000, PriceUnit::PerBooking)->create(['title' => 'Airport Pickup', 'show_on_page' => false]);
        $dinner = Experience::factory()->addOn(150000, PriceUnit::PerGuest)->create(['title' => 'Bonfire Dinner']);
        $breakfast = Experience::factory()->addOn(40000, PriceUnit::PerGuestPerNight)->create(['title' => 'In-room Breakfast']);
        $heater = Experience::factory()->addOn(30000, PriceUnit::PerNight)->create(['title' => 'Extra Heater']);
        $notOffered = Experience::factory()->create(['title' => 'Not An Add-on']);

        $stay = ['check_in' => '2026-11-10', 'check_out' => '2026-11-13', 'adults' => 2, 'children' => 1];

        // "Book this room" opens a pop-up with the extras, priced for these dates and guests.
        $this->get(route('booking.search', $stay))
            ->assertOk()
            ->assertSee('data-addon-modal', false)
            ->assertSee('data-book-room', false)
            ->assertSee('Airport Pickup')
            ->assertSee('₹1,500 per guest')
            ->assertSee('₹4,500 for your stay')
            ->assertDontSee('Not An Add-on');

        // Continuing carries the chosen extras to the details page; anything not on offer is dropped.
        $this->get(route('booking.create', ['deluxe-room', ...$stay, 'add_ons' => [$pickup->id, $dinner->id, $notOffered->id]]))
            ->assertOk()
            ->assertSee('+ Airport Pickup')
            ->assertSee('+ Bonfire Dinner')
            ->assertDontSee('Not An Add-on')
            ->assertSee('name="add_ons[]" value="'.$pickup->id.'"', false)
            ->assertSee('₹21,500')
            ->assertSee('Change extras');

        $response = $this->post(route('booking.store', 'deluxe-room'), $this->bookingForm([
            'add_ons' => [$pickup->id, $dinner->id, $breakfast->id, $heater->id],
        ]));

        $booking = Booking::with('addOns')->sole();
        $response->assertRedirect(BookingController::confirmationUrl($booking));

        // Room: ₹4,500 × 3 nights. Add-ons for 3 guests and 3 nights:
        // pickup ₹3,500 + dinner ₹1,500 × 3 + breakfast ₹400 × 9 + heater ₹300 × 3.
        $this->assertSame(1350000, $booking->room_amount);
        $this->assertSame([350000, 450000, 360000, 90000], $booking->addOns->sortBy('experience_id')->pluck('amount')->values()->all());
        $this->assertSame(1350000 + 350000 + 450000 + 360000 + 90000, $booking->total_amount);

        $pickup->update(['price' => 999900]);
        $this->assertSame(350000, $booking->addOns()->where('experience_id', $pickup->id)->value('amount'));

        $this->get(BookingController::confirmationUrl($booking))
            ->assertSee('Airport Pickup')
            ->assertSee('₹1,500 per guest × 3', false)
            ->assertSee('₹26,000');

        $this->post(route('booking.store', 'deluxe-room'), $this->bookingForm(['add_ons' => [$notOffered->id]]))
            ->assertSessionHasErrors('add_ons.0');
        $this->assertSame(1, Booking::count());
    }

    public function test_an_add_on_withdrawn_while_booking_is_caught(): void
    {
        $dinner = Experience::factory()->addOn(150000, PriceUnit::PerGuest)->create();
        $dinner->update(['is_active' => false]);

        $this->post(route('booking.store', 'deluxe-room'), $this->bookingForm(['add_ons' => [$dinner->id]]))
            ->assertSessionHasErrors('add_ons.0');

        $this->assertSame(0, Booking::count());
    }
}
