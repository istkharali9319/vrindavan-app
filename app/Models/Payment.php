<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One Razorpay checkout attempt (one Razorpay order) for a booking.
 * Status: created → captured, or failed. "refund_due" means the money arrived after the room was released.
 */
class Payment extends Model
{
    public const CREATED = 'created';

    public const CAPTURED = 'captured';

    public const FAILED = 'failed';

    public const REFUND_DUE = 'refund_due';

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function formattedAmount(): string
    {
        return Money::format($this->amount);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::CAPTURED => 'Paid',
            self::FAILED => 'Failed',
            self::REFUND_DUE => 'Refund due',
            default => 'Started',
        };
    }
}
