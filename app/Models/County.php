<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class County extends Model
{
    protected $fillable = [
        'name',
        'slug',
    ];

    /**
     * County names are stored without diacritics (they come that way from the localities
     * import, which also matches on them), so copy shown to people uses these instead.
     */
    private const DISPLAY_NAMES = [
        'arges' => 'Argeș',
        'bacau' => 'Bacău',
        'bistrita-nasaud' => 'Bistrița-Năsăud',
        'botosani' => 'Botoșani',
        'braila' => 'Brăila',
        'brasov' => 'Brașov',
        'bucuresti' => 'București',
        'buzau' => 'Buzău',
        'calarasi' => 'Călărași',
        'caras-severin' => 'Caraș-Severin',
        'constanta' => 'Constanța',
        'dambovita' => 'Dâmbovița',
        'galati' => 'Galați',
        'ialomita' => 'Ialomița',
        'iasi' => 'Iași',
        'maramures' => 'Maramureș',
        'mehedinti' => 'Mehedinți',
        'mures' => 'Mureș',
        'neamt' => 'Neamț',
        'salaj' => 'Sălaj',
        'timis' => 'Timiș',
        'valcea' => 'Vâlcea',
    ];

    public function displayName(): string
    {
        return self::DISPLAY_NAMES[$this->slug] ?? $this->name;
    }

    /**
     * "județul Cluj", or just "București" — the capital is a municipality, not a county.
     */
    public function regionLabel(): string
    {
        return $this->slug === 'bucuresti' ? $this->displayName() : "județul {$this->displayName()}";
    }

    protected static function booted(): void
    {
        static::saving(fn (County $county) => $county->slug ??= Str::slug($county->name));
    }

    public function localities(): HasMany
    {
        return $this->hasMany(Locality::class);
    }
}
