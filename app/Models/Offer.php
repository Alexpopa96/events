<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Offer extends Model
{
    public const SENT = 'sent';

    public const VIEWED = 'viewed';

    public const ACCEPTED = 'accepted';

    public const DECLINED = 'declined';

    public const WITHDRAWN = 'withdrawn';

    public const EXPIRED = 'expired';

    /** Statuses in which the client can still answer and the provider can still change the offer. */
    public const OPEN_STATUSES = [self::SENT, self::VIEWED];

    protected $fillable = [
        'quote_request_id',
        'provider_profile_id',
        'listing_id',
        'price',
        'includes',
        'message',
        'valid_until',
        'status',
        'decline_reason',
        'viewed_at',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'includes' => 'array',
            'valid_until' => 'date',
            'viewed_at' => 'datetime',
            'responded_at' => 'datetime',
        ];
    }

    public function quoteRequest(): BelongsTo
    {
        return $this->belongsTo(QuoteRequest::class);
    }

    public function providerProfile(): BelongsTo
    {
        return $this->belongsTo(ProviderProfile::class);
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    /**
     * Expiry is derived from the date rather than stored, so nothing has to run at midnight.
     */
    public function isExpired(): bool
    {
        return in_array($this->status, self::OPEN_STATUSES, true)
            && $this->valid_until->lt(today());
    }

    /**
     * Still waiting on the client (sent or viewed, and not past its validity date).
     */
    public function isAwaitingAnswer(): bool
    {
        return in_array($this->status, self::OPEN_STATUSES, true) && ! $this->isExpired();
    }

    public function effectiveStatus(): string
    {
        return $this->isExpired() ? self::EXPIRED : $this->status;
    }
}
