<?php

namespace Tests\Feature;

use App\Actions\Booking\ReleaseExpiredHolds;
use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Booking\BookingController;
use App\Http\Controllers\Booking\PaymentController;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\RoomNight;
use App\Notifications\BookingConfirmedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class RazorpayPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private const SECRET = 'test_key_secret';

    private const WEBHOOK_SECRET = 'test_webhook_secret';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.razorpay' => ['key_id' => 'rzp_test_abc123', 'key_secret' => self::SECRET, 'webhook_secret' => self::WEBHOOK_SECRET]]);
        $this->travelTo(now(config('booking.timezone'))->setDate(2026, 11, 2)->setTime(10, 0));

        Http::fake([
            'api.razorpay.com/v1/orders' => fn ($request) => Http::response([
                'id' => 'order_'.substr(md5((string) microtime(true)), 0, 14),
                'amount' => $request['amount'],
                'currency' => 'INR',
                'status' => 'created',
            ]),
        ]);
        Notification::fake();
    }

    /**
     * @return array<string, mixed>
     */
    private function bookingForm(array $overrides = []): array
    {
        return [
            'check_in' => '2026-11-10', 'check_out' => '2026-11-12', 'adults' => 2, 'children' => 0,
            'title' => 'Ms', 'first_name' => 'Priya', 'last_name' => 'Malhotra',
            'contact_email' => 'priya@example.com', 'country_code' => '+91', 'mobile' => '98300 12345', 'terms' => '1',
            'payment_method' => 'online',
            ...$overrides,
        ];
    }

    /**
     * Books online and opens the payment page, which creates the Razorpay order.
     */
    private function startOnlineBooking(): Payment
    {
        $this->post(route('booking.store', 'deluxe-room'), $this->bookingForm())
            ->assertRedirect(PaymentController::paymentUrl(Booking::sole()));

        $this->get(PaymentController::paymentUrl(Booking::sole()))->assertOk();

        return Payment::sole();
    }

    private function signedWebhook(array $payload): TestResponse
    {
        $body = json_encode($payload);

        return $this->call('POST', route('payments.razorpay.webhook'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_RAZORPAY_SIGNATURE' => hash_hmac('sha256', $body, self::WEBHOOK_SECRET),
        ], $body);
    }

    public function test_guests_can_choose_to_pay_online_or_at_the_resort(): void
    {
        $this->get(route('booking.create', ['deluxe-room', 'check_in' => '2026-11-10', 'check_out' => '2026-11-12', 'adults' => 2]))
            ->assertOk()
            ->assertSee('Pay now online')
            ->assertSee('Pay at the resort')
            ->assertSee('Continue to payment');

        config(['booking.pay_at_resort' => false]);
        $this->post(route('booking.store', 'deluxe-room'), $this->bookingForm(['payment_method' => 'pay_at_resort']))
            ->assertSessionHasErrors('payment_method');
    }

    public function test_paying_online_holds_the_room_then_confirms_it_after_a_verified_payment(): void
    {
        $payment = $this->startOnlineBooking();
        $booking = Booking::sole();

        $this->assertSame(BookingStatus::PendingPayment, $booking->status);
        $this->assertSame(PaymentStatus::Pending, $booking->payment_status);
        $this->assertSame(PaymentMethod::Online, $booking->payment_method);
        $this->assertSame(2, $booking->nights()->count());
        $this->assertSame($booking->total_amount, $payment->amount);
        Notification::assertNothingSent();
        Http::assertSent(fn ($request) => $request['receipt'] === $booking->reference && $request['amount'] === $booking->total_amount);

        $this->get(PaymentController::paymentUrl($booking))
            ->assertSee($payment->order_id)
            ->assertSee('checkout.razorpay.com', false)
            ->assertSee('Test mode');
        $this->assertSame(1, Payment::count(), 'Refreshing reuses the open order');

        $this->post(URL::signedRoute('booking.payment.verify', $booking), [
            'razorpay_order_id' => $payment->order_id,
            'razorpay_payment_id' => 'pay_TEST123',
            'razorpay_signature' => hash_hmac('sha256', $payment->order_id.'|pay_TEST123', self::SECRET),
        ])->assertRedirect(BookingController::confirmationUrl($booking));

        $booking->refresh();
        $this->assertSame(BookingStatus::Confirmed, $booking->status);
        $this->assertSame(PaymentStatus::Paid, $booking->payment_status);
        $this->assertNotNull($booking->paid_at);
        $this->assertSame(Payment::CAPTURED, $payment->fresh()->status);
        $this->assertSame('pay_TEST123', $payment->fresh()->payment_id);
        Notification::assertSentOnDemandTimes(BookingConfirmedNotification::class, 1);

        // The webhook arriving afterwards changes nothing and sends nothing twice.
        $this->signedWebhook(['event' => 'payment.captured', 'payload' => ['payment' => ['entity' => ['id' => 'pay_TEST123', 'order_id' => $payment->order_id, 'method' => 'upi']]]])->assertNoContent();
        Notification::assertSentOnDemandTimes(BookingConfirmedNotification::class, 1);
    }

    public function test_a_forged_signature_does_not_confirm_the_booking(): void
    {
        $payment = $this->startOnlineBooking();
        $booking = Booking::sole();

        $this->post(URL::signedRoute('booking.payment.verify', $booking), [
            'razorpay_order_id' => $payment->order_id,
            'razorpay_payment_id' => 'pay_FAKE',
            'razorpay_signature' => 'not-a-real-signature',
        ])->assertRedirect(PaymentController::paymentUrl($booking))->assertSessionHas('payment_error');

        $this->assertSame(BookingStatus::PendingPayment, $booking->fresh()->status);

        $this->call('POST', route('payments.razorpay.webhook'), [], [], [], [
            'CONTENT_TYPE' => 'application/json', 'HTTP_X_RAZORPAY_SIGNATURE' => 'wrong',
        ], json_encode(['event' => 'payment.captured']))->assertStatus(400);

        $this->post(route('booking.payment.verify', $booking), [])->assertForbidden();
    }

    public function test_the_webhook_confirms_a_booking_when_the_guest_closed_the_browser(): void
    {
        $payment = $this->startOnlineBooking();

        $this->signedWebhook(['event' => 'payment.captured', 'payload' => ['payment' => ['entity' => [
            'id' => 'pay_HOOK1', 'order_id' => $payment->order_id, 'method' => 'card',
        ]]]])->assertNoContent();

        $booking = Booking::sole();
        $this->assertSame(BookingStatus::Confirmed, $booking->status);
        $this->assertSame(PaymentStatus::Paid, $booking->payment_status);
        $this->assertSame('card', $payment->fresh()->method);
        Notification::assertSentOnDemandTimes(BookingConfirmedNotification::class, 1);
    }

    public function test_an_unpaid_hold_expires_and_frees_the_room(): void
    {
        $this->startOnlineBooking();
        $booking = Booking::sole();

        $this->travel(config('booking.hold_minutes') + 1)->minutes();
        $this->assertSame(1, app(ReleaseExpiredHolds::class)->handle());

        $booking->refresh();
        $this->assertSame(BookingStatus::Cancelled, $booking->status);
        $this->assertSame(0, $booking->nights()->count());

        $this->get(PaymentController::paymentUrl($booking))->assertOk()->assertSee('Your room hold has expired');
        $this->get(BookingController::confirmationUrl($booking))->assertOk();
    }

    public function test_a_late_payment_rebooks_the_room_if_it_is_still_free(): void
    {
        $payment = $this->startOnlineBooking();
        $this->travel(config('booking.hold_minutes') + 1)->minutes();
        app(ReleaseExpiredHolds::class)->handle();

        $this->signedWebhook(['event' => 'payment.captured', 'payload' => ['payment' => ['entity' => ['id' => 'pay_LATE', 'order_id' => $payment->order_id]]]]);

        $booking = Booking::sole();
        $this->assertSame(BookingStatus::Confirmed, $booking->status);
        $this->assertSame(2, $booking->nights()->count());
        $this->assertNull($booking->cancelled_at);
    }

    public function test_a_late_payment_for_a_room_taken_meanwhile_is_flagged_for_refund(): void
    {
        $payment = $this->startOnlineBooking();
        $first = Booking::sole();
        $this->travel(config('booking.hold_minutes') + 1)->minutes();
        app(ReleaseExpiredHolds::class)->handle();

        // Someone else books the same room while the hold has lapsed.
        RoomNight::insert([
            ['room_id' => $first->room_id, 'night_date' => '2026-11-10', 'booking_id' => Booking::factory()->create()->id, 'created_at' => now()],
        ]);

        $this->signedWebhook(['event' => 'payment.captured', 'payload' => ['payment' => ['entity' => ['id' => 'pay_LATE2', 'order_id' => $payment->order_id]]]]);

        $this->assertSame(BookingStatus::Cancelled, $first->fresh()->status);
        $this->assertSame(Payment::REFUND_DUE, $payment->fresh()->status);
        $this->get(PaymentController::paymentUrl($first))->assertSee('We received your payment after the hold ended');
    }

    public function test_guests_can_switch_to_paying_at_the_resort(): void
    {
        $this->startOnlineBooking();
        $booking = Booking::sole();

        $this->post(URL::signedRoute('booking.payment.pay-at-resort', $booking))
            ->assertRedirect(BookingController::confirmationUrl($booking));

        $booking->refresh();
        $this->assertSame(BookingStatus::Confirmed, $booking->status);
        $this->assertSame(PaymentStatus::Unpaid, $booking->payment_status);
        $this->assertSame(PaymentMethod::PayAtResort, $booking->payment_method);
        $this->assertNull($booking->hold_expires_at);
        Notification::assertSentOnDemandTimes(BookingConfirmedNotification::class, 1);
    }

    public function test_without_razorpay_keys_bookings_are_paid_at_the_resort(): void
    {
        config(['services.razorpay.key_id' => '', 'services.razorpay.key_secret' => '']);

        $this->get(route('booking.create', ['deluxe-room', 'check_in' => '2026-11-10', 'check_out' => '2026-11-12', 'adults' => 2]))
            ->assertDontSee('Pay now online');

        $this->post(route('booking.store', 'deluxe-room'), $this->bookingForm(['payment_method' => null]));

        $this->assertSame(BookingStatus::Confirmed, Booking::sole()->status);
        Http::assertNothingSent();
    }
}
