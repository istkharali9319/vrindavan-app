<?php

use App\Models\ContentItem;
use Database\Seeders\SiteContentSeeder;
use Illuminate\Database\Migrations\Migration;

/**
 * The home page returns to the original static design: no highlights strip, the original feature text,
 * the original photos in the nearby carousel and its own experience tiles. Admin edits are kept.
 */
return new class extends Migration
{
    /**
     * Feature text from the short-lived redesign, mapped to the original wording.
     *
     * @var array<string, array{0: string, 1: string}>
     */
    private const FEATURE_TEXT = [
        'Pure Alpine Freshness' => [
            'The day begins cold and clear, with first light spilling across the Kanchenjunga range. Pine-scented air drifts through the valley — best enjoyed from your balcony with a warm cup of Temi tea.',
            'The day begins cold and clear, with first light spilling across the Kanchenjunga range above the ridge. Pine-scented air drifts through the valley — best enjoyed from your balcony with a warm cup of Temi tea and the mountains slowly coming alive.',
        ],
        'Adventure, Unhurried' => [
            'Riverside walks, village trails, the old Silk Route and high-altitude lakes. We arrange each day around your rhythm — never a checklist, always an invitation to wander a little further.',
            'From riverside walks and village trails to the old Silk Route and high-altitude lakes, the mountains are yours to explore. We arrange each day around your rhythm — never a checklist, always an invitation to wander a little further.',
        ],
        'Heritage Hospitality' => [
            "Three generations of our family have grown up among these hills. Grandmother's recipes still reach the table, and our staff will remember how you take your tea.",
            "Three generations of our family have grown up among these hills. Our grandmother's recipes still find their way to the table, handwritten notes wait in your room, and our staff will remember how you take your tea — sometimes before you ask.",
        ],
    ];

    /**
     * @var array<string, string>
     */
    private const PLACE_HOME_PHOTOS = [
        'Lampokhari (Aritar) Emerald Lake' => 'images/near-lampokhari.jpg',
        'Mankhim Ridge & 360° Kanchenjunga Belvederes' => 'images/near-mankhim.jpg',
        'The Legendary Old Silk Route & Zuluk Loops' => 'images/near-silk-route.jpg',
        'Reshi Riverbank Sanctuary & Boulder Walk' => 'images/near-reshi.jpg',
    ];

    public function up(): void
    {
        ContentItem::where('collection', 'home_highlights')->delete();

        foreach (ContentItem::where('collection', 'home_features')->get() as $feature) {
            [$redesign, $original] = self::FEATURE_TEXT[$feature->get('title')] ?? [null, null];

            if ($redesign !== null && $feature->get('text') === $redesign) {
                $feature->update(['data' => [...$feature->data, 'text' => $original]]);
            }
        }

        foreach (ContentItem::where('collection', 'places')->get() as $place) {
            $photo = self::PLACE_HOME_PHOTOS[$place->get('title')] ?? null;

            if ($photo !== null && ! $place->has('home_image')) {
                $place->update(['data' => [...$place->data, 'home_image' => $photo]]);
            }
        }

        // Fills the new "Experience tiles" list (only lists that are still empty are touched).
        (new SiteContentSeeder)->run();
    }

    public function down(): void
    {
        ContentItem::where('collection', 'home_experiences')->delete();
    }
};
