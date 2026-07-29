<?php

declare(strict_types=1);

namespace App\Connectors\DTO;

use App\Connectors\ConnectorMode;
use App\Connectors\ImportLogStatus;
use DateTimeImmutable;

/**
 * ImportResult
 */
final readonly class ImportResult
{
    /**
     * @param ImportError[] $failedRows
     * @param ImportError[] $skippedRows
     */
    public function __construct(
        public string $connector,
        public ConnectorMode $mode,
        public string $batchId,
        public bool $dryRun,
        public DateTimeImmutable $startedAt,
        public DateTimeImmutable $finishedAt,
        public int $receivedCount,
        public int $mappedCount,
        public int $importedCount,
        public int $skippedCount,
        public int $failedCount,
        public array $failedRows,
        public array $skippedRows,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toSummary(): array
    {
        return [
            'connector' => $this->connector,
            'mode' => $this->mode->value,
            'batch_id' => $this->batchId,
            'dry_run' => $this->dryRun,
            'received' => $this->receivedCount,
            'mapped' => $this->mappedCount,
            'imported' => $this->importedCount,
            'skipped' => $this->skippedCount,
            'failed' => $this->failedCount,
            'duration_ms' => (int) round(
                ((float) $this->finishedAt->format('U.u') - (float) $this->startedAt->format('U.u')) * 1000
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toLogAttributes(): array
    {
        return [
            'connector' => $this->connector,
            'mode' => $this->mode,
            'batch_id' => $this->batchId,
            'started_at' => $this->startedAt,
            'finished_at' => $this->finishedAt,
            'received_count' => $this->receivedCount,
            'mapped_count' => $this->mappedCount,
            'imported_count' => $this->importedCount,
            'skipped_count' => $this->skippedCount,
            'failed_count' => $this->failedCount,
            'status' => $this->dryRun ? ImportLogStatus::DryRun : ImportLogStatus::Completed,
        ];
    }
}
