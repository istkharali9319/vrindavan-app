<?php

namespace App\Models;

use App\Enums\PriceUnit;
use App\Support\Money;
use App\Support\WebsiteContent;
use Database\Factories\ExperienceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * An experience or extra. It can be listed on the Experiences page, offered as a booking add-on, or both.
 */
#[Fillable(['title', 'slug', 'kicker', 'badge', 'layout', 'categories', 'description', 'highlights', 'schedule', 'link_label', 'price', 'price_unit', 'show_on_page', 'offer_as_addon', 'is_active', 'sort_order'])]
class Experience extends Model
{
    /** @use HasFactory<ExperienceFactory> */
    use HasFactory;

    /**
     * @var array<string, string>
     */
    public const CATEGORIES = [
        'culinary' => 'Culinary & Craft',
        'village' => 'Village & Heritage',
        'nature' => 'Nature & Forest Trails',
        'twilight' => 'Twilight & Bonfire',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'categories' => 'array',
            'highlights' => 'array',
            'price' => 'integer',
            'price_unit' => PriceUnit::class,
            'show_on_page' => 'boolean',
            'offer_as_addon' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @param  Builder<Experience>  $query
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @param  Builder<Experience>  $query
     */
    public function scopeOnPage(Builder $query): void
    {
        $query->where('is_active', true)->where('show_on_page', true);
    }

    /**
     * Active add-ons with a price that can be chosen while booking.
     *
     * @param  Builder<Experience>  $query
     */
    public function scopeBookableAddOns(Builder $query): void
    {
        $query->where('is_active', true)->where('offer_as_addon', true)->whereNotNull('price');
    }

    public function imageUrl(): string
    {
        return WebsiteContent::imageUrl($this->image_path);
    }

    public function isComplimentary(): bool
    {
        return $this->price === 0;
    }

    /**
     * "₹1,500 per guest", "Complimentary" or null when the price is on request.
     */
    public function priceLabel(): ?string
    {
        return match (true) {
            $this->price === null => null,
            $this->price === 0 => 'Complimentary',
            default => Money::format($this->price).' '.$this->price_unit->label(),
        };
    }

    public function quantityFor(int $guests, int $nights): int
    {
        return $this->price_unit->quantity($guests, $nights);
    }

    /**
     * @return list<string>
     */
    public function highlightList(): array
    {
        return array_values(array_filter($this->highlights ?? []));
    }
}
