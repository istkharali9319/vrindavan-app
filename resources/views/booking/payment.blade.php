@extends('layouts.site')

@use('App\Support\Money')
@use('App\Http\Controllers\Booking\PaymentController')

@section('title', 'Secure payment · '.config('resort.name'))

@php
    $secondsLeft = $booking->holdSecondsLeft();
    $expired = $booking->status === \App\Enums\BookingStatus::Cancelled;
    $site = app(\App\Support\WebsiteContent::class)->for('site');
@endphp

@section('content')
<main class="booking-flow">
  @include('booking.partials.steps', ['current' => 2, 'heading' => 'Secure Payment'])

  <div class="container booking-flow-body">
    <div class="booking-grid">
      <section class="guest-panel pay-panel">
        @if ($refundDue)
          <span class="pay-state-icon is-warn" aria-hidden="true"><x-site-icon name="clock" /></span>
          <h2>We received your payment after the hold ended</h2>
          <p class="guest-panel-intro">Your room was released before the payment arrived and has since been booked by someone else. Don't worry — our team will refund the full amount to your original payment method. Please call us with booking reference <strong>{{ $booking->reference }}</strong> and we'll help you find another room.</p>
        @elseif ($expired)
          <span class="pay-state-icon is-warn" aria-hidden="true"><x-site-icon name="clock" /></span>
          <h2>Your room hold has expired</h2>
          <p class="guest-panel-intro">We held this room for {{ config('booking.hold_minutes') }} minutes while you paid. The time ran out, so the room has been released and no money was taken. You can start a new booking — it only takes a minute.</p>
          <a href="{{ route('booking.search', ['check_in' => $booking->check_in->toDateString(), 'check_out' => $booking->check_out->toDateString(), 'adults' => $booking->adults, 'children' => $booking->children, 'room_type' => $booking->roomType->slug]) }}" class="btn btn-forest">Book again <span class="arrow">→</span></a>
        @else
          <h2>Complete your payment</h2>
          <p class="guest-panel-intro">Booking <strong>{{ $booking->reference }}</strong> is reserved for you. Pay now to confirm it — you'll get your confirmation and invoice by email straight away.</p>

          @if (session('payment_error'))
            <div class="pay-alert" role="alert">{{ session('payment_error') }}</div>
          @endif
          @if ($problem)
            <div class="pay-alert" role="alert">{{ $problem }}</div>
          @endif
          <div class="pay-alert" role="alert" data-pay-message hidden></div>

          <div class="pay-amount">
            <span>Amount to pay</span>
            <strong>{{ $booking->formattedTotal() }}</strong>
            <small>GST included · {{ $booking->currency }}</small>
          </div>

          <p class="pay-timer" data-hold-seconds="{{ $secondsLeft }}">
            <x-site-icon name="clock" />
            <span>Room held for you for <b data-hold-clock>{{ sprintf('%02d:%02d', intdiv($secondsLeft, 60), $secondsLeft % 60) }}</b></span>
          </p>

          @if ($payment)
            <button type="button" class="btn btn-forest pay-now" data-pay-now>
              <x-site-icon name="lock" /> Pay {{ $booking->formattedTotal() }} securely
            </button>
            <p class="pay-secure">Secured by <b>Razorpay</b> · UPI, cards, net banking &amp; wallets
              @if (\App\Support\Razorpay::isTestMode())
                <span class="pay-test">Test mode — no real money is charged</span>
              @endif
            </p>

            <form method="POST" action="{{ URL::signedRoute('booking.payment.verify', $booking) }}" data-pay-form hidden>
              @csrf
              <input type="hidden" name="razorpay_payment_id">
              <input type="hidden" name="razorpay_order_id">
              <input type="hidden" name="razorpay_signature">
            </form>
          @endif

          @if ($canPayAtResort)
            <form method="POST" action="{{ URL::signedRoute('booking.payment.pay-at-resort', $booking) }}" class="pay-later">
              @csrf
              <span>Prefer to settle when you arrive?</span>
              <button type="submit" class="text-link">Pay at the resort instead</button>
            </form>
          @endif
        @endif
      </section>

      <aside class="booking-side">
        <section class="stay-card-box">
          <h2>Your Stay</h2>
          <div class="stay-card-room">
            <img src="{{ $booking->roomType->imageUrl() }}" alt="{{ $booking->roomType->name }}">
            <div>
              <strong>{{ $booking->guestFullName() }}</strong>
              <span>{{ $booking->roomType->name }} · {{ $booking->guestSummary() }}</span>
            </div>
          </div>
          <dl class="stay-card-dates">
            <div><dt>Check-in</dt><dd>{{ $booking->check_in->format('D, j M Y') }}</dd></div>
            <div><dt>Check-out</dt><dd>{{ $booking->check_out->format('D, j M Y') }}</dd></div>
          </dl>
          <div class="stay-card-lines">
            <div><span>{{ $booking->formattedNightlyPrice() }} × {{ $booking->nights }} {{ str('night')->plural($booking->nights) }}</span><span>{{ Money::format($booking->room_amount) }}</span></div>
            @foreach ($booking->addOns as $addOn)
              <div class="is-addon"><span>+ {{ $addOn->name }}</span><span>{{ $addOn->amount === 0 ? 'Free' : $addOn->formattedAmount() }}</span></div>
            @endforeach
            <div class="is-muted"><span>Taxes (GST)</span><span>Included</span></div>
          </div>
          <div class="stay-card-total"><span>Total Amount</span><strong>{{ $booking->formattedTotal() }}</strong></div>
        </section>
      </aside>
    </div>
  </div>
