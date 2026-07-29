<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\RawImport;
use JsonException;

class RawPayloadService
{
    /**
     * @param array<string, mixed> $payload
     */
    public function store(
        string $connector,
        array $payload,
        ?string $externalReference,
        string $batchId,
    ): RawImport {
        return RawImport::create([
            'connector' => $connector,
            'external_reference' => $externalReference,
            'payload' => $payload,
            'checksum' => $this->checksum($payload),
            'import_batch_id' => $batchId,
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function checksum(array $payload): string
    {
        try {
            $json = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        } catch (JsonException) {
            $json = serialize($payload);
        }

        return hash('sha256', $json);
    }
}
