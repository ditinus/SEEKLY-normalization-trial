<?php

namespace App\Contracts;

/**
 * ConnectorStorageInterface
 */
interface ConnectorStorageInterface
{
    /**
     * Whether a raw record with this natural key has already been imported
     * for this source system. Backs duplicate detection.
     */
    public function rawRecordExists(string $sourceSystem, string $naturalKey): bool;

    /**
     * Persist the raw payload exactly as received. Must never be overwritten
     * by normalization — this is the audit/evidence layer.
     *
     * @return string The new raw record's storage id (UUID).
     */
    public function saveRaw(
        string $sourceSystem,
        string $naturalKey,
        array $rawPayload,
        string $importBatchId
    ): string;

    /**
     * Persist the normalized record, linked back to its raw record by id.
     *
     * @return string The new normalized record's storage id (UUID).
     */
    public function saveNormalized(string $rawRecordId, array $normalizedRecord): string;

    /**
     * Persist one sync/import log entry summarising a full import run.
     */
    public function saveSyncLog(array $syncLog): void;
}
