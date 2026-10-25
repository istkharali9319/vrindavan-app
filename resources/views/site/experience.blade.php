@extends('layouts.site')

@section('title', $content->text('seo.title'))
@section('description', $content->text('seo.description'))

@section('content')
@php
    $features = $experiences->where('layout', '!=', 'card')->values();
    $cards = $experiences->where('layout', 'card')->values();
    $categories = \App\Models\Experience::CATEGORIES;
@endphp
<main>
  <section class="hero hero-dark" style="background-image:url('{{ $content->image('hero.image') }}')">
    <div class="container">
      <p class="eyebrow">{{ $content->text('hero.eyebrow') }}</p>
      <h1 class="hero-title">{{ $content->html('hero.title') }}</h1>
      <p class="hero-sub">{{ $content->html('hero.subtitle') }}</p>
      <div class="pill-tabs" data-filters role="group" aria-label="Filter experiences">
        <button type="button" class="active" data-filter="all">All Experiences</button>
        @foreach ($categories as $value => $label)
          <button type="button" data-filter="{{ $value }}">{{ $label }}</button>
        @endforeach
      </div>
    </div>
  </section>

  <section class="section" style="padding-bottom:0">
    <div class="container">
      <div class="section-title reveal" style="margin-bottom:20px">
        <p class="eyebrow center">{{ $content->text('intro.eyebrow') }}</p>
        <h2 class="display">{{ $content->html('intro.title') }}</h2>
        <hr class="rule">
        <p>{{ $content->html('intro.text') }}</p>
      </div>
    </div>
  </section>

  <div class="container" id="experiences">
    @foreach ($features as $experience)
      @php $isReversed = $loop->even; @endphp
      <article class="exp-row" id="{{ $experience->slug }}" data-cat="{{ implode(' ', $experience->categories ?? []) }}">
        <div @class(['split', 'reveal', 'reverse' => $isReversed])>
          <div class="split-media media">
            @if ($experience->badge)
              <span @class(['media-badge', $isReversed ? 'right' : 'left'])>{{ $experience->badge }}</span>
            @endif
            <img src="{{ $experience->imageUrl() }}" alt="{{ $experience->title }}" loading="lazy">
          </div>
          <div>
            @if ($experience->kicker)
              <span class="kicker">{{ $experience->kicker }}</span>
            @endif
            <h2>{{ $experience->title }}</h2>
            <p class="desc">{{ \App\Support\WebsiteContent::html((string) $experience->description) }}</p>
            @if ($experience->highlightList())
              <div class="highlights">
                <h5>Experience highlights:</h5>
                <ul>
                  @foreach ($experience->highlightList() as $highlight)
                    <li>{{ $highlight }}</li>
                  @endforeach
                </ul>
              </div>
            @endif
            <div class="exp-foot"><span>{{ $experience->schedule }}@if ($experience->priceLabel())<b class="exp-price">{{ $experience->priceLabel() }}</b>@endif</span><a href="{{ route('contact') }}" class="link-arrow">{{ $experience->link_label ?: 'Inquire & book' }} <span class="arrow">→</span></a></div>
          </div>
        </div>
      </article>
    @endforeach
  </div>

  @if ($cards->isNotEmpty())
    <section class="exp-duo" data-filter-group>
      <div class="container grid-2">
        @foreach ($cards as $experience)
          <article class="duo-card reveal" id="{{ $experience->slug }}" data-cat="{{ implode(' ', $experience->categories ?? []) }}">
            <div class="media">
              @if ($experience->badge)
                <span class="media-badge left">{{ $experience->badge }}</span>
              @endif
              <img src="{{ $experience->imageUrl() }}" alt="{{ $experience->title }}" loading="lazy">
            </div>
            <h3>{{ $experience->title }}</h3>
            <p>{{ \App\Support\WebsiteContent::html((string) $experience->description) }}</p>
            @if ($experience->highlightList())
              <ul>
                @foreach ($experience->highlightList() as $highlight)
                  <li>{{ $highlight }}</li>
                @endforeach
              </ul>
            @endif
            <div class="exp-foot"><span>{{ $experience->schedule }}@if ($experience->priceLabel())<b class="exp-price">{{ $experience->priceLabel() }}</b>@endif</span><a href="{{ route('contact') }}" class="link-arrow">{{ $experience->link_label ?: 'Details' }} <span class="arrow">→</span></a></div>
          </article>
        @endforeach
      </div>
    </section>
  @endif

  <section class="bg-forest ethos">
    <div class="container">
      <div class="reveal">
        <p class="eyebrow center">{{ $content->text('ethos.eyebrow') }}</p>
        <h2>{{ $content->html('ethos.title') }}</h2>
        <hr class="rule" style="margin:28px auto">
        @if ($content->has('ethos.quote'))
          <blockquote>"{{ $content->html('ethos.quote') }}"</blockquote>
        @endif
      </div>
      @if ($content->items('experience_values')->isNotEmpty())
        <div class="ethos-grid">
          @foreach ($content->items('experience_values') as $value)
            <div class="reveal">
              <div class="icon"><x-site-icon :name="$value->get('icon')" /></div>
              <h4>{{ $value->get('title') }}</h4>
              <p>{{ $value->html('text') }}</p>
            </div>
          @endforeach
        </div>
      @endif
    </div>
  </section>

  @if ($content->items('experience_steps')->isNotEmpty())
    <section class="section">
      <div class="container">
        <div class="section-title reveal">
          <p class="eyebrow center">{{ $content->text('steps.eyebrow') }}</p>
          <h2 class="display">{{ $content->html('steps.title') }}</h2>
          <hr class="rule">
        </div>
        <div class="steps">
          @foreach ($content->items('experience_steps') as $step)
            <div class="step reveal"><div class="n">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</div><h4>{{ $step->get('title') }}</h4><p>{{ $step->html('text') }}</p></div>
          @endforeach
        </div>
      </div>
    </section>
  @endif

  <x-cta-card :content="$content" />
</main>
@endsection
