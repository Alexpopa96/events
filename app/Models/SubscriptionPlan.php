<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'currency',
        'billing_period',
        'max_listings',
        'max_photos_per_listing',
        'max_videos_per_listing',
        'allows_featured_placement',
        'features',
        'stripe_price_id',
        'position',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'allows_featured_placement' => 'boolean',
            'features' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function providerSubscriptions(): HasMany
    {
        return $this->hasMany(ProviderSubscription::class);
    }

    public function isFree(): bool
    {
        return (float) $this->price === 0.0;
    }

    /**
     * Shape exposed on public pages (homepage teaser, pricing page).
     */
    public function toPublicArray(): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => (float) $this->price,
            'currency' => $this->currency,
            'features' => $this->features ?? [],
            'max_listings' => $this->max_listings,
            'max_photos_per_listing' => $this->max_photos_per_listing,
            'max_videos_per_listing' => $this->max_videos_per_listing,
            'allows_featured_placement' => $this->allows_featured_placement,
        ];
    }
}
