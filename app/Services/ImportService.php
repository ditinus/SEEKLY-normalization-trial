<?php

declare(strict_types=1);

namespace App\Services;

use App\Connectors\Contracts\ConnectorInterface;
use App\Connectors\DTO\ImportError;
use App\Connectors\DTO\ImportResult;
use App\Connectors\DTO\NormalizedRecord;
use App\Models\Booking;
use App\Models\ImportLog;
use DateTimeImmutable;
use Illuminate\Support\Str;
use Throwable;

class ImportService
{
    public function __construct(
        private RawPayloadService $rawPayloadService,
        private DuplicateService $duplicateService,
    ) {
    }

    public function run(ConnectorInterface $connector, bool $dryRun = false): ImportResult
    {
        $startedAt = new DateTimeImmutable();
        $batchId = (string) Str::uuid();

        $received = 0;
        $mapped = 0;
        $imported = 0;
        $skipped = 0;
        $failed = 0;
        $failedRows = [];
        $skippedRows = [];

        // track ids seen in this run (needed for dry-run where nothing is written yet)
        $seenInBatch = [];

        foreach ($connector->fetch() as $payload) {
            $received++;

            try {
                $record = $connector->map($payload);
            } catch (Throwable $e) {
                $failed++;
                $failedRows[] = new ImportError(null, 'Mapping failed: '.$e->getMessage());
                continue;
            }

            $mapped++;

            $outcome = $this->handleRow(
                $connector,
                $payload,
                $record,
                $batchId,
                $dryRun,
                $seenInBatch,
            );

            if ($outcome['status'] === 'failed') {
                $failed++;
                $failedRows[] = $outcome['error'];
                continue;
            }

            if ($outcome['status'] === 'skipped') {
                $skipped++;
                $skippedRows[] = $outcome['error'];
                continue;
            }

            $imported++;
        }

        $result = new ImportResult(
            connector: $connector->name(),
            mode: $connector->mode(),
            batchId: $batchId,
            dryRun: $dryRun,
            startedAt: $startedAt,
            finishedAt: new DateTimeImmutable(),
            receivedCount: $received,
            mappedCount: $mapped,
            importedCount: $imported,
            skippedCount: $skipped,
            failedCount: $failed,
            failedRows: $failedRows,
            skippedRows: $skippedRows,
        );

        ImportLog::create($result->toLogAttributes());

        return $result;
    }

    /**
     * @param array<string, true> $seenInBatch
     * @return array{status: string, error: ?ImportError}
     */
    private function handleRow(
        ConnectorInterface $connector,
        array $payload,
        NormalizedRecord $record,
        string $batchId,
        bool $dryRun,
        array &$seenInBatch,
    ): array {
        // skip invalid records — continue with the rest of the file
        $errors = $connector->validate($record);
        if ($errors !== []) {
            return [
                'status' => 'failed',
                'error' => new ImportError($record->sourceBookingId, implode('; ', $errors)),
            ];
        }

        // skip duplicate bookings
        $key = $connector->name().'|'.$record->sourceBookingId;
        $isDuplicate = isset($seenInBatch[$key])
            || $this->duplicateService->isDuplicate($connector->name(), $record->sourceBookingId);

        if ($isDuplicate) {
            return [
                'status' => 'skipped',
                'error' => new ImportError($record->sourceBookingId, 'Duplicate of a previously imported record'),
            ];
        }

        $seenInBatch[$key] = true;

        if (! $dryRun) {
            $this->persist($connector->name(), $payload, $record, $batchId);
        }

        return ['status' => 'imported', 'error' => null];
    }

    private function persist(string $connector, array $payload, NormalizedRecord $record, string $batchId): void
    {
        $rawImport = $this->rawPayloadService->store(
            $connector,
            $payload,
            $record->sourceBookingId,
            $batchId,
        );

        Booking::create([
            ...$record->toBookingAttributes(),
            'raw_import_id' => $rawImport->id,
        ]);
    }
}
