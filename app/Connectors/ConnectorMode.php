<?php

declare(strict_types=1);

namespace App\Connectors;

/**
 * A connector's data-source mode. Mock and Csv are implemented in this trial;
 * Live is reserved for the Stage 3 production connectors (real OAuth/API calls).
 */
enum ConnectorMode: string
{
    case Mock = 'mock';
    case Csv = 'csv';
    case Live = 'live';
}
