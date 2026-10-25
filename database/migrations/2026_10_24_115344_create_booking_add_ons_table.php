<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add-ons chosen with a booking, with the name and price locked in at booking time.
     * bookings.total_amount becomes room + add-ons; room_amount keeps the room part.
     */
    public function up(): void
    {
        Schema::create('booking_add_ons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('experience_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 160);
            $table->string('price_unit', 30);
            $table->unsignedBigInteger('unit_price')->comment('Paise');
            $table->unsignedSmallInteger('quantity');
            $table->unsignedBigInteger('amount')->comment('Paise: unit_price × quantity');
            $table->timestamps();
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->unsignedBigInteger('room_amount')->default(0)->after('nightly_price')->comment('Paise, room only');
        });

        DB::table('bookings')->update(['room_amount' => DB::raw('total_amount')]);
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('room_amount');
        });
        Schema::dropIfExists('booking_add_ons');
    }
};
