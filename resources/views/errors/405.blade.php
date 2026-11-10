@extends('errors.page', [
    'code' => 405,
    'title' => 'This link can’t be opened',
    'text' => 'This address is used behind the scenes and doesn’t open as a page. It may have come from an old or incomplete link.',
])

@section('error_actions')
  <a href="{{ route('home') }}" class="btn btn-forest">Go to home page</a>
  <a href="javascript:history.back()" class="btn btn-outline">Go back</a>
@endsection
