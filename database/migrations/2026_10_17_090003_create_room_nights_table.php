<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per occupied room per night. The unique index makes a double booking
 * impossible at the database level (ARCHITECTURE.md §4.3).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_nights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->restrictOnDelete();
            $table->date('night_date');
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->unique(['room_id', 'night_date']);
            $table->index('night_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_nights');
    }
};
