@extends('layouts.admin')

@section('title', 'Add a room type')
@section('subtitle', 'Set the tariff, capacity and the photos guests will see on the website.')
@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('admin.room-types.index') }}">Room types</a></li>
  <li class="breadcrumb-item" aria-current="page">Add</li>
@endsection

@section('content')
  @include('admin.room-types.partials.form', [
      'action' => route('admin.room-types.store'),
      'submitLabel' => 'Add room type',
  ])
@endsection
