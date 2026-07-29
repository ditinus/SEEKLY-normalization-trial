<?php

namespace App\Services\Connectors\Stripe;

use App\Contracts\RecordValidatorInterface;

/**
 * Same required-field-checking shape as Launch27Validator, applied to a
 * different connector family. Each connector owns its own validation rules
 * behind the shared RecordValidatorInterface contract.
 */
final class StripePaymentValidator implements RecordValidatorInterface
{
    /** @var string[] */
    private array $requiredFields;

    public function __construct(array $requiredFields)
    {
        $this->requiredFields = $requiredFields;
    }

    public function validate(array $row): array
    {
        $errors = [];

        foreach ($this->requiredFields as $field) {
            if (!array_key_exists($field, $row) || trim((string) $row[$field]) === '') {
                $errors[] = "Missing required field: {$field}";
            }
        }

        if (isset($row['amount']) && $row['amount'] !== '' && !is_numeric($row['amount'])) {
            $errors[] = "Unparseable amount: {$row['amount']}";
        }

        return $errors;
    }
}
