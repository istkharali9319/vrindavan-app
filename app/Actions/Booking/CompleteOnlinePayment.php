<?php

namespace App\Actions\Booking;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\RoomNight;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Records a successful Razorpay payment and confirms the booking. Called from the checkout callback
 * and from the webhook, in any order and any number of times — only the first call changes anything.
 *
 * If the money arrives after the hold expired, the same room is booked again when it is still free;
 * otherwise the payment is marked "refund due" for the team to refund.
 */
class CompleteOnlinePayment
{
    public function __construct(private SendBookingConfirmation $sendConfirmation) {}

    public function handle(Payment $payment, string $razorpayPaymentId, ?string $method = null): Booking
    {
        $booking = DB::transaction(function () use ($payment, $razorpayPaymentId, $method): Booking {
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            $booking = Booking::whereKey($payment->booking_id)->lockForUpdate()->firstOrFail();

            if (in_array($payment->status, [Payment::CAPTURED, Payment::REFUND_DUE], true)) {
                return $booking;
            }

            $payment->forceFill([
                'status' => Payment::CAPTURED,
                'payment_id' => $razorpayPaymentId,
                'method' => $method ?? $payment->method,
                'error' => null,
                'paid_at' => now(),
            ]);

            if ($booking->status === BookingStatus::Cancelled && ! $this->holdRoomAgain($booking)) {
                $payment->status = Payment::REFUND_DUE;
                $payment->error = 'Paid after the room hold expired and the room was booked by someone else.';
                $payment->save();

                return $booking;
            }

            $payment->save();

            $booking->forceFill([
                'status' => BookingStatus::Confirmed,
                'payment_status' => PaymentStatus::Paid,
                'payment_method' => PaymentMethod::Online,
                'hold_expires_at' => null,
                'paid_at' => now(),
                'cancelled_at' => null,
                'cancelled_by' => null,
            ])->save();

            return $booking;
        });

        if ($booking->status === BookingStatus::Confirmed) {
            $this->sendConfirmation->handle($booking);
        }

        return $booking;
    }

    /**
     * Re-reserves the booking's original room for its nights, if nobody else has taken them.
     */
    private function holdRoomAgain(Booking $booking): bool
    {
        if ($booking->cancelled_by !== null) {
            return false;
        }

        $now = now();

        try {
            DB::transaction(fn () => RoomNight::insert(collect(range(0, $booking->nights - 1))->map(fn (int $offset): array => [
                'room_id' => $booking->room_id,
                'night_date' => $booking->check_in->addDays($offset)->toDateString(),
                'booking_id' => $booking->id,
                'created_at' => $now,
            ])->all()));
        } catch (UniqueConstraintViolationException) {
            return false;
        }

        return true;
    }
}
