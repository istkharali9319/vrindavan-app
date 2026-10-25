@extends('layouts.admin')

@use('App\Enums\EnquiryStatus')

@section('title', 'Enquiries')
@section('subtitle', 'Messages sent from the Contact page of the website.')
@section('breadcrumb')
  <li class="breadcrumb-item" aria-current="page">Enquiries</li>
@endsection

@section('actions')
  <a href="{{ route('contact') }}" class="btn btn-outline-secondary" target="_blank" rel="noopener"><i class="ti ti-external-link"></i> Contact page</a>
@endsection

@section('content')
<div class="row g-3 mb-4">
  @foreach ([
      ['label' => 'All enquiries', 'value' => $counts['all'], 'icon' => 'ti-inbox', 'tone' => 'is-indigo', 'query' => []],
      ['label' => 'New', 'value' => $counts['new'], 'icon' => 'ti-mail', 'tone' => 'is-amber', 'query' => ['status' => 'new']],
      ['label' => 'Replied', 'value' => $counts['replied'], 'icon' => 'ti-mail-forward', 'tone' => 'is-forest', 'query' => ['status' => 'replied']],
      ['label' => 'Closed', 'value' => $counts['closed'], 'icon' => 'ti-archive', 'tone' => 'is-teal', 'query' => ['status' => 'closed']],
  ] as $card)
    <div class="col-6 col-xl-3">
      <a href="{{ route('admin.enquiries.index', $card['query']) }}" class="website-summary-item user-count {{ $card['tone'] }}">
        <i class="ti {{ $card['icon'] }}"></i>
        <div><strong>{{ number_format($card['value']) }}</strong><span>{{ $card['label'] }}</span></div>
      </a>
    </div>
  @endforeach
</div>

<div class="card">
  <div class="card-body">
    <form method="GET" action="{{ route('admin.enquiries.index') }}" class="row g-2 align-items-end filters-bar mb-3" data-auto-submit>
      <div class="col-xl-6 col-lg-12">
        <label class="form-label" for="search">Search</label>
        <div class="search-field">
          <i class="ti ti-search"></i>
          <input type="search" id="search" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}"
                 placeholder="Name, email, phone or message" autocomplete="off" data-live-search>
        </div>
      </div>
      <div class="col-xl-2 col-lg-4 col-sm-6">
        <label class="form-label" for="status">Status</label>
        <select id="status" name="status" class="form-select">
          <option value="">Any</option>
          @foreach (EnquiryStatus::cases() as $status)
            <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-xl-2 col-lg-4 col-sm-6">
        <label class="form-label" for="per_page">Rows</label>
        <select id="per_page" name="per_page" class="form-select">
          @foreach (\App\Http\Controllers\Admin\EnquiryController::PAGE_SIZES as $size)
            <option value="{{ $size }}" @selected($perPage === $size)>{{ $size }} per page</option>
          @endforeach
        </select>
      </div>
      <div class="col-xl-2 col-lg-4 col-sm-6 d-flex gap-2">
        <noscript><button type="submit" class="btn btn-primary">Filter</button></noscript>
        <a href="{{ route('admin.enquiries.index') }}" class="btn btn-outline-secondary w-100">Reset</a>
      </div>
    </form>

    <div class="enquiry-list">
      @forelse ($enquiries as $enquiry)
        <a href="{{ route('admin.enquiries.show', $enquiry) }}" @class(['enquiry-row', 'is-unread' => $enquiry->isUnread()])>
          <span class="user-avatar">{{ $enquiry->initials() }}</span>
          <span class="enquiry-row-body">
            <span class="enquiry-row-head">
              <strong>{{ $enquiry->name }}</strong>
              <small>{{ $enquiry->email }}@if ($enquiry->phone) · {{ $enquiry->phone }}@endif</small>
            </span>
            <span class="enquiry-row-message">{{ \Illuminate\Support\Str::limit($enquiry->message, 160) }}</span>
            @if ($last = $enquiry->latestMessage)
              <span class="enquiry-row-last">
                <i class="ti ti-corner-down-right"></i>
                <strong>{{ $last->type === \App\Enums\EnquiryMessageType::Guest ? \Illuminate\Support\Str::of($enquiry->name)->before(' ') : ($last->author?->name ?? 'Staff') }}</strong>
                <span>{{ $last->type->label() }}{{ $last->channel ? ' · '.$last->channel->label() : '' }}: {{ \Illuminate\Support\Str::limit($last->body, 80) }}</span>
              </span>
            @endif
          </span>
          <span class="enquiry-row-side">
            @include('admin.enquiries.partials.status', ['enquiry' => $enquiry])
            @if ($enquiry->messages_count > 0)
              <span class="enquiry-row-count"><i class="ti ti-message-circle"></i> {{ $enquiry->messages_count + 1 }}</span>
            @endif
            <small title="{{ $enquiry->created_at->timezone(config('booking.timezone'))->format('d M Y, g:i A') }}">{{ $enquiry->created_at->diffForHumans() }}</small>
          </span>
        </a>
      @empty
        <div class="admin-empty">
          <i class="ti ti-inbox"></i>
          <p>{{ ($filters['search'] ?? null) || ($filters['status'] ?? null) ? 'No enquiries match these filters.' : 'No enquiries yet. Messages from the Contact page will appear here.' }}</p>
        </div>
      @endforelse
    </div>

    @include('admin.partials.pagination', ['paginator' => $enquiries, 'noun' => 'enquiry'])
  </div>
</div>
@endsection

@push('scripts')
  @vite(['resources/js/admin.js'])
@endpush
