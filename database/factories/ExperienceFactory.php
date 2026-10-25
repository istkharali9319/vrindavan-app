<?php

namespace Database\Factories;

use App\Enums\PriceUnit;
use App\Models\Experience;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Experience>
 */
class ExperienceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = Str::title(fake()->unique()->words(3, true));

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'layout' => 'feature',
            'categories' => ['nature'],
            'description' => fake()->sentence(12),
            'highlights' => ['A highlight'],
            'schedule' => 'Daily',
            'price' => 150000,
            'price_unit' => PriceUnit::PerGuest,
            'show_on_page' => true,
            'offer_as_addon' => false,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function addOn(int $price = 150000, PriceUnit $unit = PriceUnit::PerBooking): static
    {
        return $this->state(fn () => ['offer_as_addon' => true, 'price' => $price, 'price_unit' => $unit]);
    }
}
