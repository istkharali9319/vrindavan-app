<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('room_types', function (Blueprint $table) {
            $table->string('tagline', 120)->nullable()->after('slug');
            $table->string('badge', 40)->nullable()->after('tagline');
            $table->json('amenities')->nullable()->after('description');
            $table->string('image_path')->nullable()->after('amenities')->comment('On the public disk');
            $table->json('gallery')->nullable()->after('image_path')->comment('Paths on the public disk');
        });
    }

    public function down(): void
    {
        Schema::table('room_types', function (Blueprint $table) {
            $table->dropColumn(['tagline', 'badge', 'amenities', 'image_path', 'gallery']);
        });
    }
};
