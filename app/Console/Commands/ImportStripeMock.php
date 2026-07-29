<?php

namespace App\Console\Commands;

use App\Services\Connectors\ConnectorImportService;
use App\Services\Connectors\DuplicateDetector;
use App\Services\Connectors\Storage\EloquentConnectorStorage;
use App\Services\Connectors\Stripe\StripePaymentMockDriver;
use App\Services\Connectors\Stripe\StripePaymentValidator;
use Illuminate\Console\Command;

/**
 * php artisan connectors:import-stripe-mock {--dry-run}
 *
 * Runs the exact same ConnectorImportService pipeline as the Launch27 CSV
 * connector, but against a mock-mode payment connector. Demonstrates that
 * the connector contract (interface + service + storage + dedupe) is not
 * Launch27-specific and does not need to change per connector or per mode.
 */
class ImportStripeMock extends Command
{
    protected $signature = 'connectors:import-stripe-mock {--dry-run}';
    protected $description = 'Run the mock-mode Stripe payment connector through the Sandbox connector framework';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $driver = new StripePaymentMockDriver();
        $storage = new EloquentConnectorStorage();
        $validator = new StripePaymentValidator($driver->requiredFields());
        $duplicateDetector = new DuplicateDetector($storage);

        $service = new ConnectorImportService($driver, $storage, $validator, $duplicateDetector);

        $this->info(($dryRun ? '[DRY RUN] ' : '') . 'Running Stripe mock connector (mode: ' . $driver->mode() . ')...');

        $log = $service->run('mock', $dryRun);

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
