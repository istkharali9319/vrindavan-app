<?php

namespace App\Enums;

/**
 * Only active rooms can be booked. Maintenance takes a room out of sale for a while; retired is permanent.
 */
enum RoomStatus: string
{
    case Active = 'active';
    case Maintenance = 'maintenance';
    case Retired = 'retired';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'In service',
            self::Maintenance => 'Maintenance',
            self::Retired => 'Retired',
        };
    }
}
