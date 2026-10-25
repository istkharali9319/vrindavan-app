<?php

namespace App\Actions\Booking;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use Illuminate\Support\Facades\DB;

/**
 * Bookings waiting for online payment hold their room for config('booking.hold_minutes').
 * Once that time has passed without payment, the booking is cancelled and the room is free again.
 *
 * Runs every minute from the scheduler, and also before availability is checked, so holds are
 * released on time even on hosting without a cron job.
 */
class ReleaseExpiredHolds
{
    public function handle(): int
    {
        $released = 0;

        Booking::query()
            ->where('status', BookingStatus::PendingPayment)
            ->where('hold_expires_at', '<', now())
            ->pluck('id')
            ->each(function (int $id) use (&$released): void {
                DB::transaction(function () use ($id, &$released): void {
                    $booking = Booking::whereKey($id)->lockForUpdate()->first();

                    if ($booking === null || ! $booking->isAwaitingPayment() || $booking->hold_expires_at->isFuture()) {
                        return;
                    }

                    $booking->forceFill([
                        'status' => BookingStatus::Cancelled,
                        'payment_status' => PaymentStatus::Unpaid,
                        'cancelled_at' => now(),
                    ])->save();

                    $booking->nights()->delete();
                    $released++;
                });
            });

        return $released;
    }
}
