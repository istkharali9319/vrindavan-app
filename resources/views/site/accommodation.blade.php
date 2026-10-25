@extends('layouts.site')

@section('title', $content->text('seo.title'))
@section('description', $content->text('seo.description'))

@section('content')
<main>
  <section class="hero hero-fade" style="background-image:url('{{ $content->image('hero.image') }}')">
    <div class="container">
      <p class="eyebrow">{{ $content->text('hero.eyebrow') }}</p>
      <h1 class="hero-title">{{ $content->html('hero.title') }}</h1>
      <p class="hero-sub">{{ $content->html('hero.subtitle') }}</p>
    </div>
  </section>

  <section style="background:var(--paper)">
    <div class="container rooms-intro reveal">
      <h2>{{ $content->text('intro.title') }}</h2>
      <p>{{ $content->html('intro.text') }}</p>
      <hr class="rule" style="margin-top:34px">
    </div>
  </section>

  @forelse ($roomTypes as $roomType)
    @php $isReversed = $loop->even; @endphp
    <section @class(['stay', 'alt' => $isReversed]) id="{{ $roomType->slug }}">
      <div class="container">
        <div @class(['split', 'reveal', 'reverse' => $isReversed])>
          <div class="split-media media">
            @if ($roomType->badge)
              <span @class(['media-badge', $isReversed ? 'right' : 'left'])>{{ $roomType->badge }}</span>
            @endif
            <img src="{{ $roomType->imageUrl() }}" alt="{{ $roomType->name }} at {{ config('resort.name') }}" loading="lazy">
          </div>
          <div>
            @if ($roomType->tagline)
              <p class="tag">{{ $roomType->tagline }}</p>
            @endif
            <h2>{{ $roomType->name }}</h2>
            <p class="desc">{{ $roomType->description }}</p>
            @if ($roomType->amenityList())
              <ul class="dot-list">
                @foreach ($roomType->amenityList() as $amenity)
                  <li>{{ $amenity }}</li>
                @endforeach
              </ul>
            @endif
            <div class="stay-foot">
              <div class="price-block"><small>Starting from</small><strong>{{ $roomType->formattedPrice() }}</strong><em>(With GST)</em></div>
              <a href="{{ route('booking.search', ['room_type' => $roomType->slug]) }}" class="btn btn-forest">Reserve {{ $roomType->name }} <span class="arrow">→</span></a>
            </div>
          </div>
        </div>
        @if ($roomType->galleryUrls())
          <div class="thumbs-head reveal"><h3>{{ $content->text('intro.gallery_title') }}</h3><span>{{ count($roomType->galleryUrls()) }} {{ str('perspective')->plural(count($roomType->galleryUrls())) }}</span></div>
          <div class="thumbs reveal">
            @foreach ($roomType->galleryUrls() as $photo)
              <div class="media"><img src="{{ $photo }}" alt="{{ $roomType->name }}, photo {{ $loop->iteration }}" loading="lazy"></div>
            @endforeach
          </div>
        @endif
      </div>
    </section>
  @empty
    <section class="stay">
      <div class="container rooms-intro">
        <p>Our rooms are being refreshed. Please <a href="{{ route('contact') }}">get in touch</a> and we'll help you plan your stay.</p>
      </div>
    </section>
  @endforelse

  <section class="bg-forest stat-band">
    <div class="container">
      <div class="stats reveal">
        <div class="stat"><strong>{{ $roomCount }}</strong><span>{{ $content->text('stats.rooms_label') }}</span><p>{{ $content->html('stats.rooms_text') }}</p></div>
        <div class="stat"><strong>{{ $content->text('stats.stat_2_value') }}</strong><span>{{ $content->text('stats.stat_2_label') }}</span><p>{{ $content->html('stats.stat_2_text') }}</p></div>
        <div class="stat"><strong>{{ $content->text('stats.stat_3_value') }}</strong><span>{{ $content->text('stats.stat_3_label') }}</span><p>{{ $content->html('stats.stat_3_text') }}</p></div>
      </div>
    </div>
  </section>

  <x-cta-card :content="$content" wrap-style="padding-top:80px;background:var(--paper)" />
</main>
@endsection
