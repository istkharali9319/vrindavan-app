<?php

namespace App\Console\Commands;

use App\Models\ContentItem;
use App\Models\Experience;
use App\Models\RoomType;
use App\Models\SiteContent;
use App\Support\ContentFieldRules;
use App\Support\ImageUpload;
use App\Support\RoomPhoto;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Brings photos uploaded before automatic resizing up to the same standard as new uploads
 * (see ImageUpload): rooms 4:3 at 1600 × 1200, logos lossless PNG, other photos at most 2400px,
 * saved as WebP at quality 90. Every database reference to a changed file is updated.
 *
 * Safe to run more than once: images that already fit, or would shrink by less than --min-saving
 * percent, are left exactly as they are, so nothing is re-compressed for no gain. Each original is copied to
 * storage/app/private/image-backups/{date}/ before it is replaced.
 */
class OptimizeUploadedImages extends Command
{
    /**
     * @var string
     */
    protected $signature = 'images:optimize
        {--dry-run : Show what would change, without changing anything}
        {--no-backup : Do not keep a copy of the originals}
        {--min-saving=10 : Only replace an image when this saves at least this many percent}';

    /**
     * @var string
     */
    protected $description = 'Resizes and compresses photos uploaded before automatic image resizing';

    private Filesystem $disk;

    private string $backupFolder;

