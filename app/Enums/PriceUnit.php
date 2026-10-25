<?php

namespace App\Enums;

/**
 * How an experience or add-on price is multiplied for a booking.
 */
enum PriceUnit: string
{
    case PerBooking = 'per_booking';
    case PerGuest = 'per_guest';
    case PerNight = 'per_night';
    case PerGuestPerNight = 'per_guest_per_night';

    public function label(): string
    {
        return match ($this) {
            self::PerBooking => 'per booking',
            self::PerGuest => 'per guest',
            self::PerNight => 'per night',
            self::PerGuestPerNight => 'per guest, per night',
        };
    }

    /**
     * How many times the price applies to a stay. Every guest counts, children included.
     */
    public function quantity(int $guests, int $nights): int
    {
        return match ($this) {
            self::PerBooking => 1,
            self::PerGuest => max(1, $guests),
            self::PerNight => max(1, $nights),
            self::PerGuestPerNight => max(1, $guests) * max(1, $nights),
        };
    }
}
