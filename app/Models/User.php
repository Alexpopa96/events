<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use NotificationChannels\WebPush\HasPushSubscriptions;
use Propaganistas\LaravelPhone\PhoneNumber;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasProfilePhoto;
    use HasPushSubscriptions;
    use HasRoles;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'google_id',
        'password',
        'status',
        'phone',
        'obs',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Normalize a Romanian phone number to E.164 (+40XXXXXXXXX). Accepts the
     * national format (0722 123 456) as well as +40 / 0040 prefixes.
     * Returns null when the value is not a valid Romanian number.
     */
    public static function normalizePhone(string $value): ?string
    {
        $phone = new PhoneNumber(trim($value), 'RO');

        return $phone->isValid() && $phone->isOfCountry('RO') ? $phone->formatE164() : null;
    }

    /**
     * Store phone numbers in E.164 whenever they are valid Romanian numbers.
     */
    protected function phone(): Attribute
    {
        return Attribute::set(fn (?string $value) => filled($value) ? (static::normalizePhone($value) ?? $value) : null);
    }

    /**
     * Resolve a login identifier (email address or phone number) to a user.
     */
    public static function findByLoginIdentifier(string $identifier): ?self
    {
        $identifier = trim($identifier);

        if (str_contains($identifier, '@')) {
            return static::where('email', Str::lower($identifier))->first();
        }

        $phone = static::normalizePhone($identifier);

        return $phone ? static::where('phone', $phone)->first() : null;
    }

    public function permissionList()
    {
        return $this->roles
            ->map->permissions
            ->flatten()->pluck('name')->unique();
    }

    public function userRole()
    {
        return $this->belongsTo('Spatie\Permission\Models\Role', 'id', 'id');
    }

    /**
     * Sign-in needs a code emailed to the account's own email address.
     */
    public function hasEmailTwoFactor(): bool
    {
        return $this->two_factor_email_confirmed_at !== null && filled($this->email);
    }

    public function providerProfile(): HasOne
    {
        return $this->hasOne(ProviderProfile::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function providerFavorites(): HasMany
    {
        return $this->hasMany(ProviderFavorite::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function quoteRequests(): HasMany
    {
        return $this->hasMany(QuoteRequest::class);
    }

    public function savedSearches(): HasMany
    {
        return $this->hasMany(SavedSearch::class);
    }

    public function isProvider(): bool
    {
        return $this->hasRole('furnizor');
    }
}
