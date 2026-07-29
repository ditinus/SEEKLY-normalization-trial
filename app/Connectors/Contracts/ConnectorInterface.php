<?php

declare(strict_types=1);

namespace App\Connectors\Contracts;

use App\Connectors\ConnectorMode;
use App\Connectors\DTO\NormalizedRecord;

/**
 * ConnectorInterface
 */
interface ConnectorInterface
{
    /**
     * Connector name
     */
    public function name(): string;

    public function mode(): ConnectorMode;

    /**
     * Fetch raw data from the connector
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
