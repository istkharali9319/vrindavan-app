<?php

namespace Tests\Feature;

use App\Models\ContentItem;
use App\Models\Experience;
use App\Models\RoomType;
use App\Models\SiteContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OptimizeUploadedImagesTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('local');
    }

    /**
     * A large, photo-like JPEG (fine grain, so it compresses like a camera photo).
     */
    private function bigJpeg(int $width = 3200, int $height = 2400): string
    {
        $image = imagecreatetruecolor($width, $height);
        mt_srand(3);
        for ($y = 0; $y < $height; $y += 2) {
            for ($x = 0; $x < $width; $x += 2) {
                imagefilledrectangle($image, $x, $y, $x + 1, $y + 1, (int) imagecolorallocate($image, ($x / 13 + mt_rand(0, 25)) % 256, ($y / 9 + mt_rand(0, 25)) % 256, 140 + mt_rand(0, 20)));
            }
        }
        ob_start();
        imagejpeg($image, null, 92);

        return (string) ob_get_clean();
    }

    /**
     * Small and already well compressed: re-saving it can't save anything worthwhile.
     */
    private function smallWebp(): string
    {
        $image = imagecreatetruecolor(400, 300);
        mt_srand(5);
        for ($i = 0; $i < 4000; $i++) {
            imagesetpixel($image, mt_rand(0, 399), mt_rand(0, 299), (int) imagecolorallocate($image, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255)));
        }
        ob_start();
        imagewebp($image, null, 30);

        return (string) ob_get_clean();
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function sizeOf(string $path): array
    {
        $info = getimagesizefromstring(Storage::disk('public')->get($path));

        return [$info[0], $info[1]];
    }

    public function test_large_uploads_are_resized_and_every_reference_is_updated(): void
    {
        $disk = Storage::disk('public');
        $disk->put('room-types/2/main.jpg', $this->bigJpeg());
        $disk->put('room-types/2/gallery-1.jpg', $this->bigJpeg(2400, 3200));
        $disk->put('experiences/momo.jpg', $this->bigJpeg());
        $disk->put('content/gallery/lake.jpg', $this->bigJpeg(4000, 2000));

        $roomType = RoomType::where('slug', 'standard-room')->firstOrFail();
        $roomType->forceFill(['image_path' => 'room-types/2/main.jpg', 'gallery' => ['room-types/2/gallery-1.jpg']])->save();
        $experience = Experience::query()->firstOrFail();
        $experience->forceFill(['image_path' => 'experiences/momo.jpg'])->save();
        $item = ContentItem::create(['collection' => 'gallery', 'data' => ['title' => 'Lake', 'image' => 'content/gallery/lake.jpg'], 'sort_order' => 99, 'is_active' => true]);

        $this->artisan('images:optimize')->assertSuccessful();

        $roomType->refresh();
        $this->assertSame('room-types/2/main.webp', $roomType->image_path);
        $this->assertSame(['room-types/2/gallery-1.webp'], $roomType->gallery);
        $this->assertSame([1600, 1200], $this->sizeOf('room-types/2/main.webp'), 'Room photos are 4:3');
        $this->assertSame([1600, 1200], $this->sizeOf('room-types/2/gallery-1.webp'), 'Tall room photos are cropped to 4:3');

        $this->assertSame('experiences/momo.webp', $experience->refresh()->image_path);
        $this->assertSame('content/gallery/lake.webp', $item->refresh()->data['image']);
        $this->assertSame([2400, 1200], $this->sizeOf('content/gallery/lake.webp'), 'Other photos keep their shape, at most 2400px');

        // Old files are gone from the website, but the originals are backed up.
        $disk->assertMissing('room-types/2/main.jpg');
        $backups = Storage::disk('local')->allFiles('image-backups');
        $this->assertCount(4, $backups);
        $this->assertTrue(collect($backups)->contains(fn (string $path): bool => str_ends_with($path, '/room-types/2/main.jpg')));
    }

    public function test_a_dry_run_changes_nothing(): void
    {
        Storage::disk('public')->put('experiences/momo.jpg', $this->bigJpeg());
        $experience = Experience::query()->firstOrFail();
        $experience->forceFill(['image_path' => 'experiences/momo.jpg'])->save();

        $this->artisan('images:optimize', ['--dry-run' => true])
            ->expectsOutputToContain('would fix')
            ->assertSuccessful();

        $this->assertSame('experiences/momo.jpg', $experience->refresh()->image_path);
        Storage::disk('public')->assertExists('experiences/momo.jpg');
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_small_images_and_logos_are_left_alone_and_missing_files_are_reported(): void
    {
        Storage::disk('public')->put('experiences/small.webp', $original = $this->smallWebp());
        Experience::query()->firstOrFail()->forceFill(['image_path' => 'experiences/small.webp'])->save();
        Experience::query()->skip(1)->firstOrFail()->forceFill(['image_path' => 'experiences/gone.jpg'])->save();

        ob_start();
        imagepng(imagecreatetruecolor(600, 200));
        Storage::disk('public')->put('content/site/logo.png', (string) ob_get_clean());
        SiteContent::create(['page' => 'site', 'key' => 'identity.logo', 'value' => 'content/site/logo.png']);

        $this->artisan('images:optimize')
            ->expectsOutputToContain('missing   experiences/gone.jpg')
            ->assertSuccessful();

        $this->assertSame($original, Storage::disk('public')->get('experiences/small.webp'), 'Untouched');
        Storage::disk('public')->assertExists('content/site/logo.png');
        $this->assertSame('content/site/logo.png', SiteContent::where('key', 'identity.logo')->value('value'));
    }
}
