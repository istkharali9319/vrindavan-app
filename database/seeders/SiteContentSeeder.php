<?php

namespace Database\Seeders;

use App\Models\ContentItem;
use App\Support\WebsiteContent;
use Illuminate\Database\Seeder;

/**
 * Fills each website list (experiences, photos, reviews…) with its starting content.
 *
 * Only empty lists are filled, so running it again never overwrites admin edits.
 */
class SiteContentSeeder extends Seeder
{
    public function run(): void
    {
        foreach ((new WebsiteContent)->collections() as $key => $collection) {
            if (ContentItem::where('collection', $key)->exists()) {
                continue;
            }

            foreach ($collection['items'] as $sortOrder => $data) {
                ContentItem::create([
                    'collection' => $key,
                    'data' => $data,
                    'sort_order' => $sortOrder + 1,
                    'is_active' => true,
                ]);
            }
        }
    }
}
