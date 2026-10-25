<?php

namespace App\Actions\Booking;

use App\Models\Booking;
use App\Models\Payment;
use App\Support\Razorpay;

/**
 * Gets the Razorpay order to open checkout with. An unpaid order for the same amount is reused,
 * so refreshing the payment page doesn't create a new order each time.
 */
class StartOnlinePayment
{
    public function __construct(private Razorpay $razorpay) {}

    public function handle(Booking $booking): Payment
    {
        $open = $booking->payments()
            ->where('status', Payment::CREATED)
            ->where('amount', $booking->total_amount)
            ->first();

        if ($open) {
            return $open;
        }

        $order = $this->razorpay->createOrder($booking);

        return $booking->payments()->create([
            'provider' => 'razorpay',
            'order_id' => $order['id'],
            'amount' => $order['amount'],
            'currency' => $order['currency'],
            'status' => Payment::CREATED,
        ]);
    }
}
