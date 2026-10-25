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
    <div @class(['form-field', 'has-error' => $errors->has('adults')])>
      <label for="adults">Adults</label>
      <input id="adults" name="adults" type="number" min="1" max="12" value="{{ $input['adults'] }}" required>
    </div>
    <div @class(['form-field', 'has-error' => $errors->has('children')])>
      <label for="children">Children</label>
      <input id="children" name="children" type="number" min="0" max="8" value="{{ $input['children'] }}">
    </div>
    <button type="submit" class="btn btn-forest">Check availability</button>
  </form>

  @include('booking.partials.errors')

  @if ($stay)
    <p class="stay-summary">
      <strong>{{ $stay['check_in']->format('D, j M Y') }}</strong> → <strong>{{ $stay['check_out']->format('D, j M Y') }}</strong>
      · {{ $stay['nights'] }} {{ str('night')->plural($stay['nights']) }}
      · Check-in {{ \Carbon\Carbon::parse(config('booking.check_in_time'))->format('g:i A') }}, check-out {{ \Carbon\Carbon::parse(config('booking.check_out_time'))->format('g:i A') }}
    </p>
  @endif

  <div class="room-options">
    @foreach ($roomTypes as $roomType)
      @php
          $fitsGuests = (int) $input['adults'] <= $roomType->max_adults && (int) $input['children'] <= $roomType->max_children;
      @endphp
      <article @class(['room-option', 'is-selected' => $selectedType === $roomType->slug]) id="{{ $roomType->slug }}">
        <div class="room-option-media"><img src="{{ $roomType->imageUrl() }}" alt="{{ $roomType->name }}" loading="lazy"></div>
        <div class="room-option-body">
          <p class="tag">Rooms {{ $roomType->roomNumberRange() }}</p>
          <h2>{{ $roomType->name }}</h2>
          <p>{{ $roomType->description }}</p>
          <p class="room-option-meta">{{ $roomType->capacitySummary() }} · Children under {{ config('booking.child_free_below_age') }} stay free</p>
        </div>
        <div class="room-option-price">
          <div class="price-block"><small>Per night</small><strong>{{ $roomType->formattedPrice() }}</strong><em>(With GST)</em></div>

          @if ($stay)
            @if ($roomType->free_rooms === 0)
              <span class="badge-soft warn">Fully booked</span>
            @elseif (! $fitsGuests)
              <span class="badge-soft warn">Too many guests for one room</span>
            @else
              <p class="room-option-total">
                {{ \App\Support\Money::format($roomType->nightly_price * $stay['nights']) }} for {{ $stay['nights'] }} {{ str('night')->plural($stay['nights']) }}
                <span>{{ $roomType->free_rooms }} {{ str('room')->plural($roomType->free_rooms) }} left</span>
              </p>
              <a href="{{ route('booking.create', [$roomType, ...$stay['query']]) }}" class="btn btn-forest"
                 data-book-room data-room-name="{{ $roomType->name }}" data-room-amount="{{ $roomType->nightly_price * $stay['nights'] }}">Book this room <span class="arrow">→</span></a>
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
                    {{ $addOn->priceLabel() }}
                    @if (! $addOn->isComplimentary() && $addOn->price_unit !== \App\Enums\PriceUnit::PerBooking)
                      <em>· {{ \App\Support\Money::format($amount) }} for your stay</em>
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
