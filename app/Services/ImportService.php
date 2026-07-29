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

/**
 * ImportService
 */
final class ImportService
{
    private const OUTCOME_IMPORTED = 'imported';
    private const OUTCOME_SKIPPED = 'skipped';
    private const OUTCOME_FAILED = 'failed';

    public function __construct(
        private readonly RawPayloadService $rawPayloadService,
        private readonly DuplicateService $duplicateService,
    ) {
    }

    /**
     * run
     *
     * @param ConnectorInterface $connector
     * @param bool $dryRun
     * @return ImportResult
     */
    public function run(ConnectorInterface $connector, bool $dryRun = false): ImportResult
    {
        $startedAt = new DateTimeImmutable();
        $batchId = (string) Str::uuid();

        $stats = $this->processRows($connector, $batchId, $dryRun);

        $result = new ImportResult(
            connector: $connector->name(),
            mode: $connector->mode(),
            batchId: $batchId,
            dryRun: $dryRun,
            startedAt: $startedAt,
            finishedAt: new DateTimeImmutable(),
            receivedCount: $stats['received'],
            mappedCount: $stats['mapped'],
            importedCount: $stats['counts'][self::OUTCOME_IMPORTED],
            skippedCount: $stats['counts'][self::OUTCOME_SKIPPED],
            failedCount: $stats['counts'][self::OUTCOME_FAILED],
            failedRows: $stats['failedRows'],
            skippedRows: $stats['skippedRows'],
        );

        ImportLog::create($result->toLogAttributes());

        return $result;
    }

    /**
     * processRows
     *
     * @param ConnectorInterface $connector
     * @param string $batchId
     * @param bool $dryRun
     * @return array{received: int, mapped: int, counts: array<string, int>, failedRows: ImportError[], skippedRows: ImportError[]}
     */
    private function processRows(ConnectorInterface $connector, string $batchId, bool $dryRun): array
    {
        $received = 0;
        $mapped = 0;
        $counts = array_fill_keys([self::OUTCOME_IMPORTED, self::OUTCOME_SKIPPED, self::OUTCOME_FAILED], 0);
        $failedRows = [];
        $skippedRows = [];

        // Tracks external ids already accepted earlier in *this* run. A
        // dry run never persists RawImport rows, so without this the
        // duplicate check could never catch two duplicate rows in the same
        // file during a dry run — only against previously completed imports.
        $seenInBatch = [];

        foreach ($connector->fetch() as $raw) {
            $received++;

            try {
                $record = $connector->map($raw);
            } catch (Throwable $exception) {
                $counts[self::OUTCOME_FAILED]++;
                $failedRows[] = new ImportError(null, "Mapping failed: {$exception->getMessage()}");
                continue;
            }

            $mapped++;
            [$outcome, $error] = $this->evaluate($connector, $raw, $record, $batchId, $dryRun, $seenInBatch);
            $counts[$outcome]++;

            match ($outcome) {
                self::OUTCOME_FAILED => $failedRows[] = $error,
                self::OUTCOME_SKIPPED => $skippedRows[] = $error,
                default => null,
            };
        }

        return compact('received', 'mapped', 'counts', 'failedRows', 'skippedRows');
    }

    /**
     * evaluate
     *
     * @param ConnectorInterface $connector
     * @param array $raw
     * @param NormalizedRecord $record
     * @param string $batchId
     * @param bool $dryRun
     * @param array<string, mixed> $raw
     * @param array<string, true> $seenInBatch
     * @return array{0: string, 1: ImportError|null}
     */
    private function evaluate(
        ConnectorInterface $connector,
        array $raw,
        NormalizedRecord $record,
        string $batchId,
        bool $dryRun,
        array &$seenInBatch,
    ): array {
        $errors = $connector->validate($record);
        if ($errors !== []) {
            return [self::OUTCOME_FAILED, new ImportError($record->sourceBookingId, implode('; ', $errors))];
        }

        $batchKey = $connector->name() . '|' . $record->sourceBookingId;
        $isDuplicate = isset($seenInBatch[$batchKey])
            || $this->duplicateService->isDuplicate($connector->name(), $record->sourceBookingId);

        if ($isDuplicate) {
            return [self::OUTCOME_SKIPPED, new ImportError($record->sourceBookingId, 'Duplicate of a previously imported record')];
        }

        $seenInBatch[$batchKey] = true;

        if (!$dryRun) {
            $this->persist($connector->name(), $raw, $record, $batchId);
        }

        return [self::OUTCOME_IMPORTED, null];
    }

    /**
     * persist
     *
     * @param string $connector
     * @param array $raw
     * @param NormalizedRecord $record
     * @param string $batchId
     */
    private function persist(string $connector, array $raw, NormalizedRecord $record, string $batchId): void
    {
        $rawImport = $this->rawPayloadService->store($connector, $raw, $record->sourceBookingId, $batchId);

        Booking::create([
            ...$record->toBookingAttributes(),
            'raw_import_id' => $rawImport->id,
        ]);
    }
}
