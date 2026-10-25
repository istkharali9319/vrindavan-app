@php($user = auth()->user())
@extends('errors.page', [
    'code' => 403,
    'title' => 'Access denied',
    'text' => "You don't have permission to access this page.",
])

@section('error_actions')
  <a href="{{ route('home') }}" class="btn btn-forest">Go to home page</a>
  @if ($user && request()->is('admin', 'admin/*') && ! $user->isStaff())
    <form method="POST" action="{{ route('logout') }}">
      @csrf
      <input type="hidden" name="portal" value="admin">
      <button type="submit" class="btn btn-outline">Sign in with another account</button>
    </form>
  @else
    <a href="javascript:history.back()" class="btn btn-outline">Go back</a>
  @endif
@endsection
