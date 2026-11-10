<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomDetailsPageTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_every_room_on_the_accommodation_page_links_to_its_details(): void
    {
        $response = $this->get(route('accommodation'))->assertOk();

        foreach (RoomType::active()->get() as $roomType) {
            $response->assertSee('href="'.route('accommodation.show', $roomType).'"', false);
        }

        $response->assertSee('View Details');
    }

    public function test_details_page_shows_the_room_data_from_the_database(): void
    {
        $roomType = RoomType::where('slug', 'standard-room')->firstOrFail();
        $roomType->update([
            'bed_type' => 'One king bed',
            'room_size' => '320 sq ft',
            'services' => ['Daily housekeeping', 'Free Wi-Fi'],
            'max_adults' => 2,
            'max_children' => 1,
        ]);

        $this->get(route('accommodation.show', $roomType))
            ->assertOk()
            ->assertSee($roomType->name)
            ->assertSee($roomType->description)
            ->assertSee($roomType->amenityList()[0])
            ->assertSee('₹3,500')
            ->assertSee('Up to 2 adults and 1 child per room')
            ->assertSee('One king bed')
            ->assertSee('320 sq ft')
            ->assertSee('Daily housekeeping')
            ->assertSee('Free Wi-Fi')
            ->assertSee('Book Now')
            ->assertSee(e(route('booking.search', ['room_type' => 'standard-room'])), false);
    }

    public function test_empty_optional_details_are_not_shown(): void
    {
        $roomType = RoomType::where('slug', 'standard-room')->firstOrFail();
        $roomType->update(['bed_type' => null, 'room_size' => null, 'services' => null]);

        $this->get(route('accommodation.show', $roomType))
            ->assertOk()
            ->assertDontSee('<dt>Bed</dt>', false)
            ->assertDontSee('<dt>Room size</dt>', false)
            ->assertDontSee('Room features &amp; services', false);
    }

    public function test_coming_soon_rooms_show_details_but_cannot_be_booked(): void
    {
        $roomType = RoomType::where('slug', 'deluxe-room')->firstOrFail();
        $roomType->update(['is_coming_soon' => true]);

        $this->get(route('accommodation.show', $roomType))
            ->assertOk()
            ->assertSee('Coming soon')
            ->assertDontSee(e(route('booking.search', ['room_type' => 'deluxe-room'])), false);
    }

    public function test_hidden_and_unknown_rooms_are_not_found(): void
    {
        $roomType = RoomType::where('slug', 'standard-room')->firstOrFail();
        $roomType->update(['is_active' => false]);

        $this->get(route('accommodation.show', $roomType))->assertNotFound();
        $this->get('/accommodation/no-such-room')->assertNotFound();
    }

    public function test_managers_can_save_bed_type_room_size_and_services(): void
    {
        $manager = User::factory()->staff(Role::Manager)->create();
        $roomType = RoomType::where('slug', 'standard-room')->firstOrFail();

        $this->actingAs($manager)->put(route('admin.room-types.update', $roomType), [
            'name' => $roomType->name,
            'slug' => $roomType->slug,
            'description' => $roomType->description,
            'price' => '3500',
            'max_adults' => 2,
            'max_children' => 1,
            'is_active' => '1',
            'bed_type' => 'Two single beds',
            'room_size' => '28 m²',
            'services' => "Daily housekeeping\n\n  Laundry on request  ",
        ])->assertRedirect();

        $roomType->refresh();
        $this->assertSame('Two single beds', $roomType->bed_type);
        $this->assertSame('28 m²', $roomType->room_size);
        $this->assertSame(['Daily housekeeping', 'Laundry on request'], $roomType->services);
    }
}
