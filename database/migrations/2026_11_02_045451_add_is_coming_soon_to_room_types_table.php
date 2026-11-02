<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * "Coming soon" room types are shown on the website but cannot be booked yet.
 * The Deluxe Room and the Separate Family Cottage start as coming soon.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('room_types', function (Blueprint $table) {
            $table->boolean('is_coming_soon')->default(false)->after('is_active');
        });

        DB::table('room_types')
            ->whereIn('slug', ['deluxe-room', 'family-cottage'])
            ->update(['is_coming_soon' => true]);
    }

    public function down(): void
    {
        Schema::table('room_types', function (Blueprint $table) {
            $table->dropColumn('is_coming_soon');
        });
    }
};
