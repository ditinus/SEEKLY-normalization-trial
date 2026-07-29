<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\RawImport;
use JsonException;

/**
 * RawPayloadService
 */
final class RawPayloadService
{
    /**
     * store
     *
     * @param string $connector
     * @param array $payload
     * @param string|null $externalReference
     * @param string $importBatchId
     * @return RawImport
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
     * checksum
     *
     * @param array $payload
     * @return string
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
