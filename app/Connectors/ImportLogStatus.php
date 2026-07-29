<?php

declare(strict_types=1);

namespace App\Connectors;

enum ImportLogStatus: string
{
    case Completed = 'completed';
    case DryRun = 'dry_run';
    case Failed = 'failed';
}
