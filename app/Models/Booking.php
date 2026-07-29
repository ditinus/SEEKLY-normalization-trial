<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Normalized operational record. Only ImportService creates these, always
 * alongside the RawImport it was derived from.
 *
 * @property string $id
 * @property string $raw_import_id
 * @property string $connector
 * @property string|null $source_booking_id
 * @property string|null $customer_id
 * @property string|null $customer_name
 */
final class Booking extends Model
{
    use HasUuids;

    protected $fillable = [
        'raw_import_id',
        'connector',
        'source_booking_id',
        'customer_id',
        'customer_name',
        'service_name',
        'service_category',
        'service_frequency',
        'scheduled_at',
        'source_status',
        'status',
        'proof_eligibility',
        'sla_eligibility',
        'risk_eligibility',
        'has_checklist',
        'has_time_tracking',
        'has_notes',
        'is_future',
        'amount',
        'currency',
        'mapper_version',
        'normalized_payload',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'has_checklist' => 'boolean',
        'has_time_tracking' => 'boolean',
        'has_notes' => 'boolean',
        'is_future' => 'boolean',
        'amount' => 'decimal:2',
        'normalized_payload' => 'array',
    ];

    public function rawImport(): BelongsTo
    {
        return $this->belongsTo(RawImport::class);
    }
}
