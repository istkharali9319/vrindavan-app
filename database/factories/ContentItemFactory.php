<?php

namespace Database\Factories;

use App\Models\ContentItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContentItem>
 */
class ContentItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'collection' => 'reviews',
            'data' => ['name' => fake()->name(), 'trip' => 'Family stay', 'text' => fake()->sentence(12)],
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
