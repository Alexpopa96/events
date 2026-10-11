<?php

namespace App\Console\Commands;

use App\Services\WeatherTracker;
use Illuminate\Console\Command;

class SyncWeather extends Command
{
    protected $signature = 'weather:sync {--no-telegram : Save readings without sending them to Telegram}';

    protected $description = 'Fetch AccuWeather temperatures for the tracked dates, save them and send them to Telegram';

    public function handle(WeatherTracker $tracker): int
    {
        $readings = $tracker->sync(notify: ! $this->option('no-telegram'));

        if ($readings->isEmpty()) {
            $this->info('None of the tracked dates is inside the 15-day forecast window yet.');

            return self::SUCCESS;
        }

        foreach ($readings as $reading) {
            $this->line("{$reading->date->toDateString()}: {$reading->min_celsius}°C / {$reading->max_celsius}°C");
        }

        return self::SUCCESS;
    }
}
