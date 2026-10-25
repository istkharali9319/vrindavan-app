<?php

namespace App\Http\Controllers\Booking;

use App\Actions\Booking\ReleaseExpiredHolds;
use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\StayRequest;
use App\Models\Experience;
use App\Models\RoomType;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\View;

class SearchController extends Controller
{
    /**
     * Public availability search. Without dates it lists the room types and their tariffs.
     */
    public function __invoke(Request $request, ReleaseExpiredHolds $releaseExpiredHolds): View
    {
        $releaseExpiredHolds->handle();

        $roomTypes = RoomType::active()->with('rooms')->orderBy('sort_order')->get();

        $input = [
            'check_in' => $request->query('check_in'),
            'check_out' => $request->query('check_out'),
            'adults' => $request->query('adults', 2),
            'children' => $request->query('children', 0),
        ];

        $stay = null;
        $errors = new ViewErrorBag;

        if ($request->filled('check_in') || $request->filled('check_out')) {
            $validator = StayRequest::stayValidator($input);

            if ($validator->fails()) {
                $errors->put('default', $validator->errors());
            } else {
                $checkIn = CarbonImmutable::parse($input['check_in']);
                $checkOut = CarbonImmutable::parse($input['check_out']);

                $stay = [
                    'check_in' => $checkIn,
                    'check_out' => $checkOut,
                    'nights' => (int) $checkIn->diffInDays($checkOut),
                    'query' => [...$validator->validated(), 'children' => (int) ($input['children'] ?? 0)],
                ];

                $roomTypes->each(fn (RoomType $roomType) => $roomType->setAttribute(
                    'free_rooms',
                    $roomType->roomsFreeBetween($checkIn, $checkOut)->count(),
                ));
            }
        }

        return view('booking.search', [
            'roomTypes' => $roomTypes,
            'input' => $input,
            'stay' => $stay,
            'selectedType' => $request->query('room_type'),
            'earliestCheckIn' => StayRequest::earliestCheckIn(),
            'latestCheckIn' => StayRequest::latestCheckIn(),
            'addOns' => $stay ? Experience::bookableAddOns()->ordered()->get() : collect(),
            'guests' => (int) ($input['adults'] ?? 0) + (int) ($input['children'] ?? 0),
        ])->withErrors($errors->getBag('default'));
    }
}
