<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Brings guests back to the page they came from (e.g. booking details with their dates) after
 * signing in or registering. Only paths on this site are accepted, never other domains.
 */
class ReturnTo
{
    public const PARAMETER = 'return';

    /**
     * Remembers ?return=/path as the place to go after authentication.
     */
    public static function remember(Request $request): void
    {
        $path = self::safePath($request->query(self::PARAMETER));

        if ($path !== null) {
            $request->session()->put('url.intended', url($path));
        }
    }

    /**
     * The current page as a ?return= value, e.g. "/book/deluxe-room?check_in=2026-10-29&…".
     */
    public static function currentPath(Request $request): string
    {
        $query = $request->getQueryString();

        return '/'.ltrim($request->path(), '/').($query ? '?'.$query : '');
    }

    /**
     * The value to pass along between the sign-in and register pages, if any.
     */
    public static function pending(Request $request): ?string
    {
        return self::safePath($request->query(self::PARAMETER));
    }

    private static function safePath(mixed $path): ?string
    {
        if (! is_string($path) || $path === '' || strlen($path) > 1000) {
            return null;
        }

        // A local path only: "/book/…", never "//evil.example" or "https://…".
        if (! str_starts_with($path, '/') || str_starts_with($path, '//') || str_contains($path, '\\') || preg_match('/[\r\n]/', $path)) {
            return null;
        }

        return $path;
    }
}
