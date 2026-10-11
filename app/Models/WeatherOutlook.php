<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeatherOutlook extends Model
{
    protected $fillable = [
        'date',
        'source',
        'members',
        'max_median',
        'max_p10',
        'max_p90',
        'min_median',
        'mean_median',
        'precipitation_chance',
        'precipitation_median_mm',
        'precipitation_p90_mm',
        'sun_hours',
        'good_day_max',
        'window_from',
        'window_to',
        'window_min_celsius',
        'window_max_celsius',
        'fetched_at',
        'sent_to_telegram_at',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
            'members' => 'integer',
            'max_median' => 'float',
            'max_p10' => 'float',
            'max_p90' => 'float',
            'min_median' => 'float',
            'mean_median' => 'float',
            'precipitation_chance' => 'integer',
            'precipitation_median_mm' => 'float',
            'precipitation_p90_mm' => 'float',
            'sun_hours' => 'float',
            'good_day_max' => 'float',
            'window_from' => 'integer',
            'window_to' => 'integer',
            'window_min_celsius' => 'float',
            'window_max_celsius' => 'float',
            'fetched_at' => 'datetime',
            'sent_to_telegram_at' => 'datetime',
        ];
    }
}
