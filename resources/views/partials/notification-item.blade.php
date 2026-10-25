{{-- One notification row. $notification, $readRoute (route name for "mark read and open"), $compact (dropdown) --}}
@php
    $data = $notification->data;
    $icon = match ($data['kind'] ?? '') {
        'booking.new', 'booking.confirmed' => 'calendar',
        default => 'bell',
    };
@endphp
<form method="POST" action="{{ route($readRoute, $notification->id) }}" @class(['notice', 'is-unread' => $notification->unread(), 'is-compact' => $compact ?? false])>
  @csrf
  <button type="submit" class="notice-button">
    <span class="notice-icon"><x-site-icon :name="$icon" /></span>
    <span class="notice-body">
      <strong>{{ $data['title'] ?? 'Notification' }}</strong>
      @if (! empty($data['body']))
        <span>{{ $data['body'] }}</span>
      @endif
      <small>{{ $notification->created_at->diffForHumans() }}</small>
    </span>
    @if ($notification->unread())
      <span class="notice-dot" aria-label="Unread"></span>
    @endif
  </button>
</form>
