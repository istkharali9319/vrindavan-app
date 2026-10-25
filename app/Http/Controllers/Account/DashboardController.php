<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Customer dashboard: upcoming and past stays. Online payments are added in Phase 5.
     */
    public function __invoke(Request $request): View
    {
        $bookings = $request->user()->bookings()->with('roomType')->orderByDesc('check_in')->get();
        [$upcoming, $past] = $bookings->partition(fn (Booking $booking): bool => $booking->isUpcoming());

        return view('account.dashboard', [
            'user' => $request->user(),
            'upcoming' => $upcoming->sortBy('check_in')->values(),
            'past' => $past->values(),
        ]);
    }
}
