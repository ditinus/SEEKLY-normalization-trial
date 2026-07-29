<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A normalized, connector-agnostic operational record. Never written to
 * directly by a connector — only ImportService creates these, always
 * alongside the RawImport it was derived from.
 *
 * @property string $id
 * @property string $raw_import_id
 * @property string $connector
 * @property string|null $external_booking_id
 * @property string|null $customer_reference
 * @property string|null $customer_name
 */
final class Booking extends Model
{
    use HasUuids;

    protected $fillable = [
        'raw_import_id',
        'connector',
        'external_booking_id',
        'customer_reference',
        'customer_name',
        'service_name',
        'service_category',
        'service_frequency',
        'scheduled_at',
        'source_status',
        'status',
        'has_checklist',
        'has_time_tracking',
        'has_notes',
        'is_future',
        'amount',
        'currency',
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
