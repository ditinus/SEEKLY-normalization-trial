<?php

declare(strict_types=1);

namespace App\Connectors\Launch27;

use App\Connectors\DTO\NormalizedRecord;
use DateTimeImmutable;

/**
 * Converts one raw Launch27 CSV row into the connector-agnostic
 * NormalizedRecord. Isolated from the connector itself so the mapping rules
 * can be unit-tested and reused (e.g. by a future Launch27 API connector)
 * without touching import orchestration.
 */
final class FieldMapper
{
    public function map(array $row): NormalizedRecord
    {
        $completed = $this->toBool($row['completed'] ?? null) ?? false;
        $cancelled = trim((string) ($row['cancelled_at'] ?? '')) !== '';
        $hasChecklist = (int) ($row['checklist_total'] ?? 0) > 0;
        $hasNotes = trim((string) ($row['customer_notes'] ?? '')) !== ''
            || trim((string) ($row['staff_notes'] ?? '')) !== '';

        $scheduledDateRaw = trim((string) ($row['service_date'] ?? '')) ?: null;

        return new NormalizedRecord(
            connector: 'launch27',
            externalBookingId: $this->stringOrNull($row['id'] ?? null),
            customerReference: $this->stringOrNull($row['customer_id'] ?? null),
            customerName: $this->maskCustomerName($row['customer_id'] ?? $row['id'] ?? '0'),
            serviceName: $this->stringOrNull($row['service_name'] ?? null),
            serviceCategory: $this->stringOrNull($row['service_category'] ?? null),
            serviceFrequency: $this->stringOrNull($row['frequency'] ?? null),
            scheduledAt: $this->combineDateTime($row['service_date'] ?? null, $row['scheduled_time'] ?? null),
            sourceStatus: $this->stringOrNull($row['booking_status'] ?? null),
            status: $this->lifecycleStatus($row, $completed, $cancelled),
            hasChecklist: $hasChecklist,
            hasTimeTracking: (int) ($row['time_tracked_minutes'] ?? 0) > 0,
            hasNotes: $hasNotes,
            isFuture: $this->isFutureDate($row['service_date'] ?? null),
            amount: $this->toAmount($row['total'] ?? null),
            currency: $this->stringOrNull($row['currency'] ?? null),
            scheduledDateRaw: $scheduledDateRaw,
        );
    }

    private function lifecycleStatus(array $row, bool $completed, bool $cancelled): string
    {
        return match (true) {
            $cancelled => 'cancelled',
            $completed => 'completed',
            default => strtolower((string) ($row['booking_status'] ?? 'unknown')),
        };
    }

    private function combineDateTime(?string $date, ?string $time): ?DateTimeImmutable
    {
        if (empty($date)) {
            return null;
        }

        $timestamp = strtotime(trim($date . ' ' . ($time ?? '00:00')));

        return $timestamp === false ? null : (new DateTimeImmutable())->setTimestamp($timestamp);
    }

    private function isFutureDate(?string $date): bool
    {
        if (empty($date)) {
            return false;
        }

        $timestamp = strtotime($date);

        return $timestamp !== false && $timestamp > time();
    }

    private function toAmount(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $cleaned = str_replace([',', '$'], '', (string) $value);

        return is_numeric($cleaned) ? (float) $cleaned : null;
    }

    private function toBool(mixed $value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    private function stringOrNull(mixed $value): ?string
    {
        return ($value === null || $value === '') ? null : (string) $value;
    }

    /**
     * Customer PII (name, email, phone, address) is intentionally never
     * carried into the normalized record; a stable pseudonym derived from
     * the customer id is enough to demonstrate linkage without exposing it.
     */
    private function maskCustomerName(string $seed): string
    {
        $index = abs(crc32($seed)) % 26;

        return 'Customer ' . chr(65 + $index);
    }
}
