<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('drivers:expired-license-alerts')->daily();
Schedule::command('drivers:document-expiry-notifications')
    ->dailyAt('07:00')
    ->withoutOverlapping();
Schedule::command('controllers:today-trip-notifications')
    ->dailyAt('06:00')
    ->withoutOverlapping();
Schedule::command('drivers:today-trip-notifications')
    ->dailyAt('06:00')
    ->withoutOverlapping();
