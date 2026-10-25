<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\Role;
use App\Http\Controllers\Booking\BookingController;
use App\Models\Booking;
use App\Models\Room;
use App\Models\RoomNight;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();

        // 10:00 in the resort's timezone, before the same-day cutoff.
        $this->travelTo(now(config('booking.timezone'))->setDate(2026, 11, 2)->setTime(10, 0));
    }

    /**
     * @return array<string, mixed>
     */
    protected function stay(array $overrides = []): array
    {
        return [
            'check_in' => '2026-11-10',
            'check_out' => '2026-11-13',
            'adults' => 2,
            'children' => 0,
            ...$overrides,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function bookingForm(array $overrides = []): array
    {
        return [
            ...$this->stay(),
            'title' => 'Ms',
            'first_name' => 'Priya',
            'last_name' => 'Malhotra',
            'contact_email' => 'Priya@Example.com',
            'country_code' => '+91',
            'mobile' => '98300 12345',
            'special_requests' => 'Late arrival, around 9 PM.',
            'terms' => '1',
            ...$overrides,
        ];
    }

    public function test_catalogue_matches_the_tariff_card(): void
    {
        $this->assertSame(3, RoomType::count());
        $this->assertSame(15, Room::count());

        $this->assertSame(450000, RoomType::where('slug', 'deluxe-room')->value('nightly_price'));
        $this->assertSame(350000, RoomType::where('slug', 'standard-room')->value('nightly_price'));
        $this->assertSame(390000, RoomType::where('slug', 'family-cottage')->value('nightly_price'));

        $this->assertEqualsCanonicalizing(
            ['301', '302', '303', '304', '305'],
            RoomType::where('slug', 'family-cottage')->first()->rooms->pluck('number')->all(),
        );
    }

    public function test_search_page_lists_room_types_without_dates(): void
    {
        $this->get(route('booking.search'))
            ->assertOk()
            ->assertSee('Deluxe Room')
            ->assertSee('₹4,500')
            ->assertSee('Standard Room')
            ->assertSee('₹3,500')
            ->assertSee('Separate Family Cottage')
            ->assertSee('₹3,900')
            ->assertSee('Choose dates to check availability');
    }

    public function test_search_with_dates_shows_availability_and_totals(): void
    {
        $this->get(route('booking.search', $this->stay()))
            ->assertOk()
            ->assertSee('3 nights')
            ->assertSee('₹13,500 for 3 nights', false)
            ->assertSee('5 rooms left')
            ->assertSee(route('booking.create', ['deluxe-room', ...$this->stay()]));
    }

    public function test_search_rejects_past_dates_and_reversed_dates(): void
    {
        $this->get(route('booking.search', $this->stay(['check_in' => '2026-11-01'])))
            ->assertOk()
            ->assertSee('The earliest check-in we can take is 2 Nov 2026.');

        $this->get(route('booking.search', $this->stay(['check_out' => '2026-11-09'])))
            ->assertOk()
            ->assertSee('Check-out must be after check-in.');
    }

    public function test_same_day_bookings_close_at_the_cutoff(): void
    {
        $this->travelTo(now(config('booking.timezone'))->setTime(18, 30));

        $this->get(route('booking.search', $this->stay(['check_in' => '2026-11-02', 'check_out' => '2026-11-03'])))
            ->assertSee('The earliest check-in we can take is 3 Nov 2026.');
    }

    public function test_guests_can_book_without_an_account(): void
    {
        $this->get(route('booking.create', ['standard-room', ...$this->stay()]))
            ->assertOk()
            ->assertSee('Primary Guest Details')
            ->assertDontSee('name="gst_number"', false)
            ->assertSee('Personal Details')
            ->assertSee('Cancellation Policy');

        $response = $this->post(route('booking.store', 'standard-room'), $this->bookingForm(['gst_number' => '11aaaaa1111a1z5']));

        $booking = Booking::sole();
        $response->assertRedirect(BookingController::confirmationUrl($booking));

        $booking->refresh();
        $this->assertNotNull($booking->user_id, 'A customer account is created for the guest');
        $this->assertSame($booking->contact_email, $booking->user->email);
        $this->assertSame('Priya Malhotra', $booking->contact_name);
        $this->assertSame('Ms Priya Malhotra', $booking->guestFullName());
        $this->assertSame('Ms', $booking->guest_title);
        $this->assertSame('Priya', $booking->guest_first_name);
        $this->assertSame('Malhotra', $booking->guest_last_name);
        $this->assertSame('11AAAAA1111A1Z5', $booking->gst_number);
        $this->assertSame('+919830012345', $booking->contact_phone);

        $this->get(BookingController::confirmationUrl($booking))
            ->assertOk()
            ->assertSee($booking->reference)
            ->assertSee('Your stay is confirmed')
            ->assertSee('Ms Priya Malhotra')
            ->assertSee('₹10,500');

        $this->get(route('booking.confirmation', $booking))->assertForbidden();

        $manager = User::factory()->staff(Role::Manager)->create();
        $this->actingAs($manager)->get(route('admin.bookings.show', $booking))
            ->assertOk()
            ->assertDontSee('Guest checkout (no account)')
            ->assertSee('11AAAAA1111A1Z5');
    }

    public function test_signing_in_from_the_booking_page_returns_to_the_same_room_and_dates(): void
    {
        $bookingUrl = route('booking.create', ['deluxe-room', ...$this->stay(['children' => 1])]);

        // Follow the "Sign in" link exactly as it appears on the booking page.
        preg_match('#href="([^"]*/login\?return=[^"]*)"#', $this->get($bookingUrl)->getContent(), $link);
        $loginUrl = html_entity_decode($link[1]);
        parse_str((string) parse_url($loginUrl, PHP_URL_QUERY), $query);
        $this->assertStringStartsWith('/book/deluxe-room?', $query['return']);

        $customer = User::factory()->customer()->create(['name' => 'Priya Malhotra']);

        $this->get($loginUrl)->assertOk()->assertSee(e(route('register', ['return' => $query['return']])), false);
        $response = $this->post(route('login'), ['email' => $customer->email, 'password' => 'password']);
        $response->assertRedirect(url($query['return']));

        $this->actingAs($customer)->get($response->headers->get('Location'))
            ->assertOk()
            ->assertSee('value="Priya"', false)
            ->assertSee('Room 1 : 2 Adults, 1 Child')
            ->assertSee('Tue, 10 Nov 2026');
    }

    public function test_creating_an_account_from_the_booking_page_also_returns_to_it(): void
    {
        $bookingUrl = route('booking.create', ['standard-room', ...$this->stay()]);
        $returnPath = parse_url($bookingUrl, PHP_URL_PATH).'?'.parse_url($bookingUrl, PHP_URL_QUERY);

        $this->get(route('register', ['return' => $returnPath]));

        $this->post(route('register'), [
            'name' => 'Arjun Mehta',
            'email' => 'arjun@example.com',
            'phone' => '9830012345',
            'password' => 'pine-forest-99',
            'password_confirmation' => 'pine-forest-99',
            'terms' => '1',
        ])->assertRedirect($bookingUrl);
    }

    public function test_return_addresses_outside_the_site_are_ignored(): void
    {
        $customer = User::factory()->customer()->create();

        foreach (['//evil.example/phish', 'https://evil.example', '/\\evil.example'] as $return) {
            $this->get(route('login', ['return' => $return]));
            $this->post(route('login'), ['email' => $customer->email, 'password' => 'password'])
                ->assertRedirect(route('account.dashboard'));
            $this->post(route('logout'));
        }
    }

    public function test_foreign_mobile_numbers_use_the_chosen_country_code(): void
    {
        $this->post(route('booking.store', 'standard-room'), $this->bookingForm(['country_code' => '+977', 'mobile' => '980 1234567']));

        $this->assertSame('+9779801234567', Booking::sole()->contact_phone);
    }

    public function test_details_page_shows_the_summary_and_prefills_contact_details(): void
    {
        $customer = User::factory()->customer()->create(['name' => 'Priya Malhotra']);

        $this->actingAs($customer)
            ->get(route('booking.create', ['family-cottage', ...$this->stay()]))
            ->assertOk()
            ->assertSee('Separate Family Cottage')
            ->assertSee('₹3,900 × 3 nights', false)
            ->assertSee('₹11,700')
            ->assertSee('value="Priya"', false)
            ->assertSee('value="Malhotra"', false);
    }

    public function test_customer_can_book_and_the_booking_is_saved(): void
    {
        $customer = User::factory()->customer()->create();

        $response = $this->actingAs($customer)
            ->post(route('booking.store', 'deluxe-room'), $this->bookingForm());

        $booking = Booking::sole();
        $response->assertRedirect(BookingController::confirmationUrl($booking));
        $this->actingAs($customer)->get(BookingController::confirmationUrl($booking))->assertSee('View in My stays');

        $this->assertMatchesRegularExpression('/^VRN-[0-9A-HJKMNP-TV-Z]{8}$/', $booking->reference);
        $this->assertTrue($booking->user->is($customer));
        $this->assertSame('deluxe-room', $booking->roomType->slug);
        $this->assertSame('101', $booking->room->number);
        $this->assertSame('2026-11-10', $booking->check_in->toDateString());
        $this->assertSame('2026-11-13', $booking->check_out->toDateString());
        $this->assertSame(3, $booking->nights);
        $this->assertSame(450000, $booking->nightly_price);
        $this->assertSame(1350000, $booking->total_amount);
        $this->assertSame(BookingStatus::Confirmed, $booking->status);
        $this->assertSame(PaymentStatus::Unpaid, $booking->payment_status);
        $this->assertSame('priya@example.com', $booking->contact_email);
        $this->assertSame('+919830012345', $booking->contact_phone);
        $this->assertSame('Late arrival, around 9 PM.', $booking->special_requests);

        $this->assertSame(
            ['2026-11-10', '2026-11-11', '2026-11-12'],
            $booking->nights()->orderBy('night_date')->get()->map(fn ($night) => $night->night_date->toDateString())->all(),
        );

        $this->actingAs($customer)->get(route('account.bookings.show', $booking->reference))
            ->assertOk()
            ->assertSee($booking->reference)
            ->assertSee('₹13,500')
            ->assertSee('room 101');
    }

    public function test_booked_nights_are_not_offered_again(): void
    {
        $customer = User::factory()->customer()->create();

        foreach (range(1, 5) as $i) {
            $this->actingAs($customer)->post(route('booking.store', 'standard-room'), $this->bookingForm());
        }

        $this->assertSame(['201', '202', '203', '204', '205'],
            Booking::with('room')->get()->pluck('room.number')->sort()->values()->all());

        $this->get(route('booking.search', $this->stay(['check_in' => '2026-11-12', 'check_out' => '2026-11-14'])))
            ->assertSee('Fully booked');

        $this->actingAs($customer)
            ->post(route('booking.store', 'standard-room'), $this->bookingForm(['check_in' => '2026-11-12', 'check_out' => '2026-11-14']))
            ->assertRedirect()
            ->assertSessionHasErrors('check_in');

        $this->assertSame(5, Booking::count());

        // The check-out day is not a night, so the room is free again from 13 November.
        $this->actingAs($customer)
            ->post(route('booking.store', 'standard-room'), $this->bookingForm(['check_in' => '2026-11-13', 'check_out' => '2026-11-14']))
            ->assertSessionHasNoErrors();

        $this->assertSame(6, Booking::count());
    }

    public function test_two_guests_racing_for_the_last_room_get_one_booking_and_one_clear_message(): void
    {
        // Four of the five Standard rooms are taken; two guests open the details page for the last one.
        $customer = User::factory()->customer()->create();
        foreach (range(1, 4) as $i) {
            $this->actingAs($customer)->post(route('booking.store', 'standard-room'), $this->bookingForm());
        }
        auth()->logout();

        $this->get(route('booking.create', ['standard-room', ...$this->stay()]))->assertOk();
        $this->get(route('booking.create', ['standard-room', ...$this->stay()]))->assertOk();

        $this->post(route('booking.store', 'standard-room'), $this->bookingForm(['contact_email' => 'first@example.com']))
            ->assertSessionHasNoErrors();
        auth()->logout();

        $this->post(route('booking.store', 'standard-room'), $this->bookingForm(['contact_email' => 'second@example.com']))
            ->assertRedirect()
            ->assertSessionHasErrors(['check_in' => 'Sorry, the last Standard Room for these dates has just been booked. Please try other dates or another room.']);

        $this->assertSame(5, Booking::count());
        $this->assertSame(0, Booking::where('contact_email', 'second@example.com')->count());
        $this->assertSame(5, RoomNight::where('night_date', '2026-11-10')->distinct()->count('room_id'));
    }

    public function test_a_room_held_for_online_payment_cannot_be_booked_by_someone_else(): void
    {
        config(['services.razorpay' => ['key_id' => 'rzp_test_x', 'key_secret' => 's', 'webhook_secret' => 'w']]);
        $customer = User::factory()->customer()->create();
        foreach (range(1, 4) as $i) {
            $this->actingAs($customer)->post(route('booking.store', 'standard-room'), $this->bookingForm(['payment_method' => 'pay_at_resort']))
                ->assertSessionHasNoErrors();
        }

        // The last room is held while someone pays online…
        $this->actingAs($customer)->post(route('booking.store', 'standard-room'), $this->bookingForm(['payment_method' => 'online']));
        $this->assertTrue(Booking::latest('id')->first()->isAwaitingPayment());

        // …so nobody else can take it in the meantime.
        $this->actingAs($customer)->post(route('booking.store', 'standard-room'), $this->bookingForm(['payment_method' => 'pay_at_resort']))
            ->assertSessionHasErrors('check_in');
        $this->assertSame(5, Booking::count());
    }

    public function test_the_database_refuses_a_second_booking_of_the_same_room_night(): void
    {
        $booking = Booking::factory()->create();

        $booking->nights()->create(['room_id' => $booking->room_id, 'night_date' => '2026-11-10']);

        $this->expectException(UniqueConstraintViolationException::class);
        $booking->nights()->create(['room_id' => $booking->room_id, 'night_date' => '2026-11-10']);
    }

    public function test_occupancy_and_contact_details_are_validated(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->post(route('booking.store', 'standard-room'), $this->bookingForm(['adults' => 4, 'mobile' => '12', 'first_name' => '']))
            ->assertSessionHasErrors(['adults', 'contact_phone', 'first_name']);

        $this->post(route('booking.store', 'standard-room'), $this->bookingForm(['terms' => null, 'title' => 'Sir', 'gst_number' => 'NOT-A-GSTIN']))
            ->assertSessionHasErrors(['terms', 'title', 'gst_number']);

        $this->actingAs($customer)
            ->post(route('booking.store', 'standard-room'), $this->bookingForm(['check_out' => '2026-12-20']))
            ->assertSessionHasErrors('check_out');

        $this->assertSame(0, Booking::count());
    }

    public function test_staff_cannot_book_through_the_website(): void
    {
        $manager = User::factory()->staff(Role::Manager)->create();

        $this->actingAs($manager)
            ->post(route('booking.store', 'deluxe-room'), $this->bookingForm())
            ->assertRedirect(route('admin.dashboard'));

        $this->assertSame(0, Booking::count());
    }

    public function test_dashboard_lists_the_customers_stays(): void
    {
        $customer = User::factory()->customer()->create();
        $this->actingAs($customer)->post(route('booking.store', 'family-cottage'), $this->bookingForm());

        $this->actingAs($customer)->get(route('account.dashboard'))
            ->assertOk()
            ->assertSee('Upcoming stays')
            ->assertSee('Separate Family Cottage')
            ->assertSee(Booking::sole()->reference)
            ->assertDontSee('No stays booked yet');
    }

    public function test_customers_cannot_open_other_peoples_bookings(): void
    {
        $booking = Booking::factory()->create();
        $someoneElse = User::factory()->customer()->create();

        $this->actingAs($someoneElse)->get(route('account.bookings.show', $booking->reference))->assertNotFound();
        $this->actingAs($someoneElse)->post(route('account.bookings.cancel', $booking->reference))->assertNotFound();

        $this->assertSame(BookingStatus::Confirmed, $booking->fresh()->status);
    }

    public function test_customer_can_cancel_and_the_room_is_released(): void
    {
        $customer = User::factory()->customer()->create();
        $this->actingAs($customer)->post(route('booking.store', 'deluxe-room'), $this->bookingForm());
        $booking = Booking::sole();

        $this->actingAs($customer)
            ->post(route('account.bookings.cancel', $booking->reference))
            ->assertRedirect(route('account.bookings.show', $booking->reference));

        $booking->refresh();
        $this->assertSame(BookingStatus::Cancelled, $booking->status);
        $this->assertNotNull($booking->cancelled_at);
        $this->assertSame($customer->id, $booking->cancelled_by);
        $this->assertSame(0, $booking->nights()->count());

        $this->actingAs($customer)->post(route('booking.store', 'deluxe-room'), $this->bookingForm());
        $this->assertSame('101', Booking::latest('id')->first()->room->number);
    }

    public function test_bookings_cannot_be_cancelled_online_after_check_in(): void
    {
        $customer = User::factory()->customer()->create();
        $this->actingAs($customer)->post(route('booking.store', 'deluxe-room'), $this->bookingForm());
        $booking = Booking::sole();

        $this->travelTo(now(config('booking.timezone'))->setDate(2026, 11, 11));

        $this->actingAs($customer)
            ->post(route('account.bookings.cancel', $booking->reference))
            ->assertSessionHasErrors('booking');

        $this->assertSame(BookingStatus::Confirmed, $booking->fresh()->status);
    }

    public function test_admin_booking_list_is_paginated(): void
    {
        $room = Room::where('number', '101')->firstOrFail();
        $bookings = Booking::factory()->count(12)->create(['room_id' => $room->id, 'room_type_id' => $room->room_type_id]);
        $frontDesk = User::factory()->staff(Role::FrontDesk)->create();

        $this->actingAs($frontDesk)->get(route('admin.bookings.index', ['per_page' => 10]))
            ->assertOk()
            ->assertSee('Page <strong>1</strong> of <strong>2</strong>', false)
            ->assertSee('per_page=10&amp;page=2', false);

        $secondPage = $this->actingAs($frontDesk)->get(route('admin.bookings.index', ['per_page' => 10, 'page' => 2]))
            ->assertOk()
            ->assertSee('Page <strong>2</strong> of <strong>2</strong>', false);

        $this->assertSame(2, $bookings->filter(fn (Booking $booking) => str_contains($secondPage->getContent(), $booking->reference))->count());

        $this->actingAs($frontDesk)->get(route('admin.bookings.index', ['per_page' => 7]))->assertSessionHasErrors('per_page');
    }

    public function test_front_desk_can_list_view_and_cancel_bookings(): void
    {
        $customer = User::factory()->customer()->create();
        $this->actingAs($customer)->post(route('booking.store', 'family-cottage'), $this->bookingForm());
        $booking = Booking::sole();

        $frontDesk = User::factory()->staff(Role::FrontDesk)->create();

        $this->actingAs($frontDesk)->get(route('admin.bookings.index'))
            ->assertOk()
            ->assertSee($booking->reference)
            ->assertSee('Priya Malhotra');

        $this->actingAs($frontDesk)->get(route('admin.bookings.index', ['search' => 'nobody-matches']))
            ->assertOk()
            ->assertDontSee($booking->reference);

        $this->actingAs($frontDesk)->get(route('admin.bookings.show', $booking))
            ->assertOk()
            ->assertSee('301')
            ->assertSee('₹11,700');

        $this->actingAs($frontDesk)->post(route('admin.bookings.cancel', $booking))->assertRedirect();

        $this->assertSame(BookingStatus::Cancelled, $booking->fresh()->status);
        $this->assertSame(0, $booking->nights()->count());
    }

    public function test_staff_without_booking_permissions_are_refused(): void
    {
        $booking = Booking::factory()->create();
        $editor = User::factory()->staff(Role::ContentEditor)->create();
        $accounts = User::factory()->staff(Role::Accounts)->create();

        $this->actingAs($editor)->get(route('admin.bookings.index'))->assertForbidden();
        $this->actingAs($accounts)->get(route('admin.bookings.show', $booking))->assertOk();
        $this->actingAs($accounts)->post(route('admin.bookings.cancel', $booking))->assertForbidden();
    }
}
