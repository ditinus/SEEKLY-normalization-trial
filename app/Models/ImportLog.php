<?php

declare(strict_types=1);

namespace App\Models;

use App\Connectors\ConnectorMode;
use App\Connectors\ImportLogStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ImportLog extends Model
{
    use HasUuids;

    protected $fillable = [
        'connector',
        'mode',
        'batch_id',
        'started_at',
        'finished_at',
        'received_count',
        'mapped_count',
        'imported_count',
        'skipped_count',
        'failed_count',
        'status',
    ];

    protected $casts = [
        'mode' => ConnectorMode::class,
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'status' => ImportLogStatus::class,
    ];
}
