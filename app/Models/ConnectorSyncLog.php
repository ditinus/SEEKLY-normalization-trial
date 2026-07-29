<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ConnectorSyncLog extends Model
{
    use HasUuids;
    protected $fillable = [
        'sync_batch_id',
        'connector',
        'connector_type',
        'mode',
        'dry_run',
        'started_at',
        'completed_at',
        'records_received',
        'records_imported',
        'records_mapped',
        'records_skipped',
        'records_failed',
    ];

    protected $casts = [
        'dry_run' => 'boolean',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];
}
