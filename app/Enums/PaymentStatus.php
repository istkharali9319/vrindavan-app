<?php

namespace App\Enums;

/**
 * Bookings are either paid online through Razorpay or settled at the resort.
 */
enum PaymentStatus: string
{
    case Unpaid = 'unpaid';
    case Pending = 'pending';
    case Paid = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Pay at the resort',
            self::Pending => 'Awaiting online payment',
            self::Paid => 'Paid',
        };
    }
}
