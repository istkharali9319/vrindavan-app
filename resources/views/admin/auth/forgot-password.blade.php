@extends('layouts.admin-auth')

@section('title', 'Reset password')
@section('heading', 'Forgot your password?')
@section('intro', "Enter your staff email and we'll send you a link to choose a new one.")

@section('content')
  <form method="POST" action="{{ route('password.email') }}" novalidate>
    @csrf
    <div class="form-group mb-3">
      <label class="form-label" for="email">Email address</label>
      <input type="email" id="email" name="email" value="{{ old('email') }}" autocomplete="username"
             class="form-control @error('email') is-invalid @enderror" required autofocus>
      @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="d-grid mt-3">
      <button type="submit" class="btn btn-primary">Email me a reset link</button>
    </div>
  </form>
  <a href="{{ route('admin.login') }}" class="btn btn-link px-0 mt-3">Back to staff sign in</a>
@endsection
