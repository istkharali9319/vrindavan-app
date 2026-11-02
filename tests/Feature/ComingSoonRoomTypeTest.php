<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Booking;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComingSoonRoomTypeTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(now(config('booking.timezone'))->setDate(2026, 11, 2)->setTime(10, 0));

        RoomType::where('slug', 'deluxe-room')->update(['is_coming_soon' => true]);
    }

    /**
     * @return array<string, mixed>
     */
    private function stay(): array
    {
        return ['check_in' => '2026-11-10', 'check_out' => '2026-11-13', 'adults' => 2, 'children' => 0];
    }

    public function test_coming_soon_room_types_are_shown_but_not_bookable_on_the_website(): void
    {
        $this->get(route('accommodation'))
            ->assertOk()
            ->assertSee('Deluxe Room')
            ->assertSee('Coming soon')
            ->assertDontSee('Reserve Deluxe Room')
            ->assertSee('Reserve Standard Room');

        $this->get(route('home'))->assertOk()->assertSee('Coming soon');

        $this->get(route('booking.search', $this->stay()))
            ->assertOk()
            ->assertSee('Coming soon')
            ->assertDontSee(route('booking.create', ['deluxe-room', ...$this->stay()]))
            ->assertSee(route('booking.create', ['standard-room', ...$this->stay()]));
    }

    public function test_coming_soon_room_types_cannot_be_booked_directly(): void
    {
        $this->get(route('booking.create', ['deluxe-room', ...$this->stay()]))->assertNotFound();

        $this->post(route('booking.store', 'deluxe-room'), [
            ...$this->stay(),
            'title' => 'Ms',
            'first_name' => 'Priya',
            'last_name' => 'Malhotra',
            'contact_email' => 'priya@example.com',
            'country_code' => '+91',
            'mobile' => '98300 12345',
            'terms' => '1',
        ])->assertNotFound();

        $this->assertSame(0, Booking::count());
    }

    public function test_managers_can_mark_a_room_type_as_coming_soon(): void
    {
        $manager = User::factory()->staff(Role::Manager)->create();
        $roomType = RoomType::where('slug', 'standard-room')->firstOrFail();

        $this->actingAs($manager)->put(route('admin.room-types.update', $roomType), [
            'name' => $roomType->name,
            'slug' => $roomType->slug,
            'description' => $roomType->description,
            'price' => '3500',
            'max_adults' => $roomType->max_adults,
            'max_children' => $roomType->max_children,
            'is_active' => '1',
            'is_coming_soon' => '1',
        ])->assertRedirect();

        $this->assertTrue($roomType->refresh()->is_coming_soon);
        $this->assertFalse($roomType->isBookable());

        $this->actingAs($manager)->get(route('admin.room-types.index'))->assertOk()->assertSee('Coming soon');
    }
}
