<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\Role;
use App\Enums\RoomStatus;
use App\Models\Booking;
use App\Models\Room;
use App\Models\RoomNight;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminRoomManagementTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_manager_can_create_a_room_type_with_photos(): void
    {
        Storage::fake('public');
        $manager = User::factory()->staff(Role::Manager)->create();

        $this->actingAs($manager)->post(route('admin.room-types.store'), [
            'name' => 'Kanchenjunga Suite',
            'tagline' => 'High altitude panorama',
            'badge' => 'Mountain Facing',
            'description' => 'Front-row views of the peaks.',
            'amenities' => "Glass balcony\n\n  Rain shower  ",
            'price' => '14900',
            'max_adults' => 2,
            'max_children' => 1,
            'sort_order' => 5,
            'is_active' => '1',
            'image' => UploadedFile::fake()->image('suite.jpg', 1200, 800),
            'gallery' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')],
        ])->assertRedirect(route('admin.room-types.index'));

        $roomType = RoomType::where('slug', 'kanchenjunga-suite')->firstOrFail();

        $this->assertSame(1490000, $roomType->nightly_price);
        $this->assertSame(['Glass balcony', 'Rain shower'], $roomType->amenities);
        $this->assertTrue($roomType->is_active);
        Storage::disk('public')->assertExists($roomType->image_path);
        $this->assertCount(2, $roomType->gallery);
    }

    public function test_room_type_update_can_remove_gallery_photos_and_hide_the_type(): void
    {
        Storage::fake('public');
        $manager = User::factory()->staff(Role::Manager)->create();
        $roomType = RoomType::factory()->create(['gallery' => ['room-types/1/old.jpg', 'room-types/1/keep.jpg']]);
        Storage::disk('public')->put('room-types/1/old.jpg', 'x');

        $this->actingAs($manager)->put(route('admin.room-types.update', $roomType), [
            'name' => $roomType->name,
            'slug' => $roomType->slug,
            'description' => 'Updated.',
            'price' => '3999.50',
            'max_adults' => 2,
            'max_children' => 0,
            'is_active' => '0',
            'remove_gallery' => ['room-types/1/old.jpg'],
        ])->assertRedirect();

        $roomType->refresh();
        $this->assertSame(399950, $roomType->nightly_price);
        $this->assertFalse($roomType->is_active);
        $this->assertSame(['room-types/1/keep.jpg'], $roomType->gallery);
        Storage::disk('public')->assertMissing('room-types/1/old.jpg');
    }

    public function test_front_desk_cannot_manage_room_types_but_can_use_the_rooms_board(): void
    {
        $frontDesk = User::factory()->staff(Role::FrontDesk)->create();

        $this->actingAs($frontDesk)->get(route('admin.room-types.index'))->assertForbidden();
        $this->actingAs($frontDesk)->post(route('admin.room-types.store'), [])->assertForbidden();

        $this->actingAs($frontDesk)->get(route('admin.rooms.index'))
            ->assertOk()
            ->assertSee('Deluxe Room')
            ->assertSee('101')
            ->assertDontSee('Room types</span>', false);
    }

    public function test_content_editor_cannot_open_the_rooms_board(): void
    {
        $editor = User::factory()->staff(Role::ContentEditor)->create();

        $this->actingAs($editor)->get(route('admin.rooms.index'))->assertForbidden();
        $this->actingAs($editor)->get(route('admin.dashboard'))->assertOk()->assertDontSee("Tonight's rooms", false);
    }

    public function test_rooms_board_shows_who_is_in_each_room(): void
    {
        $manager = User::factory()->staff(Role::Manager)->create();
        $room = Room::where('number', '102')->firstOrFail();
        $today = now(config('booking.timezone'))->startOfDay();

        $booking = Booking::factory()->create([
            'room_id' => $room->id,
            'room_type_id' => $room->room_type_id,
            'contact_name' => 'Tenzing Bhutia',
            'check_in' => $today->toDateString(),
            'check_out' => $today->copy()->addDays(2)->toDateString(),
        ]);
        RoomNight::create(['room_id' => $room->id, 'booking_id' => $booking->id, 'night_date' => $today->toDateString()]);
        Room::where('number', '103')->update(['status' => RoomStatus::Maintenance]);

        $this->actingAs($manager)->get(route('admin.rooms.index', ['state' => 'occupied']))
            ->assertOk()
            ->assertSee('Tenzing Bhutia')
            ->assertSee($booking->reference)
            ->assertDontSee('id="room-101"', false);

        $this->actingAs($manager)->get(route('admin.rooms.index', ['state' => 'maintenance']))
            ->assertSee('id="room-103"', false)
            ->assertDontSee('id="room-102"', false);

        $this->actingAs($manager)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee("Tonight's rooms", false)
            ->assertSee('Tenzing');
    }

    public function test_rooms_can_be_added_and_taken_out_of_service(): void
    {
        $frontDesk = User::factory()->staff(Role::FrontDesk)->create();
        $roomType = RoomType::where('slug', 'deluxe-room')->firstOrFail();

        $this->actingAs($frontDesk)->post(route('admin.rooms.store'), [
            'number' => ' 106 ',
            'room_type_id' => $roomType->id,
            'status' => RoomStatus::Active->value,
        ])->assertRedirect(route('admin.rooms.index'));

        $room = Room::where('number', '106')->firstOrFail();

        $this->actingAs($frontDesk)->post(route('admin.rooms.store'), [
            'number' => '106',
            'room_type_id' => $roomType->id,
            'status' => RoomStatus::Active->value,
        ])->assertSessionHasErrors('number');

        $this->actingAs($frontDesk)->put(route('admin.rooms.update', $room), [
            'number' => '106',
            'room_type_id' => $roomType->id,
            'status' => RoomStatus::Maintenance->value,
        ])->assertRedirect();

        $this->assertSame(RoomStatus::Maintenance, $room->fresh()->status);
        $this->assertSame(0, $roomType->roomsFreeBetween(now()->addDay(), now()->addDays(2))->where('number', '106')->count());
    }

    public function test_a_room_with_upcoming_bookings_cannot_change_type(): void
    {
        $manager = User::factory()->staff(Role::Manager)->create();
        $room = Room::where('number', '101')->firstOrFail();
        $otherType = RoomType::where('slug', 'standard-room')->firstOrFail();
        $tomorrow = now(config('booking.timezone'))->addDay()->toDateString();

        $booking = Booking::factory()->create(['room_id' => $room->id, 'room_type_id' => $room->room_type_id, 'status' => BookingStatus::Confirmed]);
        RoomNight::create(['room_id' => $room->id, 'booking_id' => $booking->id, 'night_date' => $tomorrow]);

        $this->actingAs($manager)->put(route('admin.rooms.update', $room), [
            'number' => '101',
            'room_type_id' => $otherType->id,
            'status' => RoomStatus::Active->value,
        ])->assertSessionHasErrors('room_type_id');

        $this->assertNotSame($otherType->id, $room->fresh()->room_type_id);
    }

    public function test_website_rooms_page_lists_active_room_types_from_the_database(): void
    {
        RoomType::factory()->create(['name' => 'Secret Treehouse', 'slug' => 'secret-treehouse', 'is_active' => false]);
        RoomType::where('slug', 'standard-room')->update(['nightly_price' => 412300]);

        $this->get(route('accommodation'))
            ->assertOk()
            ->assertSeeInOrder(['Deluxe Room', 'Standard Room', 'Separate Family Cottage'])
            ->assertSee('₹4,123')
            ->assertSee(route('booking.search', ['room_type' => 'family-cottage']), false)
            ->assertSee('<strong>15</strong>', false)
            ->assertDontSee('Secret Treehouse');

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Separate Family Cottage')
            ->assertSee(route('accommodation').'#deluxe-room', false)
            ->assertDontSee('Secret Treehouse');
    }
}
