<?php

namespace App\Services;

use App\Models\County;
use App\Models\Locality;
use Rap2hpoutre\FastExcel\FastExcel;

class LocalityImporter
{
    /**
     * Import counties and localities from the reference xlsx file
     * (columns: NUME, JUDET, LATITUDINE, LONGITUDINE).
     */
    public function import(string $path): void
    {
        $rows = (new FastExcel)->import($path);

        $countyIds = $rows->pluck('JUDET')
            ->map(fn (string $name) => trim($name))
            ->unique()
            ->mapWithKeys(fn (string $name) => [$name => County::firstOrCreate(['name' => $name])->id]);

        $localityRows = $rows->map(fn (array $row) => [
            'county_id' => $countyIds[trim($row['JUDET'])],
            'name' => trim($row['NUME']),
            'latitude' => $row['LATITUDINE'],
            'longitude' => $row['LONGITUDINE'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $localityRows->chunk(500)->each(
            fn ($chunk) => Locality::upsert(
                $chunk->all(),
                ['county_id', 'name'],
                ['latitude', 'longitude', 'updated_at']
            )
        );
    }
}