    public function handle(): int
    {
        $this->disk = Storage::disk('public');
        $this->backupFolder = 'image-backups/'.now()->format('Y-m-d-His');
        $dryRun = (bool) $this->option('dry-run');

        if (! function_exists('imagecreatefromstring')) {
            $this->components->error('PHP\'s GD image library is not installed, so images can\'t be resized. Ask your host to enable the "gd" extension.');

            return self::FAILURE;
        }

        $images = $this->referencedImages();
        $this->components->info(count($images).' uploaded '.str('image')->plural(count($images)).' found'.($dryRun ? ' (dry run: nothing will be changed)' : '').'.');

        $totals = ['changed' => 0, 'kept' => 0, 'missing' => 0, 'failed' => 0, 'before' => 0, 'after' => 0];

        foreach ($images as $path => $profile) {
            if (! $this->disk->exists($path)) {
                $this->line("  missing   {$path}");
                $totals['missing']++;

                continue;
            }

            try {
                $original = (string) $this->disk->get($path);
                $result = $this->process($original, $this->disk->path($path), $profile);
            } catch (Throwable $e) {
                $this->line("  failed    {$path} ({$e->getMessage()})");
                $totals['failed']++;

                continue;
            }

            $minSaving = max(0, min(90, (int) $this->option('min-saving'))) / 100;

            if ($result === null || strlen($result[0]) > strlen($original) * (1 - $minSaving)) {
                $this->line("  ok        {$path} (already fine, left as it is)");
                $totals['kept']++;

                continue;
            }

            [$bytes, $extension] = $result;
            $newPath = $this->newPath($path, $extension);
            $this->line(sprintf('  %s %s  %s → %s%s', $dryRun ? 'would fix' : 'fixed    ', $path, $this->kb(strlen($original)), $this->kb(strlen($bytes)), $newPath !== $path ? "  (now {$newPath})" : ''));

            $totals['changed']++;
            $totals['before'] += strlen($original);
            $totals['after'] += strlen($bytes);

            if (! $dryRun) {
                $this->replace($path, $newPath, $original, $bytes);
            }
        }

        $this->newLine();
        $this->components->twoColumnDetail($dryRun ? 'Would be resized' : 'Resized', (string) $totals['changed']);
        $this->components->twoColumnDetail('Already fine', (string) $totals['kept']);
        $this->components->twoColumnDetail('Missing files', (string) $totals['missing']);
        $this->components->twoColumnDetail('Failed', (string) $totals['failed']);
        if ($totals['changed'] > 0) {
            $saved = $totals['before'] - $totals['after'];
            $this->components->twoColumnDetail($dryRun ? 'Would save' : 'Saved', $this->kb($saved).' ('.(int) round(100 * $saved / max(1, $totals['before'])).'% of those images)');
        }
        if (! $dryRun && $totals['changed'] > 0 && ! $this->option('no-backup')) {
            $this->components->twoColumnDetail('Originals kept in', 'storage/app/private/'.$this->backupFolder);
        }

        return $totals['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Every uploaded image the database points to, with how it should be processed.
     *
     * @return array<string, string> path => 'room' | 'logo' | 'icon' | 'photo'
     */
    private function referencedImages(): array
    {
        $images = [];

        foreach (RoomType::all() as $roomType) {
            foreach (array_filter([$roomType->image_path, ...($roomType->gallery ?? [])]) as $path) {
                $images[$path] = 'room';
            }
        }

        foreach (Experience::whereNotNull('image_path')->pluck('image_path') as $path) {
            $images[$path] ??= 'photo';
        }

        foreach (SiteContent::all() as $row) {
            if ($this->isUploadedImage($row->value)) {
                $images[$row->value] = match (true) {
                    str_ends_with($row->key, 'favicon') => 'icon',
                    str_ends_with($row->key, 'logo') => 'logo',
                    default => $images[$row->value] ?? 'photo',
                };
            }
        }

        foreach (ContentItem::all() as $item) {
            foreach ((array) $item->data as $value) {
                if (is_string($value) && $this->isUploadedImage($value)) {
                    $images[$value] ??= 'photo';
                }
            }
        }

        return array_filter($images, fn (string $profile, string $path): bool => $this->isUploadedImage($path), ARRAY_FILTER_USE_BOTH);
    }

    private function isUploadedImage(?string $path): bool
    {
        return ContentFieldRules::isUpload($path) && (bool) preg_match('/\.(jpe?g|png|webp)$/i', (string) $path);
    }

    /**
     * @return array{0: string, 1: string}|null
     */
    private function process(string $contents, string $sourcePath, string $profile): ?array
    {
        return match ($profile) {
            'room' => ImageUpload::processPhoto($contents, $sourcePath, RoomPhoto::WIDTH, RoomPhoto::HEIGHT, RoomPhoto::WIDTH / RoomPhoto::HEIGHT),
            'logo' => ImageUpload::processLogo($contents, $sourcePath, 1200),
            'icon' => ImageUpload::processLogo($contents, $sourcePath, 512),
            default => ImageUpload::processPhoto($contents, $sourcePath, 2400, 2400),
        };
    }

    /**
     * Same folder and name, new extension; a different name if that file already exists.
     */
    private function newPath(string $path, string $extension): string
    {
        $base = preg_replace('/\.[^.\/]+$/', '', $path);
        $candidate = "{$base}.{$extension}";

        if ($candidate === $path || ! $this->disk->exists($candidate)) {
            return $candidate;
        }

        return $base.'-'.Str::lower(Str::random(6)).'.'.$extension;
    }

    /**
     * Backs up the original, writes the new file, points the database at it, then removes the old file.
     */
    private function replace(string $path, string $newPath, string $original, string $bytes): void
    {
        if (! $this->option('no-backup')) {
            Storage::disk('local')->put($this->backupFolder.'/'.$path, $original);
        }

        $this->disk->put($newPath, $bytes);

        if ($newPath === $path) {
            return;
        }

        DB::transaction(fn () => $this->updateReferences($path, $newPath));
        $this->disk->delete($path);
    }

    private function updateReferences(string $old, string $new): void
    {
        foreach (RoomType::all() as $roomType) {
            $gallery = $roomType->gallery ?? [];
            $updatedGallery = array_map(fn (string $path): string => $path === $old ? $new : $path, $gallery);

            if ($roomType->image_path === $old || $updatedGallery !== $gallery) {
                $roomType->forceFill([
                    'image_path' => $roomType->image_path === $old ? $new : $roomType->image_path,
                    'gallery' => $updatedGallery,
                ])->save();
            }
        }

        Experience::where('image_path', $old)->update(['image_path' => $new]);
        SiteContent::where('value', $old)->update(['value' => $new]);

        foreach (ContentItem::all() as $item) {
            $data = (array) $item->data;
            $updated = array_map(fn ($value) => $value === $old ? $new : $value, $data);

            if ($updated !== $data) {
                $item->forceFill(['data' => $updated])->save();
            }
        }
    }

    private function kb(int $bytes): string
    {
        return $bytes >= 1048576 ? round($bytes / 1048576, 1).' MB' : (int) round($bytes / 1024).' KB';
    }
}
