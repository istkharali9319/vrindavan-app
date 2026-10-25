<?php

namespace App\Http\Controllers\Account;

use App\Actions\Booking\CancelBooking;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\User;
use App\Support\BookingInvoice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class BookingController extends Controller
{
    public function show(Request $request, string $reference): View
    {
        return view('account.bookings.show', [
            'booking' => $this->findOwnBooking($request->user(), $reference)->load('roomType', 'room', 'addOns'),
        ]);
    }

    public function cancel(Request $request, string $reference, CancelBooking $cancelBooking): RedirectResponse
    {
        $booking = $this->findOwnBooking($request->user(), $reference);

        if (! $booking->canBeCancelled()) {
            return back()->withErrors(['booking' => 'This booking can no longer be cancelled online. Please call us.']);
        }

        $cancelBooking->handle($booking, $request->user());

        return redirect()->route('account.bookings.show', $booking->reference)->with('status', 'booking-cancelled');
    }

    /**
     * Customers can only open their own bookings; anyone else's reference is a 404.
     */
    protected function findOwnBooking(User $user, string $reference): Booking
    {
        return $user->bookings()->where('reference', $reference)->firstOrFail();
    }

    /**
     * The booking's PDF invoice.
     */
    public function invoice(Request $request, string $reference): Response
    {
        return BookingInvoice::for($this->findOwnBooking($request->user(), $reference))->download();
    }
}
