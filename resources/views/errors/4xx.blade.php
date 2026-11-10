{{-- Any other 4xx error without its own page (e.g. 400, 410, 413): shown in the website design. --}}
@extends('errors.page', [
    'code' => $exception->getStatusCode(),
    'title' => 'This page can’t be shown',
    'text' => 'Something about this request didn’t work. Please go back and try again, or start again from the home page.',
])

@section('error_actions')
  <a href="{{ route('home') }}" class="btn btn-forest">Go to home page</a>
  <a href="javascript:history.back()" class="btn btn-outline">Go back</a>
@endsection
