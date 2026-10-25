<?php

namespace App\Support;

use Laravel\Fortify\Fortify;

/**
 * Turns flashed status keys (from Fortify and our own controllers) into guest-friendly sentences.
 * Anything not listed (e.g. translated password-reset messages) is shown as-is.
 */
class StatusMessage
{
    public static function for(?string $status): ?string
    {
        return match ($status) {
            null, '' => null,
            Fortify::VERIFICATION_LINK_SENT => 'A new verification link is on its way to your inbox.',
            Fortify::PROFILE_INFORMATION_UPDATED => 'Your profile has been updated.',
            Fortify::PASSWORD_UPDATED => 'Your password has been changed.',
            Fortify::TWO_FACTOR_AUTHENTICATION_ENABLED => 'Scan the QR code with your authenticator app, then enter the 6-digit code to finish.',
            Fortify::TWO_FACTOR_AUTHENTICATION_CONFIRMED => 'Two-factor authentication is on. Keep your recovery codes somewhere safe.',
            Fortify::TWO_FACTOR_AUTHENTICATION_DISABLED => 'Two-factor authentication has been turned off.',
            Fortify::RECOVERY_CODES_GENERATED => 'New recovery codes have been generated. The old ones no longer work.',
            'account-deleted' => 'Your account has been deleted. We hope to welcome you back to the hills one day.',
            'booking-confirmed' => 'Your booking is confirmed. We look forward to welcoming you to the hills.',
            'enquiry-sent' => 'Thank you! Your message has reached us. We usually reply within a few hours.',
            'booking-cancelled' => 'Your booking has been cancelled and the room released.',
            default => $status,
        };
    }
}
