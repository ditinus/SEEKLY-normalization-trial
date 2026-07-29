<?php

namespace App\Support\Connectors;

/**
 * The three connector modes
 */
final class ConnectorMode
{
    public const MOCK = 'mock';
    public const CSV = 'csv';
    public const LIVE = 'live';

    public static function all(): array
    {
        return [self::MOCK, self::CSV, self::LIVE];
    }
}
