<?php

use Database\Seeders\SiteContentSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Website content edited in the admin. Fields not stored here fall back to config/site-content.php.
     */
    public function up(): void
    {
        Schema::create('site_contents', function (Blueprint $table) {
            $table->id();
            $table->string('page', 40);
            $table->string('key', 120)->comment('section.field');
            $table->text('value')->nullable();
            $table->timestamps();

            $table->unique(['page', 'key']);
        });

        Schema::create('content_items', function (Blueprint $table) {
            $table->id();
            $table->string('collection', 60);
            $table->json('data');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['collection', 'sort_order']);
        });

        // Start every list (experiences, photos, reviews…) with the content the site already had.
        (new SiteContentSeeder)->run();
    }

    public function down(): void
    {
        Schema::dropIfExists('content_items');
        Schema::dropIfExists('site_contents');
    }
};
