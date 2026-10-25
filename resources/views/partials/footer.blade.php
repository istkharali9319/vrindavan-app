@php $site = app(\App\Support\WebsiteContent::class)->for('site'); @endphp
<footer class="site-footer">
  <div class="container">
    <div class="footer-cols">
      <div>
        <div class="footer-brand">
          @if ($site->text('identity.brand_style') === 'logo')
            <img src="{{ $site->image('identity.logo') }}" alt="{{ config('resort.name') }}" class="footer-logo">
          @else
            <span class="brand-name">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::before(config('resort.name'), ' Resort')) }}</span><small>{{ config('resort.tagline') }}</small>
          @endif
        </div>
        <p>{{ $site->html('footer.about') }}</p>
        <div class="footer-status"><span class="live">{{ $site->text('footer.status') }}</span><span class="sep"></span><span>Altitude {{ config('resort.altitude') }}</span></div>
      </div>
      <div>
        <h5>Explore Sanctuary</h5>
        <ul>
          <li><a href="{{ route('home') }}">The Retreat Story</a></li>
          <li><a href="{{ route('accommodation') }}">Rooms &amp; Cottages</a></li>
          <li><a href="{{ route('experience') }}">Curated Experiences</a></li>
          <li><a href="{{ route('dining') }}">Garden Dining</a></li>
          <li><a href="{{ route('explore') }}">Old Silk Route Tours</a></li>
        </ul>
      </div>
      <div>
        <h5>Traveler Guide</h5>
        <ul>
          <li><a href="{{ route('contact') }}#reach">How to Reach Rhenock</a></li>
          <li><a href="{{ route('contact') }}#reach">Pakyong &amp; Bagdogra Pickups</a></li>
          <li><a href="{{ route('explore') }}#etiquette">Seasonal Weather Guide</a></li>
          <li><a href="{{ route('explore') }}#guide">Sikkim Permit Assistance</a></li>
        </ul>
      </div>
      <div>
        <h5>Connect &amp; Reserve</h5>
        <ul class="footer-contact">
          <li><b>Resort Address:</b>{{ config('resort.address') }}</li>
          <li><b>Reservations Desk:</b>@foreach (config('resort.phones') as $phone)<a href="tel:{{ $phone['dial'] }}">{{ $phone['display'] }}</a>@if (! $loop->last) / @endif @endforeach</li>
          <li><b>Direct Email:</b><a href="mailto:{{ config('resort.email') }}">{{ config('resort.email') }}</a></li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <span>© {{ now()->year }} {{ $site->text('footer.copyright') }}</span>
      <span>{{ $site->text('footer.location') }}</span>
    </div>
  </div>
</footer>
