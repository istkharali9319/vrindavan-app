@extends('layouts.admin')

@section('title', 'Notifications')
@section('subtitle', 'New bookings and other updates for you. Click one to open it; it is then marked as read.')
@section('breadcrumb')
  <li class="breadcrumb-item" aria-current="page">Notifications</li>
@endsection

@section('actions')
  @if ($unreadCount)
    <form method="POST" action="{{ route('admin.notifications.read-all') }}">
      @csrf
      <button type="submit" class="btn btn-outline-secondary"><i class="ti ti-checks"></i> Mark all as read</button>
    </form>
  @endif
@endsection

@section('content')
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <div class="notice-tabs">
      <a href="{{ route('admin.notifications.index') }}" @class(['is-active' => $filter === 'all'])>All</a>
      <a href="{{ route('admin.notifications.index', ['filter' => 'unread']) }}" @class(['is-active' => $filter === 'unread'])>Unread <span>{{ $unreadCount }}</span></a>
    </div>
  </div>
  <div class="card-body p-0">
    @forelse ($notifications as $notification)
      @include('partials.notification-item', ['notification' => $notification, 'readRoute' => 'admin.notifications.read'])
    @empty
      <div class="admin-empty"><i class="ti ti-bell-off"></i><p>{{ $filter === 'unread' ? 'No unread notifications.' : 'No notifications yet. New bookings will appear here.' }}</p></div>
    @endforelse
  </div>
  @if ($notifications->total())
    <div class="card-body pt-0">@include('admin.partials.pagination', ['paginator' => $notifications, 'noun' => 'notification'])</div>
  @endif
</div>
@endsection
