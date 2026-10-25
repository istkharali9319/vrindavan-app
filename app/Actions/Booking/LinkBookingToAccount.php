<?php

namespace App\Actions\Booking;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\Booking;
use App\Models\User;
use App\Support\GuestDevice;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Finds or creates the customer account for a guest who books without signing in, so the booking is
 * saved with that user_id and shows under My stays.
 *
 * - No account with that email yet: one is created, without a usable password. The guest chooses a
 *   password from the "set your password" email, which also proves the email is theirs.
 * - A verified, active customer account: the booking is added to it.
 * - An account still waiting for its password, when this browser is the one that was signed in to it
 *   after the earlier booking (GuestDevice): the booking is added to it.
 * - Anything else (unverified, staff, suspended or deleted account): the booking stays a guest booking,
 *   so nobody can see someone else's bookings by registering with their email address.
 *   It is linked later, once that email address is verified.
 */
class LinkBookingToAccount
{
    /**
     * The account a signed-out guest's booking belongs to, found or created from the details they typed
     * (before the booking itself is made). Null keeps the booking as a guest booking.
     *
     * @param  array{contact_name: string, contact_email: string, contact_phone: string, guest_first_name?: string|null, guest_last_name?: string|null}  $contact
     */
    public function forGuest(array $contact): ?User
    {
        $email = strtolower(trim($contact['contact_email']));
        $existing = User::withTrashed()->where('email', $email)->first();

        if ($existing !== null && $this->isAbandoned($existing)) {
            $this->remove($existing);
            $existing = null;
        }

        if ($existing === null) {
            return $this->createAccount(
                trim(($contact['guest_first_name'] ?? '').' '.($contact['guest_last_name'] ?? '')) ?: $contact['contact_name'],
                $email,
                $contact['contact_phone'],
            );
        }

        return $this->canReceiveBookings($existing) ? $existing : null;
    }

    /**
     * Fallback for a booking that was still saved without an account (e.g. confirmed by the payment webhook).
     */
    public function handle(Booking $booking): ?User
    {
        if ($booking->user_id !== null) {
            return $booking->user;
        }

        $user = $this->forGuest([
            'contact_name' => $booking->contact_name,
            'contact_email' => $booking->contact_email,
            'contact_phone' => $booking->contact_phone,
            'guest_first_name' => $booking->guest_first_name,
            'guest_last_name' => $booking->guest_last_name,
        ]);

        if ($user !== null) {
            $booking->forceFill(['user_id' => $user->id])->save();
            $booking->setRelation('user', $user);
        }

        return $user;
    }

    /**
     * Gives every guest booking made with this (now verified) email address to the account.
     */
    public static function claimGuestBookings(User $user): int
    {
        if ($user->isStaff() || ! $user->hasVerifiedEmail()) {
            return 0;
        }

        return Booking::query()
            ->whereNull('user_id')
            ->where('contact_email', strtolower($user->email))
            ->update(['user_id' => $user->id]);
    }

    /**
     * A booking account nobody ever claimed and that no longer has any bookings (they were removed).
     * It holds nothing, so it is replaced by a fresh account for the new booking.
     */
    private function isAbandoned(User $user): bool
    {
        return ! $user->trashed()
            && $user->isUnclaimedBookingAccount()
            && ! $user->isStaff()
            && $user->bookings()->doesntExist();
    }

    private function remove(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $user->notifications()->delete();
            $user->syncRoles([]);
            DB::table('sessions')->where('user_id', $user->id)->delete();
            $user->forceDelete();
        });
    }

    /**
     * An account for this email that is still waiting for its owner to set a password.
     */
    public static function unclaimedAccountFor(string $email): ?User
    {
        $user = User::where('email', strtolower(trim($email)))->first();

        return $user?->isUnclaimedBookingAccount() ? $user : null;
    }

    private function canReceiveBookings(User $user): bool
    {
        return ! $user->trashed()
            && ! $user->isStaff()
            && $user->status === UserStatus::Active
            && ($user->hasVerifiedEmail() || ($user->isUnclaimedBookingAccount() && GuestDevice::owns($user)));
    }

    private function createAccount(string $name, string $email, string $phone): ?User
    {
        try {
            return DB::transaction(function () use ($name, $email, $phone): User {
                $user = new User([
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'password' => Str::random(64),
                ]);
                $user->forceFill(['created_from_booking_at' => now()])->save();
                $user->assignRole(Role::Customer->value);

                return $user;
            });
        } catch (UniqueConstraintViolationException) {
            return null;
        }
    }
}
