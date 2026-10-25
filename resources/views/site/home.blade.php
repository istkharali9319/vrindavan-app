@extends('layouts.site')

@section('title', $content->text('seo.title'))
@section('description', $content->text('seo.description'))

@section('content')
<main>
  <!-- Hero -->
  <div class="container hero-card-wrap">
    <section class="hero-card" style="background-image:url('{{ $content->image('hero.image') }}')">
      <div>
        <h1 class="hero-title">{{ $content->html('hero.title') }}</h1>
        <p class="hero-sub">{{ $content->html('hero.subtitle', ['rooms' => \Illuminate\Support\Number::spell($roomCount)]) }}</p>
        <div class="hero-actions">
          <a href="{{ route('booking.search') }}" class="btn btn-white">{{ $content->text('hero.primary_button') }} <span class="arrow">→</span></a>
          <a href="{{ route('experience') }}" class="btn btn-glass">{{ $content->text('hero.secondary_button') }}</a>
        </div>
      </div>

      <form class="booking-bar" action="{{ route('booking.search') }}" method="get" aria-label="Check availability">
        <label><span>From</span><input type="date" name="check_in" min="{{ \App\Http\Requests\Booking\StayRequest::earliestCheckIn()->toDateString() }}" max="{{ \App\Http\Requests\Booking\StayRequest::latestCheckIn()->toDateString() }}" required></label>
        <label><span>To</span><input type="date" name="check_out" min="{{ \App\Http\Requests\Booking\StayRequest::earliestCheckIn()->addDay()->toDateString() }}" required></label>
        <label><span>Adults</span><input type="number" name="adults" min="1" max="12" value="2"></label>
        <label><span>Children</span><input type="number" name="children" min="0" max="8" value="0"></label>
        <button type="submit" class="btn">Book Now</button>
      </form>
    </section>
  </div>

  <!-- The Resort -->
  <section class="section">
    <div class="container">
      <div class="resort-intro reveal">
        <h2>{{ $content->text('about.title') }}</h2>
        <p class="lede">{{ $content->html('about.lede') }}</p>
        @if ($content->has('about.text'))
          <p class="small">{{ $content->html('about.text') }}</p>
        @endif
        <div class="stats">
          <div class="stat"><strong>{{ $roomCount }}</strong><span>{{ $content->text('about.rooms_label') }}</span></div>
          <div class="stat"><strong>{{ $content->text('about.altitude_value') }}</strong><span>{{ $content->text('about.altitude_label') }}</span></div>
          <div class="stat"><strong>{{ $content->text('about.stat_value') }}</strong><span>{{ $content->text('about.stat_label') }}</span></div>
        </div>
      </div>
    </div>
  </section>

  <!-- Why Vrindavan -->
  @if ($content->items('home_features')->isNotEmpty())
    <section class="section" style="padding-top:20px">
      <div class="container">
        <div class="features">
          @foreach ($content->items('home_features') as $feature)
            <div @class(['split', 'reverse' => $loop->even, 'reveal'])>
              <div class="split-media media"><img src="{{ $feature->image() }}" alt="{{ $feature->get('title') }}" loading="lazy"></div>
              <div>
                <h3 class="feature-title">{{ $feature->get('title') }}</h3>
                <p>{{ $feature->html('text') }}</p>
              </div>
            </div>
          @endforeach
        </div>
      </div>
    </section>
  @endif

  <!-- Rooms -->
  <section class="section bg-cream-2" style="border-top:1px solid var(--line)">
    <div class="container">
      <div class="reveal" style="margin-bottom:44px">
        <h2 class="display">{{ $content->html('rooms.title') }}</h2>
        <p class="sub">{{ $content->html('rooms.subtitle') }}</p>
      </div>
      <div class="grid-3">
        @foreach ($featuredRoomTypes as $roomType)
          <a href="{{ route('accommodation') }}#{{ $roomType->slug }}" class="card room-card reveal">
            <div class="media">
              @if ($roomType->badge)
                <span class="media-badge light right">{{ $roomType->badge }}</span>
              @endif
              <img src="{{ $roomType->imageUrl() }}" alt="{{ $roomType->name }} at {{ config('resort.name') }}" loading="lazy">
            </div>
            <div class="card-body">
              <h3>{{ $roomType->name }}</h3>
              <p>{{ $roomType->description }}</p>
              <div class="card-foot"><span class="price">From <b>{{ $roomType->formattedPrice() }}</b> / night</span><span class="link-arrow bronze">Details <span class="arrow">→</span></span></div>
            </div>
          </a>
        @endforeach
      </div>
    </div>
  </section>

  <!-- Experiences -->
  @if ($content->items('home_experiences')->isNotEmpty())
    <section class="section bg-forest">
      <div class="container">
        <div class="reveal" style="margin-bottom:44px">
          <h2 class="display">{{ $content->html('experiences.title') }}</h2>
          <p class="sub" style="max-width:580px">{{ $content->html('experiences.subtitle') }}</p>
        </div>
        <div class="grid-4">
          @foreach ($content->items('home_experiences') as $tile)
            <a href="{{ route('experience') }}" class="exp-tile reveal">
              <div class="media"><img src="{{ $tile->image() }}" alt="{{ $tile->get('title') }}" loading="lazy"></div>
              <h4>{{ $tile->get('title') }}</h4>
            </a>
          @endforeach
        </div>
      </div>
    </section>
  @endif

  <!-- Nearby (the places from the Explore page) -->
  @if ($content->items('places')->isNotEmpty())
    <section class="section">
      <div class="container">
        <div class="section-head reveal">
          <div>
            <h2 class="display">{{ $content->html('nearby.title') }}</h2>
            <p class="sub">{{ $content->html('nearby.subtitle') }}</p>
          </div>
          <a href="{{ route('explore') }}" class="link-arrow">{{ $content->text('nearby.link') }} <span class="arrow">→</span></a>
        </div>
        <div class="carousel reveal" data-carousel>
          @foreach ($content->items('places') as $place)
            <a href="{{ route('explore') }}#{{ $place->anchor() }}" class="place-card">
              <div class="media"><img src="{{ $place->image($place->has('home_image') ? 'home_image' : 'image') }}" alt="{{ $place->get('title') }}" loading="lazy"></div>
              <span class="label"><small>{{ $place->get('distance') }}</small>{{ $place->get('short_title') ?: $place->get('title') }}</span>
            </a>
          @endforeach
        </div>
        <div class="carousel-nav">
          <button type="button" data-carousel-prev aria-label="Previous places">‹</button>
          <button type="button" class="primary" data-carousel-next aria-label="Next places">›</button>
        </div>
      </div>
    </section>
  @endif

  <!-- Reviews -->
  @if ($content->items('reviews')->isNotEmpty())
    <section class="section" style="border-top:1px solid var(--line)">
      <div class="container">
        <div class="reveal">
          <h2 class="display">{{ $content->html('reviews.title') }}</h2>
          <div class="rating"><span class="stars" aria-hidden="true">★★★★★</span><b>{{ $content->text('reviews.rating') }} / 5</b> Based on {{ $content->text('reviews.count') }} reviews</div>
        </div>
        <div class="reviews">
          <div class="reviews-lead reveal">
            <div class="q" aria-hidden="true">“</div>
            <h3>{{ $content->html('reviews.lead') }}</h3>
            <hr class="rule">
          </div>
          @foreach ($content->items('reviews')->take(3) as $review)
            <figure class="review reveal">
              <blockquote class="bubble" style="margin:0">{{ $review->get('text') }}</blockquote>
              <figcaption class="who"><span class="avatar">{{ $review->initials() }}</span><span><strong>{{ $review->get('name') }}</strong><span>{{ $review->get('trip') }}</span></span></figcaption>
            </figure>
          @endforeach
        </div>
      </div>
    </section>
  @endif

  <!-- CTA -->
  <x-cta-card :content="$content" />
</main>
@endsection
