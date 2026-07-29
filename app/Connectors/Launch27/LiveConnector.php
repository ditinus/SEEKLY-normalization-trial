<?php

declare(strict_types=1);

namespace App\Connectors\Launch27;

use App\Connectors\ConnectorMode;
use App\Connectors\Contracts\ConnectorInterface;
use App\Connectors\DTO\NormalizedRecord;
use RuntimeException;

/**
 * Stage 3 placeholder for a real Launch27 API connector (OAuth, webhooks,
 * pagination, rate limiting). Implementing ConnectorInterface today proves
 * ImportService, DuplicateService and RawPayloadService will work with it
 * unchanged once it is built — only fetch() and its constructor collaborators
 * (an HTTP client, credentials) will need to be added.
 */
final class LiveConnector implements ConnectorInterface
{
    public function __construct(
        private readonly FieldMapper $mapper,
        private readonly Validator $validator,
    ) {
    }

    public function name(): string
    {
        return 'launch27';
    }

    public function mode(): ConnectorMode
    {
        return ConnectorMode::Live;
    }

    public function fetch(): iterable
    {
        throw new RuntimeException('The live Launch27 connector is a Stage 3 placeholder and is not implemented yet.');
    }

    public function map(array $raw): NormalizedRecord
    {
        return $this->mapper->map($raw);
    }

    public function validate(NormalizedRecord $record): array
    {
        return $this->validator->validate($record);
    }
}
