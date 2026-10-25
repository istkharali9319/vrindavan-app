<a href="{{ route('account.bookings.show', $booking->reference) }}" class="stay-card">
  <div>
    <strong>{{ $booking->roomType->name }}</strong>
    <span>{{ $booking->check_in->format('j M') }} – {{ $booking->check_out->format('j M Y') }} · {{ $booking->nights }} {{ str('night')->plural($booking->nights) }} · {{ $booking->guestSummary() }}</span>
    <small>{{ $booking->reference }}</small>
  </div>
  <div class="stay-card-side">
    <span class="badge-soft {{ $booking->status->tone() }}">{{ $booking->status->label() }}</span>
    <strong>{{ $booking->formattedTotal() }}</strong>
  </div>
</a>
