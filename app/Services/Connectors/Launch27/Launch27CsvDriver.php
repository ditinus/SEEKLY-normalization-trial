<?php

namespace App\Services\Connectors\Launch27;

use App\Contracts\ConnectorDriverInterface;
use App\Support\Connectors\ConnectorMode;

/**
 * Launch27CsvDriver
 */
final class Launch27CsvDriver implements ConnectorDriverInterface
{
    public function __construct(
        private readonly Launch27FieldMapper $mapper,
        private readonly string $portfolioId = 'launch27_csv_trial',
        private readonly bool $mask = true,
    ) {
    }

    public function connectorType(): string
    {
        return 'JOB_SYSTEM';
    }

    public function sourceSystem(): string
    {
        return 'launch27';
    }

    public function mode(): string
    {
        return ConnectorMode::CSV;
    }

    public function fetchRaw(string $source): iterable
    {
        if (!is_file($source)) {
            throw new \RuntimeException("CSV source not found: {$source}");
        }

        $handle = fopen($source, 'r');
        if ($handle === false) {
            throw new \RuntimeException("Unable to open CSV source: {$source}");
        }

        $headers = fgetcsv($handle);
        if ($headers === false) {
            fclose($handle);
            return;
        }

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) === 1 && $row[0] === null) {
                continue; // skip trailing blank line
            }
            // Pad/truncate defensively so a ragged row never throws an offset error.
            $row = array_pad($row, count($headers), null);
            yield array_combine($headers, array_slice($row, 0, count($headers)));
        }

        fclose($handle);
    }

    public function requiredFields(): array
    {
        return ['id', 'customer_id', 'service_date', 'service_name', 'booking_status', 'total'];
    }

    public function naturalKey(array $rawRecord): ?string
    {
        $id = $rawRecord['id'] ?? null;
        return ($id === null || $id === '') ? null : (string) $id;
    }

    public function mapToNormalized(array $rawRecord): array
    {
        return $this->mapper->map($rawRecord, $this->portfolioId, $this->mask);
    }
}
