<?php

namespace App\Actions\Booking;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PriceUnit;
use App\Models\Booking;
use App\Models\Experience;
use App\Models\RoomNight;
use App\Models\RoomType;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Books one room of the given type, assigning the lowest-numbered free room.
 *
 * The room-type row lock makes competing bookings wait for each other (on MySQL), and the
 * UNIQUE (room_id, night_date) index is the final guarantee against double bookings.
 */
class CreateBooking
{
    public function __construct(private ReleaseExpiredHolds $releaseExpiredHolds) {}

    /**
     * @param  array{check_in: string, check_out: string, adults: int|string, children?: int|string|null}  $stay
     * @param  array{contact_name: string, contact_email: string, contact_phone: string, special_requests?: string|null, guest_title?: string|null, guest_first_name?: string|null, guest_last_name?: string|null, gst_number?: string|null}  $contact
     *
     * A null user is a guest checkout (booked without signing in). $addOnIds are experiences offered as
     * add-ons; their prices come from the database, never from the request.
     * @param  list<int>  $addOnIds
     *
     * Paying online, the booking waits for payment and holds its room for config('booking.hold_minutes').
     *
     * @throws ValidationException when no room of this type is free for the dates
     */
    public function handle(?User $user, RoomType $roomType, array $stay, array $contact, ?string $ipAddress = null, array $addOnIds = [], bool $payOnline = false): Booking
    {
        $this->releaseExpiredHolds->handle();

        $checkIn = CarbonImmutable::parse($stay['check_in']);
        $checkOut = CarbonImmutable::parse($stay['check_out']);
        $nights = (int) $checkIn->diffInDays($checkOut);

        try {
            return DB::transaction(function () use ($user, $roomType, $stay, $contact, $ipAddress, $addOnIds, $payOnline, $checkIn, $checkOut, $nights): Booking {
                $roomType = RoomType::whereKey($roomType->id)->lockForUpdate()->firstOrFail();

                $room = $roomType->roomsFreeBetween($checkIn, $checkOut)->first();

                if ($room === null) {
                    throw $this->unavailable($roomType);
                }

                $guests = (int) $stay['adults'] + (int) ($stay['children'] ?? 0);
                $addOns = $this->addOns($addOnIds, $guests, $nights);
                $roomAmount = $roomType->nightly_price * $nights;

                $booking = new Booking([
                    'contact_name' => $contact['contact_name'],
                    'contact_email' => $contact['contact_email'],
                    'contact_phone' => $contact['contact_phone'],
                    'special_requests' => $contact['special_requests'] ?? null,
                    'guest_title' => $contact['guest_title'] ?? null,
                    'guest_first_name' => $contact['guest_first_name'] ?? null,
                    'guest_last_name' => $contact['guest_last_name'] ?? null,
                    'gst_number' => $contact['gst_number'] ?? null,
                ]);

                $booking->forceFill([
                    'reference' => Booking::generateReference(),
                    'user_id' => $user?->id,
                    'room_type_id' => $roomType->id,
                    'room_id' => $room->id,
                    'check_in' => $checkIn->toDateString(),
                    'check_out' => $checkOut->toDateString(),
                    'nights' => $nights,
                    'adults' => (int) $stay['adults'],
                    'children' => (int) ($stay['children'] ?? 0),
                    'nightly_price' => $roomType->nightly_price,
                    'room_amount' => $roomAmount,
                    'total_amount' => $roomAmount + $addOns->sum('amount'),
                    'currency' => config('booking.currency'),
                    'status' => $payOnline ? BookingStatus::PendingPayment : BookingStatus::Confirmed,
                    'payment_status' => $payOnline ? PaymentStatus::Pending : PaymentStatus::Unpaid,
                    'payment_method' => $payOnline ? PaymentMethod::Online : PaymentMethod::PayAtResort,
                    'hold_expires_at' => $payOnline ? now()->addMinutes((int) config('booking.hold_minutes')) : null,
                    'ip_address' => $ipAddress,
                ])->save();

                $booking->addOns()->createMany($addOns->all());

                $now = now();
                RoomNight::insert(collect(range(0, $nights - 1))->map(fn (int $offset): array => [
                    'room_id' => $room->id,
                    'night_date' => $checkIn->addDays($offset)->toDateString(),
                    'booking_id' => $booking->id,
                    'created_at' => $now,
                ])->all());

                return $booking;
            }, attempts: 3);
        } catch (UniqueConstraintViolationException) {
            throw $this->unavailable($roomType);
        }
    }

    /**
     * Prices each chosen add-on for this stay. Anything no longer on offer stops the booking.
     *
     * @param  list<int>  $ids
     * @return Collection<int, array{experience_id: int, name: string, price_unit: PriceUnit, unit_price: int, quantity: int, amount: int}>
     */
    protected function addOns(array $ids, int $guests, int $nights): Collection
    {
        if ($ids === []) {
            return collect();
        }

        $experiences = Experience::bookableAddOns()->whereIn('id', $ids)->ordered()->get();

        if ($experiences->count() !== count(array_unique($ids))) {
            throw ValidationException::withMessages([
                'add_ons' => 'One of the add-ons you chose is no longer available. Please review your selection.',
            ]);
        }

        return $experiences->map(function (Experience $experience) use ($guests, $nights): array {
            $quantity = $experience->quantityFor($guests, $nights);

            return [
                'experience_id' => $experience->id,
                'name' => $experience->title,
                'price_unit' => $experience->price_unit,
                'unit_price' => $experience->price,
                'quantity' => $quantity,
                'amount' => $experience->price * $quantity,
            ];
        })->values();
    }

    protected function unavailable(RoomType $roomType): ValidationException
    {
        return ValidationException::withMessages([
            'check_in' => "Sorry, the last {$roomType->name} for these dates has just been booked. Please try other dates or another room.",
        ]);
    }
}
