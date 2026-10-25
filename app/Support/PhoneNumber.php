<?php

namespace App\Support;

/**
 * Normalises phone numbers to E.164 (e.g. "+919432064521").
 *
 * Ten-digit Indian mobile numbers without a country code are assumed to be +91.
 */
class PhoneNumber
{
    /**
     * Returns the E.164 form, or null when the input cannot be a valid phone number.
     */
    public static function normalize(?string $input): ?string
    {
        if ($input === null) {
            return null;
        }

        $number = preg_replace('/[\s\-().]/', '', trim($input));

        if (str_starts_with($number, '00')) {
            $number = '+'.substr($number, 2);
        }

        if (preg_match('/^0?[6-9]\d{9}$/', $number)) {
            $number = '+91'.substr($number, -10);
        } elseif (preg_match('/^91[6-9]\d{9}$/', $number)) {
            $number = '+'.$number;
        }

        return preg_match('/^\+[1-9]\d{7,14}$/', $number) ? $number : null;
    }
}
