<div class="table-responsive">
  <table class="table table-hover mb-0">
    <thead>
      <tr>
        <th>Reference</th>
        <th>Guest</th>
        <th>Room</th>
        <th>Check-in</th>
        <th>Check-out</th>
        <th class="text-end">Total</th>
        <th>Status</th>
        <th>Booked</th>
        <th class="text-end">Actions</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($bookings as $booking)
        <tr>
          <td><a href="{{ route('admin.bookings.show', $booking) }}">{{ $booking->reference }}</a></td>
          <td>{{ $booking->contact_name }}<br><small class="text-muted">{{ $booking->contact_phone }}</small></td>
          <td>{{ $booking->room->number }}<br><small class="text-muted">{{ $booking->roomType->name }}</small></td>
          <td>{{ $booking->check_in->format('d M Y') }}</td>
          <td>{{ $booking->check_out->format('d M Y') }}</td>
          <td class="text-end">{{ $booking->formattedTotal() }}</td>
          <td>@include('admin.bookings.partials.status-badge')</td>
          <td>{{ $booking->created_at->timezone(config('booking.timezone'))->format('d M Y') }}</td>
          <td class="text-end">
            <a href="{{ route('admin.bookings.show', $booking) }}" class="btn btn-sm btn-view" aria-label="View booking {{ $booking->reference }}"><i class="ti ti-eye"></i> View</a>
          </td>
        </tr>
      @empty
        <tr><td colspan="9" class="text-center text-muted py-5">{{ $emptyMessage ?? 'No bookings match these filters.' }}</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
