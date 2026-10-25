@extends('layouts.admin')

@section('title', 'Room '.$room->number)
@section('subtitle', $room->roomType->name)
@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('admin.rooms.index') }}">Rooms</a></li>
  <li class="breadcrumb-item" aria-current="page">{{ $room->number }}</li>
@endsection

@section('content')
<div class="row g-3">
  <div class="col-xl-7">
    <div class="card">
      <div class="card-body">
        <form method="POST" action="{{ route('admin.rooms.update', $room) }}" novalidate>
          @csrf
          @method('PUT')
          @include('admin.rooms.partials.fields')
          <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save changes</button>
            <a href="{{ route('admin.rooms.index') }}" class="btn btn-link">Cancel</a>
          </div>
        </form>
      </div>
    </div>
  </div>
  <div class="col-xl-5">
    <div class="card room-type-preview">
      <img src="{{ $room->roomType->imageUrl() }}" alt="" loading="lazy">
      <div class="card-body">
        <h5>{{ $room->roomType->name }}</h5>
        <p class="text-muted mb-3">{{ $room->roomType->formattedPrice() }} / night · {{ $room->roomType->capacitySummary() }}</p>
        @if ($upcomingNights > 0)
          <div class="alert alert-warning mb-0"><i class="ti ti-calendar-event"></i> {{ $upcomingNights }} upcoming {{ str('night')->plural($upcomingNights) }} booked in this room. Taking it out of service won't cancel them.</div>
        @else
          <p class="mb-0 text-muted"><i class="ti ti-checks"></i> No upcoming bookings.</p>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection
