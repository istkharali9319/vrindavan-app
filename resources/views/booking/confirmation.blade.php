@extends('layouts.site')

@use('App\Support\Money')

@section('title', 'Booking '.$booking->reference.' · '.config('resort.name'))
@section('description', 'Your booking at '.config('resort.name').'.')

@php
    $content = app(\App\Support\WebsiteContent::class)->for('booking');
    $checkInTime = \Carbon\Carbon::parse(config('booking.check_in_time'))->format('g:i A');
    $checkOutTime = \Carbon\Carbon::parse(config('booking.check_out_time'))->format('g:i A');
@endphp

@section('content')
<main class="booking-flow">
  @include('booking.partials.steps', ['current' => 3, 'heading' => $booking->isCancelled() ? 'Booking Cancelled' : 'Confirmation'])

  <div class="container booking-flow-body">
    <div class="booking-grid">
      <section class="guest-panel confirm-panel">
        @if ($booking->isCancelled())
          <span class="confirm-badge is-cancelled">✕</span>
          <h2>This booking was cancelled</h2>
          <p class="guest-panel-intro">Room {{ $booking->room->number }} has been released. Contact us if this is unexpected.</p>
        @else
          <span class="confirm-badge">✓</span>
          <h2>{{ $content->text('confirmation.title') }}</h2>
          <p class="guest-panel-intro">{{ $content->html('confirmation.text') }}</p>
        @endif

        <div class="confirm-reference">
          <small>Booking reference</small>
          <strong>{{ $booking->reference }}</strong>
        </div>

        <dl class="confirm-details">
          <div><dt>Guest</dt><dd>{{ $booking->guestFullName() }}</dd></div>
          <div><dt>Email</dt><dd>{{ $booking->contact_email }}</dd></div>
          <div><dt>Mobile</dt><dd>{{ $booking->contact_phone }}</dd></div>
          @if ($booking->gst_number)
            <div><dt>GST number</dt><dd>{{ $booking->gst_number }}</dd></div>
          @endif
          <div><dt>Guests</dt><dd>{{ $booking->guestSummary() }}</dd></div>
          @if ($booking->special_requests)
            <div class="is-wide"><dt>Special requests</dt><dd>{{ $booking->special_requests }}</dd></div>
          @endif
        </dl>

        <div class="confirm-actions">
          <a href="{{ \App\Http\Controllers\Booking\BookingController::invoiceUrl($booking) }}" class="btn btn-forest">Download invoice (PDF) <span class="arrow">↓</span></a>
          <button type="button" class="btn btn-outline" onclick="window.print()">Print this page</button>
          @auth
            @if (auth()->id() === $booking->user_id)
              <a href="{{ route('account.bookings.show', $booking->reference) }}" class="btn btn-forest">View in My stays <span class="arrow">→</span></a>
            @endif
          @else
            <a href="{{ route('home') }}" class="btn btn-forest">Back to the website <span class="arrow">→</span></a>
          @endauth
        </div>
        @if (auth()->id() === $booking->user_id && $booking->user?->isUnclaimedBookingAccount())
          <div class="account-made-note">
            <strong>You're signed in to your new account</strong>
            <span>We created it with {{ $booking->user->email }} so this booking is saved under My stays. We've also emailed you a link to set a password, so you can sign in again later.</span>
          </div>
        @endif
        @guest
          @if ($booking->user?->isUnclaimedBookingAccount())
            <div class="account-made-note">
              <strong>We've created an account for you</strong>
              <span>Check {{ $booking->user->email }} for an email titled "Your {{ config('resort.name') }} account is ready" and set your password. You'll then see this booking, invoices and any future stays under My stays.</span>
            </div>
          @elseif (! $booking->user && ($waiting = \App\Actions\Booking\LinkBookingToAccount::unclaimedAccountFor($booking->contact_email)))
            <div class="account-made-note">
              <strong>Set your password to see this booking in your account</strong>
              <span>There's already an account for {{ $waiting->email }} waiting for its password. We've emailed you the link again — once you set your password, this booking appears under My stays.</span>
            </div>
          @elseif ($booking->user)
            <div class="account-made-note">
              <strong>Added to your account</strong>
              <span>This booking is in the account for {{ $booking->user->email }}. <a href="{{ route('login', ['return' => route('account.bookings.show', $booking->reference, false)]) }}">Sign in</a> to see it under My stays.</span>
            </div>
          @endif
          <p class="guest-account-note">Bookmark this page — it is your private link to this booking. Questions? Call {{ config('resort.phones.0.display') }} or email <a href="mailto:{{ config('resort.email') }}">{{ config('resort.email') }}</a> with your reference.</p>
        @endguest
      </section>

      <aside class="booking-side">
        <section class="stay-card-box">
          <h2>Your Stay</h2>
          <div class="stay-card-room">
            <img src="{{ $booking->roomType->imageUrl() }}" alt="{{ $booking->roomType->name }}">
            <div>
              <strong>Room {{ $booking->room->number }}</strong>
              <span>{{ $booking->roomType->name }}</span>
            </div>
          </div>
          <dl class="stay-card-dates">
            <div><dt>Check-in</dt><dd>{{ $booking->check_in->format('D, j M Y') }}<small>from {{ $checkInTime }}</small></dd></div>
            <div><dt>Check-out</dt><dd>{{ $booking->check_out->format('D, j M Y') }}<small>by {{ $checkOutTime }}</small></dd></div>
          </dl>
          <div class="stay-card-lines">
            <div><span>{{ $booking->formattedNightlyPrice() }} × {{ $booking->nights }} {{ str('night')->plural($booking->nights) }}</span><span>{{ Money::format($booking->room_amount) }}</span></div>
            @foreach ($booking->addOns as $addOn)
              <div><span>+ {{ $addOn->name }}<small class="stay-card-sub">{{ $addOn->breakdown() }}</small></span><span>{{ $addOn->amount === 0 ? 'Free' : $addOn->formattedAmount() }}</span></div>
            @endforeach
            <div class="is-muted"><span>Taxes (GST)</span><span>Included</span></div>
          </div>
          <div class="stay-card-total"><span>Total Amount</span><strong>{{ $booking->formattedTotal() }}</strong></div>
          <p class="stay-card-pay">{{ $booking->payment_status->label() }}</p>
        </section>

        <section class="stay-card-box is-policy">
          <h3>{{ $content->text('policy.title') }}</h3>
          @foreach ($content->paragraphs('policy.text') as $paragraph)
            <p>{{ \App\Support\WebsiteContent::html($paragraph) }}</p>
          @endforeach
        </section>
      </aside>
    </div>
  </div>
</main>
@endsection
