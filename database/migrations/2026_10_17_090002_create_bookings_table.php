<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 12)->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('room_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('room_id')->constrained()->restrictOnDelete();
            $table->date('check_in');
            $table->date('check_out');
            $table->unsignedSmallInteger('nights');
            $table->unsignedTinyInteger('adults');
            $table->unsignedTinyInteger('children')->default(0);
            $table->unsignedBigInteger('nightly_price')->comment('Paise, GST inclusive, locked at booking time');
            $table->unsignedBigInteger('total_amount')->comment('Paise, GST inclusive');
            $table->string('currency', 3)->default('INR');
            $table->string('status', 32)->index();
            $table->string('payment_status', 32)->index();
            $table->string('contact_name', 120);
            $table->string('contact_email', 191);
            $table->string('contact_phone', 20);
            $table->text('special_requests')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index('check_in');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
