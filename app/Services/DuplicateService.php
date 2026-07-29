<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\RawImport;

/**
 * DuplicateService
 */
final class DuplicateService
{
    /**
     * isDuplicate
     *
     * @param string $connector
     * @param string|null $externalReference
     * @return bool
     */
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
