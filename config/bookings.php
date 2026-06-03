<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Pending booking expiry
    |--------------------------------------------------------------------------
    |
    | Pending booking requests older than this threshold are auto-declined by
    | the `bookings:expire-stale` scheduler command. Slot is released and the
    | customer is emailed.
    |
    */

    'pending_expiry_hours' => (int) env('BOOKING_PENDING_EXPIRY_HOURS', 24),

];
