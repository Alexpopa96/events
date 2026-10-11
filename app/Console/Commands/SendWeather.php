<?php

namespace App\Console\Commands;

use App\Services\WeatherOutlookTracker;
use App\Services\WeatherTracker;
use Illuminate\Console\Command;

/**
 * One command to run by hand: sends the AccuWeather forecast for the tracked dates already
 * inside its 15-day window, otherwise the long-range Open-Meteo outlook.
 */
class SendWeather extends Command
{
    protected $signature = 'weather:send {--preview : Print the message without sending it to Telegram}';

    protected $description = 'Send the wedding weather (AccuWeather, or the Open-Meteo outlook when still too far away) to Telegram';

    public function handle(WeatherTracker $accuWeather, WeatherOutlookTracker $outlook): int
    {
        $notify = ! $this->option('preview');

        $readings = $accuWeather->sync($notify);
        [$source, $message] = $readings->isNotEmpty()
            ? ['AccuWeather', $accuWeather->message($readings)]
            : ['Open-Meteo', $outlook->message($outlook->sync($notify))];

        $this->line(strip_tags($message));
        $this->newLine();
        $this->info($notify ? "Sent to Telegram ({$source})." : "Preview only ({$source}), nothing sent.");

        return self::SUCCESS;
    }
}
