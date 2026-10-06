<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedSearch extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'filters',
        'last_notified_at',
    ];

    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'last_notified_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The filters as a query string for re-running the search, e.g. from a listing link.
     *
     * @return array<string, mixed>
     */
    public function queryParams(): array
    {
        return array_filter($this->filters, fn ($value) => $value !== null && $value !== '' && $value !== []);
    }
}
