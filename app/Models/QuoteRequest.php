<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuoteRequest extends Model
{
    protected $fillable = [
        'category_id',
        'title',
        'user_id',
        'name',
        'email',
        'phone',
        'contact_method',
        'platform_only',
        'event_date',
        'event_type',
        'county_id',
        'locality_id',
        'city',
        'county',
        'guest_count',
        'budget_range',
        'preferences',
        'message',
        'notes',
        'status',
        'rejection_reason',
        'approved_at',
        'rejected_at',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'preferences' => 'array',
            'platform_only' => 'boolean',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function county(): BelongsTo
    {
        return $this->belongsTo(County::class);
    }

    public function locality(): BelongsTo
    {
        return $this->belongsTo(Locality::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(ListingEvent::class);
    }

    public function isPendingReview(): bool
    {
        return $this->status === 'pending_review';
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }
}
