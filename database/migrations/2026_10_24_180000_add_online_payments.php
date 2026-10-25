<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Online payment through Razorpay. A booking paid online holds its room until hold_expires_at;
 * each checkout attempt is one row in payments (one Razorpay order).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->string('payment_method', 32)->default('pay_at_resort')->after('payment_status');
            $table->timestamp('hold_expires_at')->nullable()->after('payment_method')->index();
            $table->timestamp('paid_at')->nullable()->after('hold_expires_at');
        });

        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 32)->default('razorpay');
            $table->string('order_id', 64)->unique();
            $table->string('payment_id', 64)->nullable()->unique();
            $table->unsignedBigInteger('amount')->comment('Paise');
            $table->string('currency', 3)->default('INR');
            $table->string('status', 32)->index();
            $table->string('method', 32)->nullable();
            $table->string('error', 255)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');

        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropIndex(['hold_expires_at']);
            $table->dropColumn(['payment_method', 'hold_expires_at', 'paid_at']);
        });
    }
};
