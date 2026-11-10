@extends('layouts.site')

@section('title', $roomType->name.' · '.config('resort.name'))
@section('description', \Illuminate\Support\Str::limit($roomType->description, 155))

@section('content')
@php
    use App\Support\Money;

    // Main photo first, then the gallery: every photo opens in the photo viewer.
    $photos = array_values(array_unique([$roomType->imageUrl(), ...$roomType->galleryUrls()]));
    $childFreeAge = config('booking.child_free_below_age');
    $checkIn = \Carbon\Carbon::parse(config('booking.check_in_time'))->format('g:i A');
    $checkOut = \Carbon\Carbon::parse(config('booking.check_out_time'))->format('g:i A');
@endphp
<main>
  <section class="hero hero-fade" style="background-image:url('{{ $roomType->imageUrl() }}')">
    <div class="container">
      <p class="eyebrow">{{ $roomType->tagline ?: 'Rooms & Suites' }}</p>
      <h1 class="hero-title">{{ $roomType->name }}</h1>
      @if ($roomType->is_coming_soon)
        <p class="hero-sub"><span class="media-badge is-soon room-hero-badge">Coming soon</span></p>
      @elseif ($roomType->badge)
        <p class="hero-sub"><span class="media-badge light room-hero-badge">{{ $roomType->badge }}</span></p>
      @endif
    </div>
  </section>

  <section class="section room-detail">
    <div class="container">
      <a href="{{ route('accommodation') }}#{{ $roomType->slug }}" class="text-link room-back">← All rooms &amp; suites</a>

      <div class="room-detail-grid">
        <div class="room-detail-main">
          <div class="reveal">
            <p class="tag">{{ $roomType->rooms_count }} {{ str('room')->plural($roomType->rooms_count) }} of this type</p>
            <h2 class="display">About the {{ $roomType->name }}</h2>
            @foreach (\App\Support\WebsiteContent::paragraphs($roomType->description) as $paragraph)
              <p class="room-detail-text">{{ $paragraph }}</p>
            @endforeach
          </div>

          @if ($photos)
            <div class="reveal room-block">
              <div class="thumbs-head"><h3>Room Gallery</h3><span>{{ count($photos) }} {{ str('photo')->plural(count($photos)) }}</span></div>
              <div class="room-gallery">
                @foreach ($photos as $photo)
                  <figure class="g-item media" tabindex="0">
                    <img src="{{ $photo }}" alt="{{ $roomType->name }}, photo {{ $loop->iteration }}" loading="{{ $loop->first ? 'eager' : 'lazy' }}">
                  </figure>
                @endforeach
              </div>
            </div>
          @endif

          @if ($roomType->amenityList())
            <div class="reveal room-block">
              <h3 class="room-block-title">Amenities &amp; facilities</h3>
              <ul class="dot-list room-list">
                @foreach ($roomType->amenityList() as $amenity)
                  <li>{{ $amenity }}</li>
                @endforeach
              </ul>
            </div>
          @endif

          @if ($roomType->serviceList())
            <div class="reveal room-block">
              <h3 class="room-block-title">Room features &amp; services</h3>
              <ul class="dot-list room-list">
                @foreach ($roomType->serviceList() as $service)
                  <li>{{ $service }}</li>
                @endforeach
              </ul>
            </div>
          @endif

          @if ($addOns->isNotEmpty())
            <div class="reveal room-block">
              <h3 class="room-block-title">Optional extras</h3>
              <p class="muted">Add these while booking. They are charged on top of the room tariff.</p>
              <dl class="room-charges">
                @foreach ($addOns as $addOn)
                  <div><dt>{{ $addOn->title }}</dt><dd>{{ $addOn->priceLabel() }}</dd></div>
                @endforeach
              </dl>
            </div>
          @endif

          @if ($bookingContent->text('policy.text'))
            <div class="reveal room-block">
              <h3 class="room-block-title">{{ $bookingContent->text('policy.title') }}</h3>
              @foreach ($bookingContent->paragraphs('policy.text') as $paragraph)
                <p class="muted">{{ \App\Support\WebsiteContent::html($paragraph) }}</p>
              @endforeach
            </div>
          @endif
        </div>

        <aside class="room-facts reveal" aria-label="Room details and booking">
          <div class="price-block"><small>Per room, per night</small><strong>{{ $roomType->formattedPrice() }}</strong><em>(Including GST)</em></div>

          <dl class="room-facts-list">
            <div><dt>Category</dt><dd>{{ $roomType->name }}</dd></div>
            <div><dt>Occupancy</dt><dd>Up to {{ $roomType->perRoomSummary() }}</dd></div>
            @if ($roomType->bed_type)
              <div><dt>Bed</dt><dd>{{ $roomType->bed_type }}</dd></div>
            @endif
            @if ($roomType->room_size)
              <div><dt>Room size</dt><dd>{{ $roomType->room_size }}</dd></div>
            @endif
            <div><dt>Children</dt><dd>Under {{ $childFreeAge }} stay free</dd></div>
            <div><dt>Check-in / out</dt><dd>From {{ $checkIn }} · by {{ $checkOut }}</dd></div>
            <div><dt>Larger groups</dt><dd>Extra rooms are added automatically</dd></div>
          </dl>

          @if ($roomType->is_coming_soon)
            <span class="btn btn-forest is-disabled" aria-disabled="true">Coming soon</span>
            <p class="room-facts-note">This room isn't open for booking yet. <a href="{{ route('contact') }}" class="text-link">Contact us</a> to hear when it is.</p>
          @else
            <a href="{{ route('booking.search', ['room_type' => $roomType->slug]) }}" class="btn btn-forest">Book Now <span class="arrow">→</span></a>
            <p class="room-facts-note">Choose your dates and guests on the next step. You'll see the total before you pay.</p>
          @endif
        </aside>
      </div>
    </div>
  </section>

  @if ($otherRoomTypes->isNotEmpty())
    <section class="section bg-cream-2">
      <div class="container">
        <h2 class="display reveal" style="margin-bottom:32px">Other rooms</h2>
        <div class="grid-3">
          @foreach ($otherRoomTypes as $other)
            <a href="{{ route('accommodation.show', $other) }}" class="card room-card reveal">
              <div class="media">
                @if ($other->is_coming_soon)
                  <span class="media-badge is-soon right">Coming soon</span>
                @endif
                <img src="{{ $other->imageUrl() }}" alt="{{ $other->name }}" loading="lazy">
              </div>
              <div class="card-body">
                <h3>{{ $other->name }}</h3>
                <div class="card-foot"><span class="price">From <b>{{ $other->formattedPrice() }}</b> / night</span><span class="link-arrow bronze">View details <span class="arrow">→</span></span></div>
              </div>
            </a>
          @endforeach
        </div>
      </div>
    </section>
  @endif

  <div class="lightbox" role="dialog" aria-modal="true" aria-label="Photo viewer">
    <button class="lb-btn lb-close" aria-label="Close">✕</button>
    <button class="lb-btn lb-prev" aria-label="Previous photo">←</button>
    <img src="" alt="">
    <button class="lb-btn lb-next" aria-label="Next photo">→</button>
  </div>
</main>
@endsection
