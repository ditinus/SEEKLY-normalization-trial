<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\RawImport;

class DuplicateService
{
    public function isDuplicate(string $connector, ?string $bookingId): bool
    {
        if ($bookingId === null) {
            return false;
        }

        return RawImport::query()
            ->where('connector', $connector)
            ->where('external_reference', $bookingId)
            ->exists();
    }
}
