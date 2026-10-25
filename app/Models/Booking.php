<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Support\Money;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Status, payment status and amounts are set by the booking actions, never from request data.
 */
#[Fillable(['contact_name', 'guest_title', 'guest_first_name', 'guest_last_name', 'contact_email', 'contact_phone', 'gst_number', 'special_requests'])]
class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory;

    /**
     * Crockford base32 without the ambiguous I, L, O and U.
     */
    protected const REFERENCE_ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'check_in' => 'immutable_date',
            'check_out' => 'immutable_date',
            'nights' => 'integer',
            'adults' => 'integer',
            'children' => 'integer',
            'nightly_price' => 'integer',
            'room_amount' => 'integer',
            'total_amount' => 'integer',
            'status' => BookingStatus::class,
            'payment_status' => PaymentStatus::class,
            'payment_method' => PaymentMethod::class,
            'hold_expires_at' => 'datetime',
            'paid_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * A reference such as "VRN-7K3QX9PD" that can't be guessed by counting.
     */
    public static function generateReference(): string
    {
        do {
            $code = '';
            for ($i = 0; $i < 8; $i++) {
                $code .= self::REFERENCE_ALPHABET[random_int(0, strlen(self::REFERENCE_ALPHABET) - 1)];
            }
            $reference = 'VRN-'.$code;
        } while (self::where('reference', $reference)->exists());

        return $reference;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    /**
     * @return BelongsTo<RoomType, $this>
     */
    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    /**
     * @return BelongsTo<Room, $this>
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * @return HasMany<RoomNight, $this>
     */
    public function nights(): HasMany
    {
        return $this->hasMany(RoomNight::class);
    }

    /**
     * @return HasMany<BookingAddOn, $this>
     */
    public function addOns(): HasMany
    {
        return $this->hasMany(BookingAddOn::class);
    }

    public function addOnsAmount(): int
    {
        return $this->total_amount - $this->room_amount;
    }

    /**
     * Booked without signing in: no account is attached.
     */
    public function isGuestCheckout(): bool
    {
        return $this->user_id === null;
    }

    public function guestFullName(): string
    {
        return trim(($this->guest_title ? $this->guest_title.' ' : '').$this->contact_name);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('id');
    }

    public function isAwaitingPayment(): bool
    {
        return $this->status === BookingStatus::PendingPayment;
    }

    /**
     * Seconds left to pay before the held room is released (0 once expired).
     */
    public function holdSecondsLeft(): int
    {
        return $this->isAwaitingPayment() && $this->hold_expires_at
            ? max(0, (int) now()->diffInSeconds($this->hold_expires_at, false))
            : 0;
    }

    public function isCancelled(): bool
    {
        return $this->status === BookingStatus::Cancelled;
    }

    /**
     * Guests can cancel a confirmed booking until the check-in date.
     */
    public function canBeCancelled(): bool
    {
        return $this->status === BookingStatus::Confirmed
            && $this->check_in->greaterThanOrEqualTo(now(config('booking.timezone'))->startOfDay());
    }

    public function isUpcoming(): bool
    {
        return $this->status === BookingStatus::Confirmed
            && $this->check_out->greaterThan(now(config('booking.timezone'))->startOfDay());
    }

    public function formattedTotal(): string
    {
        return Money::format($this->total_amount);
    }

    public function formattedNightlyPrice(): string
    {
        return Money::format($this->nightly_price);
    }

    public function guestSummary(): string
    {
        $summary = $this->adults.' '.str('adult')->plural($this->adults);

        return $this->children > 0
            ? $summary.', '.$this->children.' '.str('child')->plural($this->children)
            : $summary;
    }
}
