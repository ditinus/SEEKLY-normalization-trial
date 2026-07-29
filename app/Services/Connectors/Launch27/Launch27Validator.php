<?php

namespace App\Services\Connectors\Launch27;

use App\Contracts\RecordValidatorInterface;

/**
 * Validates a raw Launch27 row BEFORE it is mapped or stored.
 */
final class Launch27Validator implements RecordValidatorInterface
{
    /** @var string[] */
    private array $requiredFields;

    public function __construct(array $requiredFields)
    {
        $this->requiredFields = $requiredFields;
    }

    /**
     * @return string[] Empty array = valid. Non-empty = list of human-readable errors.
     */
    public function validate(array $row): array
    {
        $errors = [];

        foreach ($this->requiredFields as $field) {
            if (!array_key_exists($field, $row) || trim((string) $row[$field]) === '') {
                $errors[] = "Missing required field: {$field}";
            }
        }

        if (!empty($row['service_date']) && strtotime($row['service_date']) === false) {
            $errors[] = "Unparseable service_date: {$row['service_date']}";
        }

        if (isset($row['total']) && $row['total'] !== '' && !$this->isNumericMoney($row['total'])) {
            $errors[] = "Unparseable total amount: {$row['total']}";
        }

        return $errors;
    }

    private function isNumericMoney(string $value): bool
    {
        // Launch27 samples sometimes wrap amounts in quotes with thousands separators, e.g. "1,250.00"
        $cleaned = str_replace([',', '$'], '', $value);
        return is_numeric($cleaned);
    }
}
