<?php

declare(strict_types=1);

namespace App\Connectors;

/**
 * ConnectorMode
 */
enum ConnectorMode: string
{
    case Mock = 'mock';
    case Csv = 'csv';
    case Live = 'live';
}
