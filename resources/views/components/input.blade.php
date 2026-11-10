@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'hint' => null,
    'bag' => 'default',
])
@php
    $errorMessage = $errors->getBag($bag)->first($name);
    $id = $attributes->get('id', $name);
@endphp
<div @class(['form-field', 'has-error' => $errorMessage])>
  <label for="{{ $id }}">{{ $label }}</label>
  @if ($type === 'password')<div class="password-field">@endif
  <input
    id="{{ $id }}"
    name="{{ $name }}"
    type="{{ $type }}"
    @if ($type !== 'password') value="{{ old($name, $value) }}" @endif
    @if ($errorMessage) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
    {{ $attributes->except('id') }}
  >
  @if ($type === 'password')
    <button type="button" class="password-toggle" data-password-toggle aria-controls="{{ $id }}" aria-label="Show password" aria-pressed="false">
      <svg class="icon-show" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
      <svg class="icon-hide" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3l18 18"/><path d="M10.6 5.1A10.8 10.8 0 0 1 12 5c6.4 0 10 7 10 7a17.6 17.6 0 0 1-3.2 4.1M6.6 6.6C3.8 8.4 2 12 2 12s3.6 7 10 7a10 10 0 0 0 5.4-1.6"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
    </button>
  </div>
  @endif
  @if ($hint && ! $errorMessage)
    <span class="hint">{{ $hint }}</span>
  @endif
  @if ($errorMessage)
    <span class="field-error" id="{{ $id }}-error">{{ $errorMessage }}</span>
  @endif
</div>
