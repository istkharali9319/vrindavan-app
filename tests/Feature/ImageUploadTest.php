<?php

namespace Tests\Feature;

use App\Support\ImageUpload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageUploadTest extends TestCase
{
    use RefreshDatabase;

    private function jpeg(int $width, int $height, int $quality = 90): string
    {
        $image = imagecreatetruecolor($width, $height);
        // A gradient, so the file behaves like a photo rather than a flat colour.
        for ($x = 0; $x < $width; $x += 4) {
            imagefilledrectangle($image, $x, 0, $x + 3, $height, (int) imagecolorallocate($image, $x % 256, ($x * 3) % 256, 120));
        }
        ob_start();
        imagejpeg($image, null, $quality);

        return (string) ob_get_clean();
    }

    private function transparentPng(int $width, int $height): string
    {
        $image = imagecreatetruecolor($width, $height);
        imagealphablending($image, false);
        imagesavealpha($image, true);
        imagefill($image, 0, 0, (int) imagecolorallocatealpha($image, 0, 0, 0, 127));
        imagefilledrectangle($image, 10, 10, (int) ($width / 2), (int) ($height / 2), (int) imagecolorallocatealpha($image, 200, 160, 70, 0));
        ob_start();
        imagepng($image);

        return (string) ob_get_clean();
    }

    /**
     * @return array{0: int, 1: int, mime: string}
     */
    private function info(string $bytes): array
    {
        return getimagesizefromstring($bytes);
    }

    public function test_large_photos_are_scaled_down_and_saved_as_webp(): void
    {
        [$bytes, $extension] = ImageUpload::processPhoto($this->jpeg(4000, 3000), null, 2400, 2400);

        $info = $this->info($bytes);
        $this->assertSame('webp', $extension);
        $this->assertSame('image/webp', $info['mime']);
        $this->assertSame([2400, 1800], [$info[0], $info[1]], 'Keeps its shape');
    }

    public function test_small_photos_are_never_enlarged(): void
    {
        $result = ImageUpload::processPhoto($this->jpeg(800, 600, 95), null, 2400, 2400);

        if ($result !== null) {
            $info = $this->info($result[0]);
            $this->assertSame([800, 600], [$info[0], $info[1]]);
        }
        $this->addToAssertionCount(1);
    }

    public function test_a_photo_that_needs_nothing_is_kept_exactly_as_uploaded(): void
    {
        // Already small and already a well-compressed WebP: saving it again at quality 90 would not be smaller.
        $image = imagecreatefromstring($this->jpeg(300, 200));
        ob_start();
        imagewebp($image, null, 10);
        $webp = (string) ob_get_clean();

        $this->assertNull(ImageUpload::processPhoto($webp, null, 2400, 2400));
    }

    public function test_transparent_photos_keep_their_transparency(): void
    {
        [$bytes] = ImageUpload::processPhoto($this->transparentPng(3000, 2000), null, 2400, 2400);
        $image = imagecreatefromstring($bytes);

        $this->assertSame(127, (imagecolorat($image, imagesx($image) - 2, imagesy($image) - 2) >> 24) & 0x7F, 'Corner stays see-through');
    }

    public function test_logos_stay_lossless_png(): void
    {
        [$bytes, $extension] = ImageUpload::processLogo($this->transparentPng(3000, 1000), null, 1200);

        $info = $this->info($bytes);
        $this->assertSame('png', $extension);
        $this->assertSame([1200, 400], [$info[0], $info[1]]);

        $this->assertNull(ImageUpload::processLogo($this->transparentPng(600, 200), null, 1200), 'Small logos are kept as uploaded');
    }

    public function test_files_that_are_not_images_are_stored_as_uploaded(): void
    {
        $this->assertNull(ImageUpload::processPhoto('not an image', null, 2400, 2400));
    }

    public function test_uploads_are_saved_on_the_public_disk(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->createWithContent('hero.jpg', $this->jpeg(3600, 2400));

        $path = ImageUpload::photo($file, 'content/home');

        $this->assertStringStartsWith('content/home/', $path);
        $this->assertStringEndsWith('.webp', $path);
        $info = $this->info(Storage::disk('public')->get($path));
        $this->assertSame([2400, 1600], [$info[0], $info[1]]);
    }
}
