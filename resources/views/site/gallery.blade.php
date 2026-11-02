@extends('layouts.site')

@section('title', $content->text('seo.title'))
@section('description', $content->text('seo.description'))

@section('content')
@php
    $photos = $content->items('gallery');
@endphp
<main>
  <section class="hero" style="background-image:url('{{ $content->image('hero.image') }}')">
    <div class="container">
      <h1 class="hero-title"><span>{{ $content->html('hero.title') }}</span></h1>
    </div>
  </section>

  <section class="section">
    <div class="container">
      <div class="masonry">
        @foreach ($photos as $photo)
          <figure class="g-item">
            <div class="media"><img src="{{ $photo->image() }}" alt="{{ $photo->get('title') }}" loading="lazy"></div>
          </figure>
        @endforeach
      </div>
    </div>
  </section>

  <div class="lightbox" role="dialog" aria-modal="true" aria-label="Photo viewer">
    <button class="lb-btn lb-close" aria-label="Close">✕</button>
    <button class="lb-btn lb-prev" aria-label="Previous photo">←</button>
    <img src="" alt="">
    <button class="lb-btn lb-next" aria-label="Next photo">→</button>
  </div>
</main>

  <x-cta-card :content="$content" wrap-style="padding-top:40px" />
@endsection
