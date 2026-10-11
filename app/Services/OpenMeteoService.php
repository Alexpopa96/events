<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Free, keyless long-range outlook from Open-Meteo's ensemble models. A single
 * run that far out is noise, so each date is summarised across all members:
 * median plus the 10th–90th percentile range of the daily maximum.
 */
class OpenMeteoService
{
    private const DAILY = 'temperature_2m_max,temperature_2m_min,temperature_2m_mean,precipitation_sum,sunshine_duration';

    /** Days with at least this much precipitation count towards the "chance of precipitation". */
    private const WET_DAY_MM = 1.0;

    /**
     * Prefers the GFS ensemble (~35 days); dates beyond it fall back to the ECMWF seasonal model.
     *
     * @param  list<string>  $dates  Y-m-d
     * @return array<string, array{source:string,members:int,max_median:float,max_p10:float,max_p90:float,min_median:float,mean_median:float,precipitation_chance:int,precipitation_median_mm:float,precipitation_p90_mm:float,sun_hours:?float,good_day_max:?float}>
     */
    public function outlook(float $latitude, float $longitude, array $dates): array
    {
        $sources = [
            'gfs-ensemble' => [config('services.open_meteo.ensemble_url'), ['models' => 'gfs05', 'forecast_days' => 35]],
            'ecmwf-seasonal' => [config('services.open_meteo.seasonal_url'), ['forecast_days' => 45]],
        ];

        $result = [];

        foreach ($sources as $source => [$url, $query]) {
            $pending = array_values(array_diff($dates, array_keys($result)));

            if ($pending === []) {
                break;
            }

            $daily = $this->fetchDaily($url, $latitude, $longitude, $query);

            foreach ($pending as $date) {
                if ($summary = $this->summarise($daily, $date)) {
                    $result[$date] = ['source' => $source] + $summary;
                }
            }
        }

        ksort($result);

        return $result;
    }

    private function fetchDaily(string $url, float $latitude, float $longitude, array $query): array
    {
        $response = Http::timeout(15)
            ->retry(1, 500, throw: false)
            ->get($url, [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'daily' => self::DAILY,
                'timezone' => 'Europe/Bucharest',
                ...$query,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException("Open-Meteo request failed ({$response->status()}): {$response->body()}");
        }

        return $response->json('daily', []);
    }

    /**
     * Null when the date is outside the model's range or not every member covers it
     * (the last day of a run is often partial).
     */
    private function summarise(array $daily, string $date): ?array
    {
        $index = array_search($date, $daily['time'] ?? [], true);

        if ($index === false) {
            return null;
        }

        $max = $this->members($daily, 'temperature_2m_max', $index);
        $min = $this->members($daily, 'temperature_2m_min', $index);
        $mean = $this->members($daily, 'temperature_2m_mean', $index);
        $precipitation = $this->members($daily, 'precipitation_sum', $index);
        $sunshine = $this->members($daily, 'sunshine_duration', $index);

        if (! $max || ! $min || ! $mean || ! $precipitation) {
            return null;
        }

        $dryMax = $max->filter(fn ($value, $member) => $precipitation[$member] < self::WET_DAY_MM)->values();

        return [
            'members' => $max->count(),
            'max_median' => $this->percentile($max, 50),
            'max_p10' => $this->percentile($max, 10),
            'max_p90' => $this->percentile($max, 90),
            'min_median' => $this->percentile($min, 50),
            'mean_median' => $this->percentile($mean, 50),
            'precipitation_chance' => (int) round(100 * $precipitation->filter(fn ($mm) => $mm >= self::WET_DAY_MM)->count() / $precipitation->count()),
            'precipitation_median_mm' => $this->percentile($precipitation, 50),
            'precipitation_p90_mm' => $this->percentile($precipitation, 90),
            'sun_hours' => $sunshine ? round($this->percentile($sunshine, 50) / 3600, 1) : null,
            // A good but realistic day: warmest quarter of the dry scenarios, not the single best one.
            'good_day_max' => $dryMax->count() >= 4 ? $this->percentile($dryMax, 75) : null,
        ];
    }

    /**
     * Control run (`<variable>`) plus every `<variable>_memberNN`; null if any is missing.
     *
     * @return Collection<int, float>|null
     */
    private function members(array $daily, string $variable, int $index): ?Collection
    {
        $values = collect($daily)
            ->filter(fn ($series, $key) => $key === $variable || str_starts_with($key, "{$variable}_member"))
            ->map(fn ($series) => $series[$index] ?? null)
            ->values();

        return $values->isEmpty() || $values->containsStrict(null) ? null : $values->map(fn ($v) => (float) $v);
    }

    /**
     * @param  Collection<int, float>  $values
     */
    private function percentile(Collection $values, int $percentile): float
    {
        $sorted = $values->sort()->values();
        $position = ($sorted->count() - 1) * $percentile / 100;
        $lower = (int) floor($position);
        $upper = (int) ceil($position);

        return round($sorted[$lower] + ($sorted[$upper] - $sorted[$lower]) * ($position - $lower), 1);
    }
}
