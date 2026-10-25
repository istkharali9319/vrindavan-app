<?php

namespace App\Models;

use App\Enums\PriceUnit;
use App\Support\Money;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An add-on chosen with a booking. Name and price are copied, so later price changes don't affect it.
 */
#[Fillable(['experience_id', 'name', 'price_unit', 'unit_price', 'quantity', 'amount'])]
class BookingAddOn extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price_unit' => PriceUnit::class,
            'unit_price' => 'integer',
            'quantity' => 'integer',
            'amount' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * @return BelongsTo<Experience, $this>
     */
    public function experience(): BelongsTo
    {
        return $this->belongsTo(Experience::class);
    }

    /**
     * e.g. "₹1,500 per guest × 3".
     */
    public function breakdown(): string
    {
        return Money::format($this->unit_price).' '.$this->price_unit->label().($this->quantity > 1 ? ' × '.$this->quantity : '');
    }

    public function formattedAmount(): string
    {
        return Money::format($this->amount);
    }
}
