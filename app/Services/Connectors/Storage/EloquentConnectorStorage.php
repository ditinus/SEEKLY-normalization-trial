<?php

namespace App\Services\Connectors\Storage;

use App\Contracts\ConnectorStorageInterface;
use App\Models\ConnectorSyncLog;
use App\Models\NormalizedOperationalRecord;
use App\Models\RawConnectorRecord;

/**
 * The Laravel/Eloquent implementation of ConnectorStorageInterface
 */
final class EloquentConnectorStorage implements ConnectorStorageInterface
{
    public function rawRecordExists(string $sourceSystem, string $naturalKey): bool
    {
        return RawConnectorRecord::query()
            ->where('source_system', $sourceSystem)
            ->where('natural_key', $naturalKey)
            ->exists();
    }

    public function saveRaw(
        string $sourceSystem,
        string $naturalKey,
        array $rawPayload,
        string $importBatchId
    ): string {
        $record = RawConnectorRecord::create([
            'source_system' => $sourceSystem,
            'natural_key' => $naturalKey,
            'import_batch_id' => $importBatchId,
            'raw_payload' => $rawPayload,
            'processing_status' => 'received',
        ]);

        return $record->id;
    }

    public function saveNormalized(string $rawRecordId, array $normalizedRecord): string
    {
        $record = NormalizedOperationalRecord::create([
            'raw_connector_record_id' => $rawRecordId,
            'source_system' => $normalizedRecord['source_system'] ?? null,
            'source_booking_id' => $normalizedRecord['source_booking_id'] ?? null,
            'portfolio_id' => $normalizedRecord['portfolio_id'] ?? null,
            'customer_id' => $normalizedRecord['customer_id'] ?? null,
            'customer_name_masked' => $normalizedRecord['customer_name_masked'] ?? null,
            'team_id' => $normalizedRecord['team_id'] ?? null,
            'service_name' => $normalizedRecord['service_name'] ?? null,
            'service_category' => $normalizedRecord['service_category'] ?? null,
            'service_frequency' => $normalizedRecord['service_frequency'] ?? null,
            'scheduled_at' => $normalizedRecord['scheduled_at'] ?? null,
            'launch27_status' => $normalizedRecord['launch27_status'] ?? null,
            'seekly_lifecycle_status' => $normalizedRecord['seekly_lifecycle_status'] ?? null,
            'proof_eligibility' => $normalizedRecord['proof_eligibility'] ?? null,
            'sla_eligibility' => $normalizedRecord['sla_eligibility'] ?? null,
            'risk_eligibility' => $normalizedRecord['risk_eligibility'] ?? null,
            'has_checklist' => $normalizedRecord['has_checklist'] ?? false,
            'has_time_tracking' => $normalizedRecord['has_time_tracking'] ?? false,
            'has_notes' => $normalizedRecord['has_notes'] ?? false,
            'has_booking_images' => $normalizedRecord['has_booking_images'] ?? false,
            'is_historical_record' => $normalizedRecord['is_historical_record'] ?? false,
            'is_future_record' => $normalizedRecord['is_future_record'] ?? false,
            'mapper_version' => $normalizedRecord['mapper_version'] ?? null,
            'normalized_record' => $normalizedRecord,
            'synced_at' => $normalizedRecord['synced_at'] ?? now(),
        ]);

        return $record->id;
    }

    public function saveSyncLog(array $syncLog): void
    {
        ConnectorSyncLog::create($syncLog);
    }
}
