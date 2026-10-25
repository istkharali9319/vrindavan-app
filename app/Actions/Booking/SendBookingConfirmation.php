<?php

namespace App\Actions\Booking;

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\Booking;
use App\Models\User;
use App\Notifications\AccountSetupNotification;
use App\Notifications\BookingConfirmedNotification;
use App\Notifications\NewBookingNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

/**
 * Sends a booking's confirmation email (with PDF invoice) and in-system notifications — once.
 *
 * The booking is "claimed" by stamping confirmation_sent_at in a single update, so a double submit,
 * a retried request or a second call can never send duplicates. A mail problem is logged but never
 * undoes or blocks the booking itself.
 */
class SendBookingConfirmation
{
    public function __construct(private LinkBookingToAccount $linkToAccount) {}

    public function handle(Booking $booking): bool
    {
        $claimed = Booking::whereKey($booking->id)
            ->whereNull('confirmation_sent_at')
            ->update(['confirmation_sent_at' => now()]);

        if ($claimed !== 1) {
            return false;
        }

        $booking->refresh();

        // Guest bookings go into an account (a new one gets a "set your password" email).
        $account = rescue(fn () => $this->linkToAccount->handle($booking));

        rescue(fn () => Notification::route('mail', [$booking->contact_email => $booking->guestFullName()])
            ->notify(new BookingConfirmedNotification($booking)));

        if ($booking->user) {
            rescue(fn () => $booking->user->notify(new BookingConfirmedNotification($booking)));
        }

        // A new account, or one still waiting for its password (this booking joins it once the password is set):
        // either way the guest gets the "set your password" link.
        $waitingAccount = $account ?? LinkBookingToAccount::unclaimedAccountFor($booking->contact_email);

        if ($waitingAccount?->isUnclaimedBookingAccount()) {
            rescue(fn () => $waitingAccount->notify(new AccountSetupNotification($booking)));
        }

        rescue(fn () => Notification::send($this->staffToNotify(), new NewBookingNotification($booking)));

        return true;
    }

    /**
     * Active staff who can see bookings.
     *
     * @return Collection<int, User>
     */
    private function staffToNotify(): Collection
    {
        return User::role(Role::staffValues())
            ->where('status', 'active')
            ->get()
            ->filter(fn (User $user): bool => $user->can(Permission::BookingsView->value))
            ->values();
    }
}
