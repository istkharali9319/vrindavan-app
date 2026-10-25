<?php

namespace App\Support;

use App\Enums\BookingStatus;
use App\Enums\RoomStatus;
use App\Models\Booking;
use App\Models\Room;
use App\Models\RoomNight;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * What every room is doing on one night: occupied (and by whom), free, or out of service.
 * Used by the admin dashboard and the rooms board.
 */
class RoomOccupancy
{
    public const OCCUPIED = 'occupied';

    public const AVAILABLE = 'available';

    public const MAINTENANCE = 'maintenance';

    public const RETIRED = 'retired';

    /**
     * @return Collection<int, array{room: Room, state: string, booking: ?Booking, arriving: ?Booking, departing: ?Booking}>
     */
    public static function on(CarbonInterface $date): Collection
    {
        $day = $date->toDateString();

        $occupiedBy = RoomNight::query()
            ->with('booking')
            ->where('night_date', $day)
            ->get()
            ->mapWithKeys(fn (RoomNight $night): array => [$night->room_id => $night->booking]);

        $confirmed = Booking::query()->where('status', BookingStatus::Confirmed);
        $arrivals = (clone $confirmed)->whereDate('check_in', $day)->get()->keyBy('room_id');
        $departures = (clone $confirmed)->whereDate('check_out', $day)->get()->keyBy('room_id');

        return Room::query()
            ->with('roomType')
            ->orderBy('number')
            ->get()
            ->map(fn (Room $room): array => [
                'room' => $room,
                'state' => match (true) {
                    $occupiedBy->has($room->id) => self::OCCUPIED,
                    $room->status === RoomStatus::Maintenance => self::MAINTENANCE,
                    $room->status === RoomStatus::Retired => self::RETIRED,
                    default => self::AVAILABLE,
                },
                'booking' => $occupiedBy->get($room->id),
                'arriving' => $arrivals->get($room->id),
                'departing' => $departures->get($room->id),
            ]);
    }

    /**
     * Counts per state, plus the occupancy rate of rooms that could be sold (retired rooms excluded).
     *
     * @param  Collection<int, array{state: string}>  $rooms
     * @return array{occupied: int, available: int, maintenance: int, retired: int, sellable: int, rate: int}
     */
    public static function summarise(Collection $rooms): array
    {
        $counts = $rooms->countBy('state');
        $sellable = $rooms->count() - $counts->get(self::RETIRED, 0);
        $occupied = $counts->get(self::OCCUPIED, 0);

        return [
            'occupied' => $occupied,
            'available' => $counts->get(self::AVAILABLE, 0),
            'maintenance' => $counts->get(self::MAINTENANCE, 0),
            'retired' => $counts->get(self::RETIRED, 0),
            'sellable' => $sellable,
            'rate' => $sellable > 0 ? (int) round($occupied / $sellable * 100) : 0,
        ];
    }

    public static function label(string $state): string
    {
        return match ($state) {
            self::OCCUPIED => 'Occupied',
            self::AVAILABLE => 'Available',
            self::MAINTENANCE => 'Maintenance',
            self::RETIRED => 'Retired',
        };
    }
}
