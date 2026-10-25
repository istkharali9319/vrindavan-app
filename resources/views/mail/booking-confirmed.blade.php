<x-mail::message>
# Your stay is confirmed

Namaste {{ $booking->guest_first_name ?: str($booking->contact_name)->before(' ') }}, thank you for booking with {{ config('resort.name') }}. Here are your booking details — your invoice **{{ $invoiceNumber }}** is attached as a PDF.

<x-mail::panel>
**Booking reference: {{ $booking->reference }}**
</x-mail::panel>

<x-mail::table>
| Your stay | |
|:--|--:|
| **Guest** | {{ $booking->guestFullName() }} |
| **Email** | {{ $booking->contact_email }} |
| **Mobile** | {{ $booking->contact_phone }} |
| **Room** | {{ $booking->roomType->name }} · room {{ $booking->room->number }} |
| **Check-in** | {{ $booking->check_in->format('D, j M Y') }}, from {{ \Carbon\Carbon::parse(config('booking.check_in_time'))->format('g:i A') }} |
| **Check-out** | {{ $booking->check_out->format('D, j M Y') }}, by {{ \Carbon\Carbon::parse(config('booking.check_out_time'))->format('g:i A') }} |
| **Guests** | {{ $booking->guestSummary() }} |
| **Booked on** | {{ $booking->created_at->timezone(config('booking.timezone'))->format('j M Y, g:i A') }} |
</x-mail::table>

<x-mail::table>
| Charges | |
|:--|--:|
| {{ $booking->roomType->name }} · {{ $booking->formattedNightlyPrice() }} × {{ $booking->nights }} {{ str('night')->plural($booking->nights) }} | {{ \App\Support\Money::format($booking->room_amount) }} |
@foreach ($booking->addOns as $addOn)
| {{ $addOn->name }} · {{ $addOn->breakdown() }} | {{ $addOn->amount === 0 ? 'Free' : $addOn->formattedAmount() }} |
@endforeach
| **Total (GST included)** | **{{ $booking->formattedTotal() }}** |
| Payment | {{ $booking->payment_status->label() }} |
</x-mail::table>

<x-mail::button :url="$url">
View your booking
</x-mail::button>

Need anything before you arrive? Reply to this email or call {{ config('resort.phones.0.display') }} with your booking reference.

Warm regards,<br>
{{ config('resort.name') }}
</x-mail::message>
