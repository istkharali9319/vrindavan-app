<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A booking can now hold several rooms of one type (a group larger than one room sleeps).
 * booking_room lists every room of a booking; bookings.room_id stays as its first room.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->unsignedTinyInteger('rooms_count')->default(1)->after('room_id');
        });

        Schema::create('booking_room', function (Blueprint $table) {
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_id')->constrained()->restrictOnDelete();

            $table->primary(['booking_id', 'room_id']);
        });

        // Every existing booking has exactly one room.
        DB::table('booking_room')->insertUsing(['booking_id', 'room_id'], DB::table('bookings')->select('id', 'room_id'));
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_room');

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('rooms_count');
        });
    }
};
