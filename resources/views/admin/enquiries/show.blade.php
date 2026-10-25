@extends('layouts.admin')

@use('App\Enums\EnquiryChannel')
@use('App\Enums\EnquiryMessageType')
@use('App\Enums\EnquiryStatus')

@section('title', 'Enquiry from '.$enquiry->name)
@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('admin.enquiries.index') }}">Enquiries</a></li>
  <li class="breadcrumb-item" aria-current="page">{{ $enquiry->name }}</li>
@endsection

@section('actions')
  <a href="{{ route('admin.enquiries.index') }}" class="btn btn-outline-secondary"><i class="ti ti-arrow-left"></i> All enquiries</a>
@endsection

@php
    $timezone = config('booking.timezone');
    $phoneDigits = preg_replace('/\D/', '', (string) $enquiry->phone);
    $firstName = \Illuminate\Support\Str::of($enquiry->name)->before(' ');
    $local = fn ($date) => $date->timezone($timezone);
    $time = fn ($date) => $local($date)->format('g:i A');
    $dayLabel = function ($date) use ($local, $timezone) {
        $day = $local($date)->startOfDay();
        $today = now($timezone)->startOfDay();

        return match (true) {
            $day->equalTo($today) => 'Today',
            $day->equalTo($today->copy()->subDay()) => 'Yesterday',
            default => $day->format('l, j F Y'),
        };
    };
    $conversation = $enquiry->messages->where('type', '!=', EnquiryMessageType::StatusChange);
    $lastActivity = $enquiry->messages->last()?->created_at ?? $enquiry->created_at;
    $firstReply = $enquiry->messages->firstWhere('type', EnquiryMessageType::Reply);
    $responseTime = $firstReply ? $enquiry->created_at->diffForHumans($firstReply->created_at, ['syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE, 'parts' => 1]) : null;
    $steps = [EnquiryStatus::New, EnquiryStatus::Replied, EnquiryStatus::Closed];
    $currentStep = array_search($enquiry->status, $steps, true);
    $replySubject = rawurlencode('Re: Your enquiry to '.config('resort.name'));
@endphp

@section('content')
<section @class(['enquiry-hero', 'is-'.$enquiry->status->value])>
  <span class="enquiry-hero-avatar">{{ $enquiry->initials() }}</span>
  <div class="enquiry-hero-text">
    <p class="enquiry-hero-eyebrow">Enquiry #{{ $enquiry->id }} · received {{ $enquiry->created_at->diffForHumans() }}</p>
    <h2>{{ $enquiry->name }}</h2>
    <ul class="enquiry-hero-contact">
      <li><i class="ti ti-mail"></i> {{ $enquiry->email }}</li>
      @if ($enquiry->phone)
        <li><i class="ti ti-phone"></i> {{ $enquiry->phone }}</li>
      @endif
      <li><i class="ti ti-calendar"></i> {{ $local($enquiry->created_at)->format('j M Y, g:i A') }}</li>
    </ul>
  </div>
  <div class="enquiry-hero-side">
    <span class="enquiry-hero-status"><i class="ti {{ match ($enquiry->status) { EnquiryStatus::New => 'ti-mail', EnquiryStatus::Replied => 'ti-mail-forward', EnquiryStatus::Closed => 'ti-archive' } }}"></i> {{ $enquiry->status->label() }}</span>
    <div class="enquiry-hero-actions">
      <a href="mailto:{{ $enquiry->email }}?subject={{ $replySubject }}" class="btn btn-light-cream"><i class="ti ti-mail"></i> Email</a>
      @if ($enquiry->phone)
        <a href="tel:{{ $enquiry->phone }}" class="btn btn-glass"><i class="ti ti-phone"></i> Call</a>
        <a href="https://wa.me/{{ $phoneDigits }}" target="_blank" rel="noopener" class="btn btn-glass"><i class="ti ti-brand-whatsapp"></i> WhatsApp</a>
      @endif
    </div>
  </div>
</section>

<div class="row g-4">
  <div class="col-xl-8">
    <div class="card conversation" id="conversation">
      <div class="card-header conversation-head">
        <div>
          <h5 class="mb-0">Conversation</h5>
          <small class="text-muted">{{ $conversation->count() + 1 }} {{ str('message')->plural($conversation->count() + 1) }} · last activity {{ $lastActivity->diffForHumans() }}</small>
        </div>
        <div class="conversation-legend">
          <span><i class="legend-swatch is-guest"></i> {{ $firstName }}</span>
          <span><i class="legend-swatch is-staff"></i> Team</span>
          <span><i class="legend-swatch is-note"></i> Notes</span>
        </div>
      </div>

      <div class="card-body conversation-thread">
        @php $currentDay = $dayLabel($enquiry->created_at); @endphp
        <div class="chat-day"><span>{{ $currentDay }}</span></div>

        {{-- The guest's original message --}}
        <div class="chat-entry is-guest">
          <span class="chat-avatar">{{ $enquiry->initials() }}</span>
          <div class="chat-bubble">
            <div class="chat-meta"><strong>{{ $enquiry->name }}</strong> <span class="chat-chip"><i class="ti ti-world"></i> Website form</span></div>
            <div class="chat-text">{{ $enquiry->message }}</div>
            <time datetime="{{ $enquiry->created_at->toIso8601String() }}">{{ $time($enquiry->created_at) }}</time>
          </div>
        </div>

        @foreach ($enquiry->messages as $message)
          @if ($dayLabel($message->created_at) !== $currentDay)
            @php $currentDay = $dayLabel($message->created_at); @endphp
            <div class="chat-day"><span>{{ $currentDay }}</span></div>
          @endif

          @switch($message->type)
            @case(EnquiryMessageType::StatusChange)
              <div class="chat-event">
                <span><i class="ti ti-flag"></i> {{ $message->author?->name ?? 'Staff' }} marked this <strong>{{ $message->status?->label() }}</strong> · {{ $time($message->created_at) }}</span>
              </div>
              @break
            @case(EnquiryMessageType::Note)
              <div class="chat-note">
                <div class="chat-meta"><i class="ti ti-lock"></i> <strong>Internal note</strong> · {{ $message->author?->name ?? 'Staff' }}</div>
                <div class="chat-text">{{ $message->body }}</div>
                <time datetime="{{ $message->created_at->toIso8601String() }}">{{ $time($message->created_at) }}</time>
              </div>
              @break
            @default
              @php $isGuest = $message->type === EnquiryMessageType::Guest; @endphp
              <div @class(['chat-entry', 'is-guest' => $isGuest, 'is-staff' => ! $isGuest])>
                <span class="chat-avatar">{{ $isGuest ? $enquiry->initials() : ($message->author?->initials() ?? 'VR') }}</span>
                <div class="chat-bubble">
                  <div class="chat-meta">
                    <strong>{{ $isGuest ? $enquiry->name : ($message->author?->name ?? config('resort.name')) }}</strong>
                    @if ($message->channel)
                      <span class="chat-chip"><i class="ti {{ $message->channel->icon() }}"></i> {{ $message->channel->label() }}</span>
                    @endif
                  </div>
                  <div class="chat-text">{{ $message->body }}</div>
                  <time datetime="{{ $message->created_at->toIso8601String() }}">{{ $time($message->created_at) }}</time>
                </div>
              </div>
          @endswitch
        @endforeach
      </div>

      <div class="conversation-composer">
        <form method="POST" action="{{ route('admin.enquiries.messages.store', $enquiry) }}" data-enquiry-composer>
          @csrf
          <div class="composer-types" role="radiogroup" aria-label="What are you adding?">
            @foreach (EnquiryMessageType::writable() as $type)
              <label class="composer-type is-{{ $type->value }}">
                <input type="radio" name="type" value="{{ $type->value }}" @checked(old('type', EnquiryMessageType::Reply->value) === $type->value)>
                <span><i class="ti {{ match ($type) { EnquiryMessageType::Reply => 'ti-send', EnquiryMessageType::Guest => 'ti-message-circle', default => 'ti-lock' } }}"></i> {{ $type->label() }}</span>
              </label>
            @endforeach
          </div>

          <div class="composer-box @error('body') has-error @enderror">
            <textarea name="body" rows="3" class="composer-text"
                      placeholder="Write your reply to {{ $firstName }}, what they told you, or a note for the team…">{{ old('body') }}</textarea>
            <div class="composer-bar">
              <div class="composer-field" data-channel-field>
                <label for="channel">Sent by</label>
                <select id="channel" name="channel" class="form-select form-select-sm">
                  @foreach (EnquiryChannel::cases() as $channel)
                    <option value="{{ $channel->value }}" @selected(old('channel', EnquiryChannel::Email->value) === $channel->value)>{{ $channel->label() }}</option>
                  @endforeach
                </select>
              </div>
              <div class="composer-field">
                <label for="status">Status after saving</label>
                <select id="status" name="status" class="form-select form-select-sm" data-current="{{ $enquiry->status->value }}">
                  @foreach (EnquiryStatus::cases() as $status)
                    <option value="{{ $status->value }}" @selected(old('status', $enquiry->status->value) === $status->value)>{{ $status->label() }}</option>
                  @endforeach
                </select>
              </div>
              <div class="composer-actions">
                <a href="mailto:{{ $enquiry->email }}" class="btn btn-outline-secondary btn-sm" data-open-email
                   data-subject="{{ 'Re: Your enquiry to '.config('resort.name') }}" title="Opens your email app with this text"><i class="ti ti-mail-forward"></i> Open in email</a>
                <button type="submit" class="btn btn-primary btn-sm"><i class="ti ti-send"></i> Save</button>
              </div>
            </div>
          </div>
          @error('body')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </form>
      </div>
    </div>
  </div>

  <div class="col-xl-4">
    <div class="card enquiry-status-card">
      <div class="card-header"><h5 class="mb-0">Status</h5></div>
      <div class="card-body">
        <ol class="status-track">
          @foreach ($steps as $index => $step)
            <li @class(['is-done' => $index < $currentStep, 'is-current' => $index === $currentStep])>
              <span class="status-track-dot">
                @if ($index < $currentStep)<i class="ti ti-check"></i>@else{{ $index + 1 }}@endif
              </span>
              <span>{{ $step->label() }}</span>
            </li>
          @endforeach
        </ol>

        <div class="status-quick">
          @php
              $quickActions = match ($enquiry->status) {
                  EnquiryStatus::New => [['replied', 'ti-mail-forward', 'Mark as replied', true], ['closed', 'ti-archive', 'Mark as closed', false]],
                  EnquiryStatus::Replied => [['closed', 'ti-archive', 'Mark as closed', true]],
                  EnquiryStatus::Closed => [['new', 'ti-rotate', 'Reopen as new', false]],
              };
          @endphp
          @foreach ($quickActions as [$status, $icon, $label, $isPrimary])
            <form method="POST" action="{{ route('admin.enquiries.messages.store', $enquiry) }}">
              @csrf
              <input type="hidden" name="type" value="note">
              <input type="hidden" name="status" value="{{ $status }}">
              <button type="submit" @class(['btn', 'btn-sm', 'w-100', 'btn-primary' => $isPrimary, 'btn-outline-secondary' => ! $isPrimary])><i class="ti {{ $icon }}"></i> {{ $label }}</button>
            </form>
          @endforeach
        </div>

        <dl class="enquiry-facts">
          <div><dt>Messages</dt><dd>{{ $conversation->count() + 1 }}</dd></div>
          <div><dt>First reply</dt><dd>{{ $responseTime ? 'after '.$responseTime : 'Not yet' }}</dd></div>
          <div><dt>Last activity</dt><dd>{{ $lastActivity->diffForHumans() }}</dd></div>
          <div><dt>Handled by</dt><dd>{{ $enquiry->handler?->name ?? '—' }}</dd></div>
        </dl>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h5 class="mb-0">Contact details</h5></div>
      <div class="card-body">
        <ul class="profile-meta">
          <li><i class="ti ti-user"></i><span>Name</span><strong>{{ $enquiry->name }}</strong></li>
          <li><i class="ti ti-mail"></i><span>Email</span><strong class="text-break"><a href="mailto:{{ $enquiry->email }}">{{ $enquiry->email }}</a></strong></li>
          <li><i class="ti ti-phone"></i><span>Phone</span><strong>{{ $enquiry->phone ?: 'Not given' }}</strong></li>
        </ul>
      </div>
    </div>

    @if ($previous->isNotEmpty())
      <div class="card">
        <div class="card-header"><h5 class="mb-0">Earlier enquiries from {{ $firstName }}</h5></div>
        <div class="card-body p-0">
          <div class="enquiry-list">
            @foreach ($previous as $earlier)
              <a href="{{ route('admin.enquiries.show', $earlier) }}" class="enquiry-row">
                <span class="enquiry-row-body"><span class="enquiry-row-message">{{ \Illuminate\Support\Str::limit($earlier->message, 90) }}</span></span>
                <span class="enquiry-row-side">
                  @include('admin.enquiries.partials.status', ['enquiry' => $earlier])
                  <small>{{ $local($earlier->created_at)->format('d M Y') }}</small>
                </span>
              </a>
            @endforeach
          </div>
        </div>
      </div>
    @endif

    @if (config('admin.deletes_enabled'))
      <div class="card booking-danger">
        <div class="card-body">
          <h6><i class="ti ti-trash"></i> Delete enquiry</h6>
          <p>Removes this message and its whole conversation for good. To keep it out of the way instead, mark it Closed.</p>
          <form method="POST" action="{{ route('admin.enquiries.destroy', $enquiry) }}" onsubmit="return confirm('Delete this enquiry and its conversation for good?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger w-100">Delete enquiry</button>
          </form>
        </div>
      </div>
    @endif
  </div>
</div>
@endsection

@push('scripts')
  <script>
    (function () {
      const form = document.querySelector('[data-enquiry-composer]');
      if (!form) return;
      const channelField = form.querySelector('[data-channel-field]');
      const status = form.querySelector('#status');
      const text = form.querySelector('textarea[name="body"]');
      const email = form.querySelector('[data-open-email]');

      const sync = () => {
        const type = form.querySelector('input[name="type"]:checked')?.value;
        channelField.hidden = type === 'note';
        email.hidden = type !== 'reply';
        // Replying to a new enquiry usually means it is now answered.
        if (type === 'reply' && status.dataset.current === 'new' && status.value === 'new') {
          status.value = 'replied';
        }
      };
      form.querySelectorAll('input[name="type"]').forEach((radio) => radio.addEventListener('change', sync));
      sync();

      email.addEventListener('click', (event) => {
        event.preventDefault();
        const subject = encodeURIComponent(email.dataset.subject);
        const body = encodeURIComponent(text.value);
        window.open(`${email.getAttribute('href')}?subject=${subject}&body=${body}`, '_blank');
      });

      // Keep the newest message in view.
      const thread = document.querySelector('.conversation-thread');
      thread.scrollTop = thread.scrollHeight;
    })();
  </script>
@endpush
