@extends('layouts.site')

@section('title', 'Book your stay · '.config('resort.name'))
@section('description', 'Check room availability and book your stay at '.config('resort.name').', Rhenock, East Sikkim.')

@section('content')
<main class="booking-flow">
  @include('booking.partials.steps', ['current' => 1, 'heading' => 'Book Your Stay'])

  <div class="container booking-page">
  <p class="lead-text booking-lead">Pick your dates to see which rooms are free. All tariffs are per room, per night, including GST.</p>

  <form method="GET" action="{{ route('booking.search') }}" class="panel stay-form" aria-label="Check availability">
    @if ($selectedType)
      <input type="hidden" name="room_type" value="{{ $selectedType }}">
    @endif
    <div @class(['form-field', 'has-error' => $errors->has('check_in')])>
      <label for="check_in">Check-in</label>
      <input id="check_in" name="check_in" type="date" value="{{ $input['check_in'] }}"
             min="{{ $earliestCheckIn->toDateString() }}" max="{{ $latestCheckIn->toDateString() }}" required>
    </div>
    <div @class(['form-field', 'has-error' => $errors->has('check_out')])>
      <label for="check_out">Check-out</label>
      <input id="check_out" name="check_out" type="date" value="{{ $input['check_out'] }}"
             min="{{ $earliestCheckIn->addDay()->toDateString() }}" required>
    </div>
    <div @class(['form-field', 'guest-field', 'has-error' => $errors->hasAny(['adults', 'children', 'rooms'])])>
      <x-guest-picker :adults="$input['adults']" :children="$input['children']" :rooms="$input['rooms']" data-auto-submit />
    </div>
    <button type="submit" class="btn btn-forest">Check availability</button>
  </form>

  @include('booking.partials.errors')

  @if ($stay)
    <p class="stay-summary">
      <strong>{{ $stay['check_in']->format('D, j M Y') }}</strong> → <strong>{{ $stay['check_out']->format('D, j M Y') }}</strong>
      · {{ $stay['nights'] }} {{ str('night')->plural($stay['nights']) }}
      · {{ $stay['query']['adults'] }} {{ str('adult')->plural((int) $stay['query']['adults']) }}@if ($stay['query']['children']), {{ $stay['query']['children'] }} {{ str('child')->plural((int) $stay['query']['children']) }}@endif
      · Check-in {{ \Carbon\Carbon::parse(config('booking.check_in_time'))->format('g:i A') }}, check-out {{ \Carbon\Carbon::parse(config('booking.check_out_time'))->format('g:i A') }}
    </p>
  @endif

  <div class="room-options">
    @foreach ($roomTypes as $roomType)
      @php
          // Rooms this group needs in this type (from the admin's per-room limits), or more if they asked for more.
          $roomsCount = $roomType->roomsToBook((int) $input['adults'], (int) $input['children'], $input['rooms'] !== null ? (int) $input['rooms'] : null);
      @endphp
      <article @class(['room-option', 'is-selected' => $selectedType === $roomType->slug]) id="{{ $roomType->slug }}">
        <div class="room-option-media"><img src="{{ $roomType->imageUrl() }}" alt="{{ $roomType->name }}" loading="lazy"></div>
        <div class="room-option-body">
          <p class="tag">Rooms {{ $roomType->roomNumberRange() }}</p>
          <h2>{{ $roomType->name }}</h2>
          <p>{{ $roomType->description }}</p>
          <p class="room-option-meta">Up to {{ $roomType->perRoomSummary() }} · Children under {{ config('booking.child_free_below_age') }} stay free</p>
        </div>
        <div class="room-option-price">
          <div class="price-block"><small>Per night</small><strong>{{ $roomType->formattedPrice() }}</strong><em>(Including GST)</em></div>

          @if ($roomType->is_coming_soon)
            <span class="badge-soft warn">Coming soon</span>
          @elseif ($stay)
            @if ($roomsCount === null)
              <span class="badge-soft warn">Doesn't fit your group</span>
              <p class="room-option-note">{{ $roomType->occupancyProblem((int) $input['adults'], (int) $input['children']) }}</p>
            @elseif ($roomType->free_rooms === 0)
              <span class="badge-soft warn">Fully booked</span>
            @elseif ($roomType->free_rooms < $roomsCount)
              <span class="badge-soft warn">Only {{ $roomType->free_rooms }} {{ str('room')->plural($roomType->free_rooms) }} left</span>
              <p class="room-option-note">Your group needs {{ $roomsCount }} rooms for these dates.</p>
            @else
              @php $stayAmount = $roomType->nightly_price * $stay['nights'] * $roomsCount; @endphp
              <p class="room-allocation">
                <strong>{{ $roomsCount }} {{ str('room')->plural($roomsCount) }}</strong>
                for {{ $input['adults'] }} {{ str('adult')->plural((int) $input['adults']) }}@if ((int) $input['children'] > 0) and {{ $input['children'] }} {{ str('child')->plural((int) $input['children']) }}@endif
              </p>
              <p class="room-option-total">
                {{ \App\Support\Money::format($stayAmount) }} for {{ $stay['nights'] }} {{ str('night')->plural($stay['nights']) }}
                <span>{{ $roomType->free_rooms }} {{ str('room')->plural($roomType->free_rooms) }} left</span>
              </p>
              <a href="{{ route('booking.create', [$roomType, ...$stay['query'], 'rooms' => $roomsCount]) }}" class="btn btn-forest"
                 data-book-room data-room-name="{{ $roomType->name }}" data-room-amount="{{ $stayAmount }}">Book {{ $roomsCount > 1 ? $roomsCount.' rooms' : 'this room' }} <span class="arrow">→</span></a>
            @endif
          @else
            <a href="#check_in" class="text-link">Choose dates to check availability</a>
          @endif
        </div>
      </article>
    @endforeach
  </div>
  </div>

  @if ($stay && $addOns->isNotEmpty())
    <dialog class="addon-modal" data-addon-modal aria-labelledby="addon-modal-title">
      <form method="dialog" class="addon-modal-inner">
        <header class="addon-modal-head">
          <div>
            <p class="eyebrow">Enhance your stay</p>
            <h2 id="addon-modal-title">Add experiences to your <span data-modal-room>room</span></h2>
            <p>{{ $stay['check_in']->format('D, j M') }} → {{ $stay['check_out']->format('D, j M Y') }} · {{ $stay['nights'] }} {{ str('night')->plural($stay['nights']) }} · {{ $guests }} {{ str('guest')->plural($guests) }}. Optional — settled at the resort.</p>
          </div>
          <button type="submit" value="close" class="addon-modal-close" aria-label="Close">✕</button>
        </header>

        <div class="addon-list addon-modal-list">
          @foreach ($addOns as $addOn)
            @php $amount = $addOn->price * $addOn->quantityFor($guests, $stay['nights']); @endphp
            <label class="addon-card">
              <input type="checkbox" value="{{ $addOn->id }}" data-addon data-name="{{ $addOn->title }}" data-amount="{{ $amount }}">
              <span class="addon-card-inner">
                <img src="{{ $addOn->imageUrl() }}" alt="" loading="lazy">
                <span class="addon-card-body">
                  <strong>{{ $addOn->title }}</strong>
                  @if ($addOn->description)
                    <small>{{ \Illuminate\Support\Str::limit($addOn->description, 120) }}</small>
                  @endif
                  <span class="addon-card-price">
                    @if ($addOn->price)<span class="stay-price">{{ \App\Support\Money::format($addOn->price) }}</span> {{ $addOn->price_unit->label() }}@else{{ $addOn->priceLabel() }}@endif
                    @if (! $addOn->isComplimentary() && $addOn->price_unit !== \App\Enums\PriceUnit::PerBooking)
                      <em>· <span class="stay-price">{{ \App\Support\Money::format($amount) }}</span> for your stay</em>
                    @endif
                  </span>
                </span>
                <span class="addon-card-check" aria-hidden="true"></span>
              </span>
            </label>
          @endforeach
        </div>

        <footer class="addon-modal-foot">
          <div class="addon-modal-total">
            <small>Room <span data-modal-room-amount></span><span data-modal-extras></span></small>
            <strong data-modal-total></strong>
          </div>
          <div class="addon-modal-actions">
            <button type="button" class="btn btn-outline" data-modal-skip>Skip</button>
            <button type="button" class="btn btn-forest" data-modal-continue>Continue <span class="arrow">→</span></button>
          </div>
        </footer>
      </form>
    </dialog>
  @endif
