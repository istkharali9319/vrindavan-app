<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Set the moment a booking's confirmation email and notifications go out, so they can never be sent twice.
     * Existing bookings are left empty: nothing is sent for them retroactively.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->timestamp('confirmation_sent_at')->nullable()->after('cancelled_by');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('confirmation_sent_at');
        });
    }
};
