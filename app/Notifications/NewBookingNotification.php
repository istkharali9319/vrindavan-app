<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Tells the team about a new booking, in the admin's notification panel.
 */
class NewBookingNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Booking $booking)
    {
        $this->afterCommit();
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $booking = $this->booking->loadMissing(['roomType', 'room', 'addOns']);

        return [
            'kind' => 'booking.new',
            'icon' => 'calendar-plus',
            'title' => "New booking {$booking->reference} · {$booking->formattedTotal()}",
            'body' => $booking->guestFullName().' ('.$booking->contact_phone.') · '.$booking->roomType->name.', room '.$booking->room->number
                .' · '.$booking->check_in->format('j M').' → '.$booking->check_out->format('j M Y').' · '.$booking->guestSummary()
                .($booking->addOns->isNotEmpty() ? ' · '.$booking->addOns->count().' add-on(s)' : '')
                .' · '.$booking->payment_status->label().($booking->isGuestCheckout() ? ' · guest checkout' : ''),
            'url' => route('admin.bookings.show', $booking),
            'reference' => $booking->reference,
            'amount' => $booking->total_amount,
            'payment_status' => $booking->payment_status->value,
        ];
    }
}
