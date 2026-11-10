@props([
    'adults' => 2,
    'children' => 0,
    'rooms' => null,
    'variant' => 'field', // 'bar' on the home hero (opens upwards), 'field' on the booking page
])
@php
    $limits = \App\Models\RoomType::pickerLimits();
    $adults = max(1, min(12, (int) $adults));
    $children = max(0, min(8, (int) $children));
    $roomsNeeded = max(
        1,
        (int) ceil($adults / $limits['adults']),
        $limits['children'] > 0 ? (int) ceil($children / $limits['children']) : 0,
    );
    $rooms = min($adults, max($roomsNeeded, (int) $rooms));
    $summary = fn (int $a, int $c, int $r): string => $a.' '.str('Adult')->plural($a)
        .($c > 0 ? ', '.$c.' '.str('Child')->plural($c) : '')
        .' | '.$r.' '.str('Room')->plural($r);
@endphp
<div {{ $attributes->class(['guest-picker', 'is-'.$variant]) }} data-guest-picker
     data-per-room-adults="{{ $limits['adults'] }}" data-per-room-children="{{ $limits['children'] }}">
  <button type="button" class="guest-picker-toggle" aria-expanded="false" aria-haspopup="dialog">
    <span class="guest-picker-label">Guests &amp; Rooms</span>
    <strong data-guest-summary>{{ $summary($adults, $children, $rooms) }}</strong>
  </button>

  <div class="guest-picker-panel" role="dialog" aria-label="Guests and rooms" hidden>
    @foreach ([
        ['rooms', 'Rooms', 'Added automatically for your group', $rooms],
        ['adults', 'Adults', 'At least one per room', $adults],
        ['children', 'Children', 'Under '.config('booking.child_free_below_age').' stay free', $children],
    ] as [$field, $label, $note, $value])
      <div class="guest-picker-row">
        <div class="guest-picker-name"><strong>{{ $label }}</strong><small>{{ $note }}</small></div>
        <div class="stepper">
          <button type="button" data-step="{{ $field }}" data-delta="-1" aria-label="Fewer {{ strtolower($label) }}">−</button>
          <output data-count="{{ $field }}" aria-live="polite">{{ $value }}</output>
          <button type="button" data-step="{{ $field }}" data-delta="1" aria-label="More {{ strtolower($label) }}">+</button>
        </div>
        {{-- Rooms are only sent when the guest added extra ones; otherwise each room type works out its own count. --}}
        <input type="hidden" name="{{ $field }}" value="{{ $value }}" @disabled($field === 'rooms' && $value <= $roomsNeeded)>
      </div>
    @endforeach

    <p class="guest-picker-note" data-guest-note>
      Each room takes up to {{ $limits['adults'] }} {{ str('adult')->plural($limits['adults']) }}@if ($limits['children'] > 0) and {{ $limits['children'] }} {{ str('child')->plural($limits['children']) }}@endif, so we add rooms for larger groups.
    </p>
    <button type="button" class="btn btn-forest btn-sm guest-picker-done">Done</button>
  </div>
</div>
