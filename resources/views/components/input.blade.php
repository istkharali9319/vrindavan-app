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
  <input
    id="{{ $id }}"
    name="{{ $name }}"
    type="{{ $type }}"
    @if ($type !== 'password') value="{{ old($name, $value) }}" @endif
    @if ($errorMessage) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
    {{ $attributes->except('id') }}
  >
  @if ($hint && ! $errorMessage)
    <span class="hint">{{ $hint }}</span>
  @endif
  @if ($errorMessage)
    <span class="field-error" id="{{ $id }}-error">{{ $errorMessage }}</span>
  @endif
</div>
