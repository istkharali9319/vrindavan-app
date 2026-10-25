@extends('layouts.admin')

@section('title', $collection['label'])
@section('subtitle', 'Shown on the '.$page['label'].' page in this order. Hidden items stay here but are not shown on the website.')
@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('admin.website.index') }}">Website</a></li>
  <li class="breadcrumb-item"><a href="{{ route('admin.website.edit', $pageKey) }}">{{ $page['label'] }}</a></li>
  <li class="breadcrumb-item" aria-current="page">{{ $collection['label'] }}</li>
@endsection

@section('actions')
  <a href="{{ route('admin.website.edit', $pageKey) }}" class="btn btn-outline-secondary"><i class="ti ti-arrow-left"></i> {{ $page['label'] }} page</a>
  @if (! $collection['max'] || $items->count() < $collection['max'])
    <a href="{{ route('admin.website.items.create', [$pageKey, $collectionKey]) }}" class="btn btn-primary"><i class="ti ti-plus"></i> Add {{ $collection['singular'] }}</a>
  @endif
@endsection

@section('content')
@php
    $imageField = collect($collection['fields'])->search(fn ($field) => $field['type'] === 'image');
    $titleField = isset($collection['fields']['title']) ? 'title' : (isset($collection['fields']['name']) ? 'name' : array_key_first($collection['fields']));
    $subtitleField = collect(['tag', 'distance', 'trip', 'short_label', 'category', 'text', 'description'])->first(fn ($key) => isset($collection['fields'][$key]));
@endphp

@if ($items->isEmpty())
  <div class="card">
    <div class="card-body admin-empty">
      <i class="ti ti-list"></i>
      <p>Nothing here yet, so this part of the page is hidden on the website.</p>
      <a href="{{ route('admin.website.items.create', [$pageKey, $collectionKey]) }}" class="btn btn-primary">Add {{ $collection['singular'] }}</a>
    </div>
  </div>
@else
  <div class="item-list">
    @foreach ($items as $item)
      <article @class(['item-row', 'is-hidden' => ! $item->is_active])>
        <span class="item-row-order">{{ $loop->iteration }}</span>
        @if ($imageField)
          <img src="{{ $item->image($imageField) }}" alt="" class="item-row-thumb" loading="lazy">
        @elseif (isset($collection['fields']['icon']))
          <span class="item-row-icon"><x-site-icon :name="$item->get('icon')" /></span>
        @endif
        <div class="item-row-body">
          <strong>{{ $item->get($titleField) ?: 'Untitled' }}</strong>
          @if ($subtitleField && $item->has($subtitleField))
            @php $options = $collection['fields'][$subtitleField]['options']; @endphp
            <span>{{ \Illuminate\Support\Str::limit($options[$item->get($subtitleField)] ?? $item->get($subtitleField), 110) }}</span>
          @endif
        </div>
        @unless ($item->is_active)
          <span class="badge bg-light-secondary text-secondary"><i class="ti ti-eye-off"></i> Hidden</span>
        @endunless
        <div class="item-row-actions">
          <form method="POST" action="{{ route('admin.website.items.move', [$pageKey, $collectionKey, $item]) }}">
            @csrf
            <input type="hidden" name="direction" value="up">
            <button type="submit" class="btn btn-icon" @disabled($loop->first) aria-label="Move up"><i class="ti ti-arrow-up"></i></button>
          </form>
          <form method="POST" action="{{ route('admin.website.items.move', [$pageKey, $collectionKey, $item]) }}">
            @csrf
            <input type="hidden" name="direction" value="down">
            <button type="submit" class="btn btn-icon" @disabled($loop->last) aria-label="Move down"><i class="ti ti-arrow-down"></i></button>
          </form>
          <a href="{{ route('admin.website.items.edit', [$pageKey, $collectionKey, $item]) }}" class="btn btn-sm btn-primary"><i class="ti ti-pencil"></i> Edit</a>
        </div>
      </article>
    @endforeach
  </div>
@endif
@endsection
