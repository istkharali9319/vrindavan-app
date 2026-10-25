@extends('layouts.admin')

@use('App\Enums\Permission')
@use('App\Support\RoomOccupancy')

@section('title', 'Rooms board')
@section('subtitle', 'Every room and who is in it on the night of '.$date->format('l, j F Y').'.')
@section('breadcrumb')
  <li class="breadcrumb-item" aria-current="page">Rooms</li>
@endsection

@section('actions')
  @can(Permission::CatalogManage->value)
    <a href="{{ route('admin.room-types.index') }}" class="btn btn-outline-secondary"><i class="ti ti-bed"></i> Room types</a>
  @endcan
  @can(Permission::InventoryManage->value)
    <a href="{{ route('admin.rooms.create') }}" class="btn btn-primary"><i class="ti ti-plus"></i> Add room</a>
  @endcan
@endsection

@section('content')
<div class="row g-3 mb-4">
  @foreach ([
      [RoomOccupancy::AVAILABLE, 'ti-lock-open'],
      [RoomOccupancy::OCCUPIED, 'ti-user-check'],
      [RoomOccupancy::MAINTENANCE, 'ti-tool'],
      [RoomOccupancy::RETIRED, 'ti-archive'],
  ] as [$state, $icon])
    @php $isCurrent = ($filters['state'] ?? null) === $state; @endphp
    <div class="col-6 col-xl-3">
      <a href="{{ route('admin.rooms.index', array_filter([...$filters, 'state' => $isCurrent ? null : $state])) }}"
         @class(['state-tile', 'is-'.$state, 'is-current' => $isCurrent])>
        <span class="state-tile-icon"><i class="ti {{ $icon }}"></i></span>
        <span>
          <strong>{{ $summary[$state] }}</strong>
          <small>{{ RoomOccupancy::label($state) }}</small>
        </span>
      </a>
    </div>
  @endforeach
</div>

