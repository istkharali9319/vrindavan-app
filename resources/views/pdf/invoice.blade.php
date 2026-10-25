@php
    use App\Support\Money;
    $logo = public_path('images/brand/logo.png');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>{{ $invoiceNumber }}</title>
<style>
  @page { margin: 34px 40px; }
  * { font-family: "DejaVu Sans", sans-serif; }
  body { color: #1d201d; font-size: 11px; line-height: 1.45; }
  .head { width: 100%; border-bottom: 2px solid #a87b45; padding-bottom: 14px; margin-bottom: 18px; }
  .head td { vertical-align: top; }
  .logo { height: 44px; }
  .title { text-align: right; }
  .title h1 { margin: 0; font-size: 22px; letter-spacing: 3px; color: #14231b; }
  .muted { color: #6b6a64; }
  .badge { display: inline-block; margin-top: 6px; padding: 3px 10px; border-radius: 10px; background: #e5eee6; color: #1d6b46; font-size: 10px; font-weight: bold; }
  .badge.cancelled { background: #f8e6e3; color: #9b2c20; }
  .parties { width: 100%; margin-bottom: 18px; }
  .parties td { width: 50%; vertical-align: top; padding-right: 16px; }
  .label { font-size: 9px; letter-spacing: 1.5px; text-transform: uppercase; color: #8f6735; margin-bottom: 4px; }
  .stay { width: 100%; border-collapse: collapse; margin-bottom: 18px; background: #f6f3ed; }
  .stay td { padding: 9px 12px; }
  .lines { width: 100%; border-collapse: collapse; }
  .lines th { padding: 8px 10px; background: #14231b; color: #fff; font-size: 9.5px; letter-spacing: 1px; text-transform: uppercase; text-align: left; }
  .lines td { padding: 9px 10px; border-bottom: 1px solid #e4ded3; }
  .num { text-align: right; white-space: nowrap; }
  .totals { width: 46%; margin-left: 54%; margin-top: 10px; border-collapse: collapse; }
  .totals td { padding: 6px 10px; }
  .totals .grand td { border-top: 2px solid #14231b; font-size: 14px; font-weight: bold; }
  .note { margin-top: 26px; padding-top: 12px; border-top: 1px solid #e4ded3; font-size: 9.5px; }
</style>
</head>
<body>
  <table class="head">
    <tr>
      <td>
        @if (is_file($logo))
          <img src="{{ $logo }}" class="logo" alt="{{ $business['name'] }}">
        @else
          <strong style="font-size:18px">{{ $business['name'] }}</strong>
        @endif
        <div class="muted" style="margin-top:6px">{{ $business['address'] }}</div>
        <div class="muted">{{ $business['phone'] }} · {{ $business['email'] }}</div>
        @if ($business['gstin'])
          <div style="margin-top:4px"><strong>GSTIN:</strong> {{ $business['gstin'] }}</div>
        @endif
      </td>
      <td class="title">
        <h1>INVOICE</h1>
        <div style="margin-top:6px"><strong>{{ $invoiceNumber }}</strong></div>
        <div class="muted">Issued {{ $issuedOn->format('j M Y') }}</div>
        <div class="muted">Booking {{ $booking->reference }}</div>
        @if ($booking->isCancelled())
          <span class="badge cancelled">CANCELLED</span>
        @else
          <span class="badge">{{ strtoupper($booking->payment_status->label()) }}</span>
        @endif
      </td>
    </tr>
  </table>

  <table class="parties">
    <tr>
      <td>
        <div class="label">Billed to</div>
        <strong>{{ $booking->guestFullName() }}</strong><br>
        {{ $booking->contact_email }}<br>
        {{ $booking->contact_phone }}
        @if ($booking->gst_number)
          <br>GSTIN: {{ $booking->gst_number }}
        @endif
      </td>
      <td>
        <div class="label">Stay</div>
        <strong>{{ $booking->roomType->name }} · Room {{ $booking->room->number }}</strong><br>
        {{ $booking->check_in->format('D, j M Y') }} → {{ $booking->check_out->format('D, j M Y') }}<br>
        {{ $booking->nights }} {{ str('night')->plural($booking->nights) }} · {{ $booking->guestSummary() }}
      </td>
    </tr>
  </table>

  <table class="lines">
    <thead>
      <tr><th>Description</th><th class="num">Rate</th><th class="num">Qty</th><th class="num">Amount</th></tr>
    </thead>
    <tbody>
      <tr>
        <td>{{ $booking->roomType->name }} — room {{ $booking->room->number }}<br><span class="muted">{{ $booking->check_in->format('j M') }} – {{ $booking->check_out->format('j M Y') }}</span></td>
        <td class="num">{{ Money::format($booking->nightly_price) }} / night</td>
        <td class="num">{{ $booking->nights }}</td>
        <td class="num">{{ Money::format($booking->room_amount) }}</td>
      </tr>
      @foreach ($booking->addOns as $addOn)
        <tr>
          <td>{{ $addOn->name }}<br><span class="muted">{{ $addOn->price_unit->label() }}</span></td>
          <td class="num">{{ Money::format($addOn->unit_price) }}</td>
          <td class="num">{{ $addOn->quantity }}</td>
          <td class="num">{{ $addOn->amount === 0 ? 'Free' : Money::format($addOn->amount) }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>

  <table class="totals">
    <tr><td>Room</td><td class="num">{{ Money::format($booking->room_amount) }}</td></tr>
    @if ($booking->addOns->isNotEmpty())
      <tr><td>Experiences &amp; add-ons</td><td class="num">{{ Money::format($booking->addOnsAmount()) }}</td></tr>
    @endif
    <tr><td class="muted">GST</td><td class="num muted">Included</td></tr>
    <tr class="grand"><td>Total</td><td class="num">{{ Money::format($booking->total_amount) }}</td></tr>
    <tr><td class="muted">Payment</td><td class="num muted">{{ $booking->payment_status->label() }}</td></tr>
  </table>

  <div class="note">
    {{ $business['note'] }}<br>
    Check-in from {{ \Carbon\Carbon::parse(config('booking.check_in_time'))->format('g:i A') }} · check-out by {{ \Carbon\Carbon::parse(config('booking.check_out_time'))->format('g:i A') }}.
  </div>
</body>
</html>
