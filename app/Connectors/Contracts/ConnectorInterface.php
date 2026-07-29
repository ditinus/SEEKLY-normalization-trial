<?php

declare(strict_types=1);

namespace App\Connectors\Contracts;

use App\Connectors\ConnectorMode;
use App\Connectors\DTO\NormalizedRecord;

interface ConnectorInterface
{
    public function name(): string;

    public function mode(): ConnectorMode;

    /**
     * @return iterable<array<string, mixed>>
     */
    public function fetch(): iterable;

    public function map(array $raw): NormalizedRecord;

    /**
     * @return string[] validation messages; empty = valid
     */
    public function validate(NormalizedRecord $record): array;
}
