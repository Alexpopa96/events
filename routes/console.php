<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command('subscriptions:notify-expiring')->dailyAt('09:00');
Schedule::command('saved-searches:notify')->dailyAt('08:00');
// The tracked dates enter AccuWeather's 15-day window on Oct 29 and the last one is Nov 15.
Schedule::command('weather:sync')->dailyAt('07:00')
    ->when(fn () => now()->between('2026-10-29 00:00', '2026-11-15 23:59'));
// Until then, a weekly long-range trend from Open-Meteo (free, no key).
Schedule::command('weather:outlook')->weeklyOn(1, '07:00')
    ->when(fn () => now()->lt('2026-10-29 00:00'));
Schedule::command('weather:send')->everySixHours()->timezone('Europe/Bucharest');
