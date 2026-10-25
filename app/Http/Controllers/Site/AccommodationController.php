<?php

namespace App\Http\Controllers\Site;

use App\Enums\RoomStatus;
use App\Http\Controllers\Controller;
use App\Models\RoomType;
use Illuminate\View\View;

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
}
