<?php

namespace App\Support\Weather;

use Illuminate\Support\Carbon;

/**
 * One day's block in the Telegram messages, focused on the event hours: chance of a dry
 * window, sunshine, temperature inside the window and a plain-Romanian line of advice.
 * Framed positively (chance of dry weather) without changing the numbers.
 */
class DayForecastMessage
{
    /**
     * @param  int  $rainChance  probability of precipitation, 0–100
     * @param  float|null  $goodDayMax  a good but realistic day's max, shown when it beats the expected one
     * @param  string|null  $type  AccuWeather PrecipitationType (Rain, Snow, Ice, Mixed); null = unknown, assume rain
     */
    public static function format(
        Carbon $date,
        EventWindow $window,
        float $low,
        float $high,
        int $rainChance,
        ?float $sunHours = null,
        ?float $goodDayMax = null,
        ?string $type = null,
    ): string {
        $dryChance = 100 - $rainChance;
        $temperature = round($low) === round($high)
            ? '~'.self::number($high).'°C'
            : self::number($low).'–'.self::number($high).'°C';

        $lines = [
            '📅 <b>'.ucfirst($date->locale('ro')->isoFormat('dddd, D MMMM')).'</b> · '.$window->label(),
            sprintf('%s %d%% șanse de vreme uscată', $dryChance >= 65 ? '☀️' : ($dryChance >= 40 ? '🌤' : '🌧'), $dryChance)
                .($sunHours !== null ? ' · ~'.self::number($sunHours).' ore de soare' : ''),
            '🌡 '.$temperature
                .($goodDayMax !== null && round($goodDayMax) > round($high) ? ' (într-o zi bună ~'.self::number($goodDayMax).'°C)' : ''),
            '💬 '.self::advice($low, $high, $rainChance, $sunHours, $type, $window),
        ];

        return implode("\n", $lines);
    }

    public static function advice(
        float $low,
        float $high,
        int $rainChance,
        ?float $sunHours = null,
        ?string $type = null,
        ?EventWindow $window = null,
    ): string {
        // Judge the same whole degrees the message shows, so "12°C" never reads as "răcoros".
        [$low, $high] = [round($low), round($high)];
        $sunny = $sunHours !== null && $sunHours >= 5;

        [$maybe, $likely, $gear] = match ($type) {
            'Snow' => ['ningă', 'ninge', 'încălțăminte de iarnă'],
            'Ice', 'Mixed' => ['fie lapoviță', 'e lapoviță', 'umbrelă și încălțăminte potrivită'],
            default => ['plouă', 'plouă', 'umbrelă'],
        };

        $sentences = [match (true) {
            $rainChance < 15 && $sunny => '☀️ Zi cu soare, fără ploaie – numai bună de poze!',
            $rainChance < 15 => '☀️ Nu se anunță ploaie.',
            $rainChance < 35 && $sunny => '🌤 Cel mai probabil uscat și cu soare – numai bun de poze!',
            $rainChance < 35 => '🌤 Cel mai probabil nu plouă.',
            $rainChance < 60 => "🌦 S-ar putea să {$maybe} – o {$gear} în mașină n-ar strica.",
            $rainChance < 85 => "🌧 Probabil {$likely} – luați {$gear}.",
            default => "☔ Aproape sigur {$likely} – {$gear} obligatorie, dar și așa va fi frumos!",
        }];

        $sentences[] = match (true) {
            $high >= 18 => 'Cald pentru noiembrie, o haină subțire ajunge.',
            $high >= 12 => 'Plăcut, o geacă subțire e suficientă.',
            $high >= 6 => 'Răcoros, e nevoie de o geacă de toamnă.',
            default => 'Frig, haine groase și fular.',
        };

        if ($low <= 0) {
            $sentences[] = '❄️ Se poate forma polei – atenție la drum.';
        } elseif ($window && $high - $low >= 4) {
            $sentences[] = $window->coolsDown()
                ? 'Spre final se răcește, ceva mai gros la îndemână.'
                : 'La început e mai răcoare, ceva mai gros la îndemână.';
        }

        return implode(' ', $sentences);
    }

    public static function number(float $value): string
    {
        return (string) (int) round($value);
    }
}
