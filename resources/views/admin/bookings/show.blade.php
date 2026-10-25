@extends('layouts.admin')

@use('App\Enums\Permission')

@section('title', 'Booking '.$booking->reference)
@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('admin.bookings.index') }}">Bookings</a></li>
  <li class="breadcrumb-item" aria-current="page">{{ $booking->reference }}</li>
@endsection

@section('actions')
  <a href="{{ route('admin.bookings.index') }}" class="btn btn-outline-secondary"><i class="ti ti-arrow-left"></i> All bookings</a>
  <button type="button" class="btn btn-outline-secondary" onclick="window.print()"><i class="ti ti-printer"></i> Print</button>
  <a href="{{ route('admin.bookings.invoice', $booking) }}" class="btn btn-primary"><i class="ti ti-file-download"></i> Invoice PDF</a>
@endsection

@php
    $timezone = config('booking.timezone');
    $today = now($timezone)->startOfDay();
    $stay = match (true) {
        $booking->isCancelled() => ['label' => 'Cancelled', 'tone' => 'is-cancelled', 'icon' => 'ti-circle-x'],
        $booking->check_in->greaterThan($today) => ['label' => 'Arrives in '.(int) $today->diffInDays($booking->check_in).' '.str('day')->plural((int) $today->diffInDays($booking->check_in)), 'tone' => 'is-upcoming', 'icon' => 'ti-calendar-event'],
        $booking->check_in->equalTo($today) => ['label' => 'Arriving today', 'tone' => 'is-today', 'icon' => 'ti-login'],
        $booking->check_out->greaterThan($today) => ['label' => 'In house', 'tone' => 'is-inhouse', 'icon' => 'ti-home'],
        $booking->check_out->equalTo($today) => ['label' => 'Leaving today', 'tone' => 'is-today', 'icon' => 'ti-logout'],
        default => ['label' => 'Stay completed', 'tone' => 'is-done', 'icon' => 'ti-checks'],
    };
    $checkInTime = \Carbon\Carbon::parse(config('booking.check_in_time'))->format('g:i A');
    $checkOutTime = \Carbon\Carbon::parse(config('booking.check_out_time'))->format('g:i A');
@endphp

@section('content')
<section @class(['booking-hero', 'is-cancelled' => $booking->isCancelled()]) style="background-image:url('{{ $booking->roomType->imageUrl() }}')">
  <div class="booking-hero-top">
    <div>
      <p class="booking-hero-eyebrow">Booking reference</p>
      <h2>{{ $booking->reference }}</h2>
      <p class="booking-hero-room">Room {{ $booking->room->number }} · {{ $booking->roomType->name }}</p>
    </div>
    <div class="booking-hero-badges">
      <span class="booking-chip {{ $stay['tone'] }}"><i class="ti {{ $stay['icon'] }}"></i> {{ $stay['label'] }}</span>
      <span class="booking-chip is-glass"><i class="ti ti-wallet"></i> {{ $booking->payment_status->label() }}</span>
    </div>
  </div>

  <div class="booking-timeline">
    <div class="booking-timeline-point">
      <small>Check-in</small>
      <strong>{{ $booking->check_in->format('D, d M Y') }}</strong>
      <span>from {{ $checkInTime }}</span>
    </div>
    <div class="booking-timeline-line">
      <span>{{ $booking->nights }} {{ str('night')->plural($booking->nights) }}</span>
    </div>
    <div class="booking-timeline-point is-end">
      <small>Check-out</small>
      <strong>{{ $booking->check_out->format('D, d M Y') }}</strong>
      <span>by {{ $checkOutTime }}</span>
    </div>
  </div>
</section>

