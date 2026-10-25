@extends('layouts.admin')

@section('title', $roomType->name)
@section('subtitle', 'Changes show on the website straight away.')
@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('admin.room-types.index') }}">Room types</a></li>
  <li class="breadcrumb-item" aria-current="page">{{ $roomType->name }}</li>
@endsection

@section('actions')
  @if ($roomType->is_active)
    <a href="{{ route('accommodation') }}#{{ $roomType->slug }}" class="btn btn-outline-secondary" target="_blank" rel="noopener"><i class="ti ti-world"></i> View on website</a>
  @endif
@endsection

@section('content')
  @include('admin.room-types.partials.form', [
      'action' => route('admin.room-types.update', $roomType),
      'submitLabel' => 'Save changes',
  ])
@endsection
