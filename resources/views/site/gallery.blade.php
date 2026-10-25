@extends('layouts.site')

@section('title', $content->text('seo.title'))
@section('description', $content->text('seo.description'))

@section('content')
@php
    $photos = $content->items('gallery');
    $categories = app(\App\Support\WebsiteContent::class)->collection('gallery')['fields']['category']['options'];
    $usedCategories = array_intersect_key($categories, $photos->groupBy(fn ($photo) => $photo->get('category'))->all());
@endphp
<main>
  <section class="hero" style="background-image:url('{{ $content->image('hero.image') }}')">
    <div class="container">
      <h1 class="hero-title"><span>{{ $content->html('hero.title') }}</span></h1>
    </div>
  </section>

  <section class="section">
    <div class="container">
      @if (count($usedCategories) > 1)
        <div class="filters reveal" role="toolbar" aria-label="Filter photos">
          <button class="filter-btn active" data-filter="all">All</button>
          @foreach ($usedCategories as $value => $label)
            <button class="filter-btn" data-filter="{{ $value }}">{{ $label }}</button>
          @endforeach
        </div>
      @endif

      <div class="masonry">
        @foreach ($photos as $photo)
          <figure class="g-item" data-cat="{{ $photo->get('category') }}">
            <div class="media"><img src="{{ $photo->image() }}" alt="{{ $photo->get('title') }}" loading="lazy"></div>
            <figcaption><em>{{ $photo->get('title') }}</em><span class="tag">{{ $categories[$photo->get('category')] ?? '' }}</span></figcaption>
          </figure>
        @endforeach
      </div>
    </div>
  </section>

  <div class="lightbox" role="dialog" aria-modal="true" aria-label="Photo viewer">
    <button class="lb-btn lb-close" aria-label="Close">✕</button>
    <button class="lb-btn lb-prev" aria-label="Previous photo">←</button>
    <img src="" alt="">
    <p class="lb-cap"></p>
    <button class="lb-btn lb-next" aria-label="Next photo">→</button>
  </div>
</main>

  <x-cta-card :content="$content" wrap-style="padding-top:40px" />
@endsection
