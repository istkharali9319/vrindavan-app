<?php

namespace App\Support;

use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Sends a plain test email right away (not queued), so a mail-settings problem shows its real cause.
 */
class MailCheck
{
    /**
     * @return string|null The mail server's error, or null when the email was handed over successfully.
     */
    public static function send(string $to): ?string
    {
        $mailer = config('mail.default');

        if ($mailer === 'smtp' && blank(config('mail.mailers.smtp.password'))) {
            return 'MAIL_PASSWORD is empty in .env. Type the mailbox password there first.';
        }

        try {
            Mail::raw(
                'This is a test email from the '.config('resort.name')." website.\n\n"
                ."If you can read this, booking confirmations, invoices, welcome and password-reset emails can be delivered.\n\n"
                .'Sent '.now(config('booking.timezone'))->format('j M Y, g:i A').' using '.$mailer
                .($mailer === 'smtp' ? ' ('.config('mail.mailers.smtp.host').':'.config('mail.mailers.smtp.port').')' : '').'.',
                fn (Message $message) => $message->to($to)->subject('Test email · '.config('resort.name')),
            );
        } catch (Throwable $exception) {
            report($exception);

            return $exception->getMessage();
        }

        return $mailer === 'log' ? 'Mail is set to "log", so the email was written to storage/logs/laravel.log instead of being sent.' : null;
    }
}
