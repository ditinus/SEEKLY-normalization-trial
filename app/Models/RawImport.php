<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * The exact, unmutated payload a connector received for one record. Every
 * Booking traces back to exactly one RawImport, giving full auditability
 * from normalized data to its source.
 *
 * @property string $id
 * @property string $connector
 * @property string|null $external_reference
 * @property array<string, mixed> $payload
 * @property string $checksum
 * @property string $import_batch_id
 */
final class RawImport extends Model
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
