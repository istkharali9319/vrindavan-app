<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Cookie;

/**
 * Remembers (in an encrypted cookie) which account a browser was signed in to right after booking as a guest.
 * If the same browser books again with that email before the password is set, the booking joins the
 * account and the guest is signed in again. Another device typing the same email gets neither.
 */
class GuestDevice
{
    public const COOKIE = 'vr_booking_account';

    private const DAYS = 30;

    public static function remember(User $user): void
    {
        Cookie::queue(self::COOKIE, (string) $user->id, self::DAYS * 24 * 60);
    }

    public static function owns(User $user): bool
    {
        $request = request();

        return $request !== null && (string) $request->cookie(self::COOKIE) === (string) $user->id;
    }

    public static function forget(): void
    {
        Cookie::queue(Cookie::forget(self::COOKIE));
    }
}