<div class="row g-4">
  <div class="col-xl-8">
    <div class="card">
      <div class="card-header"><h5 class="mb-0">Stay details</h5></div>
      <div class="card-body">
        <div class="booking-facts">
          <div><i class="ti ti-key"></i><span>Room</span><strong>{{ $booking->room->number }}</strong></div>
          <div><i class="ti ti-bed"></i><span>Room type</span><strong>{{ $booking->roomType->name }}</strong></div>
          <div><i class="ti ti-users"></i><span>Guests</span><strong>{{ $booking->guestSummary() }}</strong></div>
          <div><i class="ti ti-moon"></i><span>Nights</span><strong>{{ $booking->nights }}</strong></div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Charges</h5>
        @if ($booking->payment_status === \App\Enums\PaymentStatus::Paid)
          <span class="badge bg-light-success text-success">{{ $booking->payment_status->label() }}</span>
        @else
          <span class="badge bg-light-warning text-warning">{{ $booking->payment_status->label() }}</span>
        @endif
      </div>
      <div class="card-body">
        <div class="booking-charges">
          <div class="booking-charge-row">
            <span>{{ $booking->roomType->name }} · {{ $booking->formattedNightlyPrice() }} × {{ $booking->nights }} {{ str('night')->plural($booking->nights) }}</span>
            <span>{{ \App\Support\Money::format($booking->room_amount) }}</span>
          </div>
          @foreach ($booking->addOns as $addOn)
            <div class="booking-charge-row">
              <span>+ {{ $addOn->name }} <small class="text-muted">· {{ $addOn->breakdown() }}</small></span>
              <span>{{ $addOn->amount === 0 ? 'Free' : $addOn->formattedAmount() }}</span>
            </div>
          @endforeach
          <div class="booking-charge-row is-muted">
            <span>GST</span>
            <span>Included</span>
          </div>
          <div class="booking-charge-total">
            <span>Total</span>
            <strong>{{ $booking->formattedTotal() }}</strong>
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Payment</h5>
        <span class="text-muted small">{{ $booking->payment_method?->label() ?? 'Pay at the resort' }}</span>
      </div>
      <div class="card-body">
        @if ($booking->isAwaitingPayment())
          <p class="text-warning mb-3"><i class="ti ti-clock"></i> Waiting for the guest to pay online. The room is held until {{ $booking->hold_expires_at?->timezone(config('booking.timezone'))->format('g:i A') }}, then released automatically.</p>
        @endif
        @if ($booking->paid_at)
          <p class="mb-3"><i class="ti ti-circle-check text-success"></i> Paid {{ $booking->formattedTotal() }} online on {{ $booking->paid_at->timezone(config('booking.timezone'))->format('j M Y, g:i A') }}.</p>
        @endif
        @if ($booking->payments->isEmpty())
          <p class="text-muted mb-0">No online payment attempts. The guest settles the bill at the resort.</p>
        @else
          <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
              <thead><tr><th>Razorpay order</th><th>Payment ID</th><th>Amount</th><th>Status</th><th>When</th></tr></thead>
              <tbody>
                @foreach ($booking->payments as $payment)
                  <tr>
                    <td><code>{{ $payment->order_id }}</code></td>
                    <td>@if ($payment->payment_id)<code>{{ $payment->payment_id }}</code>@if ($payment->method)<small class="text-muted d-block">{{ strtoupper($payment->method) }}</small>@endif @else<span class="text-muted">—</span>@endif</td>
                    <td>{{ $payment->formattedAmount() }}</td>
                    <td>
                      <span @class(['badge', 'bg-light-success text-success' => $payment->status === \App\Models\Payment::CAPTURED, 'bg-light-danger text-danger' => $payment->status === \App\Models\Payment::REFUND_DUE, 'bg-light-secondary text-secondary' => ! in_array($payment->status, [\App\Models\Payment::CAPTURED, \App\Models\Payment::REFUND_DUE], true)])>{{ $payment->statusLabel() }}</span>
                      @if ($payment->error)<small class="text-muted d-block">{{ $payment->error }}</small>@endif
                    </td>
                    <td class="text-muted small">{{ ($payment->paid_at ?? $payment->created_at)->timezone(config('booking.timezone'))->format('j M, g:i A') }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @endif
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h5 class="mb-0">Special requests</h5></div>
      <div class="card-body">
        @if ($booking->special_requests)
          <blockquote class="booking-request"><i class="ti ti-message-circle"></i>{{ $booking->special_requests }}</blockquote>
        @else
          <p class="text-muted mb-0">The guest didn't add any special requests.</p>
        @endif
      </div>
    </div>
  </div>

  <div class="col-xl-4">
    <div class="card booking-guest">
      <div class="card-body">
        <div class="booking-guest-head">
          <span class="admin-avatar lg">{{ \Illuminate\Support\Str::of($booking->contact_name)->explode(' ')->filter()->take(2)->map(fn ($word) => mb_substr($word, 0, 1))->implode('') }}</span>
          <div>
            <h5>{{ $booking->guestFullName() }}</h5>
            <span>
              @if ($booking->isGuestCheckout())
                <i class="ti ti-user"></i> Guest checkout (no account)
              @else
                Account:
                @can(Permission::UsersView->value)
                  <a href="{{ route('admin.users.show', $booking->user) }}">{{ $booking->user->name }}</a>
                @else
                  {{ $booking->user->name }}
                @endcan
              @endif
            </span>
          </div>
        </div>
        <ul class="booking-contact">
          <li><i class="ti ti-mail"></i><a href="mailto:{{ $booking->contact_email }}">{{ $booking->contact_email }}</a></li>
          <li><i class="ti ti-phone"></i><a href="tel:{{ $booking->contact_phone }}">{{ $booking->contact_phone }}</a></li>
          @if ($booking->gst_number)
            <li><i class="ti ti-file-text"></i><span>GST: <strong>{{ $booking->gst_number }}</strong></span></li>
          @endif
        </ul>
        {{-- Call / Email buttons hidden for now; the email and phone above are still clickable.
        <div class="booking-guest-actions">
          <a href="tel:{{ $booking->contact_phone }}" class="btn btn-primary"><i class="ti ti-phone"></i> Call</a>
          <a href="mailto:{{ $booking->contact_email }}?subject={{ rawurlencode('Your stay at '.config('resort.name').' · '.$booking->reference) }}" class="btn btn-outline-secondary"><i class="ti ti-mail"></i> Email</a>
        </div>
        --}}
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h5 class="mb-0">History</h5></div>
      <div class="card-body">
        <ol class="booking-history">
          <li>
            <span class="booking-history-dot"></span>
            <strong>Booked online{{ $booking->isGuestCheckout() ? ' as a guest' : '' }}</strong>
            <span>{{ $booking->created_at->timezone($timezone)->format('d M Y, g:i A') }}</span>
          </li>
          @if ($booking->isCancelled())
            <li class="is-cancelled">
              <span class="booking-history-dot"></span>
              <strong>Cancelled{{ $cancelledBy ? ' by '.($booking->user && $cancelledBy->is($booking->user) ? 'the guest' : $cancelledBy->name) : '' }}</strong>
              <span>{{ $booking->cancelled_at->timezone($timezone)->format('d M Y, g:i A') }}</span>
            </li>
          @endif
        </ol>
      </div>
    </div>

    @can(Permission::BookingsManage->value)
      @unless ($booking->isCancelled())
        <div class="card booking-danger">
          <div class="card-body">
            <h6><i class="ti ti-alert-triangle"></i> Cancel booking</h6>
            <p>Releases room {{ $booking->room->number }} for these nights straight away. This can't be undone.</p>
            <form method="POST" action="{{ route('admin.bookings.cancel', $booking) }}"
                  onsubmit="return confirm('Cancel booking {{ $booking->reference }}?')">
              @csrf
              <button type="submit" class="btn btn-outline-danger w-100">Cancel booking</button>
            </form>
          </div>
        </div>
      @endunless
    @endcan
  </div>
</div>
@endsection
