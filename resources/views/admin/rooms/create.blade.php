@extends('layouts.admin')

@section('title', 'Add a room')
@section('subtitle', 'A physical room guests can be put in. Its tariff and photos come from its room type.')
@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('admin.rooms.index') }}">Rooms</a></li>
  <li class="breadcrumb-item" aria-current="page">Add</li>
@endsection

@section('content')
<div class="row">
  <div class="col-xl-7">
    <div class="card">
      <div class="card-body">
        @if ($roomTypes->isEmpty())
          <div class="admin-empty">
            <i class="ti ti-bed"></i>
            <p>Create a room type first, then add its rooms.</p>
            @can(\App\Enums\Permission::CatalogManage->value)
              <a href="{{ route('admin.room-types.create') }}" class="btn btn-primary">Add room type</a>
            @endcan
          </div>
        @else
          <form method="POST" action="{{ route('admin.rooms.store') }}" novalidate>
            @csrf
            @include('admin.rooms.partials.fields')
            <div class="form-actions">
              <button type="submit" class="btn btn-primary"><i class="ti ti-plus"></i> Add room</button>
              <a href="{{ route('admin.rooms.index') }}" class="btn btn-link">Cancel</a>
            </div>
          </form>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection
