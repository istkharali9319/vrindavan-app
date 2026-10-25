<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <title>@yield('title', 'Dashboard') · {{ config('resort.name') }} Admin</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <link rel="icon" href="{{ app(\App\Support\WebsiteContent::class)->for('site')->image('identity.favicon') }}">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap" id="main-font-link">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600&display=swap">
  <link rel="stylesheet" href="{{ asset('admin-assets/fonts/tabler-icons.min.css') }}">
  <link rel="stylesheet" href="{{ asset('admin-assets/fonts/feather.css') }}">
  <link rel="stylesheet" href="{{ asset('admin-assets/css/style.css') }}" id="main-style-link">
  <link rel="stylesheet" href="{{ asset('admin-assets/css/style-preset.css') }}">
  @vite(['resources/css/admin.css', 'resources/js/loader.js'])
  @stack('styles')
</head>
<body data-pc-preset="preset-1" data-pc-direction="ltr" data-pc-theme="light">

  @include('admin.partials.sidebar')
  @include('admin.partials.header')

  <div class="pc-container">
    <div class="pc-content">
      <div class="admin-page-head">
        <div>
          <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
            @yield('breadcrumb')
          </ul>
          <h1 class="admin-page-title">@yield('title', 'Dashboard')</h1>
          @hasSection('subtitle')
            <p class="admin-page-subtitle">@yield('subtitle')</p>
          @endif
        </div>
        @hasSection('actions')
          <div class="admin-page-actions">@yield('actions')</div>
        @endif
      </div>

      @if (session('status'))
        <div class="alert alert-success admin-alert" role="status"><i class="ti ti-circle-check"></i>{{ \App\Support\StatusMessage::for(session('status')) }}</div>
      @endif
      @if ($errors->any())
        <div class="alert alert-danger admin-alert" role="alert"><i class="ti ti-alert-triangle"></i>
          <ul class="mb-0 ps-3">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      @yield('content')
    </div>
  </div>

  <footer class="pc-footer">
    <div class="footer-wrapper container-fluid">
      <div class="row">
        <div class="col-sm my-1">
          <p class="m-0">{{ config('resort.name') }} · Rhenock, East Sikkim</p>
        </div>
        <div class="col-auto my-1">
          <ul class="list-inline footer-link mb-0">
            <li class="list-inline-item"><a href="{{ route('home') }}" target="_blank" rel="noopener">View website</a></li>
          </ul>
        </div>
      </div>
    </div>
  </footer>

  <script src="{{ asset('admin-assets/js/plugins/popper.min.js') }}"></script>
  <script src="{{ asset('admin-assets/js/plugins/simplebar.min.js') }}"></script>
  <script src="{{ asset('admin-assets/js/plugins/bootstrap.min.js') }}"></script>
  <script src="{{ asset('admin-assets/js/pcoded.js') }}"></script>
  <script src="{{ asset('admin-assets/js/plugins/feather.min.js') }}"></script>
  <script>
    layout_change('light');
    change_box_container('false');
    layout_rtl_change('false');
    preset_change('preset-1');
    font_change('Public-Sans');
  </script>
  @stack('scripts')
</body>
</html>
