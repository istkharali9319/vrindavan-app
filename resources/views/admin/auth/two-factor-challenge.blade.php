@extends('layouts.admin-auth')

@section('title', 'Two-factor check')
@section('heading', 'One more step')
@section('intro', 'Enter the 6-digit code from your authenticator app.')

@section('content')
  <form method="POST" action="{{ route('two-factor.login.store') }}" novalidate>
    @csrf
    <div class="form-group mb-3" data-mode="code">
      <label class="form-label" for="code">Authentication code</label>
      <input type="text" id="code" name="code" inputmode="numeric" autocomplete="one-time-code"
             class="form-control @error('code') is-invalid @enderror" autofocus>
      @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="form-group mb-3" data-mode="recovery" hidden>
      <label class="form-label" for="recovery_code">Recovery code</label>
      <input type="text" id="recovery_code" name="recovery_code" autocomplete="off"
             class="form-control @error('recovery_code') is-invalid @enderror">
      @error('recovery_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="d-grid mt-3">
      <button type="submit" class="btn btn-primary">Verify</button>
    </div>
  </form>
  <button type="button" class="btn btn-link px-0 mt-3" data-two-factor-toggle>Lost your phone? Use a recovery code</button>
@endsection

@push('scripts')
<script>
  document.querySelector('[data-two-factor-toggle]').addEventListener('click', function () {
    const code = document.querySelector('[data-mode="code"]');
    const recovery = document.querySelector('[data-mode="recovery"]');
    const useRecovery = recovery.hidden;
    code.hidden = useRecovery;
    recovery.hidden = !useRecovery;
    code.querySelector('input').value = '';
    recovery.querySelector('input').value = '';
    (useRecovery ? recovery : code).querySelector('input').focus();
    this.textContent = useRecovery ? 'Use your authenticator app instead' : 'Lost your phone? Use a recovery code';
  });
</script>
@endpush
