@extends('layouts.account')

@section('title', 'Booking '.$booking->reference.' · '.config('resort.name'))

@section('account')
  <p class="back-link"><a href="{{ route('account.dashboard') }}" class="text-link">← My stays</a></p>
  <h1>{{ $booking->roomType->name }}</h1>
  <p class="lead-text">
    Booking reference <strong>{{ $booking->reference }}</strong>
    <span class="badge-soft {{ $booking->status->tone() }}">{{ $booking->status->label() }}</span>
  </p>

  @if ($booking->isAwaitingPayment() && $booking->holdSecondsLeft() > 0)
    <div class="flash" role="status">This booking is waiting for payment. <a href="{{ \App\Http\Controllers\Booking\PaymentController::paymentUrl($booking) }}" class="text-link"><strong>Complete payment</strong></a> to confirm your room.</div>
  @endif

  @error('booking')
    <div class="flash error" role="alert">{{ $message }}</div>
  @enderror

  <section class="panel">
    <h2>Your stay</h2>
    <dl class="detail-list">
      <dt>Check-in</dt><dd>{{ $booking->check_in->format('l, j F Y') }}, from {{ \Carbon\Carbon::parse(config('booking.check_in_time'))->format('g:i A') }}</dd>
      <dt>Check-out</dt><dd>{{ $booking->check_out->format('l, j F Y') }}, by {{ \Carbon\Carbon::parse(config('booking.check_out_time'))->format('g:i A') }}</dd>
      <dt>Nights</dt><dd>{{ $booking->nights }}</dd>
      <dt>Room</dt><dd>{{ $booking->roomType->name }}, room {{ $booking->room->number }}</dd>
      <dt>Guests</dt><dd>{{ $booking->guestSummary() }}</dd>
      @if ($booking->special_requests)
        <dt>Requests</dt><dd>{{ $booking->special_requests }}</dd>
      @endif
    </dl>
  </section>

  <section class="panel">
    <h2>Payment</h2>
    <dl class="detail-list">
      <dt>Tariff</dt><dd>{{ $booking->formattedNightlyPrice() }} × {{ $booking->nights }} {{ str('night')->plural($booking->nights) }}</dd>
      @foreach ($booking->addOns as $addOn)
        <dt>{{ $loop->first ? 'Add-ons' : '' }}</dt><dd>{{ $addOn->name }} · {{ $addOn->amount === 0 ? 'Free' : $addOn->formattedAmount() }}</dd>
      @endforeach
      <dt>Total (incl. GST)</dt><dd><strong>{{ $booking->formattedTotal() }}</strong></dd>
      <dt>Payment</dt><dd>{{ $booking->payment_status->label() }}</dd>
    </dl>
    <p class="panel-link"><a href="{{ route('account.bookings.invoice', $booking->reference) }}" class="btn btn-outline btn-sm">Download invoice (PDF)</a></p>
  </section>

  <section class="panel">
    <h2>Contact</h2>
    <dl class="detail-list">
      <dt>Name</dt><dd>{{ $booking->contact_name }}</dd>
      <dt>Email</dt><dd>{{ $booking->contact_email }}</dd>
      <dt>Mobile</dt><dd>{{ $booking->contact_phone }}</dd>
    </dl>
    <p class="panel-link">Need to change something? Call us on
      <a href="tel:{{ config('resort.phones')[0]['dial'] }}" class="text-link">{{ config('resort.phones')[0]['display'] }}</a>
      and quote {{ $booking->reference }}.</p>
  </section>

  @if ($booking->canBeCancelled())
    <section class="panel danger">
      <h2>Cancel booking</h2>
      <p>Cancelling releases the room straight away. This can't be undone.</p>
      <form method="POST" action="{{ route('account.bookings.cancel', $booking->reference) }}"
            onsubmit="return confirm('Cancel booking {{ $booking->reference }}?')">
        @csrf
        <button type="submit" class="btn btn-danger">Cancel this booking</button>
      </form>
    </section>
  @elseif ($booking->isCancelled())
    <section class="panel">
      <p>Cancelled on {{ $booking->cancelled_at->timezone(config('booking.timezone'))->format('j M Y, g:i A') }}.</p>
    </section>
  @endif
@endsection
