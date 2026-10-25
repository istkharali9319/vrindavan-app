<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <title>@yield('title', 'Staff sign in') · {{ config('resort.name') }} Admin</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  @php $site = app(\App\Support\WebsiteContent::class)->for('site'); @endphp
  <link rel="icon" href="{{ $site->image('identity.favicon') }}">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap" id="main-font-link">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600&display=swap">
  <link rel="stylesheet" href="{{ asset('admin-assets/fonts/tabler-icons.min.css') }}">
  <link rel="stylesheet" href="{{ asset('admin-assets/css/style.css') }}" id="main-style-link">
  <link rel="stylesheet" href="{{ asset('admin-assets/css/style-preset.css') }}">
  @vite(['resources/css/admin.css', 'resources/js/loader.js'])
</head>
<body data-pc-preset="preset-1" data-pc-direction="ltr" data-pc-theme="light" class="staff-auth-body">
  <main class="staff-auth">
    <aside class="staff-auth-visual" style="background-image:url('{{ asset('images/hero-home.jpg') }}')">
      <a href="{{ route('admin.login') }}" class="staff-auth-logo">
        <img src="{{ $site->image('identity.logo') }}" alt="{{ config('resort.name') }}">
        <small>Staff portal</small>
      </a>

      <div class="staff-auth-quote">
        <p class="staff-auth-eyebrow">{{ config('resort.tagline') }}</p>
        <h2>Every stay begins with a warm welcome.</h2>
        <p>Bookings, rooms, guests and the website — everything the team needs to look after {{ config('resort.name') }}, in one place.</p>
      </div>

      <ul class="staff-auth-points">
        <li><i class="ti ti-key"></i> Rooms board</li>
        <li><i class="ti ti-calendar-event"></i> Bookings</li>
        <li><i class="ti ti-world"></i> Website content</li>
      </ul>
    </aside>

    <section class="staff-auth-panel">
      <div class="staff-auth-form">
        <a href="{{ route('admin.login') }}" class="staff-auth-logo staff-auth-logo-mobile">
          <img src="{{ $site->image('identity.logo') }}" alt="{{ config('resort.name') }}">
          <small>Staff portal</small>
        </a>

        <span class="staff-auth-badge"><i class="ti ti-shield-lock"></i> Staff only</span>
        <h1 class="staff-auth-title">@yield('heading')</h1>
        @hasSection('intro')
          <p class="staff-auth-intro">@yield('intro')</p>
        @endif

        @if (session('status'))
          <div class="alert alert-success admin-alert" role="status"><i class="ti ti-circle-check"></i>{{ \App\Support\StatusMessage::for(session('status')) }}</div>
        @endif

        @yield('content')

        <footer class="staff-auth-footer">
          <span>{{ config('resort.name') }} · Rhenock, East Sikkim</span>
          <span>
            <a href="{{ route('home') }}">Website</a>
            <a href="{{ route('login') }}">Guest sign in</a>
          </span>
        </footer>
      </div>
    </section>
  </main>
  <script src="{{ asset('admin-assets/js/plugins/bootstrap.min.js') }}"></script>
  @stack('scripts')
</body>
</html>
