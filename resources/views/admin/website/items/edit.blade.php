@extends('layouts.admin')

@section('title', 'Edit '.$collection['singular'])
@section('subtitle', $collection['label'].' on the '.$page['label'].' page.')
@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('admin.website.index') }}">Website</a></li>
  <li class="breadcrumb-item"><a href="{{ route('admin.website.edit', $pageKey) }}">{{ $page['label'] }}</a></li>
  <li class="breadcrumb-item"><a href="{{ route('admin.website.items.index', [$pageKey, $collectionKey]) }}">{{ $collection['label'] }}</a></li>
  <li class="breadcrumb-item" aria-current="page">Edit</li>
@endsection

@section('content')
<div class="row g-4">
  <div class="col-xl-8">
    @include('admin.website.items.form', [
        'action' => route('admin.website.items.update', [$pageKey, $collectionKey, $item]),
        'submitLabel' => 'Save changes',
    ])
  </div>
  @if (config('admin.deletes_enabled'))
  <div class="col-xl-4">
    <div class="card border-danger-subtle">
      <div class="card-body">
        <h6 class="mb-1">Remove this {{ $collection['singular'] }}</h6>
        <p class="text-muted small">It disappears from the website. To keep it for later, turn off "Show on website" instead.</p>
        <form method="POST" action="{{ route('admin.website.items.destroy', [$pageKey, $collectionKey, $item]) }}"
              onsubmit="return confirm('Remove this {{ $collection['singular'] }} for good?');">
          @csrf
          @method('DELETE')
          <button type="submit" class="btn btn-sm btn-outline-danger"><i class="ti ti-trash"></i> Remove {{ $collection['singular'] }}</button>
        </form>
      </div>
    </div>
  </div>
  @endif
</div>
@endsection
