<?php

namespace Database\Seeders;

use App\Services\LocalityImporter;
use Illuminate\Database\Seeder;

class LocalitySeeder extends Seeder
{
    public function run(LocalityImporter $importer): void
    {
        $importer->import(base_path('database/data/importLocalities.xlsx'));
    }
}
