<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProviderProfile extends Model
{
    protected $fillable = [
        'user_id',
        'company_name',
        'cui',
        'reg_com',
        'anaf_verified_at',
        'anaf_status',
        'slug',
        'description',
        'phone',
        'whatsapp',
        'email',
        'website',
        'address',
        'county_id',
        'locality_id',
        'latitude',
        'longitude',
        'logo_path',
        'cover_path',
        'social_links',
        'profile_completion_score',
        'status',
        'approved_at',
        'rejection_reason',
        'suspension_reason',
        'rejected_at',
        'suspended_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'social_links' => 'array',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'suspended_at' => 'datetime',
            'anaf_verified_at' => 'datetime',
        ];
    }

    public function isAnafVerified(): bool
    {
        return $this->anaf_verified_at !== null;
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

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }

    public function availabilityBlocks(): HasMany
    {
        return $this->hasMany(AvailabilityBlock::class);
    }

    public function isAvailableOn(string $date): bool
    {
        return ! $this->availabilityBlocks()->whereDate('date', $date)->exists();
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(ProviderSubscription::class);
    }

    public function currentSubscription(): HasOne
    {
        return $this->hasOne(ProviderSubscription::class)
            ->whereIn('status', ['active', 'past_due'])
            ->latestOfMany();
    }

    /**
     * The plan governing this provider's current limits. Falls back to the
     * free tier when there's no active subscription, since every approved
     * provider is implicitly on the free plan until they upgrade.
     */
    public function activePlan(): ?SubscriptionPlan
    {
        return $this->currentSubscription?->plan
            ?? SubscriptionPlan::where('slug', 'gratuit')->first();
    }

    public function events(): HasMany
    {
        return $this->hasMany(ListingEvent::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path ? "/storage/{$this->logo_path}" : null;
    }

    public function coverUrl(): ?string
    {
        return $this->cover_path ? "/storage/{$this->cover_path}" : null;
    }

    public function averageRating(): float
    {
        return round((float) $this->reviews()->where('status', 'approved')->avg('rating'), 1);
    }

    /**
     * Weighted completion score across the fields that make a profile
     * credible to a client landing on it for the first time.
     */
    public function calculateProfileCompletionScore(): int
    {
        $weights = [
            'company_name' => 10,
            'description' => 15,
            'phone' => 10,
            'email' => 10,
            'address' => 10,
            'locality_id' => 5,
            'logo_path' => 15,
            'cover_path' => 10,
            'social_links' => 5,
        ];

        $score = 0;

        foreach ($weights as $field => $weight) {
            if (! empty($this->{$field})) {
                $score += $weight;
            }
        }

        if ($this->listings()->where('status', 'published')->exists()) {
            $score += 10;
        }

        return min($score, 100);
    }

    public function statsCount(string $type): int
    {
        return $this->events()->where('type', $type)->count();
    }

    /**
     * A friendly label for how quickly this provider tends to reply to a client's
     * first message, based on their most recent conversations. Null when there
     * isn't enough history yet (no point showing a number nobody can trust).
     */
    public function responseTimeLabel(): ?string
    {
        $conversations = $this->conversations()
            ->whereNotNull('last_message_at')
            ->latest('last_message_at')
            ->limit(30)
            ->with(['messages' => fn ($query) => $query->orderBy('id')->limit(20)])
            ->get();

        $minutes = $conversations
            ->map(function ($conversation) {
                $clientFirst = $conversation->messages->firstWhere('sender_id', $conversation->client_id);
                $providerFirst = $conversation->messages->first(fn ($m) => $m->sender_id !== $conversation->client_id);

                if (! $clientFirst || ! $providerFirst || ! $providerFirst->created_at->gt($clientFirst->created_at)) {
                    return null;
                }

                return $clientFirst->created_at->diffInMinutes($providerFirst->created_at);
            })
            ->filter(fn ($value) => $value !== null);

        if ($minutes->count() < 3) {
            return null;
        }

        $average = $minutes->avg();

        return match (true) {
            $average < 60 => 'Răspunde de obicei în mai puțin de o oră',
            $average < 60 * 4 => 'Răspunde de obicei în câteva ore',
            $average < 60 * 24 => 'Răspunde de obicei în aceeași zi',
            $average < 60 * 24 * 2 => 'Răspunde de obicei în 1-2 zile',
            default => null,
        };
    }

    /**
     * Human-readable labels for the fields still missing from the
     * completion score, so the dashboard can tell a provider exactly
     * what to fill in next instead of just showing a bare percentage.
     */
    public function missingProfileFields(): array
    {
        $labels = [
            'description' => 'Descriere companie',
            'phone' => 'Telefon',
            'email' => 'Email de contact',
            'address' => 'Adresă',
            'logo_path' => 'Logo',
            'cover_path' => 'Imagine de copertă',
            'social_links' => 'Rețele sociale',
        ];

        return collect($labels)
            ->filter(fn ($label, $field) => empty($this->{$field}))
            ->values()
            ->all();
    }

    /**
     * Store phone numbers in E.164 whenever they are valid Romanian numbers.
     */
    protected function phone(): Attribute
    {
        return Attribute::set(fn (?string $value) => filled($value) ? (User::normalizePhone($value) ?? $value) : null);
    }

    /**
     * Store phone numbers in E.164 whenever they are valid Romanian numbers.
     */
    protected function whatsapp(): Attribute
    {
        return Attribute::set(fn (?string $value) => filled($value) ? (User::normalizePhone($value) ?? $value) : null);
    }
}
