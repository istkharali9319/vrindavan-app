<?php

namespace App\Enums;

enum EnquiryStatus: string
{
    case New = 'new';
    case Replied = 'replied';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Replied => 'Replied',
            self::Closed => 'Closed',
        };
    }
}
