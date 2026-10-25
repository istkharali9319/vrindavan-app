<?php

namespace App\Enums;

/**
 * One entry in an enquiry's conversation.
 */
enum EnquiryMessageType: string
{
    case Reply = 'reply';
    case Guest = 'guest';
    case Note = 'note';
    case StatusChange = 'status';

    public function label(): string
    {
        return match ($this) {
            self::Reply => 'Our reply',
            self::Guest => "Guest's response",
            self::Note => 'Internal note',
            self::StatusChange => 'Status change',
        };
    }

    /**
     * Types staff can add from the conversation box (status changes are recorded automatically).
     *
     * @return list<self>
     */
    public static function writable(): array
    {
        return [self::Reply, self::Guest, self::Note];
    }
}
