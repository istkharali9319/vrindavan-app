<?php

namespace App\Http\Controllers\Booking;

use App\Actions\Booking\CreateBooking;
use App\Actions\Booking\LinkBookingToAccount;
use App\Actions\Booking\SendBookingConfirmation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\StayRequest;
use App\Http\Requests\Booking\StoreBookingRequest;
use App\Models\Booking;
use App\Models\Experience;
use App\Models\RoomType;
use App\Models\User;
use App\Support\BookingInvoice;
use App\Support\GuestDevice;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class BookingController extends Controller
{
    /**
     * The "Personal Details" step for the chosen room type. Open to everyone; details are
     * prefilled for signed-in customers.
     */
    public function create(StayRequest $request, RoomType $roomType): View|RedirectResponse
    {
        abort_unless($roomType->is_active, Response::HTTP_NOT_FOUND);

        $stay = $request->validated();
        $checkIn = CarbonImmutable::parse($stay['check_in']);
        $checkOut = CarbonImmutable::parse($stay['check_out']);

        if (! $roomType->roomsFreeBetween($checkIn, $checkOut)->exists()) {
            return redirect()->route('booking.search', $stay)
                ->withErrors(['check_in' => "Sorry, the {$roomType->name} is fully booked for these dates."]);
        }

        $nights = (int) $checkIn->diffInDays($checkOut);

        return view('booking.create', [
            'roomType' => $roomType,
            'user' => $request->user(),
            'stay' => [...$stay, 'children' => (int) ($stay['children'] ?? 0)],
            'checkIn' => $checkIn,
            'checkOut' => $checkOut,
            'nights' => $nights,
            'total' => $roomType->nightly_price * $nights,
            'addOns' => $this->chosenAddOns($request),
            'paymentMethods' => StoreBookingRequest::paymentMethods(),
            'guests' => (int) $stay['adults'] + (int) ($stay['children'] ?? 0),
        ]);
    }

    /**
     * Extras picked in the pop-up on the room page (?add_ons[]=…). Anything not on offer is ignored here;
     * the booking itself is checked again when it is saved.
     *
     * @return Collection<int, Experience>
     */
    private function chosenAddOns(StayRequest $request): Collection
    {
        $ids = collect((array) $request->old('add_ons', $request->query('add_ons', [])))
            ->filter(fn ($id) => is_scalar($id) && ctype_digit((string) $id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->take(20);

        return $ids->isEmpty() ? collect() : Experience::bookableAddOns()->whereIn('id', $ids)->ordered()->get();
    }

    public function store(StoreBookingRequest $request, RoomType $roomType, CreateBooking $createBooking, SendBookingConfirmation $sendConfirmation, LinkBookingToAccount $accounts): RedirectResponse
    {
        abort_unless($roomType->is_active, Response::HTTP_NOT_FOUND);

        // A signed-out guest: first find or create their account and sign them in, then book under that account.
        $user = $request->user() ?? $this->accountForGuest($request, $accounts);

        try {
            $booking = $createBooking->handle(
                $user,
                $roomType,
                $request->validated(),
                $request->contact(),
                $request->ip(),
                $request->addOnIds(),
                $request->paysOnline(),
            );
        } catch (ValidationException $exception) {
            if (array_key_exists('add_ons', $exception->errors())) {
                return back()->withInput()->withErrors($exception->errors());
            }

            return redirect()->route('booking.search', collect($request->validated())->only(['check_in', 'check_out', 'adults', 'children'])->all())
                ->withErrors($exception->errors());
        }

        if ($booking->isAwaitingPayment()) {
            return redirect()->to(PaymentController::paymentUrl($booking));
        }

        $sendConfirmation->handle($booking);

        return redirect()->to(self::confirmationUrl($booking));
    }

    /**
     * The "Confirmation" step. Guests come back to it through the same signed link.
     */
    public function confirmation(Booking $booking): View|RedirectResponse
    {
        if ($booking->isAwaitingPayment()) {
            return redirect()->to(PaymentController::paymentUrl($booking));
        }

        return view('booking.confirmation', [
            'booking' => $booking->load(['roomType', 'room', 'addOns']),
        ]);
    }

    /**
     * The guest's account (new, or the one this browser was already signed in to after an earlier booking).
     * They are signed in to it straight away. An account that already has a password is never signed in to
     * here — the booking is only added to it — since anyone could type its email address.
     */
    private function accountForGuest(StoreBookingRequest $request, LinkBookingToAccount $accounts): ?User
    {
        $user = $accounts->forGuest($request->contact());

        if ($user?->isUnclaimedBookingAccount()) {
            Auth::login($user);
            $request->session()->regenerate();
            GuestDevice::remember($user);
        }

        return $user;
    }

    public static function confirmationUrl(Booking $booking): string
    {
        return URL::signedRoute('booking.confirmation', $booking);
    }

    /**
     * The PDF invoice, through the same kind of private signed link as the confirmation page.
     */
    public function invoice(Booking $booking): Response
    {
        return BookingInvoice::for($booking)->download();
    }

    public static function invoiceUrl(Booking $booking): string
    {
        return URL::signedRoute('booking.invoice', $booking);
    }
}
