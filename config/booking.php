<?php

/**
 * Booking engine rules. See ARCHITECTURE.md §4.2.
 */
return [

    'timezone' => env('BOOKING_TIMEZONE', 'Asia/Kolkata'),

    'currency' => 'INR',

    /** How long a room is held while the guest pays online with Razorpay. */
    'hold_minutes' => (int) env('BOOKING_HOLD_MINUTES', 15),

    /** Let guests choose to pay at the resort. Set to false to require online payment (when Razorpay is set up). */
    'pay_at_resort' => (bool) env('BOOKING_PAY_AT_RESORT', true),

    'min_nights' => 1,

    'max_nights' => 30,

    'max_days_in_advance' => 365,

    /** Same-day bookings close at this local time (24h). */
    'same_day_cutoff' => '18:00',

    'check_in_time' => '13:00',

    'check_out_time' => '11:00',

    /** Children younger than this stay free. */
    'child_free_below_age' => 6,

];
