<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Admin accounts are created with `php artisan admin:create`, never with a seeded password.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            RoomCatalogueSeeder::class,
            SiteContentSeeder::class,
            ExperienceSeeder::class,
        ]);
    }
}
