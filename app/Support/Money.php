<?php

namespace App\Support;

/**
 * Formats amounts stored in paise as Indian rupees, e.g. 13500000 → "₹1,35,000".
 */
class Money
{
    public static function format(int $paise): string
    {
        $rupees = intdiv($paise, 100);
        $fraction = $paise % 100;

        $digits = (string) $rupees;
        $lastThree = substr($digits, -3);
        $rest = substr($digits, 0, -3);

        $grouped = $rest === ''
            ? $lastThree
            : preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest).','.$lastThree;

        return '₹'.$grouped.($fraction > 0 ? '.'.str_pad((string) $fraction, 2, '0', STR_PAD_LEFT) : '');
    }
}
