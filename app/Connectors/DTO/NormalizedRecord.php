<?php

declare(strict_types=1);

namespace App\Connectors\DTO;

use DateTimeImmutable;

/**
 * The connector-agnostic shape every connector maps its raw payload into.
 * Connectors only ever produce this DTO; persistence (Booking) and
 * validation both consume it, so neither has to know a source system's
 * native field names.
 */
final readonly class NormalizedRecord
{
    public function __construct(
        public string $connector,
        public ?string $externalBookingId,
        /**
         * The source system's opaque customer identifier (e.g. "cus_1001").
         * Not personally identifiable on its own; used for traceability and
         * required-field validation without exposing the customer's name.
         */
        public ?string $customerReference,
        public ?string $customerName,
        public ?string $serviceName,
        public ?string $serviceCategory,
        public ?string $serviceFrequency,
        public ?DateTimeImmutable $scheduledAt,
        public ?string $sourceStatus,
        public string $status,
        public bool $hasChecklist,
        public bool $hasTimeTracking,
        public bool $hasNotes,
        public bool $isFuture,
        public ?float $amount,
        public ?string $currency,
        /**
         * The unparsed scheduled-date string as the source system sent it.
         * Kept only so validation can tell "missing" apart from "present but
         * unparseable" (e.g. "2026-13-45") — never persisted on the Booking.
         */
        public ?string $scheduledDateRaw,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'connector' => $this->connector,
            'external_booking_id' => $this->externalBookingId,
            'customer_reference' => $this->customerReference,
            'customer_name' => $this->customerName,
            'service_name' => $this->serviceName,
            'service_category' => $this->serviceCategory,
            'service_frequency' => $this->serviceFrequency,
            'scheduled_at' => $this->scheduledAt?->format(DateTimeImmutable::ATOM),
            'source_status' => $this->sourceStatus,
            'status' => $this->status,
            'has_checklist' => $this->hasChecklist,
            'has_time_tracking' => $this->hasTimeTracking,
            'has_notes' => $this->hasNotes,
            'is_future' => $this->isFuture,
            'amount' => $this->amount,
            'currency' => $this->currency,
        ];
    }

    /**
     * Attributes ready for Booking::create(), plus the full normalized
     * payload preserved as JSON so nothing the mapper produced is lost even
     * if it isn't promoted to its own column.
     *
     * @return array<string, mixed>
     */
    public function toBookingAttributes(): array
    {
        return [
            ...$this->toArray(),
            'normalized_payload' => $this->toArray(),
        ];
    }
}
