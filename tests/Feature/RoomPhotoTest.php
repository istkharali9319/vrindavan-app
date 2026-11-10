<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\RoomType;
use App\Models\User;
use App\Support\RoomPhoto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RoomPhotoTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    /**
     * @return array{int, int}
     */
    private function sizeOf(string $jpeg): array
    {
        $info = getimagesizefromstring($jpeg);

        return [$info[0], $info[1]];
    }

    private function photo(int $width, int $height): string
    {
        $image = imagecreatetruecolor($width, $height);
        ob_start();
        imagejpeg($image);

        return (string) ob_get_clean();
    }

    public function test_photos_of_any_shape_become_the_same_4_by_3_size(): void
    {
        $this->assertSame([1600, 1200], $this->sizeOf(RoomPhoto::process($this->photo(4000, 2000))), 'Wide photo');
        $this->assertSame([1600, 1200], $this->sizeOf(RoomPhoto::process($this->photo(1800, 3200))), 'Tall phone photo');
        $this->assertSame([800, 600], $this->sizeOf(RoomPhoto::process($this->photo(800, 900))), 'Small photos are cropped but not enlarged');
    }

    public function test_unreadable_files_are_left_alone(): void
    {
        $this->assertNull(RoomPhoto::process('not an image'));
    }

    public function test_photos_uploaded_in_the_admin_are_stored_at_the_standard_size(): void
    {
        Storage::fake('public');
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
            'image' => UploadedFile::fake()->image('main.png', 2400, 2400),
            'gallery' => [UploadedFile::fake()->image('tall.jpg', 1800, 3200), UploadedFile::fake()->image('wide.jpg', 4800, 1500)],
        ])->assertRedirect();

        $roomType->refresh();

        foreach ([$roomType->image_path, ...array_slice($roomType->gallery, -2)] as $path) {
            $this->assertStringEndsWith('.webp', $path);
            $this->assertSame([1600, 1200], $this->sizeOf(Storage::disk('public')->get($path)), $path);
        }
    }

    public function test_coming_soon_rooms_are_listed_last_on_the_booking_page(): void
    {
        // Deluxe is first by sort order; once it is "coming soon" it moves to the end.
        RoomType::where('slug', 'deluxe-room')->update(['is_coming_soon' => true]);

        $this->get(route('booking.search'))
            ->assertOk()
            ->assertSeeInOrder(['Standard Room', 'Separate Family Cottage', 'Deluxe Room']);
    }
}
