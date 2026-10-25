<?php

namespace App\Enums;

/**
 * How a reply or the guest's response was exchanged.
 */
enum EnquiryChannel: string
{
    case Email = 'email';
    case Phone = 'phone';
    case WhatsApp = 'whatsapp';
    case InPerson = 'in_person';

    public function label(): string
    {
        return match ($this) {
            self::Email => 'Email',
            self::Phone => 'Phone call',
            self::WhatsApp => 'WhatsApp',
            self::InPerson => 'In person',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Email => 'ti-mail',
            self::Phone => 'ti-phone',
            self::WhatsApp => 'ti-brand-whatsapp',
            self::InPerson => 'ti-user',
        };
    }
}
