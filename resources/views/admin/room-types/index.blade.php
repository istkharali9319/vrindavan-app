@extends('layouts.admin')

@use('App\Enums\Permission')
@use('App\Enums\RoomStatus')

@section('title', 'Room types')
@section('subtitle', 'What guests book. Active types appear on the website\'s Rooms page in this order.')
@section('breadcrumb')
  <li class="breadcrumb-item" aria-current="page">Room types</li>
@endsection

@section('actions')
  <a href="{{ route('accommodation') }}" class="btn btn-outline-secondary" target="_blank" rel="noopener"><i class="ti ti-world"></i> Preview on website</a>
  <a href="{{ route('admin.room-types.create') }}" class="btn btn-primary"><i class="ti ti-plus"></i> Add room type</a>
@endsection

@section('content')
@if ($roomTypes->isEmpty())
  <div class="card">
    <div class="card-body admin-empty">
      <i class="ti ti-bed"></i>
      <p>No room types yet. Add one to start selling rooms.</p>
      <a href="{{ route('admin.room-types.create') }}" class="btn btn-primary">Add room type</a>
    </div>
  </div>
@else
  <div class="row g-4">
    @foreach ($roomTypes as $roomType)
      @php $inService = $roomType->rooms->where('status', RoomStatus::Active)->count(); @endphp
      <div class="col-md-6 col-xxl-4">
        <article @class(['type-card', 'is-hidden' => ! $roomType->is_active])>
          <div class="type-card-media">
            <img src="{{ $roomType->imageUrl() }}" alt="{{ $roomType->name }}" loading="lazy">
            <span class="type-card-order">#{{ $roomType->sort_order }}</span>
            @if ($roomType->is_active)
              <span class="type-card-status is-live"><i class="ti ti-world"></i> On website</span>
            @else
              <span class="type-card-status"><i class="ti ti-eye-off"></i> Hidden</span>
            @endif
            <div class="type-card-price">
              <strong>{{ $roomType->formattedPrice() }}</strong>
              <span>per night, incl. GST</span>
            </div>
          </div>
          <div class="type-card-body">
            @if ($roomType->tagline)
              <p class="type-card-tagline">{{ $roomType->tagline }}</p>
            @endif
            <h5>{{ $roomType->name }}</h5>
            <p class="type-card-desc">{{ \Illuminate\Support\Str::limit($roomType->description, 120) }}</p>

            <dl class="type-card-facts">
              <div><dt>Rooms</dt><dd>{{ $roomType->rooms_count }}</dd></div>
              <div><dt>In service</dt><dd>{{ $inService }}</dd></div>
              <div><dt>Guests</dt><dd>{{ $roomType->max_adults }}+{{ $roomType->max_children }}</dd></div>
              <div><dt>Bookings</dt><dd>{{ $roomType->bookings_count }}</dd></div>
            </dl>

            @if ($roomType->rooms->isNotEmpty())
              <div class="type-card-rooms">
                @foreach ($roomType->rooms->sortBy('number') as $room)
                  <span @class(['room-chip', 'is-'.$room->status->value]) title="{{ $room->status->label() }}">{{ $room->number }}</span>
                @endforeach
              </div>
            @endif
          </div>
          <div class="type-card-foot">
            <a href="{{ route('admin.room-types.edit', $roomType) }}" class="btn btn-sm btn-primary"><i class="ti ti-pencil"></i> Edit</a>
            @can(Permission::InventoryManage->value)
              <a href="{{ route('admin.rooms.create', ['room_type' => $roomType->id]) }}" class="btn btn-sm btn-outline-secondary"><i class="ti ti-plus"></i> Add room</a>
            @endcan
            @can(Permission::InventoryView->value)
              <a href="{{ route('admin.rooms.index', ['room_type' => $roomType->id]) }}" class="btn btn-sm btn-link ms-auto">Board <i class="ti ti-arrow-right"></i></a>
            @endcan
          </div>
        </article>
      </div>
    @endforeach
  </div>
@endif
@endsection
