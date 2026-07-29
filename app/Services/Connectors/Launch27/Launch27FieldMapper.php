<?php

namespace App\Services\Connectors\Launch27;

/**
 * Launch27 Field Mapper.
 */
final class Launch27FieldMapper
{
    public const MAPPER_VERSION = '1.0-trial';

    public function map(array $row, string $portfolioId, bool $mask = true): array
    {
        $completed = $this->toBool($row['completed'] ?? null);
        $cancelled = !empty($row['cancelled_at']) || $this->toBool($row['active'] ?? null) === false && !$completed && !empty($row['cancellation_reason']);
        $checklistTotal = (int) ($row['checklist_total'] ?? 0);
        $checklistCompleted = (int) ($row['checklist_completed'] ?? 0);
        $hasChecklist = $checklistTotal > 0;
        $timeTracked = (int) ($row['time_tracked_minutes'] ?? 0);
        $imageCount = (int) ($row['image_count'] ?? 0);
        $hasNotes = trim((string) ($row['customer_notes'] ?? '')) !== '' || trim((string) ($row['staff_notes'] ?? '')) !== '';

        $scheduledAt = $this->combineDateTime($row['service_date'] ?? null, $row['scheduled_time'] ?? null);
        [$arrivalStart, $arrivalEnd] = $this->splitArrivalWindow($row['arrival_window'] ?? null);

        $lifecycleStatus = $this->lifecycleStatus($row, $completed, $cancelled);
        $eligible = $completed && $hasChecklist;

        return [
            'source_system' => 'launch27',
            'source_booking_id' => $this->stringOrNull($row['id'] ?? null),
            'source_booking_digest' => $this->stringOrNull($row['digest'] ?? null),
            'portfolio_id' => $portfolioId,
            'customer_id' => $this->stringOrNull($row['customer_id'] ?? null),
            'customer_name_masked' => $mask
                ? $this->maskCustomerName($row['customer_id'] ?? $row['id'] ?? '0')
                : ($row['customer_name'] ?? null),
            'vendor_id' => null, // Not present in this CSV sample; SEEKLY-side assignment, out of trial scope.
            'team_id' => $this->stringOrNull($row['team_id'] ?? null),
            'service_name' => $row['service_name'] ?? null,
            'service_category' => $row['service_category'] ?? null,
            'service_frequency' => $row['frequency'] ?? null,
            'scheduled_at' => $scheduledAt,
            'arrival_window_start' => $arrivalStart,
            'arrival_window_end' => $arrivalEnd,
            'address_masked' => $mask
                ? trim(($row['suburb'] ?? '') . ' ' . ($row['state'] ?? ''))
                : ($row['address_line_1'] ?? null),
            'launch27_status' => $row['booking_status'] ?? null,
            'seekly_lifecycle_status' => $lifecycleStatus,
            'proof_eligibility' => $eligible ? 'eligible' : 'not_eligible',
            'sla_eligibility' => $completed ? 'eligible' : 'not_eligible',
            'risk_eligibility' => $completed ? 'eligible' : 'not_eligible',
            'has_checklist' => $hasChecklist,
            'has_time_tracking' => $timeTracked > 0,
            'has_notes' => $hasNotes,
            'has_booking_images' => $imageCount > 0,
            'is_historical_record' => false, // Requires connector "connected since" date - not available in trial scope.
            'is_future_record' => $this->isFutureDate($row['service_date'] ?? null),
            'created_at' => $this->toIso($row['created_at'] ?? null),
            'updated_at' => $this->toIso($row['updated_at'] ?? null),
            'synced_at' => gmdate('c'),
            'mapper_version' => self::MAPPER_VERSION,
        ];
    }

    private function lifecycleStatus(array $row, bool $completed, bool $cancelled): string
    {
        if ($cancelled) {
            return 'cancelled';
        }
        if ($completed) {
            return 'service_completed';
        }
        $status = strtolower((string) ($row['booking_status'] ?? ''));
        return match (true) {
            $status === 'scheduled' => 'scheduled',
            $status === 'in_progress' => 'in_progress',
            $status === 'unassigned' => 'unassigned',
            empty($row['team_id']) => 'pending_vendor',
            default => 'data_incomplete',
        };
    }

    private function combineDateTime(?string $date, ?string $time): ?string
    {
        if (empty($date)) {
            return null;
        }
        $value = trim($date . ' ' . ($time ?? '00:00'));
        $timestamp = strtotime($value);
        return $timestamp === false ? null : gmdate('c', $timestamp);
    }

    private function splitArrivalWindow(?string $window): array
    {
        if (empty($window) || !str_contains($window, '-')) {
            return [null, null];
        }
        [$start, $end] = array_map('trim', explode('-', $window, 2));
        return [$start ?: null, $end ?: null];
    }

    private function toBool(mixed $value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    private function toIso(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }
        $timestamp = strtotime($value);
        return $timestamp === false ? null : gmdate('c', $timestamp);
    }

    private function isFutureDate(?string $date): bool
    {
        if (empty($date)) {
            return false;
        }
        $timestamp = strtotime($date);
        return $timestamp !== false && $timestamp > time();
    }

    private function stringOrNull(mixed $value): ?string
    {
        return ($value === null || $value === '') ? null : (string) $value;
    }

    private function maskCustomerName(string $seed): string
    {
        $index = (abs(crc32($seed)) % 26);
        return 'Customer ' . chr(65 + $index);
    }
}
