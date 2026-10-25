<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Booking\CancelBooking;
use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\RoomType;
use App\Models\User;
use App\Support\BookingInvoice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class BookingController extends Controller
{
    /**
     * @var list<int>
     */
    public const PAGE_SIZES = [10, 25, 50, 100];

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(BookingStatus::class)],
            'room_type' => ['nullable', 'integer', 'exists:room_types,id'],
            'when' => ['nullable', Rule::in(['upcoming', 'past'])],
            'per_page' => ['nullable', 'integer', Rule::in(self::PAGE_SIZES)],
        ]);

        $perPage = (int) ($filters['per_page'] ?? 25);
        $today = now(config('booking.timezone'))->toDateString();

        $bookings = Booking::query()
            ->with(['roomType', 'room'])
            ->when(trim($filters['search'] ?? '') !== '', function ($query) use ($filters) {
                // "!" escapes the LIKE wildcards; it behaves the same on MySQL and SQLite.
                $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], trim($filters['search'])).'%';

                $query->where(function ($query) use ($like) {
                    $query->whereRaw("reference LIKE ? ESCAPE '!'", [$like])
                        ->orWhereRaw("contact_name LIKE ? ESCAPE '!'", [$like])
                        ->orWhereRaw("contact_email LIKE ? ESCAPE '!'", [$like])
                        ->orWhereRaw("contact_phone LIKE ? ESCAPE '!'", [$like]);
                });
            })
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['room_type'] ?? null, fn ($query, $roomType) => $query->where('room_type_id', $roomType))
            ->when(($filters['when'] ?? null) === 'upcoming', fn ($query) => $query->where('check_out', '>', $today)->orderBy('check_in'))
            ->when(($filters['when'] ?? null) === 'past', fn ($query) => $query->where('check_out', '<=', $today)->orderByDesc('check_in'))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.bookings.index', [
            'bookings' => $bookings,
            'filters' => $filters,
            'perPage' => $perPage,
            'roomTypes' => RoomType::orderBy('sort_order')->get(),
        ]);
    }

    public function show(Booking $booking): View
    {
        return view('admin.bookings.show', [
            'booking' => $booking->load(['roomType', 'room', 'user', 'addOns', 'payments']),
            'cancelledBy' => $booking->cancelled_by ? User::withTrashed()->find($booking->cancelled_by) : null,
        ]);
    }

    public function cancel(Request $request, Booking $booking, CancelBooking $cancelBooking): RedirectResponse
    {
        if ($booking->isCancelled()) {
            return back()->withErrors(['booking' => 'This booking is already cancelled.']);
        }

        $cancelBooking->handle($booking, $request->user());

        return back()->with('status', "Booking {$booking->reference} has been cancelled and its room released.");
    }

    public function invoice(Booking $booking): Response
    {
        return BookingInvoice::for($booking)->download();
    }
}
