@extends('layouts.site')

@section('title', $content->text('seo.title'))
@section('description', $content->text('seo.description'))

@section('content')
<main>
  <section class="map-hero">
    <iframe title="Map of {{ config('resort.name') }}" src="https://maps.google.com/maps?q={{ rawurlencode($content->text('hero.map_query')) }}&t=&z=10&ie=UTF8&iwloc=&output=embed" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
    <h1 class="hero-title"><span>{{ $content->html('hero.title') }}</span></h1>
  </section>

  <section class="section">
    <div class="container contact-grid">
      <form id="contactForm" class="reveal" method="POST" action="{{ route('contact.store') }}" novalidate>
        @csrf
        @if (session('status') === 'enquiry-sent')
          <p class="form-note show" role="status">{{ $content->text('form.success') }}</p>
        @endif
        @if ($errors->any())
          <p class="form-note show is-error" role="alert">Please check the highlighted fields and try again.</p>
        @endif
        <div class="form-row">
          <div @class(['field', 'has-error' => $errors->has('name')])>
            <label for="name">Name</label>
            <input id="name" name="name" type="text" value="{{ old('name', auth()->user()?->name) }}" placeholder="Your full name" maxlength="120" required>
            @error('name')<small class="field-error">{{ $message }}</small>@enderror
          </div>
          <div @class(['field', 'has-error' => $errors->has('email')])>
            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email', auth()->user()?->email) }}" placeholder="you@example.com" maxlength="191" required>
            @error('email')<small class="field-error">{{ $message }}</small>@enderror
          </div>
        </div>
        <div @class(['field', 'has-error' => $errors->has('phone')])>
          <label for="phone">Phone (optional)</label>
          <input id="phone" name="phone" type="tel" value="{{ old('phone', auth()->user()?->phone) }}" placeholder="+91" maxlength="30">
          @error('phone')<small class="field-error">{{ $message }}</small>@enderror
        </div>
        <div @class(['field', 'has-error' => $errors->has('message')])>
          <label for="message">Message</label>
          <textarea id="message" name="message" placeholder="{{ $content->text('form.message_placeholder') }}" maxlength="3000" required>{{ old('message') }}</textarea>
          @error('message')<small class="field-error">{{ $message }}</small>@enderror
        </div>
        <div class="field-trap" aria-hidden="true">
          <label for="website">Leave this field empty</label>
          <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
        </div>
        <button type="submit" class="btn btn-forest">{{ $content->text('form.button') }} <span class="arrow">→</span></button>
      </form>

      <aside class="reveal" id="reach">
        <div class="info-card">
          <div class="info-line"><x-site-icon name="pin" /><span>{{ config('resort.name') }}, {{ config('resort.address') }}</span></div>
          @if (config('resort.phones'))
            <div class="info-line"><x-site-icon name="phone" />@foreach (config('resort.phones') as $phone)<a href="tel:{{ $phone['dial'] }}">{{ $phone['display'] }}</a>@if (! $loop->last) / @endif @endforeach</div>
          @endif
          <div class="info-line"><x-site-icon name="mail" /><a href="mailto:{{ config('resort.email') }}">{{ config('resort.email') }}</a></div>
          @php $whatsapp = preg_replace('/\D/', '', (string) app(\App\Support\WebsiteContent::class)->value('site', 'contact.whatsapp')); @endphp
          @if ($whatsapp)
            <a href="https://wa.me/{{ $whatsapp }}" class="btn btn-forest btn-sm whatsapp-btn" target="_blank" rel="noopener">Chat on WhatsApp <span class="arrow">→</span></a>
          @endif
          <div class="checkin">{{ $content->text('form.checkin') }}</div>
        </div>

        @foreach ($content->items('travel_options') as $option)
          <div class="travel-card">
            <x-site-icon :name="$option->get('icon')" />
            <div><h4>{{ $option->get('title') }}</h4><p>{{ $option->html('text') }}</p></div>
          </div>
        @endforeach
      </aside>
    </div>
  </section>
</main>
@endsection
