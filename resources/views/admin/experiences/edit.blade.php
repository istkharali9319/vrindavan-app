@extends('layouts.admin')

@section('title', $experience->title)
@section('subtitle', 'Changes show on the website straight away. Price changes don\'t affect bookings already made.')
@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('admin.experiences.index') }}">Experiences &amp; add-ons</a></li>
  <li class="breadcrumb-item" aria-current="page">{{ $experience->title }}</li>
@endsection

@section('content')
  @include('admin.experiences.partials.form', ['action' => route('admin.experiences.update', $experience), 'submitLabel' => 'Save changes'])

  @if (config('admin.deletes_enabled'))
  <div class="row">
    <div class="col-xl-4 offset-xl-8">
      <div class="card booking-danger">
        <div class="card-body">
          <h6><i class="ti ti-trash"></i> Delete</h6>
          <p>
            Removes it from the website and from the add-ons list.
            @if ($timesBooked)
              It was added to {{ $timesBooked }} {{ str('booking')->plural($timesBooked) }}; those keep their own copy of the name and price.
            @endif
            To keep it for later, turn off "Active" instead.
          </p>
          <form method="POST" action="{{ route('admin.experiences.destroy', $experience) }}" onsubmit="return confirm('Delete this for good?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger w-100">Delete</button>
          </form>
        </div>
      </div>
    </div>
  </div>
  @endif
@endsection
