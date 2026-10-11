<?php

namespace App\Support\Weather;

/**
 * The hours of a tracked day the forecast should focus on (config services.accuweather.event_windows),
 * and an estimate of the temperature inside them when only a daily min/max is available.
 */
class EventWindow
{
    /**
     * Typical position of each local hour between the day's min (0) and max (1) in mid-November
     * at Boldești-Scăieni (median over Nov 8–20, 2011–2025, Open-Meteo archive).
     */
    private const DIURNAL = [
        0 => 0.24, 1 => 0.19, 2 => 0.19, 3 => 0.16, 4 => 0.14, 5 => 0.10,
        6 => 0.10, 7 => 0.08, 8 => 0.07, 9 => 0.17, 10 => 0.41, 11 => 0.64,
        12 => 0.79, 13 => 0.91, 14 => 0.98, 15 => 0.99, 16 => 0.96, 17 => 0.85,
        18 => 0.66, 19 => 0.55, 20 => 0.43, 21 => 0.34, 22 => 0.28, 23 => 0.24,
    ];

    public function __construct(public readonly int $from, public readonly int $to) {}

    public static function forDate(string $date): self
    {
        [$from, $to] = config("services.accuweather.event_windows.{$date}", [0, 23]);

        return new self($from, $to);
    }

    /**
     * @return list<int>
     */
    public function hours(): array
    {
        return range($this->from, $this->to);
    }

    /**
     * @return array{0: float, 1: float} [lowest, highest] temperature expected inside the window
     */
    public function estimate(float $dayMin, float $dayMax): array
    {
        $factors = array_map(fn (int $hour) => self::DIURNAL[$hour], $this->hours());

        return [
            round($dayMin + min($factors) * ($dayMax - $dayMin), 1),
            round($dayMin + max($factors) * ($dayMax - $dayMin), 1),
        ];
    }

    /** Whether the window's last hour is typically colder than its first (afternoon cooling). */
    public function coolsDown(): bool
    {
        return self::DIURNAL[$this->to] < self::DIURNAL[$this->from];
    }

    /** Whether the window sits inside AccuWeather's "Day" half (07:00–19:00). */
    public function isDaytime(): bool
    {
        return $this->from >= 7 && $this->to <= 19;
    }

    public function label(): string
    {
        return sprintf('%02d:00–%02d:00', $this->from, $this->to);
    }
}
