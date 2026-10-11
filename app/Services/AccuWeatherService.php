<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class AccuWeatherService
{
    /**
     * Fetch the 15-day daily forecast (the longest window our API plan allows; the website's
     * 45-day outlook isn't available through the API),
     * keyed by local date (Y-m-d), temperatures in Celsius. The average is the mean of
     * the 24 hourly forecasts for that date when the hourly window covers the whole day,
     * otherwise (min + max) / 2. Precipitation sums the day and night halves. `hourly` holds
     * the hours (0–23 => temperature, precipitation probability) the 120-hour forecast covers.
     *
     * @return array<string, array{min:float,max:float,avg:float,avg_from_hourly:bool,hourly:array<int,array{temperature:float,precipitation_probability:int}>,hours_of_sun:?float,day_precipitation_probability:int,has_precipitation:bool,precipitation_type:?string,precipitation_probability:int,rain_mm:float,snow_cm:float,total_liquid_mm:float,hours_of_precipitation:float,day_phrase:?string,night_phrase:?string,link:?string}>
     */
    public function dailyForecast(string $locationKey): array
    {
        $hourly = $this->hourly($locationKey);

        $response = $this->get("/forecasts/v1/daily/15day/{$locationKey}", ['details' => 'true']);

        $days = [];

        foreach ($response->json('DailyForecasts', []) as $day) {
            // AccuWeather returns local time with offset (e.g. 2026-11-12T07:00:00+02:00);
            // taking the date part keeps it in the forecast location's timezone.
            $date = Carbon::parse($day['Date'])->format('Y-m-d');
            $min = (float) $day['Temperature']['Minimum']['Value'];
            $max = (float) $day['Temperature']['Maximum']['Value'];
            $halves = [$day['Day'] ?? [], $day['Night'] ?? []];
            $sum = fn (string $field) => round(array_sum(array_map(fn ($half) => (float) ($half[$field]['Value'] ?? $half[$field] ?? 0), $halves)), 1);

            $days[$date] = [
                'min' => $min,
                'max' => $max,
                'avg' => count($hourly[$date] ?? []) === 24
                    ? round(collect($hourly[$date])->avg('temperature'), 1)
                    : round(($min + $max) / 2, 1),
                'avg_from_hourly' => count($hourly[$date] ?? []) === 24,
                'hourly' => $hourly[$date] ?? [],
                'hours_of_sun' => isset($day['HoursOfSun']) ? (float) $day['HoursOfSun'] : null,
                'day_precipitation_probability' => (int) ($day['Day']['PrecipitationProbability'] ?? 0),
                'has_precipitation' => collect($halves)->contains(fn ($half) => $half['HasPrecipitation'] ?? false),
                'precipitation_type' => $day['Day']['PrecipitationType'] ?? $day['Night']['PrecipitationType'] ?? null,
                'precipitation_probability' => (int) max(array_map(fn ($half) => $half['PrecipitationProbability'] ?? 0, $halves)),
                'rain_mm' => $sum('Rain'),
                'snow_cm' => $sum('Snow'),
                'total_liquid_mm' => $sum('TotalLiquid'),
                'hours_of_precipitation' => $sum('HoursOfPrecipitation'),
                'day_phrase' => $day['Day']['IconPhrase'] ?? null,
                'night_phrase' => $day['Night']['IconPhrase'] ?? null,
                'link' => $day['Link'] ?? null,
            ];
        }

        return $days;
    }

    /**
     * The 120-hour forecast grouped by local date, then by local hour.
     *
     * @return array<string, array<int, array{temperature:float,precipitation_probability:int}>>
     */
    private function hourly(string $locationKey): array
    {
        $days = [];

        foreach ($this->get("/forecasts/v1/hourly/120hour/{$locationKey}", ['details' => 'true'])->json() as $hour) {
            $time = Carbon::parse($hour['DateTime']);

            $days[$time->format('Y-m-d')][(int) $time->format('G')] = [
                'temperature' => (float) $hour['Temperature']['Value'],
                'precipitation_probability' => (int) ($hour['PrecipitationProbability'] ?? 0),
            ];
        }

        return $days;
    }

    private function get(string $path, array $query = []): Response
    {
        $response = Http::timeout(10)
            ->retry(1, 500, throw: false)
            ->get(config('services.accuweather.base_url').$path, [
                'apikey' => config('services.accuweather.key'),
                'metric' => 'true',
                'language' => 'ro-ro',
                ...$query,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException("AccuWeather request failed ({$response->status()}): {$response->body()}");
        }

        return $response;
    }
}
