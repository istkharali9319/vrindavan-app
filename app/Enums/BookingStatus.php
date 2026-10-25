<?php

namespace App\Enums;

enum BookingStatus: string
{
    /** Paying online: the room is held until bookings.hold_expires_at while the guest completes Razorpay checkout. */
    case PendingPayment = 'pending_payment';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PendingPayment => 'Awaiting payment',
            self::Confirmed => 'Confirmed',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * Badge colour used in the customer account and the admin.
     */
    public function tone(): string
    {
        return match ($this) {
            self::PendingPayment => 'warn',
            self::Confirmed => 'ok',
            self::Cancelled => 'warn',
        };
    }
}
