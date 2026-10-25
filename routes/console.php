<?php

use App\Actions\Booking\ReleaseExpiredHolds;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Free rooms held for online payments that were never completed (see config/booking.php hold_minutes).
Schedule::call(fn () => app(ReleaseExpiredHolds::class)->handle())
    ->everyMinute()
    ->name('release-expired-booking-holds')
    ->withoutOverlapping();
