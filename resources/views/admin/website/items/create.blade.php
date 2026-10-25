@extends('layouts.admin')

@section('title', 'Add '.$collection['singular'])
@section('subtitle', $collection['label'].' on the '.$page['label'].' page.')
@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('admin.website.index') }}">Website</a></li>
  <li class="breadcrumb-item"><a href="{{ route('admin.website.edit', $pageKey) }}">{{ $page['label'] }}</a></li>
  <li class="breadcrumb-item"><a href="{{ route('admin.website.items.index', [$pageKey, $collectionKey]) }}">{{ $collection['label'] }}</a></li>
  <li class="breadcrumb-item" aria-current="page">Add</li>
@endsection

@section('content')
<div class="row">
  <div class="col-xl-8">
    @include('admin.website.items.form', [
        'action' => route('admin.website.items.store', [$pageKey, $collectionKey]),
        'submitLabel' => 'Add '.$collection['singular'],
    ])
  </div>
</div>
@endsection
