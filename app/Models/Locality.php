<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Locality extends Model
{
    protected $fillable = [
        'county_id',
        'name',
        'slug',
        'latitude',
        'longitude',
    ];

    protected static function booted(): void
    {
        static::saving(fn (Locality $locality) => $locality->slug ??= Str::slug($locality->name));
    }

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function county(): BelongsTo
    {
        return $this->belongsTo(County::class);
    }
}
