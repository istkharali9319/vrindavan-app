@props(['title', 'intro' => null])
<main class="auth-page">
  <div class="auth-card">
    <h1>{{ $title }}</h1>
    @if ($intro)
      <p class="intro-text">{{ $intro }}</p>
    @endif

    @if (session('status'))
      <div class="flash" role="status">{{ \App\Support\StatusMessage::for(session('status')) }}</div>
    @endif

    {{ $slot }}

    @isset($foot)
      <div class="auth-foot">{{ $foot }}</div>
    @endisset
  </div>
</main>
