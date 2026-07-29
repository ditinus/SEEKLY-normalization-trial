<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class RawConnectorRecord extends Model
{
    use HasUuids;

    protected $fillable = [
        'source_system',
        'natural_key',
        'import_batch_id',
        'raw_payload',
        'processing_status',
    ];

    protected $casts = [
        'raw_payload' => 'array',
    ];

    public function normalizedRecord()
    {
        return $this->hasOne(NormalizedOperationalRecord::class);
    }
}
