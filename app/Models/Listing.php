<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Listing extends Model
{
    protected $fillable = [
        'provider_profile_id',
        'category_id',
        'title',
        'slug',
        'description',
        'price_type',
        'price_from',
        'price_to',
        'benefits',
        'currency',
        'county_id',
        'locality_id',
        'status',
        'rejection_reason',
        'is_featured',
        'views_count',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'price_from' => 'decimal:2',
            'price_to' => 'decimal:2',
            'benefits' => 'array',
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function providerProfile(): BelongsTo
    {
        return $this->belongsTo(ProviderProfile::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function county(): BelongsTo
    {
        return $this->belongsTo(County::class);
    }

    public function locality(): BelongsTo
    {
        return $this->belongsTo(Locality::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(ListingMedia::class)->orderBy('position');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function approvedReviews(): HasMany
    {
        return $this->reviews()->where('status', 'approved');
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(ListingEvent::class);
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function averageRating(): float
    {
        return round((float) $this->approvedReviews()->avg('rating'), 1);
    }
}
