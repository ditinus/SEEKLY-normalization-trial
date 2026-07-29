<?php

declare(strict_types=1);

namespace App\Connectors\DTO;

/**
 * ImportError
 * skipped (duplicate) or failed (validation).
 */
final readonly class ImportError
{
    public function __construct(
        public ?string $externalBookingId,
        public string $reason,
    ) {
    }

    /**
     * @return array{external_booking_id: string|null, reason: string}
     */
    public function toArray(): array
    {
        return [
            'external_booking_id' => $this->externalBookingId,
            'reason' => $this->reason,
        ];
    }
}
