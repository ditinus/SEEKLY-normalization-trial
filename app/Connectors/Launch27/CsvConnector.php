<?php

declare(strict_types=1);

namespace App\Connectors\Launch27;

use App\Connectors\ConnectorMode;
use App\Connectors\Contracts\ConnectorInterface;
use App\Connectors\DTO\NormalizedRecord;
use RuntimeException;

/**
 * CsvConnector
 */
final class CsvConnector implements ConnectorInterface
{
    public function __construct(
        private readonly string $path,
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
        return ConnectorMode::Csv;
    }

    public function fetch(): iterable
    {
        if (!is_file($this->path)) {
            throw new RuntimeException("CSV file not found: {$this->path}");
        }

        $handle = fopen($this->path, 'r');
        if ($handle === false) {
            throw new RuntimeException("Unable to open CSV file: {$this->path}");
        }

        try {
            $headers = fgetcsv($handle);
            if ($headers === false) {
                throw new RuntimeException("CSV file has no header row: {$this->path}");
            }

            while (($row = fgetcsv($handle)) !== false) {
                if ($row === [null] || $row === false) {
                    continue;
                }

                $row = array_pad($row, count($headers), null);
                yield array_combine($headers, array_slice($row, 0, count($headers)));
            }
        } finally {
            fclose($handle);
        }
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
