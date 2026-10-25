<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

/**
 * Sent to guests whose account was created from their booking: a link to choose a password.
 */
class AccountSetupNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public const LINK_DAYS = 7;

    public function __construct(public Booking $booking)
    {
        $this->afterCommit();
    }

    /**
     * @return list<string>
     */
    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    public static function setupUrl(User $user): string
    {
        return URL::temporarySignedRoute('account.setup', now()->addDays(self::LINK_DAYS), ['user' => $user->id]);
    }

    public function toMail(User $notifiable): MailMessage
    {
        $resort = config('resort.name');

        return (new MailMessage)
            ->subject("Your {$resort} account is ready")
            ->greeting('Namaste '.str($notifiable->name)->before(' ').',')
            ->line("We've created a {$resort} account for **{$notifiable->email}** so you can see booking **{$this->booking->reference}** and any future stays in one place.")
            ->line('Choose a password to sign in. From your account you can view your bookings, download invoices and cancel if your plans change.')
            ->action('Set my password', self::setupUrl($notifiable))
            ->line('This link works for '.self::LINK_DAYS.' days. If it expires, use "Forgot password" on the sign-in page.')
            ->salutation("Warm regards,  \n".$resort);
    }
}
