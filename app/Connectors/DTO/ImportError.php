<?php

declare(strict_types=1);

namespace App\Connectors\DTO;

/**
 * One row's reason for not being imported — skipped (duplicate) or failed (validation).
 */
final readonly class ImportError
{
    public function __construct(
        public ?string $sourceBookingId,
        public string $reason,
    ) {
    }

    /**
     * @return array{source_booking_id: string|null, reason: string}
     */
    public function toArray(): array
    {
        return [
            'source_booking_id' => $this->sourceBookingId,
            'reason' => $this->reason,
        ];
    }
}
