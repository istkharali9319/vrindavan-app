<?php

/**
 * Everything on the public website that admins can edit (Admin → Website → Pages).
 *
 * Each page has sections of single fields and may own collections (repeatable items such as
 * experiences, gallery photos or reviews). Fields are written as [type, label, default, options].
 *
 * Types: text, textarea, list (one entry per line), image, select, tags (several options), icon.
 * In headings and textareas, *words in asterisks* are shown in italics and line breaks are kept.
 * Images can be a bundled path ("images/…"), a full URL, or an upload on the public disk.
 */
$icons = [
    'mountain' => 'Mountain', 'leaf' => 'Leaf', 'home' => 'Home', 'flame' => 'Flame', 'user' => 'Person',
    'users' => 'Group', 'sun' => 'Sun', 'calendar' => 'Calendar', 'car' => 'Car', 'document' => 'Document',
    'basket' => 'Picnic', 'clock' => 'Clock', 'layers' => 'Layers', 'camera' => 'Camera', 'map' => 'Map',
    'plane' => 'Plane', 'train' => 'Train', 'road' => 'Road', 'star' => 'Star', 'tea' => 'Tea', 'pin' => 'Map pin', 'phone' => 'Phone', 'mail' => 'Mail',
];

$seo = fn (string $title, string $description): array => [
    'label' => 'Search engines',
    'fields' => [
        'title' => ['text', 'Browser tab title', $title],
        'description' => ['textarea', 'Search result description', $description],
    ],
];

$cta = fn (string $image, string $eyebrow = 'Your mountain sanctuary awaits', string $text = ''): array => [
    'label' => 'Booking banner',
    'fields' => [
        'image' => ['image', 'Background photo', 'images/'.$image],
        'eyebrow' => ['text', 'Small heading', $eyebrow],
        'title' => ['textarea', 'Heading', 'Reserve your quiet corner under Kanchenjunga.'],
        'text' => ['textarea', 'Text (optional)', $text],
        'button' => ['text', 'Button label', 'Book your stay now'],
    ],
];

$bookDirect = 'Reserve your cottage or suite directly with us for guaranteed best seasonal tariffs, complimentary high-altitude tea tastings, and early check-in.';

