@extends('layouts.admin')

@use('App\Enums\Permission')
@use('App\Enums\Role')
@use('App\Support\Money')

@section('title', $user->name)
@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Users</a></li>
  <li class="breadcrumb-item" aria-current="page">{{ $user->name }}</li>
@endsection

@section('actions')
  <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary"><i class="ti ti-arrow-left"></i> All users</a>
  @if ($user->isStaff())
    @can(Permission::RolesManage->value)
      <a href="{{ route('admin.staff.edit', $user) }}" class="btn btn-primary"><i class="ti ti-shield-lock"></i> Edit staff role</a>
    @endcan
  @endif
@endsection

@php
    $timezone = config('booking.timezone');
    $roles = $user->roles->map(fn ($role) => Role::from($role->name));
@endphp

@section('content')
<section @class(['user-hero', 'is-staff' => $user->isStaff(), 'is-inactive' => $user->trashed() || ! $user->isActive()])>
  <span class="user-hero-avatar">{{ $user->initials() }}</span>
  <div class="user-hero-text">
    <h2>{{ $user->name }}</h2>
    <p>{{ $user->email }}@if ($user->phone) · {{ $user->phone }}@endif</p>
    <div class="user-hero-chips">
      @include('admin.users.partials.status-badge', ['user' => $user])
      @foreach ($roles as $role)
        <span class="user-hero-chip"><i class="ti {{ $role->isStaff() ? 'ti-shield-check' : 'ti-user' }}"></i> {{ $role->label() }}</span>
      @endforeach
      @if ($user->hasVerifiedEmail())
        <span class="user-hero-chip"><i class="ti ti-mail"></i> Email verified</span>
      @else
        <span class="user-hero-chip is-warn"><i class="ti ti-mail"></i> Email not verified</span>
      @endif
    </div>
  </div>
  @unless ($user->trashed())
    <div class="user-hero-actions">
      @if ($user->phone)
        <a href="tel:{{ $user->phone }}" class="btn btn-glass"><i class="ti ti-phone"></i> Call</a>
      @endif
      <a href="mailto:{{ $user->email }}" class="btn btn-light-cream"><i class="ti ti-mail"></i> Email</a>
    </div>
  @endunless
</section>

<div class="row g-3 mb-4">
  @foreach ([
      ['label' => 'Bookings', 'value' => number_format($stats['bookings']), 'icon' => 'ti-calendar-event', 'tone' => 'is-indigo'],
      ['label' => 'Upcoming stays', 'value' => number_format($stats['upcoming']), 'icon' => 'ti-plane-arrival', 'tone' => 'is-teal'],
      ['label' => 'Nights stayed or booked', 'value' => number_format($stats['nights']), 'icon' => 'ti-moon', 'tone' => 'is-forest'],
      ['label' => 'Total booked', 'value' => Money::format($stats['spent']), 'icon' => 'ti-currency-rupee', 'tone' => 'is-bronze'],
  ] as $stat)
    <div class="col-6 col-xl-3">
      <div class="website-summary-item {{ $stat['tone'] }}">
        <i class="ti {{ $stat['icon'] }}"></i>
        <div><strong>{{ $stat['value'] }}</strong><span>{{ $stat['label'] }}</span></div>
      </div>
    </div>
  @endforeach
</div>

<div class="row g-4">
  <div class="col-xl-8">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Bookings</h5>
        @if ($bookings->count() >= 20)
          <small class="text-muted">Latest 20</small>
        @endif
      </div>
      <div class="card-body p-0">
        @can(Permission::BookingsView->value)
          @forelse ($bookings as $booking)
            <a href="{{ route('admin.bookings.show', $booking) }}" @class(['user-booking', 'is-cancelled' => $booking->isCancelled()])>
              <span class="arrival-date">
                <strong>{{ $booking->check_in->format('j') }}</strong>
                <small>{{ $booking->check_in->format('M') }}</small>
              </span>
              <span class="user-booking-body">
                <strong>{{ $booking->roomType->name }} · Room {{ $booking->room->number }}</strong>
                <span>{{ $booking->check_in->format('D, j M Y') }} → {{ $booking->check_out->format('D, j M Y') }} · {{ $booking->nights }} {{ str('night')->plural($booking->nights) }} · {{ $booking->guestSummary() }}</span>
                <small>{{ $booking->reference }}</small>
              </span>
              <span class="user-booking-side">
                <strong>{{ $booking->formattedTotal() }}</strong>
                @if ($booking->isCancelled())
                  <span class="status-pill is-suspended">Cancelled</span>
                @elseif ($booking->isUpcoming())
                  <span class="status-pill is-active">Upcoming</span>
                @else
                  <span class="status-pill is-deleted">Completed</span>
                @endif
              </span>
            </a>
          @empty
            <div class="admin-empty">
              <i class="ti ti-calendar-off"></i>
              <p>No bookings yet.</p>
            </div>
          @endforelse
        @else
          <p class="text-muted p-4 mb-0">You don't have access to bookings.</p>
        @endcan
      </div>
    </div>
  </div>

  <div class="col-xl-4">
    <div class="card">
      <div class="card-header"><h5 class="mb-0">Account</h5></div>
      <div class="card-body">
        <ul class="profile-meta">
          <li><i class="ti ti-mail"></i><span>Email</span><strong class="text-break">{{ $user->email }}</strong></li>
          <li><i class="ti ti-phone"></i><span>Mobile</span><strong>{{ $user->phone ?? 'Not added' }}</strong></li>
          <li><i class="ti ti-lock"></i><span>Two-factor</span><strong>{{ $user->hasConfirmedTwoFactor() ? 'On' : 'Off' }}</strong></li>
          <li><i class="ti ti-calendar"></i><span>Joined</span><strong>{{ $user->created_at->timezone($timezone)->format('d M Y, g:i A') }}</strong></li>
          <li><i class="ti ti-login"></i><span>Last sign-in</span><strong>{{ $user->last_login_at ? $user->last_login_at->timezone($timezone)->format('d M Y, g:i A').' · '.$user->last_login_at->diffForHumans() : 'Never' }}</strong></li>
        </ul>
      </div>
    </div>

    @can(Permission::UsersManage->value)
      @unless ($user->trashed() || $user->is(auth()->user()))
        @if ($user->isActive())
          <div class="card booking-danger">
            <div class="card-body">
              <h6><i class="ti ti-user-off"></i> Suspend account</h6>
              <p>Signs {{ \Illuminate\Support\Str::of($user->name)->before(' ') }} out immediately and blocks future sign-ins. You can reactivate it later.</p>
              <form method="POST" action="{{ route('admin.users.suspend', $user) }}">
                @csrf
                <button type="submit" class="btn btn-outline-danger w-100">Suspend account</button>
              </form>
            </div>
          </div>
        @else
          <div class="card user-reactivate">
            <div class="card-body">
              <h6><i class="ti ti-user-check"></i> Account suspended</h6>
              <p>This account can't sign in. Reactivate it to restore access.</p>
              <form method="POST" action="{{ route('admin.users.reactivate', $user) }}">
                @csrf
                <button type="submit" class="btn btn-success w-100">Reactivate account</button>
              </form>
            </div>
          </div>
        @endif
      @endunless
    @endcan
  </div>
</div>
@endsection
