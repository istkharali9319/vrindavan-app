{{-- Booking progress: 1 Select room · 2 Personal details · 3 Confirmation. $current is 1, 2 or 3. --}}
@php
    $steps = [
        1 => ['label' => 'Select Room', 'icon' => '<path d="M3 18v-6a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v6M3 18h18M3 21v-3M21 21v-3M6 10V7a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v3"/><path d="M8 10V8.5h3V10M13 10V8.5h3V10"/>'],
        2 => ['label' => 'Personal Details', 'icon' => '<rect x="5" y="3" width="13" height="18" rx="2"/><circle cx="11.5" cy="9" r="2.3"/><path d="M8 15.5a3.5 3.5 0 0 1 7 0M8 18.5h7"/><path d="m16 15 4-4 1.5 1.5-4 4H16z"/>'],
        3 => ['label' => 'Confirmation', 'icon' => '<rect x="3" y="5" width="15" height="13" rx="2"/><path d="M3 9h15M6 13h4"/><path d="M18 11.5 21 10v3c0 2-1.3 3.4-3 4-1.7-.6-3-2-3-4v-3z"/><path d="m16.8 13.3.9.9 1.6-1.7"/>'],
    ];
@endphp
<section class="booking-steps-band">
  <div class="container">
    @isset($heading)
      <h1 class="booking-steps-title"><span>{{ $heading }}</span></h1>
    @endisset
    <ol class="booking-steps" aria-label="Booking progress">
      @foreach ($steps as $number => $step)
        <li @class(['is-done' => $number < $current, 'is-current' => $number === $current]) @if ($number === $current) aria-current="step" @endif>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $step['icon'] !!}</svg>
          <span>{{ $step['label'] }}</span>
        </li>
      @endforeach
    </ol>
  </div>
</section>
