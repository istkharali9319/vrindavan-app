@extends('layouts.account')

@section('title', 'Profile & password · '.config('resort.name'))

@section('account')
  <h1>Profile &amp; password</h1>
  <p class="lead-text">Keep your contact details current so we can reach you about your stay.</p>

  <section class="panel">
    <h2>Your details</h2>
    @unless ($user->hasVerifiedEmail())
      <div class="flash warning">
        Your email address isn't verified yet.
        <form method="POST" action="{{ route('verification.send') }}" class="inline-form">
          @csrf
          <button type="submit" class="link-button">Send a new link</button>
        </form>
      </div>
    @endunless
    <form method="POST" action="{{ route('user-profile-information.update') }}" novalidate>
      @csrf
      @method('PUT')
      <x-input name="name" label="Full name" :value="$user->name" bag="updateProfileInformation" autocomplete="name" required />
      <x-input name="email" type="email" label="Email" :value="$user->email" bag="updateProfileInformation" autocomplete="email" required
               hint="Changing your email means verifying the new address." />
      <x-input name="phone" type="tel" label="Mobile number" :value="$user->phone" bag="updateProfileInformation" autocomplete="tel" required />
      <button type="submit" class="btn btn-forest">Save details</button>
    </form>
  </section>

  <section class="panel">
    <h2>Change password</h2>
    <form method="POST" action="{{ route('user-password.update') }}" novalidate>
      @csrf
      @method('PUT')
      <x-input name="current_password" type="password" label="Current password" bag="updatePassword" autocomplete="current-password" required />
      <x-input name="password" type="password" label="New password" bag="updatePassword" autocomplete="new-password" required
               hint="At least 8 characters, with letters and numbers." id="new_password" />
      <x-input name="password_confirmation" type="password" label="Confirm new password" bag="updatePassword" autocomplete="new-password" required />
      <button type="submit" class="btn btn-forest">Change password</button>
    </form>
  </section>

  @unless ($user->isStaff())
    <section class="panel danger">
      <h2>Delete account</h2>
      <p>This removes your personal details for good. Records of past stays and payments are kept, without your name or contact details, as the law requires.</p>
      <form method="POST" action="{{ route('account.destroy') }}" novalidate>
        @csrf
        @method('DELETE')
        <x-input name="password" type="password" label="Enter your password to confirm" bag="deleteAccount" autocomplete="current-password" required id="delete_password" />
        <button type="submit" class="btn btn-danger">Delete my account</button>
      </form>
    </section>
  @endunless
@endsection
