{{-- Shared layout for error pages (403, 404, 419, 429), inside the website header and footer. --}}
@extends('layouts.site')

@section('title', $title.' · '.config('resort.name'))
@section('body_class', 'error-body')

@section('content')
<main class="error-page">
  <div class="container">
    <div class="error-box">
      <p class="error-number" aria-hidden="true">{{ $code }}</p>
      <h1 class="error-title">{{ $title }}</h1>
      <p class="error-text">{{ $text }}</p>

      <div class="error-actions">
        @yield('error_actions')
      </div>
    </div>
  </div>
</main>
@endsection
