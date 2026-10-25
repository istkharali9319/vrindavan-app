<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RoomStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RoomRequest;
use App\Models\Room;
use App\Models\RoomType;
use App\Support\RoomOccupancy;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The rooms board: every physical room and what it is doing on a chosen night.
 */
class RoomController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
            'room_type' => ['nullable', 'integer', 'exists:room_types,id'],
            'state' => ['nullable', Rule::in([RoomOccupancy::OCCUPIED, RoomOccupancy::AVAILABLE, RoomOccupancy::MAINTENANCE, RoomOccupancy::RETIRED])],
        ]);

        $today = CarbonImmutable::now(config('booking.timezone'))->startOfDay();
        $date = isset($filters['date']) ? CarbonImmutable::parse($filters['date']) : $today;

        $rooms = RoomOccupancy::on($date);
        $summary = RoomOccupancy::summarise($rooms);

        $visibleRooms = $rooms
            ->when($filters['room_type'] ?? null, fn ($rooms, $roomType) => $rooms->filter(fn (array $entry) => $entry['room']->room_type_id === (int) $roomType))
            ->when($filters['state'] ?? null, fn ($rooms, $state) => $rooms->where('state', $state));

        return view('admin.rooms.index', [
            'roomsByType' => $visibleRooms->groupBy(fn (array $entry) => $entry['room']->room_type_id),
            'roomTypes' => RoomType::orderBy('sort_order')->orderBy('name')->get()->keyBy('id'),
            'summary' => $summary,
            'filters' => $filters,
            'date' => $date,
            'isToday' => $date->equalTo($today),
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.rooms.create', [
            'room' => new Room([
                'status' => RoomStatus::Active,
                'room_type_id' => $request->integer('room_type') ?: null,
            ]),
            'roomTypes' => RoomType::orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function store(RoomRequest $request): RedirectResponse
    {
        $room = Room::create($request->validated());

        return redirect()->route('admin.rooms.index')->with('status', "Room {$room->number} was added.");
    }

    public function edit(Room $room): View
    {
        return view('admin.rooms.edit', [
            'room' => $room->load('roomType'),
            'roomTypes' => RoomType::orderBy('sort_order')->orderBy('name')->get(),
            'upcomingNights' => $room->nights()->where('night_date', '>=', now(config('booking.timezone'))->toDateString())->count(),
        ]);
    }

    /**
     * A room can't move to another type while it holds future bookings, because those were sold at that type's tariff.
     */
    public function update(RoomRequest $request, Room $room): RedirectResponse
    {
        $changesType = (int) $request->validated('room_type_id') !== $room->room_type_id;

        if ($changesType && $room->nights()->where('night_date', '>=', now(config('booking.timezone'))->toDateString())->exists()) {
            return back()->withInput()->withErrors([
                'room_type_id' => "Room {$room->number} has upcoming bookings, so its type can't change until they are over or cancelled.",
            ]);
        }

        $room->update($request->validated());

        return redirect()->route('admin.rooms.index')->with('status', "Room {$room->number} has been updated.");
    }
}
