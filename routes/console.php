<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule cleanup of expired trials to run daily at 2 AM
Schedule::command('cleanup:expired-trials')->dailyAt('02:00');

// Schedule cleanup of expired license tokens to run daily at 3 AM
Schedule::command('cleanup:expired-license-tokens')->dailyAt('03:00');
