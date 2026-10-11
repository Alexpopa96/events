<?php

namespace App\Console\Commands;

use App\Services\WeatherOutlookTracker;
use Illuminate\Console\Command;

class SyncWeatherOutlook extends Command
{
    protected $signature = 'weather:outlook {--no-telegram : Save the outlook without sending it to Telegram}';

    protected $description = 'Fetch the long-range Open-Meteo outlook for the tracked dates, save it and send it to Telegram';

    public function handle(WeatherOutlookTracker $tracker): int
    {
        $outlooks = $tracker->sync(notify: ! $this->option('no-telegram'));

        if ($outlooks->isEmpty()) {
            $this->info('None of the tracked dates is covered by the long-range models.');

            return self::SUCCESS;
        }

        foreach ($outlooks as $outlook) {
            $this->line("{$outlook->date->toDateString()} [{$outlook->source}]: max ~{$outlook->max_median}°C ({$outlook->max_p10}…{$outlook->max_p90}), precip {$outlook->precipitation_chance}%");
        }

        return self::SUCCESS;
    }
}
