<?php

namespace Database\Seeders;

use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Room types, tariffs (GST inclusive, per night) and room numbers.
 *
 * Safe to run repeatedly: existing rows are updated, nothing is deleted. Showcase content
 * (tagline, amenities, photos) is only filled in while empty, so edits made in the admin are kept.
 */
class RoomCatalogueSeeder extends Seeder
{
    /**
     * @var list<array{name: string, slug: string, tagline: string, badge: string, description: string, amenities: list<string>, image: string, gallery: list<string>, nightly_price: int, max_adults: int, max_children: int, rooms: list<string>}>
     */
    public const CATALOGUE = [
        [
            'name' => 'Deluxe Room',
            'slug' => 'deluxe-room',
            'tagline' => 'High altitude panorama',
            'badge' => 'Valley Facing',
            'description' => 'Our most spacious rooms, with the best of the valley views and a warm, timber-finished interior.',
            'amenities' => [
                'Floor-to-ceiling picture window onto the Kanchenjunga range',
                'Hand-woven Lepcha throws and goose-feather duvets',
                'Private glass balcony with a Temi tea station',
                'En-suite bathroom with rain shower',
            ],
            'image' => 'suite-main.jpg',
            'gallery' => ['suite-bedroom.jpg', 'suite-tea.jpg', 'suite-balcony.jpg', 'suite-bath.jpg'],
            'nightly_price' => 450000,
            'max_adults' => 3,
            'max_children' => 2,
            'rooms' => ['101', '102', '103', '104', '105'],
        ],
        [
            'name' => 'Standard Room',
            'slug' => 'standard-room',
            'tagline' => 'Cosy mountain comfort',
            'badge' => 'Best Value',
            'description' => 'A comfortable, cosy room with everything you need after a day in the hills.',
            'amenities' => [
                'Queen bed with thick winter duvets',
                'Forest or garden views',
                'Kettle with local Temi tea',
                'En-suite bathroom with hot water',
            ],
            'image' => 'room-kanchenjunga-suite.jpg',
            'gallery' => ['villa-living.jpg', 'villa-dining.jpg', 'villa-deck.jpg', 'villa-lawn.jpg'],
            'nightly_price' => 350000,
            'max_adults' => 2,
            'max_children' => 2,
            'rooms' => ['201', '202', '203', '204', '205'],
        ],
        [
            'name' => 'Separate Family Cottage',
            'slug' => 'family-cottage',
            'tagline' => 'Private garden setting',
            'badge' => 'Independent Retreat',
            'description' => 'A private, independent cottage surrounded by greenery — room for the whole family to spread out.',
            'amenities' => [
                'Private wooden verandah overlooking the foothills',
                "King bed and an adjoining children's loft",
                'River-stone fireplace and brass kettle station',
                'En-suite bathroom with floor heating and rain shower',
            ],
            'image' => 'cottage-main.jpg',
            'gallery' => ['cottage-bedroom.jpg', 'cottage-lounge.jpg', 'cottage-verandah.jpg', 'cottage-bath.jpg'],
            'nightly_price' => 390000,
            'max_adults' => 4,
            'max_children' => 2,
            'rooms' => ['301', '302', '303', '304', '305'],
        ],
    ];

    public function run(): void
    {
        foreach (self::CATALOGUE as $sortOrder => $entry) {
            $roomType = RoomType::updateOrCreate(
                ['slug' => $entry['slug']],
                [
                    'name' => $entry['name'],
                    'description' => $entry['description'],
                    'nightly_price' => $entry['nightly_price'],
                    'max_adults' => $entry['max_adults'],
                    'max_children' => $entry['max_children'],
                    'sort_order' => $sortOrder,
                    'is_active' => true,
                ],
            );

            $this->fillShowcase($roomType, $entry);

            foreach ($entry['rooms'] as $number) {
                Room::updateOrCreate(['number' => $number], ['room_type_id' => $roomType->id]);
            }
        }
    }

    /**
     * @param  array{slug: string, tagline: string, badge: string, amenities: list<string>, image: string, gallery: list<string>}  $entry
     */
    private function fillShowcase(RoomType $roomType, array $entry): void
    {
        $roomType->tagline ??= $entry['tagline'];
        $roomType->badge ??= $entry['badge'];
        $roomType->amenities = $roomType->amenityList() ?: $entry['amenities'];
        $roomType->image_path ??= $this->copyPhoto($entry['image'], $entry['slug']);
        $roomType->gallery = $roomType->gallery ?: array_values(array_filter(
            array_map(fn (string $file): ?string => $this->copyPhoto($file, $entry['slug']), $entry['gallery']),
        ));
        $roomType->save();
    }

    /**
     * Copies a bundled website photo onto the public disk, where admin uploads live too.
     */
    private function copyPhoto(string $file, string $slug): ?string
    {
        $source = public_path('images/'.$file);

        if (! is_file($source)) {
            return null;
        }

        $path = "room-types/{$slug}/{$file}";
        Storage::disk('public')->put($path, file_get_contents($source));

        return $path;
    }
}
