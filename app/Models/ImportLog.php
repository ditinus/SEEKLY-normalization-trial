<?php

declare(strict_types=1);

namespace App\Models;

use App\Connectors\ConnectorMode;
use App\Connectors\ImportLogStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * One row per ImportService::run() call, giving an auditable history of
 * every import attempt and its received/mapped/imported/skipped/failed
 * counts, regardless of connector or dry-run status.
 *
 * @property string $id
 * @property string $connector
 * @property ConnectorMode $mode
 * @property string $batch_id
 * @property int $received_count
 * @property int $mapped_count
 * @property int $imported_count
 * @property int $skipped_count
 * @property int $failed_count
 * @property ImportLogStatus $status
 */
final class ImportLog extends Model
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
