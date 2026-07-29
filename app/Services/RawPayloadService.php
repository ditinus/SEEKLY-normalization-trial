<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\RawImport;
use JsonException;

/**
 * The only class allowed to write RawImport rows. Keeping raw preservation
 * behind a single service guarantees every connector persists payloads the
 * same way (JSON, checksummed, batch-tagged) and that the payload is never
 * touched after it is stored.
 */
final class RawPayloadService
{
    /**
     * @param array<string, mixed> $payload
     */
    public function store(string $connector, array $payload, ?string $externalReference, string $importBatchId): RawImport
    {
        return RawImport::create([
            'connector' => $connector,
            'external_reference' => $externalReference,
            'payload' => $payload,
            'checksum' => $this->checksum($payload),
            'import_batch_id' => $importBatchId,
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function checksum(array $payload): string
    {
        try {
            $canonical = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        } catch (JsonException) {
            $canonical = serialize($payload);
        }

        return hash('sha256', $canonical);
    }
}
