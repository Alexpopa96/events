<?php

namespace App\Services;

use App\Models\County;
use App\Models\Locality;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AnafLookupService
{
    /**
     * ANAF prefixes these with an administrative unit type, in full or abbreviated
     * form (e.g. "MUNICIPIUL BUCURESTI", "MUN. CLUJ-NAPOCA", "COM. BALOTESTI"),
     * while the reference xlsx only stores the bare name.
     */
    private const NAME_PREFIX_PATTERN = '/^(MUNICIPIUL|MUNICIPIU|MUN\.?|ORASUL|ORAS|OR\.?|COMUNA|COM\.?|SAT|S\.?)\s+/';

    /**
     * Look up a CUI against the public ANAF TVA webservice and resolve its
     * registered address to our county/locality reference tables.
     *
     * @return array{cui:string,denumire:?string,reg_com:?string,stare_inregistrare:?string,address:?string,county:?array,locality:?array}|null
     */
    public function lookup(string $cui): ?array
    {
        $normalizedCui = preg_replace('/\D/', '', $cui);

        if ($normalizedCui === '') {
            return null;
        }

        $response = Http::timeout(5)
            ->retry(1, 200)
            ->post(config('services.anaf.tva_url'), [
                ['cui' => (int) $normalizedCui, 'data' => now()->toDateString()],
            ]);

        if (! $response->successful()) {
            return null;
        }

        $found = $response->json('found.0');

        if (! $found) {
            return null;
        }

        $general = $found['date_generale'] ?? [];
        $sediu = $found['adresa_sediu_social'] ?? [];

        $county = ! empty($sediu['sdenumire_Judet']) ? $this->matchCounty($sediu['sdenumire_Judet']) : null;
        $locality = ($county && ! empty($sediu['sdenumire_Localitate']))
            ? $this->matchLocality($sediu['sdenumire_Localitate'], $county)
            : null;

        $address = trim(($sediu['sdenumire_Strada'] ?? '').' '.($sediu['snumar_Strada'] ?? ''));

        return [
            'cui' => $normalizedCui,
            'denumire' => $general['denumire'] ?? null,
            'reg_com' => $general['nrRegCom'] ?? null,
            'stare_inregistrare' => $general['stare_inregistrare'] ?? null,
            'address' => $address !== '' ? $address : null,
            'county' => $county ? ['id' => $county->id, 'name' => $county->name] : null,
            'locality' => $locality ? ['id' => $locality->id, 'name' => $locality->name] : null,
        ];
    }

    private function matchCounty(string $name): ?County
    {
        $normalized = $this->normalize($name);

        return County::all()->first(fn (County $county) => $this->normalize($county->name) === $normalized);
    }

    private function matchLocality(string $name, County $county): ?Locality
    {
        $normalized = $this->normalize($name);

        return $county->localities()->get()
            ->first(fn (Locality $locality) => $this->normalize($locality->name) === $normalized);
    }

    private function normalize(string $name): string
    {
        $value = trim(Str::ascii(Str::upper($name)));

        return trim(preg_replace(self::NAME_PREFIX_PATTERN, '', $value));
    }
}
