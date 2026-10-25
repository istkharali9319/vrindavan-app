<?php

namespace App\Support;

use App\Models\Booking;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

/**
 * A small client for Razorpay Standard Checkout: create an order, then check the signatures
 * Razorpay sends back from checkout and from webhooks. Keys live in .env (config/services.php).
 */
class Razorpay
{
    private const API = 'https://api.razorpay.com/v1';

    public static function enabled(): bool
    {
        return filled(config('services.razorpay.key_id')) && filled(config('services.razorpay.key_secret'));
    }

    public static function keyId(): string
    {
        return (string) config('services.razorpay.key_id');
    }

    public static function isTestMode(): bool
    {
        return str_starts_with(self::keyId(), 'rzp_test_');
    }

    /**
     * Creates a Razorpay order for the booking total (amounts are in paise, as Razorpay expects).
     *
     * @return array{id: string, amount: int, currency: string}
     *
     * @throws RequestException
     */
    public function createOrder(Booking $booking): array
    {
        $order = Http::withBasicAuth(self::keyId(), (string) config('services.razorpay.key_secret'))
            ->acceptJson()
            ->timeout(20)
            ->post(self::API.'/orders', [
                'amount' => $booking->total_amount,
                'currency' => $booking->currency ?: 'INR',
                'receipt' => $booking->reference,
                'notes' => [
                    'booking_reference' => $booking->reference,
                    'guest' => $booking->guestFullName(),
                ],
            ])
            ->throw()
            ->json();

        return [
            'id' => (string) $order['id'],
            'amount' => (int) $order['amount'],
            'currency' => (string) $order['currency'],
        ];
    }

    /**
     * The signature checkout returns: HMAC-SHA256 of "order_id|payment_id" with the key secret.
     */
    public function paymentSignatureIsValid(string $orderId, string $paymentId, string $signature): bool
    {
        $expected = hash_hmac('sha256', $orderId.'|'.$paymentId, (string) config('services.razorpay.key_secret'));

        return $signature !== '' && hash_equals($expected, $signature);
    }

    /**
     * Webhooks are signed with the webhook secret over the raw request body.
     */
    public function webhookSignatureIsValid(string $body, string $signature): bool
    {
        $secret = (string) config('services.razorpay.webhook_secret');

        if ($secret === '' || $signature === '') {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $body, $secret), $signature);
    }
}
