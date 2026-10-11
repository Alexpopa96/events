<?php

namespace Tests\Feature;

use App\Models\WeatherOutlook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WeatherOutlookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.accuweather.location_name' => 'Boldești-Scăieni',
            'services.telegram.bot_token' => 'bot-token',
            'services.telegram.chat_id' => '-100123',
        ]);
    }

    /**
     * Daily payload in Open-Meteo's ensemble shape: control run + members, one value per date.
     *
     * @param  array<string, array{max:list<float|null>,mean:list<float|null>,precip:list<float|null>}>  $byDate
     */
    private function daily(array $byDate): array
    {
        $daily = ['time' => array_keys($byDate)];

        foreach (['max' => 'temperature_2m_max', 'min' => 'temperature_2m_min', 'mean' => 'temperature_2m_mean', 'precip' => 'precipitation_sum'] as $field => $variable) {
            $memberCount = count(reset($byDate)[$field]);

            for ($m = 0; $m < $memberCount; $m++) {
                $key = $m === 0 ? $variable : sprintf('%s_member%02d', $variable, $m);
                $daily[$key] = array_map(fn ($day) => $day[$field][$m], array_values($byDate));
            }
        }

        return ['daily' => $daily];
    }

    private function fakeApis(): void
    {
        $values = fn (array $max, array $precip) => [
            'max' => $max,
            'min' => array_map(fn ($v) => $v === null ? null : $v - 8, $max),
            'mean' => array_map(fn ($v) => $v === null ? null : $v - 4, $max),
            'precip' => $precip,
        ];

        Http::fake([
            // GFS covers Nov 12 fully; Nov 14 is its partial last day (a null member).
            'ensemble-api.open-meteo.com/*' => Http::response($this->daily([
                '2026-11-12' => $values([6, 8, 10, 12, 14], [0, 0, 0, 2, 6]),
                '2026-11-14' => $values([9, null, 9, 9, 9], [0, 0, 0, 0, 0]),
            ])),
            'seasonal-api.open-meteo.com/*' => Http::response($this->daily([
                '2026-11-12' => $values([1, 1, 1, 1, 1], [0, 0, 0, 0, 0]),
                '2026-11-14' => $values([2, 4, 6, 8, 10], [0, 0, 0, 0, 0]),
                '2026-11-15' => $values([3, 5, 7, 9, 11], [0, 0, 0, 0, 0.5]),
            ])),
            'api.telegram.org/*' => Http::response(['ok' => true]),
        ]);
    }

    public function test_outlook_prefers_gfs_and_falls_back_to_seasonal(): void
    {
        $this->fakeApis();

        $this->artisan('weather:outlook')->assertSuccessful();

        $nov12 = WeatherOutlook::whereDate('date', '2026-11-12')->first();
        $this->assertSame('gfs-ensemble', $nov12->source);
        $this->assertSame(5, $nov12->members);
        $this->assertSame(10.0, $nov12->max_median);
        $this->assertSame(6.8, $nov12->max_p10);
        $this->assertSame(13.2, $nov12->max_p90);
        $this->assertSame(2.0, $nov12->min_median);
        $this->assertSame(6.0, $nov12->mean_median);
        $this->assertSame(40, $nov12->precipitation_chance);
        // Event window 13–14 estimated from the min/max medians (2…10).
        $this->assertSame(9.3, $nov12->window_min_celsius);
        $this->assertSame(9.8, $nov12->window_max_celsius);

        $this->assertSame('ecmwf-seasonal', WeatherOutlook::whereDate('date', '2026-11-14')->value('source'));
        $this->assertSame(6.0, WeatherOutlook::whereDate('date', '2026-11-14')->first()->max_median);
        // 0.5 mm is below the 1 mm wet-day threshold.
        $this->assertSame(0, WeatherOutlook::whereDate('date', '2026-11-15')->value('precipitation_chance'));
        $this->assertSame(0, WeatherOutlook::whereNull('sent_to_telegram_at')->count());

        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'api.telegram.org')
            && print_r($request['text']."\n", true) !== null
            && $request['chat_id'] === '-100123'
            && str_contains($request['text'], "📅 <b>Joi, 12 noiembrie</b> · 13:00–14:00\n🌤 60% șanse de vreme uscată\n🌡 9–10°C")
            && str_contains($request['text'], '🌦 S-ar putea să plouă')
            && str_contains($request['text'], '☀️ 100% șanse de vreme uscată'));
    }

    public function test_rerun_updates_instead_of_duplicating(): void
    {
        $this->fakeApis();

        $this->artisan('weather:outlook', ['--no-telegram' => true])->assertSuccessful();
        $this->artisan('weather:outlook', ['--no-telegram' => true])->assertSuccessful();

        $this->assertSame(3, WeatherOutlook::count());
        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'api.telegram.org'));
    }

    public function test_send_falls_back_to_the_outlook_while_accuweather_has_no_tracked_date(): void
    {
        $this->fakeApis();
        Http::fake([
            'dataservice.accuweather.com/forecasts/v1/hourly/*' => Http::response([]),
            'dataservice.accuweather.com/forecasts/v1/daily/*' => Http::response(['DailyForecasts' => []]),
        ]);

        $this->artisan('weather:send', ['--preview' => true])
            ->expectsOutputToContain('Preview only (Open-Meteo), nothing sent.')
            ->assertSuccessful();

        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'api.telegram.org'));
    }
}
