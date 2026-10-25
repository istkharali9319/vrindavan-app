@php
    $fontFamilies = ['Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400'];
    if (View::hasSection('display_font')) {
        $fontFamilies[] = 'Playfair+Display:ital,wght@0,400;0,500;1,400';
    }
    $fontFamilies[] = 'Plus+Jakarta+Sans:wght@300;400;500;600;700';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', config('resort.name').' · Rhenock, East Sikkim')</title>
  <meta name="description" content="@yield('description')">
  <link rel="icon" href="{{ app(\App\Support\WebsiteContent::class)->for('site')->image('identity.favicon') }}">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family={{ implode('&family=', $fontFamilies) }}&display=swap" rel="stylesheet">
  @vite(['resources/css/site.css', 'resources/css/account.css', 'resources/js/site.js'])
</head>
<body @hasSection('body_class') class="@yield('body_class')" @endif>

@include('partials.header')

@yield('content')

@include('partials.footer')

@stack('scripts')

</body>
</html>
