<?php

namespace Tests\Unit;

use App\Support\Weather\DayForecastMessage;
use App\Support\Weather\EventWindow;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DayForecastMessageTest extends TestCase
{
    public static function cases(): array
    {
        return [
            'dry, sunny and mild' => [10, 14, 5, 8.0, null, null, '☀️ Zi cu soare, fără ploaie – numai bună de poze! Plăcut, o geacă subțire e suficientă.'],
            'mostly dry, cloudy, icy' => [-1, 2, 22, 2.0, null, null, '🌤 Cel mai probabil nu plouă. Frig, haine groase și fular. ❄️ Se poate forma polei – atenție la drum.'],
            'maybe rain, cools down in the evening' => [7, 12, 40, null, 'Rain', [12, 18], '🌦 S-ar putea să plouă – o umbrelă în mașină n-ar strica. Plăcut, o geacă subțire e suficientă. Spre final se răcește, ceva mai gros la îndemână.'],
            'colder at the start of a late-morning window' => [8, 12, 22, 7.0, null, [11, 16], '🌤 Cel mai probabil uscat și cu soare – numai bun de poze! Plăcut, o geacă subțire e suficientă. La început e mai răcoare, ceva mai gros la îndemână.'],
            'likely rain, warm' => [16, 19, 70, null, 'Rain', null, '🌧 Probabil plouă – luați umbrelă. Cald pentru noiembrie, o haină subțire ajunge.'],
            'rounds like the displayed value' => [11, 11.9, 26, 3.0, null, null, '🌤 Cel mai probabil nu plouă. Plăcut, o geacă subțire e suficientă.'],
            'snow' => [-3, 2, 90, null, 'Snow', null, '☔ Aproape sigur ninge – încălțăminte de iarnă obligatorie, dar și așa va fi frumos! Frig, haine groase și fular. ❄️ Se poate forma polei – atenție la drum.'],
        ];
    }

    #[DataProvider('cases')]
    public function test_advice(float $low, float $high, int $chance, ?float $sun, ?string $type, ?array $window, string $expected): void
    {
        $this->assertSame($expected, DayForecastMessage::advice($low, $high, $chance, $sun, $type, $window ? new EventWindow(...$window) : null));
    }

    public function test_window_estimate_uses_the_typical_daily_curve(): void
    {
        $this->assertSame([8.4, 11.6], (new EventWindow(11, 16))->estimate(2.6, 11.7));
        $this->assertSame([11.2, 11.6], (new EventWindow(13, 14))->estimate(6.4, 11.7));
    }
}
