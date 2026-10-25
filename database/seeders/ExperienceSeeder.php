<?php

namespace Database\Seeders;

use App\Enums\PriceUnit;
use App\Models\Experience;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * The resort's experiences, as first published. Prices start "on request" (null) except the complimentary
 * bonfire; admins set real prices in Admin → Experiences & add-ons. Only runs on an empty table.
 */
class ExperienceSeeder extends Seeder
{
    /**
     * @var list<array<string, mixed>>
     */
    public const EXPERIENCES = [
        ['image_path' => 'images/exp-embers.jpg', 'title' => 'Evenings by the Embers: Bonfire & Lepcha Folk Songs', 'kicker' => null, 'badge' => 'Evening tradition • Complimentary', 'layout' => 'feature', 'categories' => ['twilight'], 'description' => 'As dusk blankets the cardamom valleys of East Sikkim, gather around our open stone fire pit. Listen to ancient folk tales and acoustic songs passed down through generations of Lepcha and Bhutia elders, accompanied by spiced rhododendron wine or hot butter tea.', 'highlights' => ['Handcrafted river stone hearth with crackling pinewood', 'Live acoustic folk flutes & ancestral storytelling', 'Stargazing guidance & warm hand-woven wool blankets'], 'schedule' => 'Daily: 7:00 PM – 9:30 PM', 'link_label' => 'Reserve seat', 'price' => 0],
        ['image_path' => 'images/exp-momo.jpg', 'title' => 'Artisan Momo & Thukpa Masterclass', 'kicker' => 'Culinary heritage', 'badge' => 'Garden to table', 'layout' => 'feature', 'categories' => ['culinary'], 'description' => 'Step into our open-hearth garden kitchen. Learn the subtle art of folding paper-thin momo skins, balancing Himalayan mountain herbs, and preparing slow-simmered broth from heirloom recipes. Fresh cilantro, river spinach, and organic bamboo shoot are plucked straight from our morning gardens.', 'highlights' => ['Hands-on guidance from our head chef & grandmotherly village cook', 'Farm-to-counter seasonal ingredients and organic herbs', 'Fire-roasted Dalle Khursani chili sauce masterclass', 'Personalized keepsake recipe booklet to take home'], 'schedule' => '3:30 PM – 5:00 PM (Pre-booking required)', 'link_label' => 'Inquire & book', 'price' => null],
        ['image_path' => 'images/exp-reshi-angling.jpg', 'title' => 'Guided Angling & Whispers of Reshi Khola', 'kicker' => null, 'badge' => 'River adventure', 'layout' => 'feature', 'categories' => ['nature'], 'description' => 'Follow our seasoned river guides down to the pristine, boulder-strewn waters of the Reshi Khola river. Experience mindful catch-and-release fly fishing for golden Himalayan trout amidst absolute stillness, broken only by rushing currents and melodious river thrushes.', 'highlights' => ['Complete fly-fishing gear and gentle personalized instruction', 'Riverside wicker basket breakfast with fresh sourdough & artisan cheese', 'Quiet shaded spots for riverside journaling and meditation'], 'schedule' => 'Half-day excursion (Morning departure)', 'link_label' => 'Reserve excursion', 'price' => null],
        ['image_path' => 'images/exp-heritage-walk.jpg', 'title' => 'Heritage Village Walk & Sacred Pine Grove', 'kicker' => 'Cultural discovery', 'badge' => 'Community immersion', 'layout' => 'feature', 'categories' => ['village', 'nature'], 'description' => 'Wander through ancestral hamlet paths around Rhenock with our resident village elder. Visit working cardamom plantations, traditional wooden watermills, and a 200-year-old Buddhist prayer flag ridge with sweeping views of the snow-kissed Kanchenjunga range.', 'highlights' => ['Tea invitation at an authentic Sikkimese homestead', 'Introduction to local organic permaculture & native medicinal herbs', 'Gentle blessing ceremony at the peaceful hilltop monastery chorten'], 'schedule' => 'Duration: 2.5 hours • Gentle walking pace', 'link_label' => 'Inquire & book', 'price' => null],
        ['image_path' => 'images/exp-temi-tea.jpg', 'title' => 'Temi Estate Morning Tea Plucking & Tasting', 'kicker' => null, 'badge' => 'Exclusive tasting', 'layout' => 'card', 'categories' => ['culinary', 'nature'], 'description' => "An exclusive dawn excursion to Sikkim's celebrated single-estate organic tea garden. Experience the delicate art of two leaves and a bud harvesting followed by a private tea sommelier cupping.", 'highlights' => ['Private chauffeur roundtrip transfer', 'Master taster session with first-flush samples', 'Complimentary bespoke Vrindavan tea tin'], 'schedule' => '4 Hours • Morning', 'link_label' => 'Details', 'price' => null],
        ['image_path' => 'images/exp-stargazing.jpg', 'title' => 'Twilight Stargazing & Himalayan Astrophotography', 'kicker' => null, 'badge' => 'Celestial wonder', 'layout' => 'card', 'categories' => ['twilight'], 'description' => 'At 1,450 meters with clean mountain air, discover the constellations, satellites, and nebulae through our high-aperture telescope with cashmere blankets and warm cocoa.', 'highlights' => ['High-powered Celestron telescope setup', 'Mobile camera mount for astrophotography', 'Spiced Temi ginger chai & warm outdoor heaters'], 'schedule' => 'Nightly 8:30 PM (Clear skies)', 'link_label' => 'Details', 'price' => null],
    ];

    public function run(): void
    {
        if (Experience::exists()) {
            return;
        }

        foreach (self::EXPERIENCES as $position => $experience) {
            $model = new Experience([
                ...collect($experience)->except('image_path')->all(),
                'slug' => Str::slug($experience['title']),
                'price_unit' => PriceUnit::PerGuest,
                'show_on_page' => true,
                'offer_as_addon' => false,
                'is_active' => true,
                'sort_order' => $position + 1,
            ]);
            $model->image_path = $experience['image_path'];
            $model->save();
        }
    }
}
