@extends('layouts.site')

@section('title', 'Two-factor check · '.config('resort.name'))

@section('content')
  <x-auth-card title="One more step" intro="Enter the 6-digit code from your authenticator app.">
    <form method="POST" action="{{ route('two-factor.login.store') }}" novalidate data-two-factor-form>
      @csrf
      <div data-mode="code">
        <x-input name="code" label="Authentication code" inputmode="numeric" autocomplete="one-time-code" autofocus />
      </div>
      <div data-mode="recovery" hidden>
        <x-input name="recovery_code" label="Recovery code" autocomplete="off" />
      </div>
      <button type="submit" class="btn btn-forest">Verify</button>
    </form>

    <x-slot:foot>
      <button type="button" class="link-button" data-two-factor-toggle>
        Lost your device? Use a recovery code
      </button>
    </x-slot:foot>
  </x-auth-card>
@endsection
