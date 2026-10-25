@extends('layouts.site')

@section('title', $content->text('seo.title'))
@section('description', $content->text('seo.description'))
@section('body_class', 'page-explore')
@section('display_font', true)

@section('content')
@php
    $places = $content->items('places');
    $categories = app(\App\Support\WebsiteContent::class)->collection('places')['fields']['category']['options'];
@endphp
<main>
  <section class="hero-light">
    <div class="container">
      <span class="badge-pill">{{ $content->text('hero.badge') }}</span>
      <h1>{{ $content->html('hero.title') }}</h1>
      <p>{{ $content->html('hero.text') }}</p>
      <div class="chip-row" data-filters role="group" aria-label="Filter journeys">
        <button type="button" class="active" data-filter="all">All Journeys</button>
        @foreach ($categories as $value => $label)
          <button type="button" data-filter="{{ $value }}">{{ $label }}</button>
        @endforeach
      </div>
    </div>
  </section>

  <section class="journeys">
    <div class="container">
      @foreach ($places as $place)
        @php $isReversed = $loop->even; @endphp
        <article @class(['journey', 'reveal', 'reverse' => $isReversed]) id="{{ $place->anchor() }}" data-cat="{{ $place->get('category') }}">
          @if ($isReversed)
            @include('site.partials.place-media', ['place' => $place])
          @endif
          <div>
            <div class="journey-tags">
              @foreach (['distance', 'altitude'] as $tag)
                @if ($place->has($tag))
                  <span>{{ $place->get($tag) }}</span>
                @endif
              @endforeach
              @if ($place->has('highlight'))
                <span class="hl">{{ $place->get('highlight') }}</span>
              @endif
            </div>
            <h2>{{ $place->get('title') }}</h2>
            <p>{{ $place->html('description') }}</p>
            @if ($place->list('points'))
              <ul class="dot-list">
                @foreach ($place->list('points') as $point)
                  <li>{{ $point }}</li>
                @endforeach
              </ul>
            @endif
            <div class="journey-cta"><a href="{{ route('contact') }}" class="btn btn-forest">Plan excursion <span class="arrow">→</span></a>@if ($place->has('note'))<small>{{ $place->get('note') }}</small>@endif</div>
          </div>
          @unless ($isReversed)
            @include('site.partials.place-media', ['place' => $place])
          @endunless
        </article>
      @endforeach
    </div>
  </section>

  <section class="bg-forest guide" id="guide">
    <div class="container">
      <div class="reveal">
        <p class="eyebrow">{{ $content->text('guide.eyebrow') }}</p>
        <h2>{{ $content->html('guide.title') }}</h2>
        <p class="lead">{{ $content->html('guide.text') }}</p>
      </div>
      @if ($content->items('explore_services')->isNotEmpty())
        <div class="guide-grid">
          @foreach ($content->items('explore_services') as $service)
            <div class="guide-card reveal">
              <div class="icon"><x-site-icon :name="$service->get('icon')" /></div>
              <h4>{{ $service->get('title') }}</h4>
              <p>{{ $service->html('text') }}</p>
            </div>
          @endforeach
        </div>
      @endif
    </div>
  </section>

  <section class="etiquette" id="etiquette">
    <div class="container">
      <div class="reveal">
        <p class="eyebrow">{{ $content->text('etiquette.eyebrow') }}</p>
        <h2>{{ $content->html('etiquette.title') }}</h2>
      </div>
      @if ($content->items('explore_tips')->isNotEmpty())
        <div class="grid-3" style="gap:24px">
          @foreach ($content->items('explore_tips') as $tip)
            <div class="tip-card reveal">
              <div class="tip-tag"><x-site-icon :name="$tip->get('icon')" />{{ $tip->get('tag') }}</div>
              <h4>{{ $tip->get('title') }}</h4>
              <p>{{ $tip->html('text') }}</p>
            </div>
          @endforeach
        </div>
      @endif

      <div class="concierge reveal">
        <div class="icon"><x-site-icon name="map" /></div>
        <div>
          <h4>{{ $content->text('concierge.title') }}</h4>
          <p>{{ $content->html('concierge.text') }}</p>
        </div>
        <a href="{{ route('contact') }}" class="btn">{{ $content->text('concierge.button') }}</a>
      </div>
    </div>
  </section>

  <x-cta-card :content="$content" wrap-style="background:#fbf7f0" :show-call-button="true" />
</main>
@endsection
