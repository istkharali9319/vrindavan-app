@extends('errors.page', [
    'code' => 419,
    'title' => 'Page expired',
    'text' => 'This page has expired. Please refresh and try again.',
])

@section('error_actions')
  <a href="{{ url()->previous() }}" class="btn btn-forest">Try again</a>
  <a href="{{ route('home') }}" class="btn btn-outline">Go to home page</a>
@endsection
