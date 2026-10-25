<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Guests can book without an account: user_id is empty for those bookings. Adds the details
     * asked for on the booking form.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
            $table->string('guest_title', 10)->nullable()->after('contact_name');
            $table->string('gst_number', 20)->nullable()->after('contact_phone');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['guest_title', 'gst_number']);
        });
    }
};
