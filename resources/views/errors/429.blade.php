@extends('errors.page', [
    'code' => 429,
    'title' => 'Too many requests',
    'text' => 'You have made too many requests. Please wait a minute and try again.',
])

@section('error_actions')
  <a href="{{ route('home') }}" class="btn btn-forest">Go to home page</a>
@endsection
