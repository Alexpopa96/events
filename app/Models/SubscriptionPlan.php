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
}
