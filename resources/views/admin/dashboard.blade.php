@extends('layouts.admin')

@use('App\Enums\Permission')
@use('App\Support\Money')
@use('App\Support\RoomOccupancy')

@section('title', 'Dashboard')

@section('content')
<div class="admin-hero">
  <div class="admin-hero-text">
    <p class="admin-hero-eyebrow">{{ $today->format('l, j F Y') }}</p>
    <h2>Namaste, {{ \Illuminate\Support\Str::of(auth()->user()->name)->before(' ') }}</h2>
    <p>Here's what's happening at {{ config('resort.name') }} today.</p>
    <div class="admin-hero-actions">
      @can(Permission::InventoryView->value)
        <a href="{{ route('admin.rooms.index') }}" class="btn btn-bronze"><i class="ti ti-key"></i> Rooms board</a>
      @endcan
      <a href="{{ route('home') }}" class="btn btn-glass" target="_blank" rel="noopener"><i class="ti ti-world"></i> View website</a>
    </div>
  </div>
  @can(Permission::InventoryView->value)
    <div class="admin-hero-metrics">
      <div><strong>{{ $occupancy['rate'] }}%</strong><span>Occupied tonight</span></div>
      <div><strong>{{ $occupancy['available'] }}</strong><span>Rooms free</span></div>
      @can(Permission::BookingsView->value)
        <div><strong>{{ $stats['arrivals'] }}</strong><span>Arriving today</span></div>
      @endcan
    </div>
  @endcan
</div>

@can(Permission::BookingsView->value)
  <div class="row g-3 mb-4">
    @foreach ([
        ['label' => 'Arrivals today', 'value' => number_format($stats['arrivals']), 'icon' => 'ti-plane-arrival', 'tone' => 'stat-forest', 'note' => 'Check-in from '.\Carbon\Carbon::parse(config('booking.check_in_time'))->format('g:i A')],
        ['label' => 'Departures today', 'value' => number_format($stats['departures']), 'icon' => 'ti-plane-departure', 'tone' => 'stat-amber', 'note' => 'Check-out by '.\Carbon\Carbon::parse(config('booking.check_out_time'))->format('g:i A')],
        ['label' => 'Upcoming bookings', 'value' => number_format($stats['upcoming']), 'icon' => 'ti-calendar-event', 'tone' => 'stat-teal', 'note' => 'Confirmed, arriving later'],
        ['label' => 'Booked this month', 'value' => Money::format($stats['monthRevenue']), 'icon' => 'ti-currency-rupee', 'tone' => 'stat-bronze', 'note' => 'Confirmed bookings, incl. GST'],
    ] as $stat)
      <div class="col-sm-6 col-xl-3">
        <div class="card stat-card {{ $stat['tone'] }} h-100 mb-0">
          <div class="card-body">
            <i class="ti {{ $stat['icon'] }} stat-watermark" aria-hidden="true"></i>
            <i class="ti {{ $stat['icon'] }} stat-watermark" aria-hidden="true"></i>
          <span class="stat-icon"><i class="ti {{ $stat['icon'] }}"></i></span>
            <div>
              <h6>{{ $stat['label'] }}</h6>
              <h3>{{ $stat['value'] }}</h3>
              <small class="stat-note">{{ $stat['note'] }}</small>
            </div>
          </div>
        </div>
      </div>
    @endforeach
  </div>
@endcan

