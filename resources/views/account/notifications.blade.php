@extends('layouts.account')

@section('title', 'Notifications · '.config('resort.name'))

@section('account')
  <header class="account-head notice-account-head">
    <div>
      <h1>Notifications</h1>
      <p class="lead-text">Booking confirmations and updates about your stays.</p>
    </div>
    @if ($unreadCount)
      <form method="POST" action="{{ route('account.notifications.read-all') }}">
        @csrf
        <button type="submit" class="btn btn-outline btn-sm">Mark all as read</button>
      </form>
    @endif
  </header>

  <div class="notice-tabs">
    <a href="{{ route('account.notifications.index') }}" @class(['is-active' => $filter === 'all'])>All</a>
    <a href="{{ route('account.notifications.index', ['filter' => 'unread']) }}" @class(['is-active' => $filter === 'unread'])>Unread <span>{{ $unreadCount }}</span></a>
  </div>

  <section class="panel notice-panel">
    @forelse ($notifications as $notification)
      @include('partials.notification-item', ['notification' => $notification, 'readRoute' => 'account.notifications.read'])
    @empty
      <p class="notice-empty">{{ $filter === 'unread' ? 'No unread notifications.' : 'No notifications yet. Your booking confirmations will appear here.' }}</p>
    @endforelse
  </section>

  @if ($notifications->hasPages())
    <div class="notice-pages">{{ $notifications->links() }}</div>
  @endif
@endsection
