@extends('errors.page', [
    'code' => 404,
    'title' => 'Page not found',
    'text' => "The page you are looking for doesn't exist or has been moved.",
])

@section('error_actions')
  <a href="{{ route('home') }}" class="btn btn-forest">Go to home page</a>
  <a href="javascript:history.back()" class="btn btn-outline">Go back</a>
@endsection
