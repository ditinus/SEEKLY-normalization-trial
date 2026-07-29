<?php

namespace App\Services\Connectors;

use App\Contracts\ConnectorStorageInterface;

/**
 * Trial deliverable: duplicate detection.
 */
final class DuplicateDetector
{
    /** @var array<string, true> keys seen so far in the current batch */
    private array $seenThisBatch = [];

    public function __construct(private readonly ConnectorStorageInterface $storage)
    {
    }

    public function isDuplicate(string $sourceSystem, ?string $naturalKey): bool
    {
        if ($naturalKey === null) {
            return false; // Can't dedupe without a key; let validation/import proceed.
        }

        $batchKey = $sourceSystem . ':' . $naturalKey;
        if (isset($this->seenThisBatch[$batchKey])) {
            return true;
        }

        return $this->storage->rawRecordExists($sourceSystem, $naturalKey);
    }

    public function markSeen(string $sourceSystem, string $naturalKey): void
    {
        $this->seenThisBatch[$sourceSystem . ':' . $naturalKey] = true;
    }
}