@can(Permission::InventoryView->value)
  <div class="row g-3 mb-4">
    <div class="col-xl-8">
      <div class="card h-100 mb-0">
        <div class="card-header d-flex justify-content-between align-items-center">
          <div>
            <h5 class="mb-0">Tonight's rooms</h5>
            <small class="text-muted">{{ $occupancy['occupied'] }} of {{ $occupancy['sellable'] }} rooms occupied</small>
          </div>
          <a href="{{ route('admin.rooms.index') }}" class="btn btn-sm btn-outline-secondary">Open board <i class="ti ti-arrow-right"></i></a>
        </div>
        <div class="card-body">
          @if ($rooms->isEmpty())
            <div class="admin-empty">
              <i class="ti ti-layout-grid"></i>
              <p>No rooms have been set up yet.</p>
            </div>
          @else
            <div class="room-mini-grid">
              @foreach ($rooms as $entry)
                <a href="{{ route('admin.rooms.index', ['room_type' => $entry['room']->room_type_id]) }}#room-{{ $entry['room']->number }}"
                   class="room-mini is-{{ $entry['state'] }}"
                   title="{{ $entry['room']->roomType->name }} · {{ RoomOccupancy::label($entry['state']) }}{{ $entry['booking'] ? ' · '.$entry['booking']->contact_name : '' }}">
                  <strong>{{ $entry['room']->number }}</strong>
                  <span>{{ $entry['booking'] ? \Illuminate\Support\Str::of($entry['booking']->contact_name)->before(' ') : RoomOccupancy::label($entry['state']) }}</span>
                </a>
              @endforeach
            </div>
            <div class="room-legend">
              @foreach ([RoomOccupancy::AVAILABLE, RoomOccupancy::OCCUPIED, RoomOccupancy::MAINTENANCE, RoomOccupancy::RETIRED] as $state)
                <span><i class="legend-dot is-{{ $state }}"></i>{{ RoomOccupancy::label($state) }} · {{ $occupancy[$state] }}</span>
              @endforeach
            </div>
          @endif
        </div>
      </div>
    </div>
    <div class="col-xl-4">
      <div class="card h-100 mb-0">
        <div class="card-header"><h5 class="mb-0">Occupancy tonight</h5></div>
        <div class="card-body d-flex flex-column align-items-center justify-content-center gap-3">
          <div class="occupancy-ring" style="--rate: {{ $occupancy['rate'] }}" role="img" aria-label="{{ $occupancy['rate'] }} percent of rooms occupied tonight">
            <div><strong>{{ $occupancy['rate'] }}%</strong><span>occupied</span></div>
          </div>
          <ul class="occupancy-list">
            <li class="is-available"><span><i class="legend-dot is-available"></i>Free to sell</span><strong>{{ $occupancy['available'] }}</strong></li>
            <li class="is-occupied"><span><i class="legend-dot is-occupied"></i>Guests in house</span><strong>{{ $occupancy['occupied'] }}</strong></li>
            <li class="is-maintenance"><span><i class="legend-dot is-maintenance"></i>Under maintenance</span><strong>{{ $occupancy['maintenance'] }}</strong></li>
          </ul>
        </div>
      </div>
    </div>
  </div>
@endcan

