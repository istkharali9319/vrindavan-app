<?php

use App\Http\Controllers\Account;
use App\Http\Controllers\Booking;
use App\Http\Controllers\Site;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| Public website
|--------------------------------------------------------------------------
*/

Route::get('/', Site\HomeController::class)->name('home');
Route::get('/accommodation', Site\AccommodationController::class)->name('accommodation');
Route::get('/experiences', Site\ExperienceController::class)->name('experience');
Route::view('/dining', 'site.dining')->name('dining');
Route::view('/gallery', 'site.gallery')->name('gallery');
Route::view('/explore', 'site.explore')->name('explore');
Route::view('/contact', 'site.contact')->name('contact');
Route::post('/contact', [Site\ContactController::class, 'store'])->middleware('throttle:5,1')->name('contact.store');

/*
|--------------------------------------------------------------------------
| Booking (ARCHITECTURE.md §8.1, §8.3)
|--------------------------------------------------------------------------
|
| Anyone can book: signed-in customers get the booking on their account, guests book without one.
| Staff are sent to the admin. The confirmation page is reached through a signed, unguessable link.
|
*/

Route::get('/book', Booking\SearchController::class)->name('booking.search');

Route::middleware('customer.area')->group(function () {
    Route::get('/book/{roomType:slug}', [Booking\BookingController::class, 'create'])->name('booking.create');
    Route::post('/book/{roomType:slug}', [Booking\BookingController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('booking.store');
});

Route::get('/booking/{booking:reference}/confirmed', [Booking\BookingController::class, 'confirmation'])
    ->middleware('signed')
    ->name('booking.confirmation');

// Online payment with Razorpay (signed links, like the confirmation page)
Route::get('/booking/{booking:reference}/pay', [Booking\PaymentController::class, 'show'])
    ->middleware('signed')
    ->name('booking.payment');
Route::post('/booking/{booking:reference}/pay', [Booking\PaymentController::class, 'verify'])
    ->middleware(['signed', 'throttle:20,1'])
    ->name('booking.payment.verify');
Route::post('/booking/{booking:reference}/pay/failed', [Booking\PaymentController::class, 'failed'])
    ->middleware(['signed', 'throttle:20,1'])
    ->name('booking.payment.failed');
Route::post('/booking/{booking:reference}/pay-at-resort', [Booking\PaymentController::class, 'payAtResort'])
    ->middleware(['signed', 'throttle:10,1'])
    ->name('booking.payment.pay-at-resort');
Route::post('/payments/razorpay/webhook', [Booking\PaymentController::class, 'webhook'])
    ->withoutMiddleware(ValidateCsrfToken::class)
    ->middleware('throttle:120,1')
    ->name('payments.razorpay.webhook');

Route::get('/booking/{booking:reference}/invoice', [Booking\BookingController::class, 'invoice'])
    ->middleware('signed')
    ->name('booking.invoice');

/*
|--------------------------------------------------------------------------
| Customer account (ARCHITECTURE.md §8.3)
|--------------------------------------------------------------------------
*/

Route::get('/account/setup/{user}', [Account\AccountSetupController::class, 'show'])
    ->middleware(['signed', 'throttle:10,1'])
    ->name('account.setup');

Route::middleware(['auth', 'customer.area'])->prefix('account')->name('account.')->group(function () {
    Route::get('/', Account\DashboardController::class)->middleware('verified.or.booking')->name('dashboard');
    Route::post('/setup/resend', [Account\AccountSetupController::class, 'resend'])->middleware('throttle:3,10')->name('setup.resend');
    Route::get('/profile', [Account\ProfileController::class, 'edit'])->name('profile');
    Route::get('/security', [Account\SecurityController::class, 'edit'])->name('security');
    Route::get('/bookings/{reference}', [Account\BookingController::class, 'show'])->name('bookings.show');
    Route::get('/bookings/{reference}/invoice', [Account\BookingController::class, 'invoice'])->name('bookings.invoice');
    Route::get('/notifications', [Account\NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [Account\NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::post('/notifications/{notification}/read', [Account\NotificationController::class, 'read'])->name('notifications.read');
    Route::post('/bookings/{reference}/cancel', [Account\BookingController::class, 'cancel'])->name('bookings.cancel');
    Route::delete('/', Account\DeleteAccountController::class)->name('destroy');
});

/*
|--------------------------------------------------------------------------
| Uploaded photos (public disk)
|--------------------------------------------------------------------------
|
| Normally the web server serves /storage straight from the `storage:link` symlink and this route
| is never reached. On shared hosting where symlinks can't be created, it serves the files instead.
|
*/

Route::get('/storage/{path}', function (string $path) {
    abort_if(str_contains($path, '..'), 404);
    abort_unless(Storage::disk('public')->exists($path), 404);

    return Storage::disk('public')->response($path, null, ['Cache-Control' => 'public, max-age=2592000']);
})->where('path', '.+')->name('storage.public');

/*
|--------------------------------------------------------------------------
| Password re-confirmation is not used
|--------------------------------------------------------------------------
|
| Fortify always registers /user/confirm-password. These routes are registered after Fortify's,
| so they replace them and the page no longer exists.
|
*/

Route::get('/user/confirm-password', fn () => abort(404));
Route::post('/user/confirm-password', fn () => abort(404));
Route::get('/user/confirmed-password-status', fn () => abort(404));

/*
|--------------------------------------------------------------------------
| Legacy static-site URLs (301 to keep existing links and search rankings)
|--------------------------------------------------------------------------
*/

Route::permanentRedirect('/index.html', '/');
Route::permanentRedirect('/accommodation.html', '/accommodation');
Route::permanentRedirect('/experience.html', '/experiences');
Route::permanentRedirect('/dining.html', '/dining');
Route::permanentRedirect('/gallery.html', '/gallery');
Route::permanentRedirect('/explore.html', '/explore');
Route::permanentRedirect('/contact.html', '/contact');