return [

    'icons' => $icons,

    'pages' => [

        'site' => [
            'label' => 'Site settings',
            'description' => 'Resort name, contact details and footer, used on every page and in emails.',
            'icon' => 'ti-settings',
            'sections' => [
                'identity' => [
                    'label' => 'Resort',
                    'fields' => [
                        'name' => ['text', 'Resort name', 'Vrindavan Resort'],
                        'tagline' => ['text', 'Tagline under the logo', 'Rhenock • East Sikkim'],
                        'altitude' => ['text', 'Altitude', '1,450m'],
                        'brand_style' => ['select', 'Show in the header and footer', 'logo', ['options' => ['logo' => 'Logo image', 'text' => 'Resort name as text']]],
                        'logo' => ['image', 'Logo', 'images/brand/logo.png', ['help' => 'A wide PNG or WebP with a transparent background works best.']],
                        'favicon' => ['image', 'Browser tab icon', 'images/brand/favicon.png', ['help' => 'Square image, at least 64 × 64 pixels.']],
                    ],
                ],
                'contact' => [
                    'label' => 'Contact details',
                    'fields' => [
                        'address' => ['textarea', 'Address', 'Reshi Road, Near Rhenock Bazar, East Sikkim — 737133, India'],
                        'email' => ['text', 'Email', 'stay@vrindavansikkim.com'],
                        'phones' => ['list', 'Phone numbers (one per line)', "+91 94320 64521\n+91 98300 12345"],
                        'whatsapp' => ['text', 'WhatsApp number (optional)', '', ['help' => 'With country code, e.g. +91 94320 64521. Shown as a button on the Contact page.']],
                    ],
                ],
                'invoice' => [
                    'label' => 'Invoices',
                    'fields' => [
                        'legal_name' => ['text', 'Business name on invoices', 'Vrindavan Resort'],
                        'gstin' => ['text', 'Resort GSTIN (optional)', '', ['help' => 'Printed on every invoice when filled in.']],
                        'note' => ['textarea', 'Note at the bottom of invoices', 'All amounts are in Indian rupees and include GST. Thank you for staying with us.'],
                    ],
                ],
                'footer' => [
                    'label' => 'Footer',
                    'fields' => [
                        'about' => ['textarea', 'About text', 'An intimate Himalayan retreat rooted in Sikkimese warmth, fresh farm cooking, and the restorative silence of the high mountains. Built slowly with local stone and pine.'],
                        'status' => ['text', 'Status badge', 'Open Year-Round'],
                        'copyright' => ['text', 'Copyright line', 'Vrindavan Luxury Resort Sikkim. All rights reserved.'],
                        'location' => ['text', 'Location line', 'Rhenock · East Sikkim · India'],
                    ],
                ],
            ],
        ],

        'home' => [
            'label' => 'Home',
            'description' => 'Hero, The Resort story, features, experiences, reviews and the booking banner.',
            'icon' => 'ti-home',
            'route' => 'home',
            'sections' => [
                'seo' => $seo('Vrindavan Resort · Rhenock, East Sikkim', 'A family-run luxury retreat in Rhenock, East Sikkim — intimate rooms, a garden-grown kitchen, and Kanchenjunga unfolding beyond your window.'),
                'hero' => [
                    'label' => 'Hero',
                    'fields' => [
                        'image' => ['image', 'Background photo', 'images/hero-home.jpg'],
                        'title' => ['textarea', 'Headline', 'Where the Himalayas teach you to breathe slow.'],
                        'subtitle' => ['textarea', 'Text under the headline', 'A family-run luxury retreat in Rhenock, East Sikkim — {rooms} intimate rooms, a garden-grown kitchen, and Kanchenjunga unfolding beyond your window.', ['help' => '{rooms} is replaced with the number of rooms.']],
                        'primary_button' => ['text', 'Main button', 'Reserve your stay'],
                        'secondary_button' => ['text', 'Second button', 'Discover experiences'],
                    ],
                ],
                'about' => [
                    'label' => 'The Resort',
                    'fields' => [
                        'title' => ['text', 'Heading', 'The Resort'],
                        'lede' => ['textarea', 'Main paragraph (italic)', "Vrindavan rests in a green valley near Rhenock, where pine and cardamom scent the air and the river becomes the evening's quiet soundtrack. We built it slowly, room by room, for those who come seeking the mountains without giving up a warm bed, a generous table and the comfort of feeling at home."],
                        'text' => ['textarea', 'Second paragraph', 'Families spread out on the lawn. Friends linger over bonfires. Solo travellers finally finish their books. The hills do the rest.'],
                        'rooms_label' => ['text', 'Room count label', 'Rooms & suites'],
                        'altitude_value' => ['text', 'Second figure', '1,450 M'],
                        'altitude_label' => ['text', 'Second figure label', 'Above sea level'],
                        'stat_value' => ['text', 'Third figure', '14°'],
                        'stat_label' => ['text', 'Third figure label', 'Average evening'],
                    ],
                ],
                'rooms' => [
                    'label' => 'Rooms section',
                    'fields' => [
                        'title' => ['textarea', 'Heading', 'Rooms with the range in them'],
                        'subtitle' => ['textarea', 'Text', 'Crafted with native cedarwood, handwoven fabrics, and sweeping valley vistas.', ['help' => 'The rooms shown are the first three active Room types.']],
                    ],
                ],
                'experiences' => [
                    'label' => 'Experiences section',
                    'fields' => [
                        'title' => ['textarea', 'Heading', 'Days made by hand'],
                        'subtitle' => ['textarea', 'Text', 'Thoughtful excursions curated with local village artisans, forest forages, and traditional culinary masterclasses.'],
                    ],
                ],
                'nearby' => [
                    'label' => 'Nearby places section',
                    'fields' => [
                        'title' => ['textarea', 'Heading', 'An hour from everywhere worth being'],
                        'subtitle' => ['textarea', 'Text', 'Historic Silk Route gateways, sacred pine monasteries, and quiet alpine valleys.', ['help' => 'Shows the places from the Explore page.']],
                        'link' => ['text', 'Link label', 'Explore nearby'],
                    ],
                ],
                'reviews' => [
                    'label' => 'Reviews section',
                    'fields' => [
                        'title' => ['textarea', 'Heading', 'Reviews from our Guests'],
                        'rating' => ['text', 'Average rating', '4.5'],
                        'count' => ['text', 'Number of reviews', '3,987'],
                        'lead' => ['textarea', 'Text beside the reviews', 'What our Guests think about us.'],
                    ],
                ],
                'cta' => $cta('cta-lupine-valley.jpg'),
            ],
        ],

        'accommodation' => [
            'label' => 'Rooms & Suites',
            'description' => 'Hero and intro. The rooms themselves come from Room types.',
            'icon' => 'ti-bed',
            'route' => 'accommodation',
            'sections' => [
                'seo' => $seo('Rooms & Suites · Vrindavan Resort', 'Family cottages, Kanchenjunga-facing rooms and forest views in Rhenock, East Sikkim — built from local pine and stone.'),
                'hero' => [
                    'label' => 'Hero',
                    'fields' => [
                        'image' => ['image', 'Background photo', 'images/hero-accommodation.jpg'],
                        'eyebrow' => ['text', 'Small heading', 'Quiet mountain sanctuary'],
                        'title' => ['textarea', 'Headline', "Rooms Nestled Among\nthe Mountains"],
                        'subtitle' => ['textarea', 'Text', 'Perched along the cardamom hills of Rhenock, our private sanctuaries open your eyes to misty pine groves and the timeless snows of Kanchenjunga.'],
                    ],
                ],
                'intro' => [
                    'label' => 'Introduction',
                    'fields' => [
                        'title' => ['text', 'Heading', 'Rooms & Suites'],
                        'text' => ['textarea', 'Text', 'Every room faces something worth waking up to — the range, the river or the forest. Built from local pine and stone, warmed by hand-woven Lepcha textiles, and finished with the things that matter: thick duvets, hot water, and a kettle.'],
                        'gallery_title' => ['text', 'Photo strip heading', 'Spacious rooms with beautiful views'],
                    ],
                ],
                'stats' => [
                    'label' => 'Figures band',
                    'fields' => [
                        'rooms_label' => ['text', 'Room count label', 'Bespoke rooms & suites'],
                        'rooms_text' => ['textarea', 'Room count text', 'Ensuring absolute privacy, uninterrupted stillness, and heartfelt personalized hospitality.'],
                        'stat_2_value' => ['text', 'Second figure', '1,450 M'],
                        'stat_2_label' => ['text', 'Second label', 'Above sea level'],
                        'stat_2_text' => ['textarea', 'Second text', 'Crisp mountain breezes, pure Himalayan air, and direct sightlines to Kanchenjunga.'],
                        'stat_3_value' => ['text', 'Third figure', '100%'],
                        'stat_3_label' => ['text', 'Third label', 'Mountain & pine views'],
                        'stat_3_text' => ['textarea', 'Third text', 'Every window and balcony looks onto either ancient pine woods, river vales, or snow ridges.'],
                    ],
                ],
                'cta' => $cta('cta-sunset-meadow.jpg', text: $bookDirect),
            ],
        ],

        'experience' => [
            'label' => 'Experiences',
            'description' => 'Hero, intro, our approach and how stays are planned. The experiences and their prices are in Hotel → Experiences & add-ons.',
            'icon' => 'ti-flame',
            'route' => 'experience',
            'sections' => [
                'seo' => $seo('Experiences · Vrindavan Resort', 'Bonfires and Lepcha folk songs, momo masterclasses, Reshi Khola angling, village walks, Temi tea and stargazing — days shaped by the mountain rhythm.'),
                'hero' => [
                    'label' => 'Hero',
                    'fields' => [
                        'image' => ['image', 'Background photo', 'images/hero-experience.jpg'],
                        'eyebrow' => ['text', 'Small heading', 'Immersive Sikkimese living'],
                        'title' => ['textarea', 'Headline', 'Days Shaped by the Mountain Rhythm'],
                        'subtitle' => ['textarea', 'Text', 'At Vrindavan, time slows down to the cadence of crackling pinewood, simmering cardamom tea, and the quiet wisdom of the Himalayas. We invite you into authentic Sikkimese traditions, mindful mountain journeys, and handcrafted local encounters.'],
                    ],
                ],
                'intro' => [
                    'label' => 'Introduction',
                    'fields' => [
                        'eyebrow' => ['text', 'Small heading', 'Authentic gatherings'],
                        'title' => ['textarea', 'Heading', 'Curated For Quiet Wonder'],
                        'text' => ['textarea', 'Text', 'Every encounter is hosted with deep personal care, led by resident mountain elders, tea masters, and naturalists born in these valleys.'],
                    ],
                ],
                'ethos' => [
                    'label' => 'Our approach (dark band)',
                    'fields' => [
                        'eyebrow' => ['text', 'Small heading', 'Days made by hand'],
                        'title' => ['textarea', 'Heading', 'Crafted around your pace, never a checklist.'],
                        'quote' => ['textarea', 'Quote', 'The true luxury of Vrindavan is not being rushed. Every morning, discuss your mood with your personal retreat host—whether you seek an exhilarating mountain trek or simply quiet hours reading in our pine grove with hot tea.'],
                    ],
                ],
                'steps' => [
                    'label' => 'How we plan your stay',
                    'fields' => [
                        'eyebrow' => ['text', 'Small heading', 'Effortless journey'],
                        'title' => ['textarea', 'Heading', 'How We Curate Your Stay'],
                    ],
                ],
                'cta' => $cta('cta-sunset-stream.jpg', text: $bookDirect),
            ],
        ],

        'dining' => [
            'label' => 'Dining',
            'description' => 'Kitchen story, menu highlights and the table booking callout.',
            'icon' => 'ti-tools-kitchen-2',
            'route' => 'dining',
            'sections' => [
                'seo' => $seo('Dining · Vrindavan Resort', 'Garden-to-table Sikkimese and Pahadi cooking at Vrindavan Resort — momos, thalis and long-table dinners on the deck.'),
                'hero' => [
                    'label' => 'Hero',
                    'fields' => [
                        'image' => ['image', 'Background photo', 'https://images.unsplash.com/photo-1585937421612-70a008356fbe?auto=format&fit=crop&w=2200&q=75'],
                        'title' => ['textarea', 'Headline', "Cooked slow,\nserved warm."],
                    ],
                ],
                'kitchen' => [
                    'label' => 'Our kitchen',
                    'fields' => [
                        'eyebrow' => ['text', 'Small heading', 'Our Kitchen'],
                        'title' => ['textarea', 'Heading', 'A hundred steps from soil to plate'],
                        'text' => ['textarea', 'Text (blank line between paragraphs)', "Our kitchen garden grows the greens; the village supplies the rest. Dalle chilli comes from Kanchan's farm up the ridge, the cheese from a dairy two bends down, and the river gives us trout when the season is right.\n\nBreakfast is fresh bread, sel roti and fruit that was on a tree yesterday. Dinner is whatever the fire and the season agreed upon."],
                        'image' => ['image', 'Photo', 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=1100&q=75'],
                    ],
                ],
                'menu' => [
                    'label' => 'Menu',
                    'fields' => [
                        'eyebrow' => ['text', 'Small heading', 'From the Menu'],
                    ],
                ],
                'callout' => [
                    'label' => 'Table booking callout',
                    'fields' => [
                        'title' => ['text', 'Heading', 'Hungry yet?'],
                        'text' => ['textarea', 'Text', "The kitchen takes requests — dietary needs, a birthday cake, a picnic for the drive. Tell us when you book and we'll take care of the rest."],
                        'button' => ['text', 'Button label', 'Book a table'],
                    ],
                ],
                'cta' => $cta('cta-lupine-valley.jpg'),
            ],
        ],

        'gallery' => [
            'label' => 'Gallery',
            'description' => 'Photos shown in the gallery, grouped by category.',
            'icon' => 'ti-photo',
            'route' => 'gallery',
            'sections' => [
                'seo' => $seo('Gallery · Vrindavan Resort', 'Photographs from Vrindavan Resort and the Rhenock valley — the resort, the rooms, the food, the culture and the mountains.'),
                'hero' => [
                    'label' => 'Hero',
                    'fields' => [
                        'image' => ['image', 'Background photo', 'images/hero-accommodation.jpg'],
                        'title' => ['textarea', 'Headline', "The valley,\nin fragments"],
                    ],
                ],
                'cta' => $cta('cta-lupine-valley.jpg'),
            ],
        ],

        'explore' => [
            'label' => 'Explore',
            'description' => 'Nearby places, how trips are arranged, and travel tips.',
            'icon' => 'ti-map-2',
            'route' => 'explore',
            'sections' => [
                'seo' => $seo('Explore Nearby · Vrindavan Resort', 'Lampokhari lake, Mankhim ridge, the Old Silk Route at Zuluk, Reshi Khola, Temi tea estate and Rhenock Gompa — all within easy reach of Vrindavan.'),
                'hero' => [
                    'label' => 'Hero',
                    'fields' => [
                        'badge' => ['text', 'Badge', 'Gateways of the Old Silk Route & East Sikkim'],
                        'title' => ['textarea', 'Headline', 'An hour from everywhere *worth being*.'],
                        'text' => ['textarea', 'Text', 'Perched along the quiet emerald ridges of Rhenock, Vrindavan is your launchpad into sacred glacial tarns, centuries-old Buddhist monasteries, the dramatic hairpin curves of the ancient Silk Route, and single-estate organic tea slopes.'],
                    ],
                ],
                'guide' => [
                    'label' => 'How we guide your journeys',
                    'fields' => [
                        'eyebrow' => ['text', 'Small heading', 'Curated for effortless exploration'],
                        'title' => ['textarea', 'Heading', 'How We Guide Your Journeys Beyond Our Gates'],
                        'text' => ['textarea', 'Text', 'High-altitude roads require native intuition, reliable vehicles, and quiet hospitality. Our dedicated private fleet and experienced local hosts ensure you travel without friction or schedule anxiety.'],
                    ],
                ],
                'etiquette' => [
                    'label' => 'Travel tips',
                    'fields' => [
                        'eyebrow' => ['text', 'Small heading', 'Traveler knowledge'],
                        'title' => ['textarea', 'Heading', 'Altitude, Light & Himalayan Etiquette'],
                    ],
                ],
                'concierge' => [
                    'label' => 'Concierge callout',
                    'fields' => [
                        'title' => ['text', 'Heading', 'Custom Day-Trek or Silk Route Itinerary?'],
                        'text' => ['textarea', 'Text', 'Our resident naturalist curates bespoke walks customized to your fitness and photographic rhythm.'],
                        'button' => ['text', 'Button label', 'Inquire with concierge'],
                    ],
                ],
                'cta' => $cta('cta-mountain-terrace.jpg', 'Begin your high mountain chapter', 'Reserve your cottage or suite directly with us for guaranteed seasonal tariffs, complimentary high-altitude tea tastings, and flexible Silk Route permits.'),
            ],
        ],

        'booking' => [
            'label' => 'Booking pages',
            'description' => 'Texts on the guest details step, the cancellation policy and the confirmation page.',
            'icon' => 'ti-calendar-event',
            'route' => 'booking.search',
            'sections' => [
                'details' => [
                    'label' => 'Personal details step',
                    'fields' => [
                        'title' => ['text', 'Page heading', 'Personal Details'],
                        'form_title' => ['text', 'Form heading', 'Primary Guest Details'],
                        'form_intro' => ['text', 'Text under the form heading', 'Please fill all relevant fields to proceed further.'],
                        'terms' => ['text', 'Checkbox text', 'I have read and agree to the booking terms and the cancellation policy.'],
                        'button' => ['text', 'Button label', 'Confirm booking'],
                        'payment_note' => ['textarea', 'Payment note', 'No payment is taken now. The full amount is settled at the resort.'],
                        'gst_field' => ['select', 'GST number field', 'hidden', ['options' => ['hidden' => 'Hidden', 'shown' => 'Shown (optional for guests)']]],
                    ],
                ],
                'policy' => [
                    'label' => 'Cancellation policy',
                    'fields' => [
                        'title' => ['text', 'Heading', 'Cancellation Policy'],
                        'text' => ['textarea', 'Policy', "Free cancellation up to your check-in date.\n\nBooked with an account? Cancel any time before check-in from My stays. Otherwise call or email us with your booking reference."],
                    ],
                ],
                'confirmation' => [
                    'label' => 'Confirmation page',
                    'fields' => [
                        'title' => ['text', 'Heading', 'Your stay is confirmed'],
                        'text' => ['textarea', 'Text', 'Thank you for choosing Vrindavan. Keep this page or your booking reference handy — we look forward to welcoming you to the hills.'],
                    ],
                ],
            ],
        ],

        'contact' => [
            'label' => 'Contact',
            'description' => 'Map, contact form texts and directions. Phone and email come from Site settings.',
            'icon' => 'ti-mail',
            'route' => 'contact',
            'sections' => [
                'seo' => $seo('Contact Us · Vrindavan Resort', 'Contact Vrindavan Resort, Reshi Khola Road, Rhenock, East Sikkim 737133. Directions by air, rail and road.'),
                'hero' => [
                    'label' => 'Hero',
                    'fields' => [
                        'title' => ['textarea', 'Headline', "Feel free\nContact Us"],
                        'map_query' => ['text', 'Map location (as you would search on Google Maps)', 'Rhenock Bazar, East Sikkim'],
                    ],
                ],
                'form' => [
                    'label' => 'Contact form',
                    'fields' => [
                        'message_placeholder' => ['text', 'Message hint', 'Tell us about your trip — dates, people, anything on your mind.'],
                        'button' => ['text', 'Button label', 'Send Message'],
                        'success' => ['textarea', 'Message after sending', 'Thank you! Your message has reached us. We usually reply within a few hours.'],
                        'checkin' => ['text', 'Check-in line', 'Check in 1:00 PM · Check out 11:00 AM'],
                    ],
                ],
            ],
        ],

    ],

    'collections' => [

        'home_features' => [
            'page' => 'home',
            'label' => 'Features (photo and text rows)',
            'singular' => 'feature',
            'max' => 6,
            'fields' => [
                'image' => ['image', 'Photo', ''],
                'title' => ['text', 'Title', ''],
                'text' => ['textarea', 'Text', ''],
            ],
            'items' => [
                ['image' => 'images/alpine-freshness.jpg', 'title' => 'Pure Alpine Freshness', 'text' => 'The day begins cold and clear, with first light spilling across the Kanchenjunga range above the ridge. Pine-scented air drifts through the valley — best enjoyed from your balcony with a warm cup of Temi tea and the mountains slowly coming alive.'],
                ['image' => 'images/adventure-trail.jpg', 'title' => 'Adventure, Unhurried', 'text' => 'From riverside walks and village trails to the old Silk Route and high-altitude lakes, the mountains are yours to explore. We arrange each day around your rhythm — never a checklist, always an invitation to wander a little further.'],
                ['image' => 'images/heritage-hearth.jpg', 'title' => 'Heritage Hospitality', 'text' => "Three generations of our family have grown up among these hills. Our grandmother's recipes still find their way to the table, handwritten notes wait in your room, and our staff will remember how you take your tea — sometimes before you ask."],
            ],
        ],

        'home_experiences' => [
            'page' => 'home',
            'label' => 'Experience tiles',
            'singular' => 'tile',
            'max' => 8,
            'fields' => [
                'image' => ['image', 'Photo', ''],
                'title' => ['text', 'Title', ''],
            ],
            'items' => [
                ['image' => 'images/exp-cooking.jpg', 'title' => 'Sikkimese Cooking Masterclass'],
                ['image' => 'images/exp-bonfire.jpg', 'title' => 'Bonfire & Folk Songs'],
                ['image' => 'images/exp-fishing.jpg', 'title' => 'River Fishing on Reshi Khola'],
                ['image' => 'images/exp-village-walk.jpg', 'title' => 'Village Walk with a Local Guide'],
            ],
        ],

        'reviews' => [
            'page' => 'home',
            'label' => 'Guest reviews (first three are shown)',
            'singular' => 'review',
            'fields' => [
                'name' => ['text', 'Guest name', ''],
                'trip' => ['text', 'Trip details', '', ['help' => 'e.g. Stayed with family • Oct 2024']],
                'text' => ['textarea', 'Review', ''],
            ],
            'items' => [
                ['name' => 'Priya Malhotra', 'trip' => 'Stayed with family • Oct 2024', 'text' => "We came for two nights and stayed for five. My daughter still talks about planting onions with the chef, and my husband still talks about the momos he didn't share. We'll be back for the bonfire songs."],
                ['name' => 'Arjun Mehta', 'trip' => 'Solo Traveller • Nov 2024', 'text' => "The view from the suite at sunrise is something I'll carry for a long time. Quiet, warm, and genuinely kind people who care about making your experience feel effortless."],
                ['name' => 'Sunita & Devraj', 'trip' => 'Couple Retreat • Dec 2024', 'text' => 'They packed warm ginger tea for our road trip towards Zuluk without us even asking. Vrindavan feels less like a commercial resort and more like visiting old friends in the mountains.'],
            ],
        ],

        'experience_values' => [
            'page' => 'experience',
            'label' => 'Our approach',
            'singular' => 'point',
            'max' => 8,
            'fields' => [
                'icon' => ['icon', 'Icon', 'star'],
                'title' => ['text', 'Title', ''],
                'text' => ['textarea', 'Text', ''],
            ],
            'items' => [
                ['icon' => 'user', 'title' => 'Personal Retreat Host', 'text' => "Dedicated concierge to organize tailor-made itineraries tailored to your family's pace."],
                ['icon' => 'sun', 'title' => 'Elder & Artisan Led', 'text' => 'Genuine cultural exchanges guided by Rhenock elders, preserving living folklore.'],
                ['icon' => 'users', 'title' => 'Intimate Group Size', 'text' => 'Never a crowd. Excursions are strictly restricted to 4–6 guests to preserve serenity.'],
                ['icon' => 'calendar', 'title' => 'Seasonal Calendars', 'text' => 'From spring scarlet rhododendrons to crisp autumn peaks and winter fireplace gatherings.'],
            ],
        ],

        'experience_steps' => [
            'page' => 'experience',
            'label' => 'How we plan your stay',
            'singular' => 'step',
            'max' => 6,
            'fields' => [
                'title' => ['text', 'Title', ''],
                'text' => ['textarea', 'Text', ''],
            ],
            'items' => [
                ['title' => 'Pre-Arrival Consultation', 'text' => 'Before your car climbs the hills to Rhenock, we learn your interests, dietary preferences, and energy levels to design a fluid stay.'],
                ['title' => 'Season & Weather Tuned', 'text' => 'Himalayan vistas change daily. We dynamically shift walk departures so you experience Kanchenjunga during its most radiant clarity.'],
                ['title' => 'All-Inclusive Equipment', 'text' => 'Hand-carved trekking poles, Nikon binoculars, fleece throws, warm rain gear, and handcrafted picnic hampers are provided effortlessly.'],
            ],
        ],

        'dining_features' => [
            'page' => 'dining',
            'label' => 'Kitchen facts',
            'singular' => 'fact',
            'max' => 6,
            'fields' => [
                'icon' => ['icon', 'Icon', 'leaf'],
                'title' => ['text', 'Text', ''],
            ],
            'items' => [
                ['icon' => 'leaf', 'title' => 'Organic garden on site'],
                ['icon' => 'flame', 'title' => 'Wood-fired cooking'],
                ['icon' => 'clock', 'title' => 'Kitchen 7 AM – 10 PM'],
            ],
        ],

        'dining_menu' => [
            'page' => 'dining',
            'label' => 'Menu highlights',
            'singular' => 'dish',
            'fields' => [
                'image' => ['image', 'Photo', ''],
                'tag' => ['text', 'Label', ''],
                'title' => ['text', 'Dish', ''],
                'text' => ['textarea', 'Description', ''],
            ],
            'items' => [
                ['image' => 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?auto=format&fit=crop&w=900&q=75', 'tag' => "Chef's signature", 'title' => 'The Pahadi Thali', 'text' => 'Seasonal rice, dal, gundruk soup, a slow-cooked meat or paneer curry, three chutneys and a little something sweet. Ordered by the table — the kitchen fills it.'],
                ['image' => 'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?auto=format&fit=crop&w=900&q=75', 'tag' => 'All day', 'title' => 'Momos & Mountain Smalls', 'text' => 'Steamed, fried and jhol momos with tomato-sesame achar; smoked pork sekuwa, chhurpi fritters and wai-wai sadeko for the road.'],
                ['image' => 'https://images.unsplash.com/photo-1528605248644-14dd04022da1?auto=format&fit=crop&w=900&q=75', 'tag' => 'Fridays & Saturdays', 'title' => 'The Long Table Dinner', 'text' => 'One table on the deck, candles, the river below and twelve courses shared family-style. Guests, staff and the odd neighbour — everybody eats together.'],
            ],
        ],

        'gallery' => [
            'page' => 'gallery',
            'label' => 'Photos',
            'singular' => 'photo',
            'fields' => [
                'image' => ['image', 'Photo', ''],
                'title' => ['text', 'Caption', ''],
                'category' => ['select', 'Category', 'resort', ['options' => ['resort' => 'Resort', 'nature' => 'Nature', 'rooms' => 'Rooms', 'dining' => 'Dining', 'culture' => 'Culture']]],
            ],
            'items' => [
                ['image' => 'images/alpine-freshness.jpg', 'title' => 'Morning from the terrace', 'category' => 'resort'],
                ['image' => 'images/exp-embers.jpg', 'title' => 'Folk songs by the fire', 'category' => 'culture'],
                ['image' => 'images/exp-momo.jpg', 'title' => 'Momo masterclass, mid-pleat', 'category' => 'dining'],
                ['image' => 'images/explore-lampokhari.jpg', 'title' => 'Lampokhari, an hour from lunch', 'category' => 'nature'],
                ['image' => 'images/exp-stargazing.jpg', 'title' => 'Stars over Reshi', 'category' => 'nature'],
                ['image' => 'images/suite-main.jpg', 'title' => 'The suite, before sunrise', 'category' => 'rooms'],
                ['image' => 'images/explore-rhenock-gompa.jpg', 'title' => 'Butter lamps at Rhenock Gompa', 'category' => 'culture'],
                ['image' => 'images/villa-dining.jpg', 'title' => 'Dinner by candlelight', 'category' => 'dining'],
                ['image' => 'images/room-pine-cottage.jpg', 'title' => 'Heritage Pine Cottage', 'category' => 'rooms'],
                ['image' => 'images/explore-reshi.jpg', 'title' => 'Reshi Khola after the rains', 'category' => 'nature'],
                ['image' => 'images/suite-balcony.jpg', 'title' => 'The balcony at first light', 'category' => 'resort'],
                ['image' => 'images/explore-silk-route.jpg', 'title' => 'The Old Silk Route loops', 'category' => 'nature'],
                ['image' => 'images/room-himalayan-villa.jpg', 'title' => 'Family Himalayan Villa', 'category' => 'rooms'],
                ['image' => 'images/exp-cooking.jpg', 'title' => 'Picked this morning', 'category' => 'dining'],
                ['image' => 'images/villa-lawn.jpg', 'title' => 'Home, under the ridge', 'category' => 'resort'],
                ['image' => 'images/exp-village-walk.jpg', 'title' => 'Village walk with a local guide', 'category' => 'culture'],
            ],
        ],

        'places' => [
            'page' => 'explore',
            'label' => 'Nearby places',
            'singular' => 'place',
            'fields' => [
                'image' => ['image', 'Photo', ''],
                'title' => ['text', 'Name', ''],
                'short_title' => ['text', 'Short name (home page)', ''],
                'home_image' => ['image', 'Photo on the home page (optional)', ''],
                'distance' => ['text', 'Distance', '', ['help' => 'e.g. 25 mins away']],
                'altitude' => ['text', 'Altitude', ''],
                'highlight' => ['text', 'Highlight tag', ''],
                'category' => ['select', 'Category', 'lakes', ['options' => ['silk' => 'Historic Silk Route (Zuluk)', 'lakes' => 'Lakes & Waters', 'heritage' => 'Monasteries & Heritage', 'tea' => 'Tea Slopes & Valleys']]],
                'description' => ['textarea', 'Description', ''],
                'points' => ['list', 'Highlights (one per line)', ''],
                'note' => ['text', 'Note under the button', ''],
                'caption' => ['text', 'Photo caption', ''],
            ],
            'items' => [
                ['image' => 'images/explore-lampokhari.jpg', 'title' => 'Lampokhari (Aritar) Emerald Lake', 'short_title' => 'Lampokhari Emerald Lake', 'distance' => '25 mins away', 'altitude' => 'Altitude: 1,498 m', 'highlight' => 'Tranquil waters', 'category' => 'lakes', 'description' => "One of Sikkim's oldest natural emerald tarns, cradled gently by weeping willows and pine-clad hills. Guests experience serene pedalo drifts across glass-calm waters, meditative morning walks under fluttering sacred prayer flags, and trails leading to the British-era dak bungalow.", 'points' => "Perimeter cobblestone nature trail under pine canopies and Tibetan prayer flags\nTraditional quiet pedalo boating reflecting misty cloud shadows and forest greens\nHeritage 1895 British dak bungalow viewpoint overlooking the water basin", 'note' => 'Morning & afternoon slots', 'caption' => 'Aritar Valley • 4.8 km from Vrindavan'],
                ['image' => 'images/explore-mankhim.jpg', 'title' => 'Mankhim Ridge & 360° Kanchenjunga Belvederes', 'short_title' => 'Mankhim Panoramic Ridge', 'distance' => '40 mins away', 'altitude' => 'Altitude: 1,980 m', 'highlight' => 'Panoramic sunrise', 'category' => 'heritage', 'description' => 'Perched at the high crest above Aritar, Mankhim offers unobstructed panoramic vistas of the snow-crowned Kanchenjunga massif alongside a dizzying aerial gaze down upon the teardrop form of Lampokhari lake. Home to the sacred temple of the indigenous Rai community.', 'points' => "Sacred open-air temple of the indigenous Rai heritage and tranquil courtyard\nDawn sunrise viewpoint with artisan hot Temi tea hampers provided by our retreat\nWild rhododendron groves and mountain orchid canopy footpaths", 'note' => 'Recommended departure: 5:15 AM', 'caption' => 'Mankhim Viewpoint • 1,980 m peak'],
                ['image' => 'images/explore-silk-route.jpg', 'title' => 'The Legendary Old Silk Route & Zuluk Loops', 'short_title' => 'Historic Silk Route Pass', 'distance' => '1.5 hours away', 'altitude' => 'Altitude: 2,865 m – 3,500 m', 'highlight' => 'Historic pass', 'category' => 'silk', 'description' => 'Retrace the legendary trade passage between Lhasa and Bengal. Wind up the thirty-two serpentine hairpin turns of Zuluk carved into sheer mountain faces, leading to Thambi View Point where the grand eastern Himalayas unfold into Bhutan and Tibet.', 'points' => "Private luxury 4×4 chauffeured expedition with heated passenger blankets\nPanoramic vantage from Thambi View Point (11,200 ft) over zigzag alpine roads\nComplimentary guest permit facilitation handled entirely by our front desk", 'note' => 'Full day expedition', 'caption' => 'Historic Zuluk Pass • 32 curves'],
                ['image' => 'images/explore-reshi.jpg', 'title' => 'Reshi Riverbank Sanctuary & Boulder Walk', 'short_title' => 'Reshi Riverbank Sanctuary', 'distance' => '30 mins away', 'altitude' => 'Altitude: 600 m', 'highlight' => 'River & angling', 'category' => 'lakes', 'description' => 'Descend into the warm subtropical foothills where the crystalline waters of the Reshi Khola tumble over polished granite stones. An acoustic haven for Himalayan kingfishers, migrating thrushes, pebble meditation, and secluded picnics by the riverbank.', 'points' => "Riverside picnic brunch with farm cheeses and fresh mountain fruit baskets\nNatural shallow wading pools and rare iridescent butterfly viewing trails\nGentle wooden suspension footbridge leading into historic Kalimpong border trails", 'note' => 'Half day itinerary', 'caption' => 'Reshi Khola • 600 m valley floor'],
                ['image' => 'images/explore-temi.jpg', 'title' => 'Temi Tea Estate & Tendong Ridge', 'short_title' => 'Temi Tea Estate', 'distance' => '1 hour 15 mins away', 'altitude' => 'Altitude: 1,650 m', 'highlight' => 'Organic single estate', 'category' => 'tea', 'description' => "Spread across 440 terraced acres along the gentle slopes of Tendong Hill, Temi is Sikkim's sole and world-acclaimed single-estate organic tea garden. Walk between emerald hedges, experience rare first-flush tea tastings, and stroll along cherry blossom lined roads.", 'points' => "Private tea sommelier tour and cupping masterclass in the heritage factory\nPink cherry blossom avenue trails blooming in November across the slopes\nEstate reserve souvenir gift tin curated especially for Vrindavan guests", 'note' => 'Seasonal tastings', 'caption' => 'Temi Estate • Ravangla District'],
                ['image' => 'images/explore-rhenock-gompa.jpg', 'title' => 'Rhenock Gompa & Monastery of Stillness', 'short_title' => 'Rhenock Gompa', 'distance' => '15 mins away', 'altitude' => 'Altitude: 1,450 m', 'highlight' => 'Spiritual blessing', 'category' => 'heritage', 'description' => 'Sheltered within ancient deodar groves just moments from our retreat gates, this intimate century-old monastery echoes with deep low-frequency Tibetan horn resonance and the scent of juniper incense. A sanctuary for quiet contemplative restoration.', 'points' => "Silent morning chanting and sitting meditation with resident Sikkimese lamas\nHand-carved woodblock scriptures, gold leaf murals, and heritage stone chortens\nTraditional braided thread blessing ceremony for peaceful and secure travels", 'note' => 'Walking distance', 'caption' => 'Rhenock Monastery • 15 mins'],
            ],
        ],

        'explore_services' => [
            'page' => 'explore',
            'label' => 'How we guide your journeys',
            'singular' => 'service',
            'max' => 8,
            'fields' => [
                'icon' => ['icon', 'Icon', 'car'],
                'title' => ['text', 'Title', ''],
                'text' => ['textarea', 'Text', ''],
            ],
            'items' => [
                ['icon' => 'car', 'title' => 'Dedicated Luxury 4x4 Fleet', 'text' => 'Spacious high-clearance Mahindra Scorpios and Toyota 4×4s piloted by lifelong mountain drivers who know every turn of the Old Silk Route.'],
                ['icon' => 'document', 'title' => 'Seamless Permits & Passes', 'text' => 'Zero queueing for guests. We coordinate required Inner Line Permits (ILP) and military checkpoint clearances for Zuluk and border viewpoints directly.'],
                ['icon' => 'basket', 'title' => 'Handcrafted Picnic Hampers', 'text' => 'Fresh artisan sandwiches, steamed mountain momos, local orchard preserves, and boiling hot Temi cardamom tea packed in heavy brass thermoses.'],
                ['icon' => 'sun', 'title' => 'Weather-Tuned Departures', 'text' => 'Mountains are dynamic. Our concierges dynamically tune your morning departure times according to real-time ridge forecasts for optimal peak clarity.'],
            ],
        ],

        'explore_tips' => [
            'page' => 'explore',
            'label' => 'Travel tips',
            'singular' => 'tip',
            'max' => 9,
            'fields' => [
                'icon' => ['icon', 'Icon', 'clock'],
                'tag' => ['text', 'Label', ''],
                'title' => ['text', 'Title', ''],
                'text' => ['textarea', 'Text', ''],
            ],
            'items' => [
                ['icon' => 'clock', 'tag' => 'Peak mountain clarity', 'title' => 'Dawn to 10:00 AM Vistas', 'text' => 'The Kanchenjunga range is most pristine and cloud-free between 5:30 AM and 10:00 AM. After midday, alpine thermals pull gentle valley mist over higher passes.'],
                ['icon' => 'layers', 'tag' => 'Layering & high altitude', 'title' => 'What to Wear in the Hills', 'text' => 'Temperatures drop significantly between Rhenock (1,450 m) and Zuluk / Thambi (3,500 m). Bring breathable woolens, windproof outer shells, and sturdy trail footwear.'],
                ['icon' => 'camera', 'tag' => 'Photography & reverence', 'title' => 'Sanctuary Photography', 'text' => 'Photography is warmly welcomed in outer monastery courtyards. We ask guests to step lightly, seek permission for monk portraits, and respect designated border camera zones.'],
            ],
        ],

        'travel_options' => [
            'page' => 'contact',
            'label' => 'How to reach us',
            'singular' => 'route',
            'max' => 6,
            'fields' => [
                'icon' => ['icon', 'Icon', 'plane'],
                'title' => ['text', 'Title', ''],
                'text' => ['textarea', 'Text', ''],
            ],
            'items' => [
                ['icon' => 'plane', 'title' => 'By Air', 'text' => 'Pakyong Airport 42 km · Bagdogra Airport 118 km. We arrange pickups from both.'],
                ['icon' => 'train', 'title' => 'By Rail', 'text' => 'New Jalpaiguri (NJP) 105 km. Roughly four hours of increasingly beautiful road.'],
                ['icon' => 'road', 'title' => 'By Road', 'text' => 'Gangtok 63 km · Siliguri 100 km. Via Rongli on the Rorathang–Rhenock road.'],
            ],
        ],

    ],

];
