<?php

namespace App\Notifications;

use App\Http\Controllers\Booking\BookingController;
use App\Models\Booking;
use App\Support\BookingInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * The guest's confirmation. Sent by email (with the PDF invoice) to the booking's contact email, and,
 * for guests with an account, also shown in their notifications.
 */
class BookingConfirmedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Booking $booking)
    {
        $this->afterCommit();
    }

    /**
     * Email for the contact address (sent on demand); the in-system notice for the account holder.
     *
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return $notifiable instanceof AnonymousNotifiable ? ['mail'] : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $booking = $this->booking->loadMissing(['roomType', 'room', 'addOns']);
        $invoice = BookingInvoice::for($booking);

        return (new MailMessage)
            ->subject("Booking confirmed · {$booking->reference} · ".config('resort.name'))
            ->markdown('mail.booking-confirmed', [
                'booking' => $booking,
                'invoiceNumber' => $invoice->number(),
                'url' => $booking->user_id
                    ? route('account.bookings.show', $booking->reference)
                    : BookingController::confirmationUrl($booking),
            ])
            ->attachData($invoice->pdf(), $invoice->filename(), ['mime' => 'application/pdf']);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $booking = $this->booking->loadMissing('roomType');

        return [
            'kind' => 'booking.confirmed',
            'icon' => 'calendar-check',
            'title' => "Your booking {$booking->reference} is confirmed",
            'body' => $booking->roomType->name.' · '.$booking->check_in->format('j M').' → '.$booking->check_out->format('j M Y').' · '.$booking->formattedTotal(),
            'url' => route('account.bookings.show', $booking->reference),
            'reference' => $booking->reference,
        ];
    }
}
