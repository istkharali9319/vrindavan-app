@extends('layouts.site')

@section('title', 'Sign in · '.config('resort.name'))
@section('description', 'Sign in to view and manage your stays at '.config('resort.name').'.')

@section('content')
  <x-auth-card title="Welcome back" intro="Sign in to view your stays and manage your bookings.">
    <form method="POST" action="{{ route('login') }}" novalidate>
      @csrf
      <x-input name="email" type="email" label="Email" autocomplete="email" required autofocus />
      <x-input name="password" type="password" label="Password" autocomplete="current-password" required />

      <div class="form-row-split">
        <label class="check"><input type="checkbox" name="remember" value="1" @checked(old('remember'))> Remember me</label>
        <a href="{{ route('password.request') }}" class="text-link">Forgot password?</a>
      </div>

      <button type="submit" class="btn btn-forest">Sign in <span class="arrow">→</span></button>
    </form>

    <x-slot:foot>New to Vrindavan? <a href="{{ route('register', array_filter(['return' => $returnTo ?? null])) }}">Create an account</a></x-slot:foot>
  </x-auth-card>
@endsection
