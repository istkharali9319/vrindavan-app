<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\RoomType;
use App\Models\User;
use App\Support\RoomOccupancy;
use Carbon\CarbonImmutable;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Admin home: tonight's rooms, today's arrivals and departures, bookings and customers.
     */
    public function __invoke(): View
    {
        $today = CarbonImmutable::now(config('booking.timezone'))->startOfDay();
        $customers = User::role(Role::Customer->value);
        $confirmed = Booking::query()->where('status', BookingStatus::Confirmed);

        $rooms = RoomOccupancy::on($today);

        return view('admin.dashboard', [
            'today' => $today,
            'stats' => [
                'customers' => (clone $customers)->count(),
                'newCustomers' => (clone $customers)->where('created_at', '>=', now()->subDays(30))->count(),
                'unverifiedCustomers' => (clone $customers)->whereNull('email_verified_at')->count(),
                'staff' => User::role(Role::staffValues())->count(),
                'arrivals' => (clone $confirmed)->whereDate('check_in', $today)->count(),
                'departures' => (clone $confirmed)->whereDate('check_out', $today)->count(),
                'upcoming' => (clone $confirmed)->whereDate('check_in', '>', $today)->count(),
                'monthRevenue' => (int) (clone $confirmed)->where('created_at', '>=', $today->startOfMonth())->sum('total_amount'),
            ],
            'rooms' => $rooms,
            'occupancy' => RoomOccupancy::summarise($rooms),
            'roomTypes' => RoomType::query()->withCount('rooms')->orderBy('sort_order')->orderBy('name')->get(),
            'nextArrivals' => (clone $confirmed)->with(['room', 'roomType'])
                ->whereDate('check_in', '>=', $today)
                ->orderBy('check_in')
                ->limit(6)
                ->get(),
            'recentCustomers' => (clone $customers)->latest()->limit(6)->get(),
        ]);
    }
}
