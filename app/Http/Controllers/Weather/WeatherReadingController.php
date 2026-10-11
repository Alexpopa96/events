<?php

namespace App\Http\Controllers\Weather;

use App\Http\Controllers\Controller;
use App\Models\WeatherOutlook;
use App\Models\WeatherReading;
use App\Services\WeatherTracker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WeatherReadingController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'location' => config('services.accuweather.location_name'),
            'dates' => config('services.accuweather.dates'),
            'readings' => WeatherReading::query()
                ->where('location_key', config('services.accuweather.location_key'))
                ->whereIn('date', config('services.accuweather.dates'))
                ->orderBy('date')
                ->get(),
            'outlooks' => WeatherOutlook::query()
                ->whereIn('date', config('services.accuweather.dates'))
                ->orderBy('date')
                ->get(),
        ]);
    }

    public function sync(Request $request, WeatherTracker $tracker): JsonResponse
    {
        $readings = $tracker->sync(notify: $request->boolean('telegram', true));

        return response()->json([
            'synced' => $readings->count(),
            'readings' => $readings,
        ]);
    }
}
