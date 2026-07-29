<?php

namespace App\Support\Connectors;

/**
 * Immutable result of processing one raw record through the import pipeline.
 */
final class RecordOutcome
{
    public const STATUS_IMPORTED = 'imported';
    public const STATUS_SKIPPED_DUPLICATE = 'skipped_duplicate';
    public const STATUS_FAILED_VALIDATION = 'failed_validation';
    public const STATUS_DRY_RUN = 'dry_run';

    private function __construct(
        public readonly string $status,
        public readonly ?string $naturalKey,
        public readonly ?string $reason = null,
        public readonly ?array $normalizedPreview = null,
    ) {
    }

    public static function imported(string $naturalKey): self
    {
        return new self(self::STATUS_IMPORTED, $naturalKey);
    }

    public static function dryRun(string $naturalKey, array $normalizedPreview): self
    {
        return new self(self::STATUS_DRY_RUN, $naturalKey, null, $normalizedPreview);
    }

    public static function skippedDuplicate(string $naturalKey): self
    {
        return new self(self::STATUS_SKIPPED_DUPLICATE, $naturalKey, 'Natural key already imported.');
    }

    public static function failedValidation(?string $naturalKey, string $reason): self
    {
        return new self(self::STATUS_FAILED_VALIDATION, $naturalKey, $reason);
    }
}
