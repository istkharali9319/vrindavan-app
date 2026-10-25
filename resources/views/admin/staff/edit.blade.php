@extends('layouts.admin')

@section('title', 'Edit '.$member->name)
@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('admin.staff.index') }}">Staff</a></li>
  <li class="breadcrumb-item" aria-current="page">{{ $member->name }}</li>
@endsection

@section('content')
<div class="row">
  <div class="col-lg-7">
    <div class="card">
      <div class="card-body">
        <p class="text-muted mb-4">Email: <strong>{{ $member->email }}</strong>. Staff change their own email from their profile.</p>
        <form method="POST" action="{{ route('admin.staff.update', $member) }}" novalidate>
          @csrf
          @method('PUT')
          @include('admin.staff.partials.fields', ['member' => $member])
          <button type="submit" class="btn btn-primary">Save changes</button>
          <a href="{{ route('admin.staff.index') }}" class="btn btn-link">Cancel</a>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
