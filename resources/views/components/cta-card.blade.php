@props([
    'content',
    'wrapStyle' => null,
    'showCallButton' => false,
])
<section class="cta-wrap" @if ($wrapStyle) style="{{ $wrapStyle }}" @endif>
  <div class="container">
    <div class="cta-card reveal" style="background-image:url('{{ $content->image('cta.image') }}')">
      <p class="eyebrow">{{ $content->text('cta.eyebrow') }}</p>
      <h2>{{ $content->html('cta.title') }}</h2>
      @if ($content->has('cta.text'))
        <p>{{ $content->html('cta.text') }}</p>
      @endif
      <div class="hero-actions">
        <a href="{{ route('booking.search') }}" class="btn btn-bronze">{{ $content->text('cta.button') }} <span class="arrow">→</span></a>
        @if ($showCallButton && config('resort.phones'))
          <a href="tel:{{ config('resort.phones.0.dial') }}" class="btn btn-glass">Call reservations desk</a>
        @endif
      </div>
    </div>
  </div>
</section>
