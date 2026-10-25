@extends('layouts.site')

@use('App\Http\Requests\Booking\StoreBookingRequest')
@use('App\Support\Money')

@section('title', 'Personal details · '.config('resort.name'))
@section('description', 'Complete your booking at '.config('resort.name').'.')

@php
    $content = app(\App\Support\WebsiteContent::class)->for('booking');
    $checkInTime = \Carbon\Carbon::parse(config('booking.check_in_time'))->format('g:i A');
    $checkOutTime = \Carbon\Carbon::parse(config('booking.check_out_time'))->format('g:i A');
    [$firstName, $lastName] = array_pad(explode(' ', (string) $user?->name, 2), 2, '');
    $mobile = $user?->phone && str_starts_with($user->phone, '+91') ? substr($user->phone, 3) : $user?->phone;
@endphp

@section('content')
<main class="booking-flow">
  @include('booking.partials.steps', ['current' => 2, 'heading' => $content->text('details.title')])

  <div class="container booking-flow-body">
    <p class="back-link"><a href="{{ route('booking.search', $stay) }}" class="text-link">← Change room or dates</a></p>

    @include('booking.partials.errors')

    <div class="booking-grid">
      <section class="guest-panel">
        <h2>{{ $content->text('details.form_title') }}</h2>
        <p class="guest-panel-intro">{{ $content->text('details.form_intro') }}</p>

        <form method="POST" action="{{ route('booking.store', $roomType) }}" class="guest-form" novalidate>
          @csrf
          @foreach (['check_in', 'check_out', 'adults', 'children'] as $field)
            <input type="hidden" name="{{ $field }}" value="{{ $stay[$field] }}">
          @endforeach

          <div class="guest-row is-name">
            <div @class(['line-field', 'has-error' => $errors->has('title')])>
              <label for="title">Title<span>*</span></label>
              <select id="title" name="title" required>
                <option value="" disabled @selected(! old('title'))>Select</option>
                @foreach (StoreBookingRequest::TITLES as $title)
                  <option value="{{ $title }}" @selected(old('title') === $title)>{{ $title }}</option>
                @endforeach
              </select>
              @error('title')<small class="field-error">{{ $message }}</small>@enderror
            </div>
            <div @class(['line-field', 'has-error' => $errors->has('first_name')])>
              <label for="first_name">First Name<span>*</span></label>
              <input id="first_name" name="first_name" type="text" value="{{ old('first_name', $firstName) }}" autocomplete="given-name" maxlength="60" required>
              @error('first_name')<small class="field-error">{{ $message }}</small>@enderror
            </div>
            <div @class(['line-field', 'has-error' => $errors->has('last_name')])>
              <label for="last_name">Last Name<span>*</span></label>
              <input id="last_name" name="last_name" type="text" value="{{ old('last_name', $lastName) }}" autocomplete="family-name" maxlength="60" required>
              @error('last_name')<small class="field-error">{{ $message }}</small>@enderror
            </div>
          </div>

          <div class="guest-row is-two">
            <div @class(['line-field', 'has-error' => $errors->has('contact_email')])>
              <label for="contact_email">Email<span>*</span></label>
              <input id="contact_email" name="contact_email" type="email" value="{{ old('contact_email', $user?->email) }}" autocomplete="email" maxlength="191" required>
              @error('contact_email')<small class="field-error">{{ $message }}</small>@enderror
            </div>
            <div @class(['line-field', 'has-error' => $errors->has('contact_phone') || $errors->has('mobile')])>
              <label for="mobile">Mobile Number<span>*</span></label>
              <div class="phone-input">
                <select name="country_code" aria-label="Country code">
                  @foreach (StoreBookingRequest::COUNTRY_CODES as $code => $country)
                    <option value="{{ $code }}" @selected(old('country_code', '+91') === $code)>{{ $code }} {{ $country }}</option>
                  @endforeach
                </select>
                <input id="mobile" name="mobile" type="tel" value="{{ old('mobile', $mobile) }}" autocomplete="tel-national" inputmode="tel" maxlength="20" required>
              </div>
              @error('contact_phone')<small class="field-error">{{ $message }}</small>@enderror
              @error('mobile')<small class="field-error">{{ $message }}</small>@enderror
            </div>
          </div>

          @if ($content->text('details.gst_field') === 'shown')
            <div class="guest-row is-two">
              <div @class(['line-field', 'has-error' => $errors->has('gst_number')])>
                <label for="gst_number">GST Number <em>(optional)</em></label>
                <input id="gst_number" name="gst_number" type="text" value="{{ old('gst_number') }}" maxlength="15" autocapitalize="characters" placeholder="For a GST invoice">
                @error('gst_number')<small class="field-error">{{ $message }}</small>@enderror
              </div>
            </div>
          @endif

          <div @class(['line-field', 'is-area', 'has-error' => $errors->has('special_requests')])>
            <label for="special_requests">Special Requests</label>
            <textarea id="special_requests" name="special_requests" rows="2" maxlength="500" data-counter="special-count"
                      placeholder="Arrival time, dietary needs, a cake for a birthday…">{{ old('special_requests') }}</textarea>
            <small class="char-count"><span id="special-count">{{ mb_strlen((string) old('special_requests')) }}</span>/500</small>
            @error('special_requests')<small class="field-error">{{ $message }}</small>@enderror
          </div>

          @foreach ($addOns as $addOn)
            <input type="hidden" name="add_ons[]" value="{{ $addOn->id }}">
          @endforeach
          @if ($errors->has('add_ons') || $errors->has('add_ons.*'))
            <small class="field-error">{{ $errors->first('add_ons') ?: $errors->first('add_ons.*') }}</small>
          @endif

          @if (count($paymentMethods) > 1)
            @php $chosenMethod = old('payment_method', $paymentMethods[0]->value); @endphp
            <fieldset @class(['pay-choice', 'has-error' => $errors->has('payment_method')])>
              <legend>How would you like to pay?</legend>
              @foreach ($paymentMethods as $method)
                <label class="pay-option">
                  <input type="radio" name="payment_method" value="{{ $method->value }}" @checked($chosenMethod === $method->value) data-button="{{ $method === \App\Enums\PaymentMethod::Online ? 'Continue to payment' : $content->text('details.button') }}">
                  <span class="pay-option-body">
                    @if ($method === \App\Enums\PaymentMethod::Online)
                      <strong>Pay now online <em class="pay-badge">Secure</em></strong>
                      <small>UPI, cards, net banking or wallets through Razorpay. Your room is confirmed instantly.</small>
                      <span class="pay-logos" aria-hidden="true"><b>UPI</b><b>VISA</b><b>Mastercard</b><b>RuPay</b><b>Net banking</b></span>
                    @else
                      <strong>Pay at the resort</strong>
                      <small>Reserve now and pay the full amount when you check in.</small>
                    @endif
                  </span>
                </label>
              @endforeach
              @error('payment_method')<small class="field-error">{{ $message }}</small>@enderror
            </fieldset>
          @else
            <input type="hidden" name="payment_method" value="{{ $paymentMethods[0]->value }}">
          @endif

          <label @class(['terms-check', 'has-error' => $errors->has('terms')])>
            <input type="checkbox" name="terms" value="1" @checked(old('terms'))>
            <span>{{ $content->text('details.terms') }} <a href="#cancellation-policy">Read the policy</a></span>
          </label>
          @error('terms')<small class="field-error">{{ $message }}</small>@enderror

          <div class="guest-submit">
            <button type="submit" class="btn btn-forest" data-pay-button><span data-pay-label>{{ old('payment_method', $paymentMethods[0]->value) === 'online' ? 'Continue to payment' : $content->text('details.button') }}</span> <span class="arrow">→</span></button>
            @php $onlineNote = 'You\'ll pay securely with Razorpay on the next step. Your room is held for '.config('booking.hold_minutes').' minutes.'; @endphp
            <p data-pay-note data-online="{{ $onlineNote }}" data-later="{{ $content->text('details.payment_note') }}">{{ old('payment_method', $paymentMethods[0]->value) === 'online' ? $onlineNote : $content->text('details.payment_note') }}</p>
          </div>

          @guest
            <p class="guest-account-note">Have an account? <a href="{{ route('login', ['return' => \App\Support\ReturnTo::currentPath(request())]) }}">Sign in</a> to keep this booking in My stays. It's optional — you can book as a guest.</p>
          @endguest
        </form>
      </section>

      <aside class="booking-side">
        <section class="stay-card-box">
          <h2>Your Stay</h2>
          <div class="stay-card-room">
            <img src="{{ $roomType->imageUrl() }}" alt="{{ $roomType->name }}">
            <div>
              <strong>Room 1 : {{ $stay['adults'] }} {{ str('Adult')->plural((int) $stay['adults']) }}@if ($stay['children']), {{ $stay['children'] }} {{ str('Child')->plural($stay['children']) }}@endif</strong>
              <span>{{ $roomType->name }}</span>
            </div>
          </div>
          <dl class="stay-card-dates">
            <div><dt>Check-in</dt><dd>{{ $checkIn->format('D, j M Y') }}<small>from {{ $checkInTime }}</small></dd></div>
            <div><dt>Check-out</dt><dd>{{ $checkOut->format('D, j M Y') }}<small>by {{ $checkOutTime }}</small></dd></div>
          </dl>
          <div class="stay-card-lines">
            <div><span>{{ $roomType->formattedPrice() }} × {{ $nights }} {{ str('night')->plural($nights) }}</span><span>{{ Money::format($total) }}</span></div>
            @foreach ($addOns as $addOn)
              @php $addOnAmount = $addOn->price * $addOn->quantityFor($guests, $nights); @endphp
              <div class="is-addon"><span>+ {{ $addOn->title }}<small class="stay-card-sub">{{ $addOn->priceLabel() }}</small></span><span>{{ $addOnAmount === 0 ? 'Free' : Money::format($addOnAmount) }}</span></div>
            @endforeach
            <a class="stay-card-change" href="{{ route('booking.search', [...$stay, 'room_type' => $roomType->slug, 'extras' => 1, 'add_ons' => $addOns->pluck('id')->all()]) }}">{{ $addOns->isEmpty() ? '+ Add experiences & extras' : 'Change extras' }}</a>
            <div class="is-muted"><span>Taxes (GST)</span><span>Included</span></div>
          </div>
          <div class="stay-card-total"><span>Total Amount</span><strong>{{ Money::format($total + $addOns->sum(fn ($addOn) => $addOn->price * $addOn->quantityFor($guests, $nights))) }}</strong></div>
        </section>

        <section class="stay-card-box is-policy" id="cancellation-policy">
          <h3>{{ $content->text('policy.title') }}</h3>
          @foreach ($content->paragraphs('policy.text') as $paragraph)
            <p>{{ \App\Support\WebsiteContent::html($paragraph) }}</p>
          @endforeach
        </section>
      </aside>
    </div>
  </div>
</main>
@endsection
