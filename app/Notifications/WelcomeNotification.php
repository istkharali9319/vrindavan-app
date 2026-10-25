<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent once when a guest creates an account on the website.
 */
class WelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct()
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

    public function toMail(User $notifiable): MailMessage
    {
        $resort = config('resort.name');

        return (new MailMessage)
            ->subject("Welcome to {$resort}")
            ->greeting('Namaste '.str($notifiable->name)->before(' ').',')
            ->line("Thank you for creating your {$resort} account. You can now book in a few clicks, see all your stays in one place and manage your bookings.")
            ->action('Sign in to your account', route('login'))
            ->line('Signing in with: '.$notifiable->email)
            ->line('We look forward to welcoming you to the hills of Rhenock.')
            ->salutation("Warm regards,  \n".$resort);
    }
}
