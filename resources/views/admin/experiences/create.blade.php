@extends('layouts.admin')

@section('title', 'Add experience or add-on')
@section('subtitle', 'An experience for the Experiences page, an extra guests can add while booking, or both.')
@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('admin.experiences.index') }}">Experiences &amp; add-ons</a></li>
  <li class="breadcrumb-item" aria-current="page">Add</li>
@endsection

@section('content')
  @include('admin.experiences.partials.form', ['action' => route('admin.experiences.store'), 'submitLabel' => 'Add'])
@endsection
