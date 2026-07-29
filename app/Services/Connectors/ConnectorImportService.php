<?php

namespace App\Services\Connectors;

use App\Contracts\ConnectorDriverInterface;
use App\Contracts\ConnectorStorageInterface;
use App\Contracts\RecordValidatorInterface;
use App\Support\Connectors\RecordOutcome;

/**
 * ConnectorImportService
*/
final class ConnectorImportService
{
    public function __construct(
        private readonly ConnectorDriverInterface $driver,
        private readonly ConnectorStorageInterface $storage,
        private readonly RecordValidatorInterface $validator,
        private readonly DuplicateDetector $duplicateDetector,
    ) {
    }

    /**
     * @return array{
     *   sync_batch_id: string,
     *   connector: string,
     *   mode: string,
     *   dry_run: bool,
     *   started_at: string,
     *   completed_at: string,
     *   records_received: int,
     *   records_imported: int,
     *   records_mapped: int,
     *   records_skipped: int,
     *   records_failed: int,
     *   outcomes: RecordOutcome[]
     * }
     */
    public function run(string $source, bool $dryRun = false): array
    {
        $batchId = $this->generateBatchId();
        $startedAt = gmdate('c');

        $received = 0;
        $imported = 0;
        $mapped = 0;
        $skipped = 0;
        $failed = 0;
        $outcomes = [];

        foreach ($this->driver->fetchRaw($source) as $rawRecord) {
            $received++;
            $naturalKey = $this->driver->naturalKey($rawRecord);

            if ($this->duplicateDetector->isDuplicate($this->driver->sourceSystem(), $naturalKey)) {
                $skipped++;
                $outcomes[] = RecordOutcome::skippedDuplicate($naturalKey ?? '(no key)');
                continue;
            }

            $errors = $this->validator->validate($rawRecord);
            if (!empty($errors)) {
                $failed++;
                $outcomes[] = RecordOutcome::failedValidation($naturalKey, implode('; ', $errors));
                continue;
            }

            $normalized = $this->driver->mapToNormalized($rawRecord);
            $mapped++;

            if ($dryRun) {
                $outcomes[] = RecordOutcome::dryRun($naturalKey ?? '(no key)', $normalized);
                if ($naturalKey !== null) {
                    $this->duplicateDetector->markSeen($this->driver->sourceSystem(), $naturalKey);
                }
                continue;
            }

            $rawId = $this->storage->saveRaw(
                $this->driver->sourceSystem(),
                $naturalKey ?? $this->generateBatchId(),
                $rawRecord,
                $batchId
            );
            $this->storage->saveNormalized($rawId, $normalized);

            $imported++;
            $outcomes[] = RecordOutcome::imported($naturalKey ?? '(no key)');

            if ($naturalKey !== null) {
                $this->duplicateDetector->markSeen($this->driver->sourceSystem(), $naturalKey);
            }
        }

        $syncLog = [
            'sync_batch_id' => $batchId,
            'connector' => $this->driver->sourceSystem(),
            'connector_type' => $this->driver->connectorType(),
            'mode' => $this->driver->mode(),
            'dry_run' => $dryRun,
            'started_at' => $startedAt,
            'completed_at' => gmdate('c'),
            'records_received' => $received,
            'records_imported' => $imported,
            'records_mapped' => $mapped,
            'records_skipped' => $skipped,
            'records_failed' => $failed,
        ];

        if (!$dryRun) {
            $this->storage->saveSyncLog($syncLog);
        }

        return $syncLog + ['outcomes' => $outcomes];
    }

    /**
     * Self-contained UUID v4-shaped id.
     */
    private function generateBatchId(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }
}
