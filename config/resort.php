<?php

/**
 * Public resort details shown across the website.
 *
 * These move into the `site_settings` table in Phase 2 so admins can edit them.
 */
return [

    'name' => 'Vrindavan Resort',

    'tagline' => 'Rhenock • East Sikkim',

    'address' => 'Reshi Road, Near Rhenock Bazar, East Sikkim — 737133, India',

    'email' => env('RESORT_EMAIL', 'stay@vrindavansikkim.com'),

    /**
     * @var list<array{display: string, dial: string}>
     */
    'phones' => [
        ['display' => '+91 94320 64521', 'dial' => '+919432064521'],
        ['display' => '+91 98300 12345', 'dial' => '+919830012345'],
    ],

    'altitude' => '1,450m',

];
