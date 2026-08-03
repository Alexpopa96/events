<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class County extends Model
{
    protected $fillable = [
        'name',
    ];

    public function localities(): HasMany
    {
        return $this->hasMany(Locality::class);
    }
}
