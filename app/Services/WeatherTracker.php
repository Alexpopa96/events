<?php

namespace App\Services;

use App\Models\WeatherReading;
use App\Support\Weather\DayForecastMessage;
use App\Support\Weather\EventWindow;
use Illuminate\Support\Collection;

/**
 * Tracks the weather for a fixed set of dates (config services.accuweather.dates), focused
 * on each date's event hours (services.accuweather.event_windows).
 * AccuWeather's API only forecasts 15 days ahead, so each run stores whichever target dates
 * are currently inside that window and sends them to Telegram; dates still too far
 * away are skipped and picked up by a later (scheduled) run.
 */
class WeatherTracker
{
    public function __construct(
        private AccuWeatherService $accuWeather,
        private TelegramService $telegram,
    ) {}

    /**
     * @return Collection<int, WeatherReading> readings saved in this run
     */
    public function sync(bool $notify = true): Collection
    {
        $locationKey = (string) config('services.accuweather.location_key');
        $locationName = config('services.accuweather.location_name');
        $targetDates = config('services.accuweather.dates');

        $forecast = collect($this->accuWeather->dailyForecast($locationKey))
            ->only($targetDates);

        if ($forecast->isEmpty()) {
            return collect();
        }

        $readings = $forecast->map(fn (array $day, string $date) => WeatherReading::updateOrCreate(
            ['date' => $date, 'location_key' => $locationKey],
            $this->keepHourlyAverage($date, $locationKey, $day) + $this->eventWindow($date, $day) + [
                'location_name' => $locationName,
                'min_celsius' => $day['min'],
                'max_celsius' => $day['max'],
                'avg_celsius' => $day['avg'],
                'avg_from_hourly' => $day['avg_from_hourly'],
                'has_precipitation' => $day['has_precipitation'],
                'precipitation_type' => $day['precipitation_type'],
                'precipitation_probability' => $day['precipitation_probability'],
                'rain_mm' => $day['rain_mm'],
                'snow_cm' => $day['snow_cm'],
                'total_liquid_mm' => $day['total_liquid_mm'],
                'hours_of_precipitation' => $day['hours_of_precipitation'],
                'hours_of_sun' => $day['hours_of_sun'],
                'day_phrase' => $day['day_phrase'],
                'night_phrase' => $day['night_phrase'],
                'link' => $day['link'],
                'fetched_at' => now(),
            ],
        ))->sortBy('date')->values();

        if ($notify) {
            $this->telegram->sendMessage($this->message($readings));

            WeatherReading::whereKey($readings->pluck('id'))->update(['sent_to_telegram_at' => now()]);
            $readings->each->refresh();
        }

        return $readings;
    }

    /**
     * Temperature and chance of precipitation inside the date's event hours: exact from the
     * hourly forecast once every hour is covered, otherwise estimated from the daily values.
     */
    private function eventWindow(string $date, array $day): array
    {
        $window = EventWindow::forDate($date);
        $hours = collect($day['hourly'])->only($window->hours());

        if ($hours->count() === count($window->hours())) {
            return [
                'window_from' => $window->from,
                'window_to' => $window->to,
                'window_min_celsius' => $hours->min('temperature'),
                'window_max_celsius' => $hours->max('temperature'),
                'window_precipitation_probability' => $hours->max('precipitation_probability'),
                'window_from_hourly' => true,
            ];
        }

        [$low, $high] = $window->estimate($day['min'], $day['max']);

        return [
            'window_from' => $window->from,
            'window_to' => $window->to,
            'window_min_celsius' => $low,
            'window_max_celsius' => $high,
            'window_precipitation_probability' => $window->isDaytime()
                ? $day['day_precipitation_probability']
                : $day['precipitation_probability'],
            'window_from_hourly' => false,
        ];
    }

    /**
     * On the target day itself the hourly forecast no longer covers the hours already
     * past, so instead of downgrading to (min + max) / 2 keep the earlier hourly mean.
     */
    private function keepHourlyAverage(string $date, string $locationKey, array $day): array
    {
        if ($day['avg_from_hourly']) {
            return [];
        }

        $previous = WeatherReading::whereDate('date', $date)
            ->where('location_key', $locationKey)
            ->where('avg_from_hourly', true)
            ->first();

        return $previous ? ['avg_celsius' => $previous->avg_celsius, 'avg_from_hourly' => true] : [];
    }

    /**
     * @param  Collection<int, WeatherReading>  $readings
     */
    public function message(Collection $readings): string
    {
        $locationName = config('services.accuweather.location_name');

        $blocks = $readings->map(fn (WeatherReading $reading) => DayForecastMessage::format(
            date: $reading->date,
            window: new EventWindow($reading->window_from, $reading->window_to),
            low: $reading->window_min_celsius,
            high: $reading->window_max_celsius,
            rainChance: $reading->window_precipitation_probability,
            sunHours: $reading->hours_of_sun,
            type: $reading->precipitation_type,
        ));

        return implode("\n\n", [
            '💍 <b>Vremea pentru nuntă'.($locationName ? ' – '.e($locationName) : '').'</b>',
            ...$blocks,
            '<i>Prognoză AccuWeather, actualizată zilnic.</i>',
        ]);
    }
}
