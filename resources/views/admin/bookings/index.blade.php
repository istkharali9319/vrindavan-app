@extends('layouts.admin')

@section('title', 'Bookings')
@section('breadcrumb')
  <li class="breadcrumb-item" aria-current="page">Bookings</li>
@endsection

@section('content')
<div class="card">
  <div class="card-body">
    <form method="GET" action="{{ route('admin.bookings.index') }}" class="row g-2 align-items-end filters-bar mb-3" data-auto-submit>
      <div class="col-xl-3 col-lg-12">
        <label class="form-label" for="search">Search</label>
        <div class="search-field">
          <i class="ti ti-search"></i>
          <input type="search" id="search" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}"
                 placeholder="Reference, guest name, email or mobile" autocomplete="off" data-live-search>
        </div>
      </div>
      <div class="col-xl-2 col-lg-3 col-sm-6">
        <label class="form-label" for="status">Status</label>
        <select id="status" name="status" class="form-select">
          <option value="">Any</option>
          @foreach (\App\Enums\BookingStatus::cases() as $status)
            <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-xl-2 col-lg-3 col-sm-6">
        <label class="form-label" for="room_type">Room type</label>
        <select id="room_type" name="room_type" class="form-select">
          <option value="">All</option>
          @foreach ($roomTypes as $roomType)
            <option value="{{ $roomType->id }}" @selected((int) ($filters['room_type'] ?? 0) === $roomType->id)>{{ $roomType->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-xl-2 col-lg-3 col-sm-6">
        <label class="form-label" for="when">Stay</label>
        <select id="when" name="when" class="form-select">
          <option value="">All dates</option>
          <option value="upcoming" @selected(($filters['when'] ?? '') === 'upcoming')>Upcoming &amp; in-house</option>
          <option value="past" @selected(($filters['when'] ?? '') === 'past')>Past</option>
        </select>
      </div>
      <div class="col-xl-2 col-lg-3 col-sm-6">
        <label class="form-label" for="per_page">Rows</label>
        <select id="per_page" name="per_page" class="form-select">
          @foreach (\App\Http\Controllers\Admin\BookingController::PAGE_SIZES as $size)
            <option value="{{ $size }}" @selected($perPage === $size)>{{ $size }} per page</option>
          @endforeach
        </select>
      </div>
      <div class="col-xl-1 col-lg-3 col-sm-6 d-flex gap-2">
        <noscript><button type="submit" class="btn btn-primary">Filter</button></noscript>
        <a href="{{ route('admin.bookings.index') }}" class="btn btn-outline-secondary w-100">Reset</a>
      </div>
    </form>

    <div class="d-flex justify-content-between align-items-center mb-2 results-summary">
      <span class="text-muted">
        @if ($bookings->total() > 0)
          Showing <strong>{{ number_format($bookings->firstItem()) }}–{{ number_format($bookings->lastItem()) }}</strong>
          of <strong>{{ number_format($bookings->total()) }}</strong> {{ \Illuminate\Support\Str::plural('booking', $bookings->total()) }}
        @else
          No results
        @endif
      </span>
    </div>

    @include('admin.bookings.partials.table')

    @include('admin.partials.pagination', ['paginator' => $bookings, 'noun' => 'booking'])
  </div>
</div>
@endsection

@push('scripts')
  @vite(['resources/js/admin.js'])
@endpush
