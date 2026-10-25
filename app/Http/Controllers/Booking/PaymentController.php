<?php

namespace App\Http\Controllers\Booking;

use App\Actions\Booking\CompleteOnlinePayment;
use App\Actions\Booking\ReleaseExpiredHolds;
use App\Actions\Booking\SendBookingConfirmation;
use App\Actions\Booking\StartOnlinePayment;
use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Support\Razorpay;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;
use Throwable;

/**
 * Paying a booking online with Razorpay Standard Checkout. Every guest-facing route is a signed link,
 * like the confirmation page, so it works for guests without an account.
 */
class PaymentController extends Controller
{
    public static function paymentUrl(Booking $booking): string
    {
        return URL::signedRoute('booking.payment', $booking);
    }

    public function show(Booking $booking, ReleaseExpiredHolds $releaseExpiredHolds, StartOnlinePayment $startPayment): View|RedirectResponse
    {
        $releaseExpiredHolds->handle();
        $booking->refresh()->load(['roomType', 'addOns']);

        if ($booking->status === BookingStatus::Confirmed) {
            return redirect()->to(BookingController::confirmationUrl($booking));
        }

        $payment = null;
        $problem = null;

        if ($booking->isAwaitingPayment()) {
            if (! Razorpay::enabled()) {
                $problem = 'Online payment is not available right now.';
            } else {
                try {
                    $payment = $startPayment->handle($booking);
                } catch (Throwable $exception) {
                    report($exception);
                    $problem = 'We could not reach the payment gateway. Please try again in a moment.';
                }
            }
        }

        return view('booking.payment', [
            'booking' => $booking,
            'payment' => $payment,
            'problem' => $problem,
            'refundDue' => $booking->payments()->where('status', Payment::REFUND_DUE)->exists(),
            'canPayAtResort' => config('booking.pay_at_resort'),
        ]);
    }

    /**
     * Razorpay checkout posts back here after a successful payment.
     */
    public function verify(Request $request, Booking $booking, Razorpay $razorpay, CompleteOnlinePayment $completePayment): RedirectResponse
    {
        $data = $request->validate([
            'razorpay_payment_id' => ['required', 'string', 'max:64'],
            'razorpay_order_id' => ['required', 'string', 'max:64'],
            'razorpay_signature' => ['required', 'string', 'max:128'],
        ]);

        $payment = $booking->payments()->where('order_id', $data['razorpay_order_id'])->first();

        if ($payment === null || ! $razorpay->paymentSignatureIsValid($data['razorpay_order_id'], $data['razorpay_payment_id'], $data['razorpay_signature'])) {
            return redirect()->to(self::paymentUrl($booking))
                ->with('payment_error', 'We could not verify this payment. If money was taken, it will be confirmed automatically within a few minutes, or contact us with your booking reference.');
        }

        $booking = $completePayment->handle($payment, $data['razorpay_payment_id']);

        return $booking->status === BookingStatus::Confirmed
            ? redirect()->to(BookingController::confirmationUrl($booking))
            : redirect()->to(self::paymentUrl($booking));
    }

    /**
     * Checkout reports a failed attempt (card declined, UPI timed out…). Kept for the team; the guest can retry.
     */
    public function failed(Request $request, Booking $booking): Response
    {
        $data = $request->validate([
            'order_id' => ['required', 'string', 'max:64'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $booking->payments()
            ->where('order_id', $data['order_id'])
            ->where('status', Payment::CREATED)
            ->update(['error' => $data['reason'] ?: 'Payment failed']);

        return response()->noContent();
    }

    /**
     * The guest decides to pay at the resort instead: the held room becomes a normal confirmed booking.
     */
    public function payAtResort(Booking $booking, SendBookingConfirmation $sendConfirmation): RedirectResponse
    {
        abort_unless(config('booking.pay_at_resort'), 403);

        $switched = DB::transaction(function () use ($booking): bool {
            $booking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();

            if (! $booking->isAwaitingPayment() || $booking->holdSecondsLeft() === 0) {
                return false;
            }

            $booking->forceFill([
                'status' => BookingStatus::Confirmed,
                'payment_status' => PaymentStatus::Unpaid,
                'payment_method' => PaymentMethod::PayAtResort,
                'hold_expires_at' => null,
            ])->save();

            return true;
        });

        $booking->refresh();

        if (! $switched && $booking->status !== BookingStatus::Confirmed) {
            return redirect()->to(self::paymentUrl($booking));
        }

        $sendConfirmation->handle($booking);

        return redirect()->to(BookingController::confirmationUrl($booking));
    }

    /**
     * Razorpay webhook (payment.captured, order.paid, payment.failed). Confirms bookings even when the guest
     * closed the browser before returning to the site.
     */
    public function webhook(Request $request, Razorpay $razorpay, CompleteOnlinePayment $completePayment): Response
    {
        if (! $razorpay->webhookSignatureIsValid($request->getContent(), (string) $request->header('X-Razorpay-Signature'))) {
            return response('Invalid signature', 400);
        }

        $entity = $request->input('payload.payment.entity', []);
        $orderId = (string) ($entity['order_id'] ?? '');
        $payment = $orderId !== '' ? Payment::where('order_id', $orderId)->first() : null;

        if ($payment === null) {
            return response()->noContent();
        }

        match ($request->input('event')) {
            'payment.captured', 'order.paid' => $completePayment->handle($payment, (string) $entity['id'], $entity['method'] ?? null),
            'payment.failed' => $payment->status === Payment::CREATED
                ? $payment->update(['error' => $entity['error_description'] ?? 'Payment failed'])
                : null,
            default => null,
        };

        return response()->noContent();
    }
}
