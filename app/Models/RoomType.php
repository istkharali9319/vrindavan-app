<?php

namespace App\Models;

use App\Enums\RoomStatus;
use App\Support\Money;
use Carbon\CarbonInterface;
use Database\Factories\RoomTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'tagline', 'badge', 'description', 'amenities', 'nightly_price', 'max_adults', 'max_children', 'sort_order', 'is_active'])]
class RoomType extends Model
{
    /** @use HasFactory<RoomTypeFactory> */
    use HasFactory;

    /**
     * Shown when a room type has no photo of its own yet.
     */
    public const FALLBACK_IMAGE = 'images/hero-accommodation.jpg';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'nightly_price' => 'integer',
            'max_adults' => 'integer',
            'max_children' => 'integer',
            'is_active' => 'boolean',
            'amenities' => 'array',
            'gallery' => 'array',
        ];
    }

    /**
     * @return HasMany<Room, $this>
     */
    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    /**
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * @param  Builder<RoomType>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Active rooms of this type with no occupied night in [check-in, check-out).
     *
     * @return HasMany<Room, $this>
     */
    public function roomsFreeBetween(CarbonInterface $checkIn, CarbonInterface $checkOut): HasMany
    {
        return $this->rooms()
            ->where('status', RoomStatus::Active)
            ->whereDoesntHave('nights', fn (Builder $query) => $query
                ->where('night_date', '>=', $checkIn->toDateString())
                ->where('night_date', '<', $checkOut->toDateString()))
            ->orderBy('number');
    }

    public function formattedPrice(): string
    {
        return Money::format($this->nightly_price);
    }

    /**
     * Built from the request host (not APP_URL) so uploads show on any local port.
     */
    public function imageUrl(): string
    {
        return asset($this->image_path ? 'storage/'.$this->image_path : self::FALLBACK_IMAGE);
    }

    /**
     * @return list<string>
     */
    public function galleryUrls(): array
    {
        return array_map(fn (string $path): string => asset('storage/'.$path), $this->gallery ?? []);
    }

    /**
     * @return list<string>
     */
    public function amenityList(): array
    {
        return array_values(array_filter($this->amenities ?? []));
    }

    public function capacitySummary(): string
    {
        $summary = 'Sleeps up to '.$this->max_adults.' '.str('adult')->plural($this->max_adults);

        return $this->max_children > 0
            ? $summary.' and '.$this->max_children.' '.str('child')->plural($this->max_children)
            : $summary;
    }

    public function roomNumberRange(): string
    {
        $numbers = $this->rooms->pluck('number')->sort()->values();

        return $numbers->count() > 1 ? $numbers->first().'–'.$numbers->last() : (string) $numbers->first();
    }
}
