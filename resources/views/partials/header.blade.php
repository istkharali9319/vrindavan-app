@php
    $site = app(\App\Support\WebsiteContent::class)->for('site');
    $navItems = [
        'home' => 'Our Vrindavan',
        'accommodation' => 'Accommodation',
        'experience' => 'Experience',
        'dining' => 'Dining',
        'gallery' => 'Gallery',
        'explore' => 'Explore',
        'contact' => 'Contact us',
    ];
@endphp
<header class="site-header">
  <div class="container nav-wrap">
    <a href="{{ route('home') }}" class="brand" aria-label="{{ config('resort.name') }} home">
      @if ($site->text('identity.brand_style') === 'logo')
        <img src="{{ $site->image('identity.logo') }}" alt="{{ config('resort.name') }}" class="brand-logo">
      @else
        <span class="brand-name">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::before(config('resort.name'), ' Resort')) }}</span>
        <small>{{ config('resort.tagline') }}</small>
      @endif
    </a>
    <nav class="main-nav" aria-label="Main">
      @foreach ($navItems as $routeName => $label)
        <a href="{{ route($routeName) }}" @class(['active' => request()->routeIs($routeName)])@if (request()->routeIs($routeName)) aria-current="page"@endif>{{ $label }}</a>
      @endforeach
    </nav>
    @auth
      @php $accountUnread = auth()->user()->isStaff() ? 0 : auth()->user()->unreadNotifications()->count(); @endphp
      <a href="{{ auth()->user()->isStaff() ? route('admin.dashboard') : ($accountUnread ? route('account.notifications.index') : route('account.dashboard')) }}" class="nav-account">My account{!! $accountUnread ? ' <span class="nav-count" aria-label="'.(int) $accountUnread.' unread notifications">'.(int) $accountUnread.'</span>' : '' !!}</a>
    @else
      <a href="{{ route('login', request()->routeIs('booking.search', 'booking.create') ? ['return' => \App\Support\ReturnTo::currentPath(request())] : []) }}" class="nav-account">Sign in</a>
    @endauth
    <a href="{{ route('booking.search') }}" class="btn btn-book">Book Now</a>
    <button class="nav-toggle" aria-label="Open menu" aria-expanded="false"><span></span><span></span><span></span></button>
  </div>
</header>
