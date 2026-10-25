@extends('layouts.site')

@section('title', 'Verify your email · '.config('resort.name'))

@section('content')
  <x-auth-card title="Check your inbox" intro="We sent a verification link to {{ auth()->user()->email }}. Click it to open your account dashboard.">
    <form method="POST" action="{{ route('verification.send') }}">
      @csrf
      <button type="submit" class="btn btn-forest">Resend the link</button>
    </form>

    <x-slot:foot>
      Wrong address? <a href="{{ route('account.profile') }}">Update your email</a>
      <form method="POST" action="{{ route('logout') }}" class="auth-foot-form">
        @csrf
        <button type="submit" class="link-button">Sign out</button>
      </form>
    </x-slot:foot>
  </x-auth-card>
@endsection
