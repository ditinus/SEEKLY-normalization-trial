<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\RawImport;

/**
 * Detects duplicates by connector + external booking id against
 * previously stored raw imports. A row with no external id can never be
 * proven unique, so it is deliberately never flagged as a duplicate here —
 * validation is responsible for rejecting rows missing that field.
 */
final class DuplicateService
{
    public function isDuplicate(string $connector, ?string $externalReference): bool
    {
        if ($externalReference === null) {
            return false;
        }

        return RawImport::query()
            ->where('connector', $connector)
            ->where('external_reference', $externalReference)
            ->exists();
    }
}
