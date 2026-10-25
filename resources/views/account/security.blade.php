@extends('layouts.account')

@section('title', 'Security · '.config('resort.name'))

@section('account')
  <h1>Security</h1>
  <p class="lead-text">Add a second step to sign-in with an authenticator app such as Google Authenticator, Microsoft Authenticator or 1Password.</p>

  <section class="panel">
    <h2>
      Two-factor authentication
      @if ($user->hasConfirmedTwoFactor())
        <span class="badge-soft ok">On</span>
      @elseif ($user->two_factor_secret)
        <span class="badge-soft warn">Setup not finished</span>
      @else
        <span class="badge-soft">Off</span>
      @endif
    </h2>

    @if ($user->isStaff())
      <p>Two-factor authentication is required for staff accounts.</p>
    @endif

    @if (! $user->two_factor_secret)
      <p>When it's on, you'll enter a 6-digit code from your phone after your password.</p>
      <form method="POST" action="{{ route('two-factor.enable') }}">
        @csrf
        <button type="submit" class="btn btn-forest">Turn on two-factor authentication</button>
      </form>

    @elseif (! $user->hasConfirmedTwoFactor())
      <p>Scan this QR code with your authenticator app, then enter the 6-digit code it shows.</p>
      <div class="two-factor-qr">{!! $user->twoFactorQrCodeSvg() !!}</div>
      <p>Can't scan it? Enter this setup key instead: <code>{{ decrypt($user->two_factor_secret) }}</code></p>
      <form method="POST" action="{{ route('two-factor.confirm') }}" novalidate>
        @csrf
        <x-input name="code" label="6-digit code" bag="confirmTwoFactorAuthentication" inputmode="numeric" autocomplete="one-time-code" required autofocus />
        <div class="btn-row">
          <button type="submit" class="btn btn-forest">Confirm and turn on</button>
        </div>
      </form>
      <form method="POST" action="{{ route('two-factor.disable') }}" class="inline-form">
        @csrf
        @method('DELETE')
        <button type="submit" class="link-button">Cancel setup</button>
      </form>

    @else
      <p>Store these recovery codes in a password manager. Each one signs you in once if you lose your phone.</p>
      <div class="recovery-codes">
        @foreach ($user->recoveryCodes() as $code)
          <span>{{ $code }}</span>
        @endforeach
      </div>
      <div class="btn-row">
        <form method="POST" action="{{ route('two-factor.regenerate-recovery-codes') }}" class="inline-form">
          @csrf
          <button type="submit" class="btn btn-ghost">Generate new codes</button>
        </form>
        @unless ($user->isStaff())
          <form method="POST" action="{{ route('two-factor.disable') }}" class="inline-form">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger">Turn off</button>
          </form>
        @endunless
      </div>
    @endif
  </section>
@endsection
