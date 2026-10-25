@extends('layouts.site')

@section('title', 'Reset your password · '.config('resort.name'))

@section('content')
  <x-auth-card title="Forgot your password?" intro="Enter your email and we'll send you a link to choose a new one.">
    <form method="POST" action="{{ route('password.email') }}" novalidate>
      @csrf
      <x-input name="email" type="email" label="Email" autocomplete="email" required autofocus />
      <button type="submit" class="btn btn-forest">Email me a reset link</button>
    </form>

    <x-slot:foot>Remembered it? <a href="{{ route('login') }}">Back to sign in</a></x-slot:foot>
  </x-auth-card>
@endsection
