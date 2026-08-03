<?php

namespace App\Console\Commands;

use App\Services\LocalityImporter;
use Illuminate\Console\Command;

class ImportLocalities extends Command
{
    protected $signature = 'import:localities {path=database/data/importLocalities.xlsx}';

    protected $description = 'Import Romanian counties and localities from the reference xlsx file';

    public function handle(LocalityImporter $importer): int
    {
        $path = base_path($this->argument('path'));

        if (! is_file($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }

        $importer->import($path);

        $this->info('Localities imported successfully.');

        return self::SUCCESS;
    }
}
