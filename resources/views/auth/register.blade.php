@extends('layouts.site')

@section('title', 'Create an account · '.config('resort.name'))
@section('description', 'Create an account to book and manage your stay at '.config('resort.name').'.')

@section('content')
  <x-auth-card title="Create an account" intro="One account for every stay: book, pay and see your reservations in one place.">
    <form method="POST" action="{{ route('register') }}" novalidate>
      @csrf
      <x-input name="name" label="Full name" autocomplete="name" required autofocus />
      <x-input name="email" type="email" label="Email" autocomplete="email" required />
      <x-input name="phone" type="tel" label="Mobile number" autocomplete="tel" required
               hint="We only call about your booking. Indian numbers can be entered without +91." />
      <x-input name="password" type="password" label="Password" autocomplete="new-password" required
               hint="At least 8 characters, with letters and numbers." />
      <x-input name="password_confirmation" type="password" label="Confirm password" autocomplete="new-password" required />

      <button type="submit" class="btn btn-forest">Create account <span class="arrow">→</span></button>
    </form>

    <x-slot:foot>Already have an account? <a href="{{ route('login', array_filter(['return' => $returnTo ?? null])) }}">Sign in</a></x-slot:foot>
  </x-auth-card>
@endsection
