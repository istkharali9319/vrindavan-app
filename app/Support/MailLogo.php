<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Mime\Email;

/**
 * The logo from Site settings, attached inside every email so it shows even
 * when the mail client can't (or won't) load images from the website.
 */
class MailLogo
{
    public const CID = 'resort-logo';

    /**
     * What the <img src> in the email header should be, or null to show the resort name instead.
     */
    public static function src(): ?string
    {
        $setting = self::setting();

        if (Str::startsWith((string) $setting, ['http://', 'https://'])) {
            return $setting;
        }

        return self::path() ? 'cid:'.self::CID : null;
    }

    /**
     * The logo file on disk, if it is one that mail clients can show.
     */
    public static function path(): ?string
    {
        $setting = self::setting();

        if (blank($setting) || Str::startsWith($setting, ['http://', 'https://'])) {
            return null;
        }

        $path = Str::startsWith($setting, 'images/')
            ? public_path($setting)
            : Storage::disk('public')->path($setting);

        $type = is_file($path) ? mime_content_type($path) : null;

        return in_array($type, ['image/png', 'image/jpeg', 'image/gif'], true) ? $path : null;
    }

    /**
     * Attaches the logo to an outgoing message whose HTML refers to it.
     */
    public static function embedInto(Email $email): void
    {
        $path = self::path();

        if ($path && str_contains((string) $email->getHtmlBody(), 'cid:'.self::CID)) {
            $email->embedFromPath($path, self::CID, mime_content_type($path));
        }
    }

    private static function setting(): ?string
    {
        return rescue(fn () => app(WebsiteContent::class)->for('site')->text('identity.logo'), report: false)
            ?: 'images/brand/logo.png';
    }
}
