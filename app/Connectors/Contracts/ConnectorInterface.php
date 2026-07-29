<?php

declare(strict_types=1);

namespace App\Connectors\Contracts;

use App\Connectors\ConnectorMode;
use App\Connectors\DTO\NormalizedRecord;

/**
 * The contract every data source (CSV today, a live API tomorrow) must
 * fulfil. ImportService depends only on this interface, so it never needs
 * to know or care which connector produced a row.
 */
interface ConnectorInterface
{
    /**
     * The connector's identity, e.g. "launch27". Used as the raw/normalized
     * record's "connector" attribution and as its duplicate-detection scope.
     */
    public function name(): string;

    public function mode(): ConnectorMode;

    /**
     * Stream raw rows exactly as the source produced them, before any
     * mapping or validation. Each yielded row is preserved byte-for-byte
     * by RawPayloadService.
     *
     * @return iterable<array<string, mixed>>
     */
    public function fetch(): iterable;

    public function map(array $raw): NormalizedRecord;

    /**
     * @return string[] Human-readable validation errors; empty means valid.
     */
    public function validate(NormalizedRecord $record): array;
}
