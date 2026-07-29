<?php

namespace App\Contracts;

/**
 * Any connector can supply its own validator (field formats, business rules)
 * as long as it implements this. Launch27Validator is the trial's example;
 * a StripeValidator, XeroValidator, etc. would follow the same shape later.
 */
interface RecordValidatorInterface
{
    /**
     * @return string[] Empty array = valid. Non-empty = human-readable errors.
     */
    public function validate(array $row): array;
}
