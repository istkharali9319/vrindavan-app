@extends('layouts.site')

@php
    // Guests whose account was made from their booking arrive here from the "Set my password" email.
    $settingUp = $request->boolean('setup');
    $heading = $settingUp ? 'Set your password' : 'Choose a new password';
@endphp

@section('title', $heading.' · '.config('resort.name'))

@section('content')
  <x-auth-card :title="$heading">
    @if ($settingUp)
      <p class="auth-intro">Your account was created with your booking. Choose a password to see your stays, download invoices and manage bookings.</p>
    @endif
    <form method="POST" action="{{ route('password.update') }}" novalidate>
      @csrf
      <input type="hidden" name="token" value="{{ $request->route('token') }}">
      <x-input name="email" type="email" label="Email" :value="$request->email" autocomplete="email" required />
      <x-input name="password" type="password" label="New password" autocomplete="new-password" required autofocus
               hint="At least 8 characters, with letters and numbers." />
      <x-input name="password_confirmation" type="password" label="Confirm new password" autocomplete="new-password" required />
      <button type="submit" class="btn btn-forest">{{ $settingUp ? 'Set password' : 'Save password' }}</button>
    </form>
  </x-auth-card>
@endsection
