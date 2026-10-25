<?php

use Database\Seeders\ExperienceSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Experiences and extras with prices: shown on the Experiences page and/or offered as booking add-ons.
     * The experiences that were website content move here unchanged; prices start empty ("on request"),
     * except experiences the site already described as complimentary.
     */
    public function up(): void
    {
        Schema::create('experiences', function (Blueprint $table) {
            $table->id();
            $table->string('title', 160);
            $table->string('slug', 160)->unique();
            $table->string('kicker', 80)->nullable();
            $table->string('badge', 80)->nullable();
            $table->string('layout', 20)->default('feature')->comment('feature (large row) or card');
            $table->json('categories')->nullable();
            $table->string('image_path')->nullable();
            $table->text('description')->nullable();
            $table->json('highlights')->nullable();
            $table->string('schedule', 160)->nullable();
            $table->string('link_label', 60)->nullable();
            $table->unsignedBigInteger('price')->nullable()->comment('Paise, GST inclusive. Null = price on request');
            $table->string('price_unit', 30)->default('per_guest');
            $table->boolean('show_on_page')->default(true)->comment('Listed on the Experiences page');
            $table->boolean('offer_as_addon')->default(false)->comment('Offered while booking a room');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $items = DB::table('content_items')->where('collection', 'experiences')->orderBy('sort_order')->orderBy('id')->get();

        foreach ($items as $item) {
            $data = json_decode($item->data, true) ?: [];
            $title = trim((string) ($data['title'] ?? 'Experience'));
            $slug = Str::slug($title) ?: 'experience-'.$item->id;
            $complimentary = str_contains(strtolower(($data['badge'] ?? '').' '.($data['description'] ?? '')), 'complimentary');

            DB::table('experiences')->insert([
                'title' => $title,
                'slug' => DB::table('experiences')->where('slug', $slug)->exists() ? $slug.'-'.$item->id : $slug,
                'kicker' => ($data['kicker'] ?? '') ?: null,
                'badge' => ($data['badge'] ?? '') ?: null,
                'layout' => ($data['layout'] ?? 'feature') === 'card' ? 'card' : 'feature',
                'categories' => json_encode(array_values((array) ($data['categories'] ?? []))),
                'image_path' => ($data['image'] ?? '') ?: null,
                'description' => ($data['description'] ?? '') ?: null,
                'highlights' => json_encode(array_values(array_filter(array_map('trim', preg_split('/\R/', (string) ($data['highlights'] ?? '')))))),
                'schedule' => ($data['schedule'] ?? '') ?: null,
                'link_label' => ($data['link_label'] ?? '') ?: null,
                'price' => $complimentary ? 0 : null,
                'price_unit' => 'per_guest',
                'show_on_page' => true,
                'offer_as_addon' => false,
                'is_active' => (bool) $item->is_active,
                'sort_order' => $item->sort_order,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('content_items')->where('collection', 'experiences')->delete();

        // A fresh install has nothing to move: start with the resort's published experiences.
        (new ExperienceSeeder)->run();
    }

    public function down(): void
    {
        Schema::dropIfExists('experiences');
    }
};
