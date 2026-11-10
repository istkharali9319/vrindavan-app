<?php

namespace App\Support;

use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Every image uploaded in the admin goes through here, so the website never serves huge files.
 *
 * Photos: scaled down to fit the size given (never enlarged), turned upright, and saved as WebP at
 * quality 90, which looks the same as the original but is much smaller. When a photo needs no
 * resizing and WebP wouldn't make it smaller, the original file is kept exactly as it was.
 *
 * Logos and icons stay lossless PNG (emails embed the logo, and many mail apps can't show WebP);
 * they are only scaled down when very large.
 *
 * Uses PHP's built-in GD library. Anything it can't process is stored exactly as uploaded.
 */
class ImageUpload
{
    public const PHOTO_QUALITY = 90;

    /**
     * Images bigger than this are stored as uploaded: decoding them could run out of memory on shared hosting.
     */
    public const MAX_PIXELS = 40_000_000;

    /**
     * A photo for the website (hero, gallery, experience…): at most $maxSide pixels on its long side.
     */
    public static function photo(UploadedFile $file, string $folder, int $maxSide = 2400): string
    {
        return self::store($file, $folder, fn (string $contents, string $path) => self::processPhoto($contents, $path, $maxSide, $maxSide));
    }

    /**
     * A logo or icon: lossless PNG, at most $maxSide pixels on its long side.
     */
    public static function logo(UploadedFile $file, string $folder, int $maxSide = 1200): string
    {
        return self::store($file, $folder, fn (string $contents, string $path) => self::processLogo($contents, $path, $maxSide));
    }

    /**
     * @param  callable(string, string): ?array{0: string, 1: string}  $process  returns [bytes, extension], or null to keep the original
     */
    public static function store(UploadedFile $file, string $folder, callable $process): string
    {
        $path = (string) $file->getRealPath();

        try {
            $result = $process((string) file_get_contents($path), $path);
        } catch (Throwable) {
            $result = null;
        }

        if ($result === null) {
            return $file->store($folder, 'public');
        }

        [$bytes, $extension] = $result;
        $stored = $folder.'/'.Str::random(40).'.'.$extension;
        Storage::disk('public')->put($stored, $bytes);

        return $stored;
    }

    /**
     * Scales a photo to fit inside $maxWidth × $maxHeight, optionally cropping it to $ratio (width / height) first.
     *
     * @return array{0: string, 1: string}|null [bytes, extension], or null to keep the original file
     */
    public static function processPhoto(string $contents, ?string $sourcePath, int $maxWidth, int $maxHeight, ?float $ratio = null): ?array
    {
        $source = self::open($contents, $sourcePath);

        if ($source === null) {
            return null;
        }

        [$image, $rotated] = $source;
        [$width, $height] = [imagesx($image), imagesy($image)];

        // Crop to the requested shape around the centre.
        [$cropX, $cropY, $cropWidth, $cropHeight] = [0, 0, $width, $height];
        if ($ratio !== null && abs($width / $height - $ratio) > 0.01) {
            if ($width / $height > $ratio) {
                $cropWidth = (int) round($height * $ratio);
                $cropX = (int) floor(($width - $cropWidth) / 2);
            } else {
                $cropHeight = (int) round($width / $ratio);
                $cropY = (int) floor(($height - $cropHeight) / 2);
            }
        }

        // Never enlarge: a bigger copy of a small photo only looks blurred.
        $scale = min(1, $maxWidth / $cropWidth, $maxHeight / $cropHeight);
        $targetWidth = max(1, (int) round($cropWidth * $scale));
        $targetHeight = max(1, (int) round($cropHeight * $scale));
        $changed = $rotated || $scale < 1 || $cropWidth !== $width || $cropHeight !== $height;

        $hasAlpha = self::hasTransparency($image);
        $canWebp = function_exists('imagewebp');
        $target = self::resample($image, $cropX, $cropY, $cropWidth, $cropHeight, $targetWidth, $targetHeight, keepAlpha: $hasAlpha && $canWebp);

        $result = $canWebp
            ? [self::encode(fn () => imagewebp($target, null, self::PHOTO_QUALITY)), 'webp']
            : [self::encode(fn () => imagejpeg($target, null, self::PHOTO_QUALITY)), 'jpg'];

        // Nothing to resize and no saving: keep the original, untouched.
        if (! $changed && strlen($result[0]) >= strlen($contents)) {
            return null;
        }

        return $result;
    }

    /**
     * @return array{0: string, 1: string}|null [bytes, extension], or null to keep the original file
     */
    public static function processLogo(string $contents, ?string $sourcePath, int $maxSide): ?array
    {
        $source = self::open($contents, $sourcePath);

        if ($source === null) {
            return null;
        }

        [$image, $rotated] = $source;
        [$width, $height] = [imagesx($image), imagesy($image)];
        $scale = min(1, $maxSide / max($width, $height));

        // Small enough already: keep the original file exactly as it is.
        if ($scale >= 1 && ! $rotated) {
            return null;
        }

        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));
        $target = self::resample($image, 0, 0, $width, $height, $targetWidth, $targetHeight, keepAlpha: true);

        // PNG is lossless; 9 is only the zip effort, not quality.
        return [self::encode(fn () => imagepng($target, null, 9)), 'png'];
    }

    /**
     * @return array{0: GdImage, 1: bool}|null the image and whether it was rotated upright
     */
    private static function open(string $contents, ?string $sourcePath): ?array
    {
        if (! function_exists('imagecreatefromstring')) {
            return null;
        }

        $size = @getimagesizefromstring($contents);
        if ($size === false || $size[0] * $size[1] > self::MAX_PIXELS) {
            return null;
        }

        $image = @imagecreatefromstring($contents);
        if (! $image instanceof GdImage) {
            return null;
        }

        if (! imageistruecolor($image)) {
            imagepalettetotruecolor($image);
        }
        imagesavealpha($image, true);

        return self::upright($image, $sourcePath);
    }

    /**
     * Phone photos are often stored sideways with an EXIF note saying how to turn them.
     *
     * @return array{0: GdImage, 1: bool}
     */
    private static function upright(GdImage $image, ?string $sourcePath): array
    {
        if ($sourcePath === null || ! function_exists('exif_read_data')) {
            return [$image, false];
        }

        $orientation = (int) (@exif_read_data($sourcePath)['Orientation'] ?? 1);
        $angle = match ($orientation) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        if ($angle === 0) {
            return [$image, false];
        }

        $rotated = imagerotate($image, $angle, 0);

        return $rotated instanceof GdImage ? [$rotated, true] : [$image, false];
    }

    private static function resample(GdImage $image, int $x, int $y, int $width, int $height, int $targetWidth, int $targetHeight, bool $keepAlpha): GdImage
    {
        $target = imagecreatetruecolor($targetWidth, $targetHeight);

        if ($keepAlpha) {
            imagealphablending($target, false);
            imagesavealpha($target, true);
            imagefill($target, 0, 0, (int) imagecolorallocatealpha($target, 0, 0, 0, 127));
        } else {
            // Formats without transparency get a white background instead of black.
            imagefill($target, 0, 0, (int) imagecolorallocate($target, 255, 255, 255));
        }

        imagecopyresampled($target, $image, 0, 0, $x, $y, $targetWidth, $targetHeight, $width, $height);

        return $target;
    }

    /**
     * Whether any pixel is see-through (checked on a coarse grid; enough for photos and logos).
     */
    private static function hasTransparency(GdImage $image): bool
    {
        [$width, $height] = [imagesx($image), imagesy($image)];
        $step = max(1, (int) floor(min($width, $height) / 40));

        for ($y = 0; $y < $height; $y += $step) {
            for ($x = 0; $x < $width; $x += $step) {
                if (((imagecolorat($image, $x, $y) >> 24) & 0x7F) > 0) {
                    return true;
                }
            }
        }

        return false;
    }

    private static function encode(callable $write): string
    {
        ob_start();
        $write();

        return (string) ob_get_clean();
    }
}
