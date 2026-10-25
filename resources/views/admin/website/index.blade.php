@extends('layouts.admin')

@section('title', 'Pages & content')
@section('subtitle', 'Edit the text, photos and lists on every page of the website. Changes go live as soon as you save.')
@section('breadcrumb')
  <li class="breadcrumb-item" aria-current="page">Website</li>
@endsection

@section('actions')
  <a href="{{ route('home') }}" class="btn btn-outline-secondary" target="_blank" rel="noopener"><i class="ti ti-external-link"></i> Open website</a>
@endsection

@php
    /** One accent colour per page, matching the dashboard palette. */
    $accents = [
        'site' => 'is-indigo', 'home' => 'is-forest', 'accommodation' => 'is-bronze', 'experience' => 'is-rose',
        'dining' => 'is-amber', 'gallery' => 'is-teal', 'explore' => 'is-forest', 'contact' => 'is-indigo',
    ];
    $listCount = $pages->sum(fn ($page) => count($page['collections']));
    $itemCount = $pages->sum(fn ($page) => collect($page['collections'])->sum('count'));
@endphp

@section('content')
<div class="website-summary">
  <div class="website-summary-item is-forest"><i class="ti ti-file-text"></i><div><strong>{{ $pages->count() }}</strong><span>Pages</span></div></div>
  <div class="website-summary-item is-teal"><i class="ti ti-list"></i><div><strong>{{ $listCount }}</strong><span>Lists</span></div></div>
  <div class="website-summary-item is-bronze"><i class="ti ti-photo"></i><div><strong>{{ $itemCount }}</strong><span>List items</span></div></div>
  <div class="website-summary-item is-rose"><i class="ti ti-forms"></i><div><strong>{{ $pages->sum('fields') }}</strong><span>Editable fields</span></div></div>
</div>

<div class="row g-4">
  @foreach ($pages as $page)
    <div class="col-md-6 col-xxl-4">
      <article class="page-tile {{ $accents[$page['key']] ?? 'is-forest' }}">
        <a href="{{ route('admin.website.edit', $page['key']) }}" class="page-tile-cover" @if ($page['cover']) style="background-image:url('{{ $page['cover'] }}')" @endif>
          <span class="page-tile-icon"><i class="ti {{ $page['icon'] }}"></i></span>
          <span class="page-tile-name">
            <small>{{ $page['route'] ? 'Page' : 'Every page' }}</small>
            {{ $page['label'] }}
          </span>
          @if ($page['route'])
            <span class="page-tile-url">{{ parse_url(route($page['route']), PHP_URL_PATH) ?: '/' }}</span>
          @endif
        </a>

        <div class="page-tile-body">
          <p>{{ $page['description'] }}</p>
          <div class="page-tile-chips">
            <span class="page-chip is-plain"><i class="ti ti-layout-list"></i> {{ count($page['sections']) }} {{ str('section')->plural(count($page['sections'])) }}</span>
            @foreach ($page['collections'] as $collectionKey => $collection)
              <a href="{{ route('admin.website.items.index', [$page['key'], $collectionKey]) }}" class="page-chip">
                {{ $collection['label'] }} <b>{{ $collection['count'] }}</b>
              </a>
            @endforeach
          </div>
        </div>

        <div class="page-tile-foot">
          <a href="{{ route('admin.website.edit', $page['key']) }}" class="btn btn-sm page-tile-edit"><i class="ti ti-pencil"></i> Edit {{ $page['route'] ? 'page' : 'settings' }}</a>
          @if ($page['last_edit'])
            <small class="text-muted"><i class="ti ti-clock"></i> {{ \Illuminate\Support\Carbon::parse($page['last_edit'])->diffForHumans() }}</small>
          @else
            <small class="text-muted">Original content</small>
          @endif
          @if ($page['route'])
            <a href="{{ route($page['route']) }}" class="page-tile-view" target="_blank" rel="noopener" aria-label="View {{ $page['label'] }} on the website"><i class="ti ti-external-link"></i></a>
          @endif
        </div>
      </article>
    </div>
  @endforeach
</div>
@endsection