</main>
@endsection

@push('scripts')
  <script>
    (function () {
      const modal = document.querySelector('[data-addon-modal]');
      if (!modal || typeof modal.showModal !== 'function') return; // without the pop-up, the button just continues

      const boxes = modal.querySelectorAll('[data-addon]');
      const rupees = (paise) => '₹' + Math.round(paise / 100).toLocaleString('en-IN');
      let target = null;

      const update = () => {
        let extras = 0;
        let count = 0;
        boxes.forEach((box) => {
          box.closest('.addon-card').classList.toggle('is-selected', box.checked);
          if (box.checked) { extras += Number(box.dataset.amount); count++; }
        });
        const room = Number(target?.dataset.roomAmount || 0);
        modal.querySelector('[data-modal-room-amount]').textContent = rupees(room);
        modal.querySelector('[data-modal-extras]').textContent = count ? ` + ${count} ${count === 1 ? 'extra' : 'extras'} ${rupees(extras)}` : '';
        modal.querySelector('[data-modal-total]').textContent = rupees(room + extras);
        modal.querySelector('[data-modal-continue]').firstChild.textContent = count ? `Continue with ${count} ${count === 1 ? 'extra' : 'extras'} ` : 'Continue ';
      };

      const go = (withExtras) => {
        const url = new URL(target.href);
        url.searchParams.delete('add_ons[]');
        if (withExtras) {
          boxes.forEach((box) => box.checked && url.searchParams.append('add_ons[]', box.value));
        }
        window.location.assign(url.toString());
      };

      document.querySelectorAll('[data-book-room]').forEach((link) => {
        link.addEventListener('click', (event) => {
          event.preventDefault();
          target = link;
          modal.querySelector('[data-modal-room]').textContent = link.dataset.roomName;
          update();
          modal.showModal();
        });
      });

      boxes.forEach((box) => box.addEventListener('change', update));
      modal.querySelector('[data-modal-skip]').addEventListener('click', () => go(false));
      modal.querySelector('[data-modal-continue]').addEventListener('click', () => go(true));
      // Click on the dimmed background closes the pop-up.
      modal.addEventListener('click', (event) => { if (event.target === modal) modal.close(); });

      // "Change extras" on the details page brings the guest back with the pop-up open.
      const reopen = new URLSearchParams(location.search);
      if (reopen.get('extras') === '1' && reopen.get('room_type')) {
        const link = [...document.querySelectorAll('[data-book-room]')].find((a) => a.href.includes('/book/' + reopen.get('room_type') + '?'));
        // Previously chosen extras come back as add_ons[]=… or add_ons[0]=….
        [...reopen.entries()].filter(([key]) => key.startsWith('add_ons[')).forEach(([, id]) => {
          const box = modal.querySelector(`[data-addon][value="${CSS.escape(id)}"]`);
          if (box) box.checked = true;
        });
        link?.click();
      }
    })();
  </script>
@endpush
