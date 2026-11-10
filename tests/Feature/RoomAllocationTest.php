<?php

namespace Tests\Feature;

use App\Http\Controllers\Booking\BookingController;
use App\Models\Booking;
use App\Models\RoomNight;
use App\Models\RoomType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomAllocationTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private RoomType $standard;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(now(config('booking.timezone'))->setDate(2026, 11, 2)->setTime(10, 0));

        // The client's rule: each room takes up to 2 adults and 1 child (editable in the admin).
        $this->standard = RoomType::where('slug', 'standard-room')->firstOrFail();
        $this->standard->update(['max_adults' => 2, 'max_children' => 1]);
    }

    /**
     * @return array<string, mixed>
     */
    private function stay(array $overrides = []): array
    {
        return ['check_in' => '2026-11-10', 'check_out' => '2026-11-13', 'adults' => 2, 'children' => 0, ...$overrides];
    }

    /**
     * @return array<string, mixed>
     */
    private function bookingForm(array $overrides = []): array
    {
        return [
            ...$this->stay(),
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

    public function test_rooms_needed_follow_the_per_room_limits(): void
    {
        $this->assertSame(1, $this->standard->roomsNeededFor(1));
        $this->assertSame(1, $this->standard->roomsNeededFor(2));
        $this->assertSame(2, $this->standard->roomsNeededFor(3));
        $this->assertSame(2, $this->standard->roomsNeededFor(4));
        $this->assertSame(3, $this->standard->roomsNeededFor(5));
        $this->assertSame(1, $this->standard->roomsNeededFor(2, 1));
        $this->assertSame(2, $this->standard->roomsNeededFor(2, 2), 'Two children need two rooms');
        $this->assertNull($this->standard->roomsNeededFor(1, 2), 'Each room needs an adult');

        $this->assertSame(2, $this->standard->roomsToBook(2, 0, 2), 'Guests may ask for an extra room');
        $this->assertSame(2, $this->standard->roomsToBook(2, 0, 9), 'Never more than one room per adult');
        $this->assertSame(3, $this->standard->roomsToBook(5, 0, 1), 'Never fewer rooms than the group needs');
    }

    public function test_search_shows_the_rooms_allocated_and_their_total(): void
    {
        $this->get(route('booking.search', $this->stay(['adults' => 3])))
            ->assertOk()
            ->assertSee('2 rooms</strong>', false)
            ->assertSee('for 3 adults')
            ->assertSee('₹21,000 for 3 nights', false)
            ->assertSee('Book 2 rooms')
            ->assertSee(e(route('booking.create', ['standard-room', ...$this->stay(['adults' => 3]), 'rooms' => 2])), false);
    }

    public function test_each_room_type_works_out_its_own_room_count(): void
    {
        // The cottage takes 4 adults per room, the Standard Room 2: 8 adults need 2 cottages or 4 Standard Rooms.
        RoomType::where('slug', 'family-cottage')->update(['max_adults' => 4, 'max_children' => 2, 'is_coming_soon' => false]);

        $this->get(route('booking.search', $this->stay(['adults' => 8])))
            ->assertOk()
            ->assertSee('Book 2 rooms')
            ->assertSee('Book 4 rooms')
            ->assertSee('8 adults');
    }

    public function test_five_adults_are_booked_into_three_rooms(): void
    {
        $this->get(route('booking.create', ['standard-room', ...$this->stay(['adults' => 5])]))
            ->assertOk()
            ->assertSee('3 Rooms · 5 Adults')
            ->assertSee('Your group needs 3 rooms');

        $response = $this->post(route('booking.store', 'standard-room'), $this->bookingForm(['adults' => 5]));

        $booking = Booking::sole();
        $response->assertRedirect(BookingController::confirmationUrl($booking));

        $this->assertSame(3, $booking->rooms_count);
        $this->assertSame(['201', '202', '203'], $booking->rooms->pluck('number')->all());
        $this->assertSame(350000 * 3 * 3, $booking->room_amount);
        $this->assertSame(9, RoomNight::where('booking_id', $booking->id)->count(), '3 rooms × 3 nights');

        $this->get(BookingController::confirmationUrl($booking))
            ->assertOk()
            ->assertSee('Rooms 201, 202, 203');
    }

    public function test_a_group_is_not_booked_when_too_few_rooms_are_free(): void
    {
        // 12 adults need 6 Standard Rooms; there are only 5.
        $this->get(route('booking.search', $this->stay(['adults' => 12])))
            ->assertOk()
            ->assertSee('Only 5 rooms left')
            ->assertSee('Your group needs 6 rooms');

        $this->get(route('booking.create', ['standard-room', ...$this->stay(['adults' => 12])]))
            ->assertRedirect()
            ->assertSessionHasErrors('check_in');

        $this->post(route('booking.store', 'standard-room'), $this->bookingForm(['adults' => 12]))
            ->assertRedirect()
            ->assertSessionHasErrors('check_in');

        $this->assertSame(0, Booking::count());
    }

    public function test_a_group_that_cannot_be_split_is_refused(): void
    {
        $this->post(route('booking.store', 'standard-room'), $this->bookingForm(['adults' => 1, 'children' => 2]))
            ->assertSessionHasErrors('children');

        $this->assertSame(0, Booking::count());
    }
}