<div class="card">
  <div class="card-body">
    <form method="GET" action="{{ route('admin.rooms.index') }}" class="row g-2 align-items-end filters-bar" data-auto-submit>
      @if ($filters['state'] ?? null)
        <input type="hidden" name="state" value="{{ $filters['state'] }}">
      @endif
      <div class="col-lg-4 col-sm-6">
        <label class="form-label" for="date">Night of</label>
        <div class="date-stepper">
          <a href="{{ route('admin.rooms.index', [...$filters, 'date' => $date->subDay()->toDateString()]) }}" class="btn btn-outline-secondary" aria-label="Previous night"><i class="ti ti-chevron-left"></i></a>
          <input type="date" id="date" name="date" class="form-control" value="{{ $date->toDateString() }}" data-change-submit>
          <a href="{{ route('admin.rooms.index', [...$filters, 'date' => $date->addDay()->toDateString()]) }}" class="btn btn-outline-secondary" aria-label="Next night"><i class="ti ti-chevron-right"></i></a>
        </div>
      </div>
      <div class="col-lg-3 col-sm-6">
        <label class="form-label" for="room_type">Room type</label>
        <select id="room_type" name="room_type" class="form-select">
          <option value="">All types</option>
          @foreach ($roomTypes as $roomType)
            <option value="{{ $roomType->id }}" @selected((int) ($filters['room_type'] ?? 0) === $roomType->id)>{{ $roomType->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-lg-5 d-flex gap-2 justify-content-lg-end">
        <noscript><button type="submit" class="btn btn-primary">Show</button></noscript>
        @unless ($isToday)
          <a href="{{ route('admin.rooms.index', array_filter([...$filters, 'date' => null])) }}" class="btn btn-outline-secondary"><i class="ti ti-calendar-time"></i> Tonight</a>
        @endunless
        <a href="{{ route('admin.rooms.index') }}" class="btn btn-outline-secondary">Reset</a>
      </div>
    </form>
  </div>
</div>

@forelse ($roomsByType as $roomTypeId => $entries)
  @php $roomType = $roomTypes[$roomTypeId]; @endphp
  <section class="board-group">
    <header class="board-group-head">
      <img src="{{ $roomType->imageUrl() }}" alt="" loading="lazy">
      <div>
        <h5>
          {{ $roomType->name }}
          @unless ($roomType->is_active)
            <span class="badge bg-light-secondary text-secondary">Hidden from website</span>
          @endunless
        </h5>
        <span>{{ $roomType->formattedPrice() }} / night · {{ $roomType->capacitySummary() }}</span>
      </div>
      <span class="board-group-count">{{ $entries->where('state', RoomOccupancy::OCCUPIED)->count() }}/{{ $entries->count() }} occupied</span>
    </header>

    <div class="room-board">
      @foreach ($entries as $entry)
        @php $room = $entry['room']; $booking = $entry['booking']; @endphp
        @php
            $stateIcon = [
                RoomOccupancy::AVAILABLE => 'ti-circle-check',
                RoomOccupancy::OCCUPIED => 'ti-user-check',
                RoomOccupancy::MAINTENANCE => 'ti-tool',
                RoomOccupancy::RETIRED => 'ti-archive',
            ][$entry['state']];
        @endphp
        <article class="room-tile is-{{ $entry['state'] }}" id="room-{{ $room->number }}">
          <i class="ti ti-key room-tile-watermark" aria-hidden="true"></i>

          <div class="room-tile-top">
            <div>
              <small>Room</small>
              <span class="room-tile-number">{{ $room->number }}</span>
            </div>
            <span class="room-tile-icon"><i class="ti {{ $stateIcon }}"></i></span>
          </div>

          <span class="room-tile-state">{{ RoomOccupancy::label($entry['state']) }}</span>

          <div class="room-tile-body">
            @if ($booking)
              <strong>{{ $booking->contact_name }}</strong>
              <span><i class="ti ti-users"></i> {{ $booking->guestSummary() }}</span>
              <span><i class="ti ti-calendar"></i> Until {{ $booking->check_out->format('D, j M') }}</span>
            @elseif ($entry['state'] === RoomOccupancy::AVAILABLE)
              <strong>Ready for guests</strong>
              <span>Free to sell tonight</span>
            @elseif ($entry['state'] === RoomOccupancy::MAINTENANCE)
              <strong>Under maintenance</strong>
              <span>Not bookable online</span>
            @else
              <strong>Retired</strong>
              <span>No longer in use</span>
            @endif
          </div>

          @if ($entry['arriving'] || $entry['departing'])
            <div class="room-tile-flags">
              @if ($entry['arriving'])
                <span class="flag"><i class="ti ti-login"></i> Arriving</span>
              @endif
              @if ($entry['departing'])
                <span class="flag"><i class="ti ti-logout"></i> {{ \Illuminate\Support\Str::of($entry['departing']->contact_name)->before(' ') }} leaving</span>
              @endif
            </div>
          @endif

          <div class="room-tile-foot">
            @if ($booking)
              @can(Permission::BookingsView->value)
                <a href="{{ route('admin.bookings.show', $booking) }}" class="room-tile-link">{{ $booking->reference }} <i class="ti ti-arrow-right"></i></a>
              @else
                <span>{{ $booking->reference }}</span>
              @endcan
            @else
              <span>{{ $room->status->label() }}</span>
            @endif
            @can(Permission::InventoryManage->value)
              <a href="{{ route('admin.rooms.edit', $room) }}" class="room-tile-edit" aria-label="Edit room {{ $room->number }}"><i class="ti ti-pencil"></i></a>
            @endcan
          </div>
        </article>
      @endforeach
    </div>
  </section>
@empty
  <div class="card">
    <div class="card-body admin-empty">
      <i class="ti ti-layout-grid"></i>
      <p>No rooms match these filters.</p>
    </div>
  </div>
@endforelse
@endsection

@push('scripts')
  @vite(['resources/js/admin.js'])
@endpush
