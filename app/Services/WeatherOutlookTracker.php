<?php

namespace App\Services;

use App\Models\WeatherOutlook;
use App\Support\Weather\DayForecastMessage;
use App\Support\Weather\EventWindow;
use Illuminate\Support\Collection;

/**
 * Weekly long-range trend for the tracked dates (config services.accuweather.dates)
 * until they enter AccuWeather's window and WeatherTracker takes over.
 */
class WeatherOutlookTracker
{
    public function __construct(
        private OpenMeteoService $openMeteo,
        private TelegramService $telegram,
    ) {}

    /**
     * @return Collection<int, WeatherOutlook> outlooks saved in this run
     */
    public function sync(bool $notify = true): Collection
    {
        $forecast = $this->openMeteo->outlook(
            (float) config('services.open_meteo.latitude'),
            (float) config('services.open_meteo.longitude'),
            config('services.accuweather.dates'),
        );

        if ($forecast === []) {
            return collect();
        }

        $outlooks = collect($forecast)
            ->map(function (array $day, string $date) {
                $window = EventWindow::forDate($date);
                [$low, $high] = $window->estimate($day['min_median'], $day['max_median']);

                return WeatherOutlook::updateOrCreate(['date' => $date], $day + [
                    'window_from' => $window->from,
                    'window_to' => $window->to,
                    'window_min_celsius' => $low,
                    'window_max_celsius' => $high,
                    'fetched_at' => now(),
                ]);
            })
            ->values();

        if ($notify) {
            $this->telegram->sendMessage($this->message($outlooks));

            WeatherOutlook::whereKey($outlooks->pluck('id'))->update(['sent_to_telegram_at' => now()]);
            $outlooks->each->refresh();
        }

        return $outlooks;
    }

    /**
     * @param  Collection<int, WeatherOutlook>  $outlooks
     */
    public function message(Collection $outlooks): string
    {
        $locationName = config('services.accuweather.location_name');

        $blocks = $outlooks->map(fn (WeatherOutlook $outlook) => DayForecastMessage::format(
            date: $outlook->date,
            window: new EventWindow($outlook->window_from, $outlook->window_to),
            low: $outlook->window_min_celsius,
            high: $outlook->window_max_celsius,
            rainChance: $outlook->precipitation_chance,
            sunHours: $outlook->sun_hours,
            goodDayMax: $outlook->good_day_max,
        ));

        return implode("\n\n", [
            '💍 <b>Vremea pentru nuntă'.($locationName ? ' – '.e($locationName) : '').'</b>',
            ...$blocks,
            '<i>Prognoză orientativă (modele GFS/ECMWF), se va preciza pe măsură ce ne apropiem. 🤞</i>',
        ]);
    }
}
