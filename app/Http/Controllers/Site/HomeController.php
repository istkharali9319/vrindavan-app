<?php

namespace App\Http\Controllers\Site;

use App\Enums\RoomStatus;
use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\RoomType;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Home page. The rooms section shows the first three active room types from the admin.
     */
    public function __invoke(): View
    {
        return view('site.home', [
            'featuredRoomTypes' => RoomType::active()->orderBy('sort_order')->orderBy('name')->limit(3)->get(),
            'roomCount' => Room::query()
                ->where('status', '!=', RoomStatus::Retired)
                ->whereHas('roomType', fn ($query) => $query->where('is_active', true))
                ->count(),
        ]);
    }
}
