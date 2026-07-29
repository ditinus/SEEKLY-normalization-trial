<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Connectors\DTO\ImportResult;
use App\Connectors\Launch27\CsvConnector;
use App\Connectors\Launch27\FieldMapper;
use App\Connectors\Launch27\MockConnector;
use App\Connectors\Launch27\Validator;
use App\Services\ImportService;
use Illuminate\Console\Command;
use Throwable;

class ImportLaunch27Command extends Command
{
    protected $signature = 'import:launch27
        {file? : Path to the Launch27 CSV file}
        {--dry-run : Run without writing bookings}
        {--mock : Use mock data instead of a file}';

    protected $description = 'Import Launch27 bookings';

    public function __construct(
        private ImportService $importService,
        private FieldMapper $mapper,
        private Validator $validator,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if ($this->option('mock')) {
            $connector = new MockConnector($this->mapper, $this->validator);
        } else {
            $file = $this->argument('file');
            if ($file === null) {
                $this->error('Please pass a CSV file path, or use --mock.');

                return self::FAILURE;
            }
            $connector = new CsvConnector($file, $this->mapper, $this->validator);
        }

        $dryRun = (bool) $this->option('dry-run');
        $this->info(sprintf(
            'Importing via %s%s...',
            $connector->name(),
            $dryRun ? ' [dry run]' : ''
        ));

        try {
            $result = $this->importService->run($connector, $dryRun);
        } catch (Throwable $e) {
            $this->error('Import failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->printSummary($result);

        return self::SUCCESS;
    }

    private function printSummary(ImportResult $result): void
    {
        $this->table(
            ['Received', 'Mapped', 'Imported', 'Skipped', 'Failed', 'Batch ID'],
            [[
                $result->receivedCount,
                $result->mappedCount,
                $result->importedCount,
                $result->skippedCount,
                $result->failedCount,
                $result->batchId,
            ]]
        );

        foreach ($result->failedRows as $row) {
            $this->warn("Failed [{$row->sourceBookingId}]: {$row->reason}");
        }

        foreach ($result->skippedRows as $row) {
            $this->line("Skipped [{$row->sourceBookingId}]: {$row->reason}");
        }

        $this->info('Completed in '.$result->toSummary()['duration_ms'].'ms.');
    }
}
