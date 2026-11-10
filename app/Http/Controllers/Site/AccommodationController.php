<?php

namespace App\Http\Controllers\Site;

use App\Enums\RoomStatus;
use App\Http\Controllers\Controller;
use App\Models\Experience;
use App\Models\RoomType;
use App\Support\WebsiteContent;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class AccommodationController extends Controller
{
    /**
     * The Rooms & Suites page, built from the room types managed in the admin.
     */
    public function __invoke(): View
    {
        $roomTypes = RoomType::active()
            ->withCount(['rooms' => fn ($query) => $query->where('status', '!=', RoomStatus::Retired)])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('site.accommodation', [
            'roomTypes' => $roomTypes,
            'roomCount' => $roomTypes->sum('rooms_count'),
        ]);
    }

    /**
     * One room type's details page, everything read from the admin's data for this room type.
     * Hidden room types are not shown.
     */
    public function show(RoomType $roomType, WebsiteContent $content): View
    {
        abort_unless($roomType->is_active, Response::HTTP_NOT_FOUND);

        $roomType->loadCount(['rooms' => fn ($query) => $query->where('status', '!=', RoomStatus::Retired)]);

        return view('site.room-type', [
            'roomType' => $roomType,
            'addOns' => Experience::bookableAddOns()->ordered()->get(),
            'bookingContent' => $content->for('booking'),
            'otherRoomTypes' => RoomType::active()->whereKeyNot($roomType->id)->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }
}
