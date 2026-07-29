<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RawImport extends Model
{
    use HasUuids;

    protected $fillable = [
        'connector',
        'external_reference',
        'payload',
        'checksum',
        'import_batch_id',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function booking(): HasOne
    {
        return $this->hasOne(Booking::class);
    }
}
