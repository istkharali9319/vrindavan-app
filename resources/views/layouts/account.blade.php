@extends('layouts.site')

@section('description', 'Your '.config('resort.name').' account.')

@section('content')
<main class="container account-shell">
  <nav class="account-nav" aria-label="Account">
    <div class="who">
      <strong>{{ auth()->user()->name }}</strong>
      <span>{{ auth()->user()->email }}</span>
    </div>
    <a href="{{ route('account.dashboard') }}" @class(['active' => request()->routeIs('account.dashboard')])>My stays</a>
    @php $unreadNotifications = auth()->user()->unreadNotifications()->count(); @endphp
    <a href="{{ route('account.notifications.index') }}" @class(['active' => request()->routeIs('account.notifications.*')])>
      Notifications
      @if ($unreadNotifications)
        <span class="nav-count">{{ $unreadNotifications }}</span>
      @endif
    </a>
    @unless (auth()->user()->isUnclaimedBookingAccount())
      <a href="{{ route('account.profile') }}" @class(['active' => request()->routeIs('account.profile')])>Profile &amp; password</a>
      <a href="{{ route('account.security') }}" @class(['active' => request()->routeIs('account.security')])>Security</a>
    @endunless
    <form method="POST" action="{{ route('logout') }}">
      @csrf
      <button type="submit">Sign out</button>
    </form>
  </nav>

  <div class="account-main">
    @if (session('status'))
      <div class="flash" role="status">
        {{ \App\Support\StatusMessage::for(session('status')) }}
      </div>
    @endif

    @if (auth()->user()->isUnclaimedBookingAccount())
      <div class="setup-banner">
        <div>
          <strong>Set a password to keep your account</strong>
          <span>We emailed a link to {{ auth()->user()->email }}. Until you set a password you can see your stays, but you'll need the link to sign in again after signing out.</span>
        </div>
        <form method="POST" action="{{ route('account.setup.resend') }}">
          @csrf
          <button type="submit" class="btn btn-outline btn-sm">Email the link again</button>
        </form>
      </div>
    @endif

    @yield('account')
  </div>
</main>
@endsection