</main>
@endsection

@if ($payment && ! $expired && ! $refundDue)
  @push('scripts')
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script>
      (function () {
        const button = document.querySelector("[data-pay-now]");
        const form = document.querySelector("[data-pay-form]");
        const message = document.querySelector("[data-pay-message]");
        const timer = document.querySelector("[data-hold-seconds]");
        const clock = document.querySelector("[data-hold-clock]");
        let secondsLeft = Number(timer.dataset.holdSeconds);

        const show = (text) => { message.textContent = text; message.hidden = false; };

        const tick = () => {
          secondsLeft = Math.max(0, secondsLeft - 1);
          clock.textContent = String(Math.floor(secondsLeft / 60)).padStart(2, "0") + ":" + String(secondsLeft % 60).padStart(2, "0");
          timer.classList.toggle("is-low", secondsLeft < 120);
          if (secondsLeft === 0) window.location.reload();
        };
        setInterval(tick, 1000);

        const checkout = new Razorpay({
          key: @json(\App\Support\Razorpay::keyId()),
          order_id: @json($payment->order_id),
          amount: @json($payment->amount),
          currency: @json($payment->currency),
          name: @json(config('resort.name')),
          description: @json($booking->roomType->name.' · '.$booking->reference),
          image: @json($site->image('identity.favicon')),
          prefill: {
            name: @json($booking->guestFullName()),
            email: @json($booking->contact_email),
            contact: @json($booking->contact_phone),
          },
          notes: { booking_reference: @json($booking->reference) },
          theme: { color: "#14231b" },
          timeout: Math.max(60, secondsLeft),
          handler: function (response) {
            button.disabled = true;
            button.textContent = "Confirming your payment…";
            form.razorpay_payment_id.value = response.razorpay_payment_id;
            form.razorpay_order_id.value = response.razorpay_order_id;
            form.razorpay_signature.value = response.razorpay_signature;
            form.submit();
          },
          modal: {
            ondismiss: function () {
              show("Payment was not completed. Your room is still held — you can try again.");
            },
          },
        });

        checkout.on("payment.failed", function (response) {
          const reason = (response.error && response.error.description) || "The payment did not go through.";
          show(reason + " You can try again with another method.");
          fetch(@json(URL::signedRoute('booking.payment.failed', $booking)), {
            method: "POST",
            headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": @json(csrf_token()), "Accept": "application/json" },
            body: JSON.stringify({ order_id: @json($payment->order_id), reason: reason }),
          }).catch(() => {});
        });

        button.addEventListener("click", () => { message.hidden = true; checkout.open(); });

        @unless (session('payment_error'))
          checkout.open();
        @endunless
      })();
    </script>
  @endpush
@endif
