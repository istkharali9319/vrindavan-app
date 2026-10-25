@extends('layouts.admin-auth')

@section('title', 'Staff sign in')
@section('heading', 'Staff sign in')
@section('intro', 'For resort staff only. Guests sign in on the website.')

@section('content')
  <form method="POST" action="{{ route('login') }}" novalidate>
    @csrf
    <input type="hidden" name="portal" value="admin">

    <div class="mb-3">
      <label class="form-label" for="email">Email address</label>
      <div class="input-icon">
        <i class="ti ti-mail"></i>
        <input type="email" id="email" name="email" value="{{ old('email') }}" autocomplete="username" placeholder="you@vrindavan.com"
               class="form-control @error('email') is-invalid @enderror" required autofocus>
      </div>
      @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>

    <div class="mb-3">
      <div class="d-flex justify-content-between align-items-baseline">
        <label class="form-label" for="password">Password</label>
        <a href="{{ route('password.request', ['portal' => 'admin']) }}" class="small link-primary">Forgot password?</a>
      </div>
      <div class="input-icon">
        <i class="ti ti-lock"></i>
        <input type="password" id="password" name="password" autocomplete="current-password" placeholder="Your password"
               class="form-control @error('password') is-invalid @enderror" required>
        <button type="button" class="input-icon-toggle" data-password-toggle="password" aria-label="Show password"><i class="ti ti-eye"></i></button>
      </div>
      @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>

    <div class="form-check mb-4">
      <input class="form-check-input input-primary" type="checkbox" id="remember" name="remember" value="1" @checked(old('remember'))>
      <label class="form-check-label text-muted" for="remember">Keep me signed in</label>
    </div>

    <button type="submit" class="btn btn-primary btn-lg w-100 staff-auth-submit">Sign in <i class="ti ti-arrow-right"></i></button>
  </form>
@endsection

@push('scripts')
  <script>
    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
      const input = document.getElementById(button.dataset.passwordToggle);
      button.addEventListener('click', () => {
        const isHidden = input.type === 'password';
        input.type = isHidden ? 'text' : 'password';
        button.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
        button.querySelector('i').className = isHidden ? 'ti ti-eye-off' : 'ti ti-eye';
      });
    });
  </script>
@endpush
