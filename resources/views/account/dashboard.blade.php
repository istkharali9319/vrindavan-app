@extends('layouts.account')

@section('title', 'My stays · '.config('resort.name'))

@section('account')
  <h1>Namaste, {{ \Illuminate\Support\Str::of($user->name)->before(' ') }}</h1>
  <p class="lead-text">Your upcoming stays, payments and booking history live here.</p>

  @if ($upcoming->isEmpty() && $past->isEmpty())
    <section class="panel empty-state">
      <h2>No stays booked yet</h2>
      <p>When you reserve a room or cottage, its dates, payment status and confirmation will appear here.</p>
      <a href="{{ route('booking.search') }}" class="btn btn-forest">Book a stay <span class="arrow">→</span></a>
    </section>
  @else
    <section class="panel">
      <h2>Upcoming stays</h2>
      @forelse ($upcoming as $booking)
        @include('account.bookings.partials.card')
      @empty
        <p>No upcoming stays. <a href="{{ route('booking.search') }}" class="text-link">Book your next visit</a></p>
      @endforelse
    </section>

    @if ($past->isNotEmpty())
      <section class="panel">
        <h2>Past &amp; cancelled</h2>
        @foreach ($past as $booking)
          @include('account.bookings.partials.card')
        @endforeach
      </section>
    @endif
  @endif

  <section class="panel">
    <h2>Your details</h2>
    <dl class="detail-list">
      <dt>Name</dt><dd>{{ $user->name }}</dd>
      <dt>Email</dt><dd>{{ $user->email }} @if ($user->hasVerifiedEmail())<span class="badge-soft ok">Verified</span>@else<span class="badge-soft warn">Password not set yet</span>@endif</dd>
      <dt>Mobile</dt><dd>{{ $user->phone ?? '—' }}</dd>
      <dt>Member since</dt><dd>{{ $user->created_at->format('F Y') }}</dd>
    </dl>
    <p class="panel-link"><a href="{{ route('account.profile') }}" class="text-link">Edit your details</a></p>
  </section>
@endsection
