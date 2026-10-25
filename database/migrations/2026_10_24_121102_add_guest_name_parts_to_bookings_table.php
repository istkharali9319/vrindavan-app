<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Stores the guest's first and last name separately, next to the title (Mr, Ms, Mrs, Dr, Mx).
     * contact_name keeps the full name for search and display.
     *
     * Safe on a live database: the new columns are nullable, and existing bookings are filled in
     * by splitting their full name at the first space.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('guest_first_name', 60)->nullable()->after('guest_title');
            $table->string('guest_last_name', 60)->nullable()->after('guest_first_name');
        });

        DB::table('bookings')->whereNull('guest_first_name')->orderBy('id')->chunkById(200, function ($bookings): void {
            foreach ($bookings as $booking) {
                [$first, $last] = array_pad(preg_split('/\s+/', trim((string) $booking->contact_name), 2) ?: [], 2, null);

                DB::table('bookings')->where('id', $booking->id)->update([
                    'guest_first_name' => $first !== null && $first !== '' ? mb_substr($first, 0, 60) : null,
                    'guest_last_name' => $last !== null && $last !== '' ? mb_substr($last, 0, 60) : null,
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['guest_first_name', 'guest_last_name']);
        });
    }
};
