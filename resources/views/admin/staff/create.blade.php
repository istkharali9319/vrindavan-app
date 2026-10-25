@extends('layouts.admin')

@section('title', 'Add staff member')
@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('admin.staff.index') }}">Staff</a></li>
  <li class="breadcrumb-item" aria-current="page">Add</li>
@endsection

@section('content')
<div class="row">
  <div class="col-lg-7">
    <div class="card">
      <div class="card-body">
        <p class="text-muted">We'll email them a link to set their own password, then they can sign in at the staff sign-in page.</p>
        <form method="POST" action="{{ route('admin.staff.store') }}" novalidate>
          @csrf
          @include('admin.staff.partials.fields', ['member' => null])
          <div class="mb-3">
            <label class="form-label" for="email">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" required>
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <button type="submit" class="btn btn-primary">Add staff member</button>
          <a href="{{ route('admin.staff.index') }}" class="btn btn-link">Cancel</a>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
