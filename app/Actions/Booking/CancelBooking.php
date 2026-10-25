<?php

namespace App\Actions\Booking;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Cancels a booking and frees its room nights so they can be booked again.
 * The booking row is kept for history.
 */
class CancelBooking
{
    public function handle(Booking $booking, User $cancelledBy): void
    {
        DB::transaction(function () use ($booking, $cancelledBy): void {
            $booking->forceFill([
                'status' => BookingStatus::Cancelled,
                'cancelled_at' => now(),
                'cancelled_by' => $cancelledBy->id,
            ])->save();

            $booking->nights()->delete();
        });
    }
}
