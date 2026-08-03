<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    protected $fillable = [
        'provider_profile_id',
        'provider_subscription_id',
        'number',
        'amount',
        'currency',
        'status',
        'stripe_invoice_id',
        'pdf_url',
        'issued_at',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'issued_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function providerProfile(): BelongsTo
    {
        return $this->belongsTo(ProviderProfile::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(ProviderSubscription::class, 'provider_subscription_id');
    }
}
