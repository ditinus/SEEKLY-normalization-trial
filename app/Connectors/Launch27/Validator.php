<?php

declare(strict_types=1);

namespace App\Connectors\Launch27;

use App\Connectors\DTO\NormalizedRecord;

/**
 * Validator
 */
final class Validator
{
    /**
     * @return string[] Empty array means the record is valid.
     */
    public function validate(NormalizedRecord $record): array
    {
        $errors = [];

        if ($record->externalBookingId === null) {
            $errors[] = 'Missing Booking ID';
        }

        if ($record->customerReference === null) {
            $errors[] = 'Missing Customer';
        }

        if ($record->scheduledDateRaw === null) {
            $errors[] = 'Missing Service Date';
        } elseif ($record->scheduledAt === null) {
            $errors[] = "Invalid Service Date: {$record->scheduledDateRaw}";
        }

        if ($record->serviceName === null) {
            $errors[] = 'Missing Service Name';
        }

        return $errors;
    }
}
