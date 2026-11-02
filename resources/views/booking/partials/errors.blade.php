{{-- Errors for the booking pages. Messages listed in $except are shown elsewhere on the page. --}}
@php $messages = collect($errors->all())->reject(fn ($message) => in_array($message, $except ?? [], true)); @endphp
@if ($messages->isNotEmpty())
  <div class="flash error" role="alert">
    @foreach ($messages as $error)
      <div>{{ $error }}</div>
    @endforeach
  </div>
@endif
