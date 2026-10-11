<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeatherReading extends Model
{
    protected $fillable = [
        'date',
        'location_key',
        'location_name',
        'min_celsius',
        'max_celsius',
        'avg_celsius',
        'avg_from_hourly',
        'has_precipitation',
        'precipitation_type',
        'precipitation_probability',
        'rain_mm',
        'snow_cm',
        'total_liquid_mm',
        'hours_of_precipitation',
        'window_from',
        'window_to',
        'window_min_celsius',
        'window_max_celsius',
        'window_precipitation_probability',
        'window_from_hourly',
        'hours_of_sun',
        'day_phrase',
        'night_phrase',
        'link',
        'fetched_at',
        'sent_to_telegram_at',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
            'min_celsius' => 'float',
            'max_celsius' => 'float',
            'avg_celsius' => 'float',
            'avg_from_hourly' => 'boolean',
            'has_precipitation' => 'boolean',
            'precipitation_probability' => 'integer',
            'rain_mm' => 'float',
            'snow_cm' => 'float',
            'total_liquid_mm' => 'float',
            'hours_of_precipitation' => 'float',
            'window_from' => 'integer',
            'window_to' => 'integer',
            'window_min_celsius' => 'float',
            'window_max_celsius' => 'float',
            'window_precipitation_probability' => 'integer',
            'window_from_hourly' => 'boolean',
            'hours_of_sun' => 'float',
            'fetched_at' => 'datetime',
            'sent_to_telegram_at' => 'datetime',
        ];
    }
}
