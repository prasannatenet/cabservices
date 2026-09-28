<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// A driver has 6 hours to accept or refuse a new ride. This releases the rides
// nobody answered so the admin can assign them to another driver.
Schedule::command('assignments:reject-expired')
    ->everyFiveMinutes()
    ->withoutOverlapping();
