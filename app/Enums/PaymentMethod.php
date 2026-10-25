<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Online = 'online';
    case PayAtResort = 'pay_at_resort';

    public function label(): string
    {
        return match ($this) {
            self::Online => 'Paid online (Razorpay)',
            self::PayAtResort => 'Pay at the resort',
        };
    }
}
