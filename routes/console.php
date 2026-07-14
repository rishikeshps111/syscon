<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('drivers:expired-license-alerts')->daily();
Schedule::command('controllers:today-trip-notifications')
    ->dailyAt('06:00')
    ->withoutOverlapping();
