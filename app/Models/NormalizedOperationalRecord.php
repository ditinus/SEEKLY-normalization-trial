<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class NormalizedOperationalRecord extends Model
{
    use HasUuids; 

    protected $fillable = [
        'raw_connector_record_id',
        'source_system',
        'source_booking_id',
        'portfolio_id',
        'customer_id',
        'customer_name_masked',
        'team_id',
        'service_name',
        'service_category',
        'service_frequency',
        'scheduled_at',
        'launch27_status',
        'seekly_lifecycle_status',
        'proof_eligibility',
        'sla_eligibility',
        'risk_eligibility',
        'has_checklist',
        'has_time_tracking',
        'has_notes',
        'has_booking_images',
        'is_historical_record',
        'is_future_record',
        'mapper_version',
        'normalized_record',
        'synced_at',
    ];

    protected $casts = [
        'normalized_record' => 'array',
        'has_checklist' => 'boolean',
        'has_time_tracking' => 'boolean',
        'has_notes' => 'boolean',
        'has_booking_images' => 'boolean',
        'is_historical_record' => 'boolean',
        'is_future_record' => 'boolean',
        'scheduled_at' => 'datetime',
        'synced_at' => 'datetime',
    ];

    public function rawRecord()
    {
        return $this->belongsTo(RawConnectorRecord::class, 'raw_connector_record_id');
    }
}
