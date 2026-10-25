@php
    $tone = match ($enquiry->status) {
        \App\Enums\EnquiryStatus::New => 'is-new',
        \App\Enums\EnquiryStatus::Replied => 'is-active',
        \App\Enums\EnquiryStatus::Closed => 'is-deleted',
    };
@endphp
<span class="status-pill {{ $tone }}">{{ $enquiry->status->label() }}</span>