<div class="row g-3 mb-4">
  @can(Permission::InventoryView->value)
    <div class="col-xl-6">
      <div class="card h-100 mb-0">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">Room types</h5>
          @can(Permission::CatalogManage->value)
            <a href="{{ route('admin.room-types.index') }}" class="btn btn-sm btn-outline-secondary">Manage</a>
          @endcan
        </div>
        <div class="card-body p-0">
          @forelse ($roomTypes as $roomType)
            <div class="type-row">
              <img src="{{ $roomType->imageUrl() }}" alt="" loading="lazy">
              <div class="type-row-body">
                <strong>{{ $roomType->name }}</strong>
                <span>{{ $roomType->rooms_count }} {{ str('room')->plural($roomType->rooms_count) }} · {{ $roomType->capacitySummary() }}</span>
              </div>
              <div class="type-row-side">
                <strong>{{ $roomType->formattedPrice() }}</strong>
                @if ($roomType->is_active)
                  <span class="badge bg-light-success text-success">On website</span>
                @else
                  <span class="badge bg-light-secondary text-secondary">Hidden</span>
                @endif
              </div>
            </div>
          @empty
            <p class="text-muted p-4 mb-0">No room types yet.</p>
          @endforelse
        </div>
      </div>
    </div>
  @endcan

  @can(Permission::BookingsView->value)
    <div class="col-xl-6">
      <div class="card h-100 mb-0">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">Next arrivals</h5>
          <a href="{{ route('admin.bookings.index', ['when' => 'upcoming']) }}" class="btn btn-sm btn-outline-secondary">All upcoming</a>
        </div>
        <div class="card-body p-0">
          @forelse ($nextArrivals as $booking)
            <a href="{{ route('admin.bookings.show', $booking) }}" class="arrival-row">
              <span class="arrival-date">
                <strong>{{ $booking->check_in->format('j') }}</strong>
                <small>{{ $booking->check_in->format('M') }}</small>
              </span>
              <span class="arrival-body">
                <strong>{{ $booking->contact_name }}</strong>
                <span>Room {{ $booking->room->number }} · {{ $booking->roomType->name }} · {{ $booking->nights }} {{ str('night')->plural($booking->nights) }}</span>
              </span>
              <span class="arrival-side">
                {{ $booking->formattedTotal() }}
                @if ($booking->check_in->isSameDay($today))
                  <span class="badge bg-light-warning text-warning">Today</span>
                @endif
              </span>
            </a>
          @empty
            <div class="admin-empty">
              <i class="ti ti-calendar-off"></i>
              <p>No upcoming arrivals.</p>
            </div>
          @endforelse
        </div>
      </div>
    </div>
  @endcan
</div>

<h6 class="admin-section-label">Guests &amp; team</h6>
<div class="row g-3 mb-4">
  @foreach ([
      ['label' => 'Customers', 'value' => $stats['customers'], 'icon' => 'ti-users', 'tone' => 'stat-indigo'],
      ['label' => 'New customers (30 days)', 'value' => $stats['newCustomers'], 'icon' => 'ti-user-plus', 'tone' => 'stat-rose'],
      ['label' => 'Awaiting email verification', 'value' => $stats['unverifiedCustomers'], 'icon' => 'ti-mail', 'tone' => 'stat-amber'],
      ['label' => 'Staff accounts', 'value' => $stats['staff'], 'icon' => 'ti-shield-check', 'tone' => 'stat-teal'],
  ] as $stat)
    <div class="col-sm-6 col-xl-3">
      <div class="card stat-card stat-card-compact {{ $stat['tone'] }} h-100 mb-0">
        <div class="card-body">
          <i class="ti {{ $stat['icon'] }} stat-watermark" aria-hidden="true"></i>
          <span class="stat-icon"><i class="ti {{ $stat['icon'] }}"></i></span>
          <div>
            <h6>{{ $stat['label'] }}</h6>
            <h3>{{ number_format($stat['value']) }}</h3>
          </div>
        </div>
      </div>
    </div>
  @endforeach
</div>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Latest sign-ups</h5>
    @can(Permission::UsersView->value)
      <a href="{{ route('admin.users.index', ['type' => 'customer']) }}" class="btn btn-sm btn-outline-secondary">All customers</a>
    @endcan
  </div>
  <div class="card-body p-0">
    @if ($recentCustomers->isEmpty())
      <p class="text-muted p-4 mb-0">No customers have signed up yet.</p>
    @else
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead><tr><th>Name</th><th>Email</th><th>Mobile</th><th>Joined</th></tr></thead>
          <tbody>
            @foreach ($recentCustomers as $customer)
              <tr>
                <td>
                  <span class="d-inline-flex align-items-center">
                    <span class="admin-avatar sm">{{ $customer->initials() }}</span>
                    @can(Permission::UsersView->value)
                      <a href="{{ route('admin.users.show', $customer) }}">{{ $customer->name }}</a>
                    @else
                      {{ $customer->name }}
                    @endcan
                  </span>
                </td>
                <td>{{ $customer->email }}</td>
                <td>{{ $customer->phone ?? '—' }}</td>
                <td>{{ $customer->created_at->timezone(config('booking.timezone'))->format('d M Y, H:i') }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>
</div>
@endsection
