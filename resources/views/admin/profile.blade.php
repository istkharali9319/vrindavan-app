@extends('layouts.admin')

@section('title', 'My profile')
@section('subtitle', 'Keep your contact details current and your account secure.')
@section('breadcrumb')
  <li class="breadcrumb-item" aria-current="page">My profile</li>
@endsection

@php
    $timezone = config('booking.timezone');
    $profileErrors = $errors->getBag('updateProfileInformation');
    $passwordErrors = $errors->getBag('updatePassword');
@endphp

@section('content')
<div class="row g-4">
  <div class="col-xl-4">
    <div class="profile-card">
      <div class="profile-card-banner"></div>
      <div class="profile-card-body">
        <span class="profile-avatar">{{ $user->initials() }}</span>
        <h4>{{ $user->name }}</h4>
        <p class="profile-email">{{ $user->email }}</p>
        <div class="profile-roles">
          @foreach ($user->getRoleNames() as $role)
            <span><i class="ti ti-shield-check"></i> {{ \App\Enums\Role::from($role)->label() }}</span>
          @endforeach
        </div>
        <ul class="profile-meta">
          <li><i class="ti ti-phone"></i><span>Mobile</span><strong>{{ $user->phone ?: 'Not added' }}</strong></li>
          <li><i class="ti ti-calendar"></i><span>Member since</span><strong>{{ $user->created_at->timezone($timezone)->format('j M Y') }}</strong></li>
          <li><i class="ti ti-login"></i><span>Last sign-in</span><strong>{{ $user->last_login_at ? $user->last_login_at->timezone($timezone)->format('j M Y, g:i A') : 'Now' }}</strong></li>
        </ul>
      </div>
    </div>
  </div>

  <div class="col-xl-8">
    <div class="card profile-form">
      <div class="card-header profile-form-head">
        <span class="profile-form-icon is-details"><i class="ti ti-user"></i></span>
        <div>
          <h5 class="mb-0">Your details</h5>
          <small class="text-muted">Shown to the team and used for sign-in.</small>
        </div>
      </div>
      <div class="card-body">
        <form method="POST" action="{{ route('user-profile-information.update') }}" novalidate>
          @csrf
          @method('PUT')
          <div class="row g-3">
            @foreach ([
                ['name' => 'name', 'label' => 'Full name', 'type' => 'text', 'value' => $user->name, 'auto' => 'name', 'icon' => 'ti-user', 'col' => 'col-12'],
                ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'value' => $user->email, 'auto' => 'username', 'icon' => 'ti-mail', 'col' => 'col-md-7'],
                ['name' => 'phone', 'label' => 'Mobile', 'type' => 'tel', 'value' => $user->phone, 'auto' => 'tel', 'icon' => 'ti-phone', 'col' => 'col-md-5', 'placeholder' => '+91 94320 64521'],
            ] as $field)
              <div class="{{ $field['col'] }}">
                <label class="form-label" for="{{ $field['name'] }}">{{ $field['label'] }}</label>
                <div class="input-icon">
                  <i class="ti {{ $field['icon'] }}"></i>
                  <input type="{{ $field['type'] }}" id="{{ $field['name'] }}" name="{{ $field['name'] }}"
                         value="{{ old($field['name'], $field['value']) }}" autocomplete="{{ $field['auto'] }}" placeholder="{{ $field['placeholder'] ?? '' }}"
                         class="form-control {{ $profileErrors->has($field['name']) ? 'is-invalid' : '' }}" required>
                </div>
                @if ($profileErrors->has($field['name']))
                  <div class="invalid-feedback d-block">{{ $profileErrors->first($field['name']) }}</div>
                @endif
              </div>
            @endforeach
          </div>
          <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy"></i> Save details</button>
          </div>
        </form>
      </div>
    </div>

    <div class="card profile-form">
      <div class="card-header profile-form-head">
        <span class="profile-form-icon is-password"><i class="ti ti-lock"></i></span>
        <div>
          <h5 class="mb-0">Change password</h5>
          <small class="text-muted">At least 8 characters, with letters and numbers.</small>
        </div>
      </div>
      <div class="card-body">
        <form method="POST" action="{{ route('user-password.update') }}" novalidate>
          @csrf
          @method('PUT')
          <div class="row g-3">
            @foreach ([
                ['name' => 'current_password', 'label' => 'Current password', 'auto' => 'current-password', 'col' => 'col-12'],
                ['name' => 'password', 'label' => 'New password', 'auto' => 'new-password', 'col' => 'col-md-6'],
                ['name' => 'password_confirmation', 'label' => 'Confirm new password', 'auto' => 'new-password', 'col' => 'col-md-6'],
            ] as $field)
              <div class="{{ $field['col'] }}">
                <label class="form-label" for="pw_{{ $field['name'] }}">{{ $field['label'] }}</label>
                <div class="input-icon">
                  <i class="ti ti-lock"></i>
                  <input type="password" id="pw_{{ $field['name'] }}" name="{{ $field['name'] }}" autocomplete="{{ $field['auto'] }}"
                         class="form-control {{ $passwordErrors->has($field['name']) ? 'is-invalid' : '' }}" required>
                  <button type="button" class="input-icon-toggle" data-password-toggle="pw_{{ $field['name'] }}" aria-label="Show password"><i class="ti ti-eye"></i></button>
                </div>
                @if ($passwordErrors->has($field['name']))
                  <div class="invalid-feedback d-block">{{ $passwordErrors->first($field['name']) }}</div>
                @endif
              </div>
            @endforeach
          </div>
          <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="ti ti-key"></i> Change password</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
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
