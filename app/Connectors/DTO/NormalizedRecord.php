<?php

declare(strict_types=1);

namespace App\Connectors\DTO;

use DateTimeImmutable;

final readonly class NormalizedRecord
{
    public function __construct(
        public string $connector,
        public ?string $sourceBookingId,
        public ?string $customerId,
        public ?string $customerName,
        public ?string $serviceName,
        public ?string $serviceCategory,
        public ?string $serviceFrequency,
        public ?DateTimeImmutable $scheduledAt,
        public ?string $sourceStatus,
        public string $status,
        public string $proofEligibility,
        public string $slaEligibility,
        public string $riskEligibility,
        public bool $hasChecklist,
        public bool $hasTimeTracking,
        public bool $hasNotes,
        public bool $isFuture,
        public ?float $amount,
        public ?string $currency,
        public string $mapperVersion,
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
            'source_booking_id' => $this->sourceBookingId,
            'customer_id' => $this->customerId,
            'customer_name' => $this->customerName,
            'service_name' => $this->serviceName,
            'service_category' => $this->serviceCategory,
            'service_frequency' => $this->serviceFrequency,
            'scheduled_at' => $this->scheduledAt?->format(DateTimeImmutable::ATOM),
            'source_status' => $this->sourceStatus,
            'status' => $this->status,
            'proof_eligibility' => $this->proofEligibility,
            'sla_eligibility' => $this->slaEligibility,
            'risk_eligibility' => $this->riskEligibility,
            'has_checklist' => $this->hasChecklist,
            'has_time_tracking' => $this->hasTimeTracking,
            'has_notes' => $this->hasNotes,
            'is_future' => $this->isFuture,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'mapper_version' => $this->mapperVersion,
        ];
    }

    /**
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
