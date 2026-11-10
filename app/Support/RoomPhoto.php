<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;

/**
 * Room photos are stored in one shape so every room looks the same size on the website:
 * cropped to 4:3 around the centre and scaled down to at most 1600 × 1200 (see ImageUpload for
 * the format and quality rules). Photos that already fit are kept as they are.
 */
class RoomPhoto
{
    public const WIDTH = 1600;

    public const HEIGHT = 1200;

    /**
     * Saves the photo on the public disk under $folder and returns its path.
     */
    public static function store(UploadedFile $file, string $folder): string
    {
        return ImageUpload::store($file, $folder, fn (string $contents, string $path) => self::processed($contents, $path));
    }

    /**
     * The photo as a 4:3 image, or null when it can't be read (or already fits and is kept as uploaded).
     */
    public static function process(string $contents, ?string $sourcePath = null): ?string
    {
        return self::processed($contents, $sourcePath)[0] ?? null;
    }

    /**
     * @return array{0: string, 1: string}|null
     */
    private static function processed(string $contents, ?string $sourcePath): ?array
    {
        return ImageUpload::processPhoto($contents, $sourcePath, self::WIDTH, self::HEIGHT, self::WIDTH / self::HEIGHT);
    }
}
