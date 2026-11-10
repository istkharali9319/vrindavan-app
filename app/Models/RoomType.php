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

#[Fillable(['name', 'slug', 'tagline', 'badge', 'description', 'amenities', 'bed_type', 'room_size', 'services', 'nightly_price', 'max_adults', 'max_children', 'sort_order', 'is_active', 'is_coming_soon'])]
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
            'is_coming_soon' => 'boolean',
            'amenities' => 'array',
            'services' => 'array',
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
     * Shown on the website and open for booking (not "coming soon").
     */
    public function isBookable(): bool
    {
        return $this->is_active && ! $this->is_coming_soon;
    }

    /**
     * How many rooms of this type a group needs, from the admin's per-room limits
     * (e.g. 2 adults and 1 child per room: 3 adults need 2 rooms, 5 adults need 3).
     * Every room needs at least one adult. Null when this type can't take the group at all.
     */
    public function roomsNeededFor(int $adults, int $children = 0): ?int
    {
        if ($adults < 1 || $this->max_adults < 1 || ($children > 0 && $this->max_children < 1)) {
            return null;
        }

        $rooms = max(
            (int) ceil($adults / $this->max_adults),
            $children > 0 ? (int) ceil($children / $this->max_children) : 0,
        );

        return $rooms <= $adults ? $rooms : null;
    }

    /**
     * Rooms to book: what the group needs, or more if the guest asked for more rooms
     * (never more than one room per adult). Null when this type can't take the group.
     */
    public function roomsToBook(int $adults, int $children = 0, ?int $requestedRooms = null): ?int
    {
        $needed = $this->roomsNeededFor($adults, $children);

        return $needed === null ? null : max($needed, min((int) $requestedRooms, $adults));
    }

    /**
     * Why this type can't take the group, or null when it can.
     */
    public function occupancyProblem(int $adults, int $children = 0): ?string
    {
        if ($this->roomsNeededFor($adults, $children) !== null) {
            return null;
        }

        return $children > 0 && $this->max_children < 1
            ? "The {$this->name} doesn't take children."
            : "The {$this->name} takes up to {$this->max_children} ".str('child')->plural($this->max_children).' per room, and each room needs an adult. Please add an adult or choose another room.';
    }

    /**
     * Per-room limits for the "Guests & Rooms" picker: the strictest among room types guests can book now,
     * so the rooms it suggests always fit (2 adults and 1 child when nothing is bookable).
     *
     * @return array{adults: int, children: int}
     */
    public static function pickerLimits(): array
    {
        $bookable = self::active()->where('is_coming_soon', false)->get(['max_adults', 'max_children']);

        return $bookable->isEmpty()
            ? ['adults' => 2, 'children' => 1]
            : ['adults' => max(1, (int) $bookable->min('max_adults')), 'children' => (int) $bookable->min('max_children')];
    }

    /**
     * "2 adults and 1 child per room".
     */
    public function perRoomSummary(): string
    {
        $summary = $this->max_adults.' '.str('adult')->plural($this->max_adults);

        return ($this->max_children > 0 ? $summary.' and '.$this->max_children.' '.str('child')->plural($this->max_children) : $summary).' per room';
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

    /**
     * @return list<string>
     */
    public function serviceList(): array
    {
        return array_values(array_filter($this->services ?? []));
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
