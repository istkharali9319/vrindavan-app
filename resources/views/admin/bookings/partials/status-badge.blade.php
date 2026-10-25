@if ($booking->status === \App\Enums\BookingStatus::Confirmed)
  <span class="badge bg-light-success text-success">{{ $booking->status->label() }}</span>
@elseif ($booking->status === \App\Enums\BookingStatus::PendingPayment)
  <span class="badge bg-light-warning text-warning">{{ $booking->status->label() }}</span>
@else
  <span class="badge bg-light-secondary text-secondary">{{ $booking->status->label() }}</span>
@endif
