<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Extra facts for the room details page, filled in by admins. Empty fields are simply not shown.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('room_types', function (Blueprint $table) {
            $table->string('bed_type', 120)->nullable()->after('amenities');
            $table->string('room_size', 60)->nullable()->after('bed_type');
            $table->json('services')->nullable()->after('room_size')->comment('Room features and services, one per entry');
        });
    }

    public function down(): void
    {
        Schema::table('room_types', function (Blueprint $table) {
            $table->dropColumn(['bed_type', 'room_size', 'services']);
        });
    }
};
