<?php

namespace App\Support;

use Illuminate\Validation\Rule;

/**
 * Validation rules for a field described in config/site-content.php.
 */
class ContentFieldRules
{
    public const MAX_IMAGE_KB = 5120;

    /**
     * Rules for the field's text value (images are validated separately as uploads).
     *
     * @param  array{type: string, options: array<string, string>}  $field
     * @return list<mixed>
     */
    public static function value(array $field): array
    {
        return match ($field['type']) {
            'text' => ['nullable', 'string', 'max:255'],
            'textarea', 'list' => ['nullable', 'string', 'max:5000'],
            'select', 'icon' => ['nullable', 'string', Rule::in(array_keys($field['options']))],
            'tags' => ['nullable', 'array'],
            default => ['nullable', 'string', 'max:255'],
        };
    }

    /**
     * @return list<string>
     */
    public static function image(): array
    {
        return ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.self::MAX_IMAGE_KB];
    }

    /**
     * @param  array{options: array<string, string>}  $field
     * @return list<mixed>
     */
    public static function tag(array $field): array
    {
        return ['string', Rule::in(array_keys($field['options']))];
    }

    /**
     * Uploads are stored on the public disk; bundled images and URLs are never deleted.
     */
    public static function isUpload(?string $path): bool
    {
        return filled($path) && ! str_starts_with($path, 'images/') && ! preg_match('#^https?://#', $path);
    }
}
