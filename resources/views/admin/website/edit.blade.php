@extends('layouts.admin')

@section('title', $page['label'])
@section('subtitle', $page['description'])
@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('admin.website.index') }}">Website</a></li>
  <li class="breadcrumb-item" aria-current="page">{{ $page['label'] }}</li>
@endsection

@section('actions')
  @if ($page['route'])
    <a href="{{ route($page['route']) }}" class="btn btn-outline-secondary" target="_blank" rel="noopener"><i class="ti ti-external-link"></i> View page</a>
  @endif
  <button type="submit" form="page-form" class="btn btn-primary"><i class="ti ti-device-floppy"></i> Save changes</button>
@endsection

@section('content')
<div class="row g-4">
  <div @class(['col-xl-8' => $collections->isNotEmpty(), 'col-xl-9' => $collections->isEmpty()])>
    <form method="POST" action="{{ route('admin.website.update', $pageKey) }}" enctype="multipart/form-data" id="page-form" novalidate>
      @csrf
      @method('PUT')

      @if ($pageKey === 'site')
        <div class="card mail-check">
          <div class="card-body">
            <span class="mail-check-icon"><i class="ti ti-mail"></i></span>
            <div>
              <strong>Email sending</strong>
              <small>Using <b>{{ config('mail.default') }}</b>@if (config('mail.default') === 'smtp') · {{ config('mail.mailers.smtp.host') }}:{{ config('mail.mailers.smtp.port') }} · from {{ config('mail.from.address') }}@endif. Mail settings live in the server's .env file.</small>
              @if (session('mail_error'))
                <div class="alert alert-danger mt-2 mb-0 py-2 small">Test email failed: {{ session('mail_error') }}</div>
              @endif
            </div>
            <button type="submit" form="mail-test-form" class="btn btn-outline-secondary btn-sm ms-auto"><i class="ti ti-send"></i> Send test email to me</button>
          </div>
        </div>
      @endif

      <nav class="section-nav" aria-label="Sections">
        @foreach ($page['sections'] as $sectionKey => $section)
          <a href="#section-{{ $sectionKey }}">{{ $section['label'] }}</a>
        @endforeach
      </nav>

      @foreach ($page['sections'] as $sectionKey => $section)
        <div class="card content-section" id="section-{{ $sectionKey }}">
          <div class="card-header"><h5 class="mb-0">{{ $section['label'] }}</h5></div>
          <div class="card-body">
            <div class="row g-3">
              @foreach ($section['fields'] as $fieldKey => $field)
                @include('admin.website.partials.field', [
                    'field' => $field,
                    'value' => $values("{$sectionKey}.{$fieldKey}"),
                    'id' => "{$sectionKey}-{$fieldKey}",
                    'key' => "fields.{$sectionKey}.{$fieldKey}",
                    'name' => "fields[{$sectionKey}][{$fieldKey}]",
                    'imageName' => "images[{$sectionKey}][{$fieldKey}]",
                    'resetName' => "reset[{$sectionKey}][{$fieldKey}]",
                ])
              @endforeach
            </div>
          </div>
        </div>
      @endforeach

      <div class="content-save-bar">
        <span class="text-muted small"><i class="ti ti-info-circle"></i> Wrap words in *asterisks* for italics. Line breaks are kept.</span>
        <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy"></i> Save changes</button>
      </div>
    </form>
  </div>

  @if ($collections->isNotEmpty())
    <div class="col-xl-4">
      <div class="content-lists">
        <h6 class="admin-section-label">Lists on this page</h6>
        @foreach ($collections as $collectionKey => $collection)
          <a href="{{ route('admin.website.items.index', [$pageKey, $collectionKey]) }}" class="list-card">
            <div class="list-card-thumbs">
              @php $imageField = collect($collection['fields'])->search(fn ($field) => $field['type'] === 'image'); @endphp
              @if ($imageField)
                @foreach ($collection['items']->take(4) as $item)
                  <img src="{{ $item->image($imageField) }}" alt="" loading="lazy">
                @endforeach
              @else
                <span class="list-card-icon"><i class="ti ti-list"></i></span>
              @endif
            </div>
            <div class="list-card-body">
              <strong>{{ $collection['label'] }}</strong>
              <span>{{ $collection['items']->count() }} {{ str($collection['singular'])->plural($collection['items']->count()) }} · {{ $collection['items']->where('is_active', false)->count() }} hidden</span>
            </div>
            <i class="ti ti-chevron-right"></i>
          </a>
        @endforeach
      </div>
    </div>
  @endif
</div>
@if ($pageKey === 'site')
  <form method="POST" action="{{ route('admin.website.test-email') }}" id="mail-test-form">@csrf</form>
@endif
@endsection

@push('scripts')
  @include('admin.website.partials.preview-script')
@endpush
