<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Bookings made with the factory do not occupy room nights. Use CreateBooking when availability matters.
 *
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $checkIn = now(config('booking.timezone'))->addDays(fake()->numberBetween(5, 60))->startOfDay();

        return [
            'reference' => Booking::generateReference(),
            'user_id' => User::factory()->customer(),
            'room_id' => Room::factory(),
            'room_type_id' => fn (array $attributes) => Room::find($attributes['room_id'])->room_type_id,
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkIn->addDays(2)->toDateString(),
            'nights' => 2,
            'adults' => 2,
            'children' => 0,
            'nightly_price' => 350000,
            'room_amount' => 700000,
            'total_amount' => 700000,
            'status' => BookingStatus::Confirmed,
            'payment_status' => PaymentStatus::Unpaid,
            'contact_name' => fake()->name(),
            'contact_email' => fake()->safeEmail(),
            'contact_phone' => '+91'.fake()->numerify('9#########'),
        ];
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BookingStatus::Cancelled,
            'cancelled_at' => now(),
        ]);
    }
}
