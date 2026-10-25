@extends('layouts.site')

@section('title', $content->text('seo.title'))
@section('description', $content->text('seo.description'))

@section('content')
<main>
  <section class="hero" style="background-image:url('{{ $content->image('hero.image') }}')">
    <div class="container">
      <h1 class="hero-title"><span>{{ $content->html('hero.title') }}</span></h1>
    </div>
  </section>

  <section class="section kitchen">
    <div class="container split">
      <div class="reveal">
        <p class="eyebrow"><span class="num">01</span><span class="dash"></span>{{ $content->text('kitchen.eyebrow') }}</p>
        <h2 class="display" style="margin-bottom:24px">{{ $content->html('kitchen.title') }}</h2>
        @foreach ($content->paragraphs('kitchen.text') as $paragraph)
          <p class="muted">{{ \App\Support\WebsiteContent::html($paragraph) }}</p>
        @endforeach
        @if ($content->items('dining_features')->isNotEmpty())
          <div class="kitchen-meta">
            @foreach ($content->items('dining_features') as $feature)
              <div><x-site-icon :name="$feature->get('icon')" />{{ $feature->get('title') }}</div>
            @endforeach
          </div>
        @endif
      </div>
      <div class="split-media media reveal"><img src="{{ $content->image('kitchen.image') }}" alt="The dining room at {{ config('resort.name') }}" loading="lazy"></div>
    </div>
  </section>

  <section class="section bg-cream-2">
    <div class="container">
      @if ($content->items('dining_menu')->isNotEmpty())
        <p class="eyebrow reveal"><span class="num">02</span><span class="dash"></span>{{ $content->text('menu.eyebrow') }}</p>
        <div class="grid-3">
          @foreach ($content->items('dining_menu') as $dish)
            <article class="card menu-card reveal">
              <div class="media"><img src="{{ $dish->image() }}" alt="{{ $dish->get('title') }}" loading="lazy"></div>
              <div class="card-body">
                @if ($dish->has('tag'))
                  <p class="tag">{{ $dish->get('tag') }}</p>
                @endif
                <h3>{{ $dish->get('title') }}</h3>
                <p>{{ $dish->html('text') }}</p>
              </div>
            </article>
          @endforeach
        </div>
      @endif

      <div class="callout light reveal" style="margin-top:72px">
        <div>
          <h3>{{ $content->text('callout.title') }}</h3>
          <p>{{ $content->html('callout.text') }}</p>
        </div>
        <a href="{{ route('contact') }}" class="btn btn-forest">{{ $content->text('callout.button') }} <span class="arrow">→</span></a>
      </div>
    </div>
  </section>
</main>

  <x-cta-card :content="$content" wrap-style="padding-top:40px" />
@endsection
