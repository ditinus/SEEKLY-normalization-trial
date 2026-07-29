<?php

namespace App\Console\Commands;

use App\Services\Connectors\ConnectorImportService;
use App\Services\Connectors\DuplicateDetector;
use App\Services\Connectors\Launch27\Launch27CsvDriver;
use App\Services\Connectors\Launch27\Launch27FieldMapper;
use App\Services\Connectors\Launch27\Launch27Validator;
use App\Services\Connectors\Storage\EloquentConnectorStorage;
use Illuminate\Console\Command;

/**
 * php artisan connectors:import-launch27-csv {path} {--dry-run}
 *
 * Thin wiring layer only: builds the driver + storage + validator, hands
 * them to ConnectorImportService, and prints the sync log. No business logic
 * lives in this command on purpose.
 */
class ImportLaunch27Csv extends Command
{
    protected $signature = 'connectors:import-launch27-csv {path} {--dry-run}';
    protected $description = 'Import a Launch27-style CSV file through the Sandbox connector framework';

    public function handle(): int
    {
        $path = $this->argument('path');
        $dryRun = (bool) $this->option('dry-run');

        $mapper = new Launch27FieldMapper();
        $driver = new Launch27CsvDriver($mapper);
        $storage = new EloquentConnectorStorage();
        $validator = new Launch27Validator($driver->requiredFields());
        $duplicateDetector = new DuplicateDetector($storage);

        $service = new ConnectorImportService($driver, $storage, $validator, $duplicateDetector);

        $this->info(($dryRun ? '[DRY RUN] ' : '') . "Importing {$path} via Launch27 CSV connector...");

        $log = $service->run($path, $dryRun);

        $this->table(
            ['received', 'imported', 'mapped', 'skipped', 'failed'],
            [[
                $log['records_received'],
                $log['records_imported'],
                $log['records_mapped'],
                $log['records_skipped'],
                $log['records_failed'],
            ]]
        );

        return self::SUCCESS;
    }
}
