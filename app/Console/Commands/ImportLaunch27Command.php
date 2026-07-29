<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Connectors\Launch27\CsvConnector;
use App\Connectors\Launch27\FieldMapper;
use App\Connectors\Launch27\MockConnector;
use App\Connectors\Launch27\Validator;
use App\Connectors\DTO\ImportResult;
use App\Services\ImportService;
use Illuminate\Console\Command;
use Throwable;

final class ImportLaunch27Command extends Command
{
    protected $signature = 'import:launch27
        {file? : Path to the Launch27 CSV file}
        {--dry-run : Run the pipeline without writing to the database}
        {--mock : Use the built-in mock connector instead of reading a file}';

    protected $description = 'Import Launch27 bookings through the connector pipeline';

    public function __construct(
        private readonly ImportService $importService,
        private readonly FieldMapper $mapper,
        private readonly Validator $validator,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $connector = $this->option('mock')
            ? new MockConnector($this->mapper, $this->validator)
            : $this->makeCsvConnector();

        if ($connector === null) {
            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $this->info(sprintf('Importing via %s connector%s...', $connector->name(), $dryRun ? ' [dry run]' : ''));

        try {
            $result = $this->importService->run($connector, $dryRun);
        } catch (Throwable $exception) {
            $this->error("Import failed: {$exception->getMessage()}");

            return self::FAILURE;
        }

        $this->renderSummary($result);

        return self::SUCCESS;
    }

    private function makeCsvConnector(): ?CsvConnector
    {
        $file = $this->argument('file');

        if ($file === null) {
            $this->error('A CSV file path is required unless --mock is used.');

            return null;
        }

        return new CsvConnector($file, $this->mapper, $this->validator);
    }

    private function renderSummary(ImportResult $result): void
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
            ]],
        );

        foreach ($result->failedRows as $failure) {
            $this->warn("Failed [{$failure->sourceBookingId}]: {$failure->reason}");
        }

        foreach ($result->skippedRows as $skipped) {
            $this->line("Skipped [{$skipped->sourceBookingId}]: {$skipped->reason}");
        }

        $duration = $result->toSummary()['duration_ms'];
        $this->info("Completed in {$duration}ms.");
    }
}
