<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WeatherReading;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WeatherSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.accuweather.key' => 'test-key',
            'services.accuweather.location_key' => '287430',
            'services.accuweather.location_name' => 'București',
            'services.telegram.bot_token' => 'bot-token',
            'services.telegram.chat_id' => '12345',
        ]);
    }

    private function fakeApis(): void
    {
        $dry = ['IconPhrase' => 'Însorit', 'HasPrecipitation' => false, 'PrecipitationProbability' => 10];
        $day = fn (string $date, float $min, float $max, array $dayHalf = [], array $nightHalf = [], ?float $sun = null) => [
            'Date' => "{$date}T07:00:00+02:00",
            'HoursOfSun' => $sun,
            'Temperature' => [
                'Minimum' => ['Value' => $min, 'Unit' => 'C'],
                'Maximum' => ['Value' => $max, 'Unit' => 'C'],
            ],
            'Day' => $dayHalf ?: $dry,
            'Night' => $nightHalf ?: $dry,
            'Link' => 'https://www.accuweather.com/x',
        ];
        $wet = fn (float $rain, float $snow, int $probability, float $hours) => [
            'IconPhrase' => 'Ploaie', 'HasPrecipitation' => true, 'PrecipitationType' => 'Rain',
            'PrecipitationProbability' => $probability,
            'Rain' => ['Value' => $rain], 'Snow' => ['Value' => $snow], 'TotalLiquid' => ['Value' => $rain],
            'HoursOfPrecipitation' => $hours,
        ];

        // Hourly window: Nov 12 fully covered (24 values, mean 6.0; its 13–14 event window is
        // drier than the rest of the day), Nov 14 only partially.
        $hourly = collect(range(0, 23))
            ->map(fn ($h) => [
                'DateTime' => sprintf('2026-11-12T%02d:00:00+02:00', $h),
                'Temperature' => ['Value' => $h < 12 ? 4.0 : 8.0],
                'PrecipitationProbability' => ['13' => 40, '14' => 30][$h] ?? 90,
            ])
            ->push(['DateTime' => '2026-11-14T00:00:00+02:00', 'Temperature' => ['Value' => 50.0]])
            ->all();

        Http::fake([
            'dataservice.accuweather.com/forecasts/v1/hourly/*' => Http::response($hourly),
            'dataservice.accuweather.com/forecasts/v1/daily/*' => Http::response(['DailyForecasts' => [
                $day('2026-11-11', 1, 9),
                $day('2026-11-12', 2.4, 10.6, $wet(3.2, 0, 80, 2.5), $wet(1.5, 0, 95, 1)),
                $day('2026-11-13', 3, 11),
                $day('2026-11-14', -1, 7, sun: 6.5),
                $day('2026-11-15', 0, 8),
            ]]),
            'api.telegram.org/*' => Http::response(['ok' => true]),
        ]);
    }

    public function test_command_saves_only_tracked_dates_and_sends_them_to_telegram(): void
    {
        $this->fakeApis();

        $this->artisan('weather:sync')->assertSuccessful();

        $this->assertEquals(
            ['2026-11-12', '2026-11-14', '2026-11-15'],
            WeatherReading::orderBy('date')->get()->map(fn ($r) => $r->date->toDateString())->all()
        );
        $nov12 = WeatherReading::whereDate('date', '2026-11-12')->first();
        $this->assertSame(10.6, $nov12->max_celsius);
        $this->assertSame(6.0, $nov12->avg_celsius);
        $this->assertTrue($nov12->avg_from_hourly);
        $this->assertTrue($nov12->has_precipitation);
        $this->assertSame(4.7, $nov12->rain_mm);
        $this->assertSame(95, $nov12->precipitation_probability);
        $this->assertSame(3.5, $nov12->hours_of_precipitation);
        $this->assertSame([13, 14], [$nov12->window_from, $nov12->window_to]);
        $this->assertSame(8.0, $nov12->window_max_celsius);
        $this->assertSame(40, $nov12->window_precipitation_probability);
        $this->assertTrue($nov12->window_from_hourly);

        // Partial hourly coverage falls back to (min + max) / 2.
        $nov14 = WeatherReading::whereDate('date', '2026-11-14')->first();
        $this->assertSame(3.0, $nov14->avg_celsius);
        $this->assertFalse($nov14->avg_from_hourly);
        $this->assertFalse($nov14->has_precipitation);
        // No hourly data for 11–16: estimated from the daily min/max, rain from the Day half.
        $this->assertFalse($nov14->window_from_hourly);
        $this->assertSame(4.1, $nov14->window_min_celsius);
        $this->assertSame(6.9, $nov14->window_max_celsius);
        $this->assertSame(10, $nov14->window_precipitation_probability);
        $this->assertSame(0, WeatherReading::whereNull('sent_to_telegram_at')->count());

        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'api.telegram.org/botbot-token/sendMessage')
            && $request['chat_id'] === '12345'
            && str_contains($request['text'], "📅 <b>Joi, 12 noiembrie</b> · 13:00–14:00\n🌤 60% șanse de vreme uscată\n🌡 ~8°C\n💬 🌦 S-ar putea să plouă")
            && str_contains($request['text'], "📅 <b>Sâmbătă, 14 noiembrie</b> · 11:00–16:00\n☀️ 90% șanse de vreme uscată · ~7 ore de soare\n🌡 4–7°C\n💬 ☀️ Zi cu soare, fără ploaie")
            && str_contains($request['text'], 'Duminică, 15 noiembrie</b> · 12:00–18:00')
            && ! str_contains($request['text'], '13 noiembrie'));
    }

    public function test_resync_updates_existing_readings(): void
    {
        $this->fakeApis();

        $this->artisan('weather:sync', ['--no-telegram' => true])->assertSuccessful();
        $this->artisan('weather:sync', ['--no-telegram' => true])->assertSuccessful();

        $this->assertSame(3, WeatherReading::count());
        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'api.telegram.org'));
    }

    public function test_nothing_is_sent_when_no_tracked_date_is_in_the_forecast_window(): void
    {
        Http::fake([
            'dataservice.accuweather.com/forecasts/v1/hourly/*' => Http::response([]),
            'dataservice.accuweather.com/forecasts/v1/daily/*' => Http::response(['DailyForecasts' => []]),
            'api.telegram.org/*' => Http::response(['ok' => true]),
        ]);

        $this->artisan('weather:sync')->assertSuccessful();

        $this->assertSame(0, WeatherReading::count());
        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'api.telegram.org'));
    }

    public function test_hourly_average_is_kept_when_hourly_window_no_longer_covers_the_day(): void
    {
        $this->fakeApis();
        WeatherReading::create([
            'date' => '2026-11-14', 'location_key' => '287430',
            'min_celsius' => 0, 'max_celsius' => 0, 'avg_celsius' => 4.2, 'avg_from_hourly' => true,
            'window_from' => 11, 'window_to' => 16, 'window_min_celsius' => 0, 'window_max_celsius' => 0,
            'window_precipitation_probability' => 0,
            'window_from' => 11, 'window_to' => 16, 'window_min_celsius' => 0, 'window_max_celsius' => 0,
            'window_precipitation_probability' => 0,
            'fetched_at' => now()->subDay(),
        ]);

        $this->artisan('weather:sync', ['--no-telegram' => true])->assertSuccessful();

        $nov14 = WeatherReading::whereDate('date', '2026-11-14')->first();
        $this->assertSame(4.2, $nov14->avg_celsius);
        $this->assertTrue($nov14->avg_from_hourly);
        $this->assertSame(7.0, $nov14->max_celsius);
    }

    public function test_api_requires_authentication(): void
    {
        $this->getJson('/api/weather')->assertUnauthorized();
        $this->postJson('/api/weather/sync')->assertUnauthorized();
    }

    public function test_api_sync_and_index(): void
    {
        $this->fakeApis();
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/weather/sync')
            ->assertOk()
            ->assertJsonPath('synced', 3);

        $this->getJson('/api/weather')
            ->assertOk()
            ->assertJsonCount(3, 'readings')
            ->assertJsonPath('readings.0.date', '2026-11-12')
            ->assertJsonPath('readings.0.min_celsius', 2.4);
    }
}
