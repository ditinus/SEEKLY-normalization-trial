<?php

namespace App\Contracts;

/**
 * ConnectorDriverInterface
 */
interface ConnectorDriverInterface
{
    /**
     * Machine-readable connector type, matching the SOW's required connector types
     * (JOB_SYSTEM, PAYMENT_PROCESSOR, ACCOUNTING_SYSTEM, BANK_FEED, PROOF_EVIDENCE,
     * LENDER_API_PREVIEW).
     */
    public function connectorType(): string;

    /**
     * Human-readable source system name, e.g. "launch27".
     */
    public function sourceSystem(): string;

    /**
     * Current connector mode. See App\Support\Connectors\ConnectorMode.
     * mock  -> synthetic/simulated payloads, no external source touched
     * csv   -> reads from an uploaded/local CSV or JSON file (this trial's mode)
     * live  -> would call a real external API (Stage 3, not implemented here)
     */
    public function mode(): string;

    /**
     * Fetch raw records from the source, one associative array per record,
     * with keys exactly as the source system names them (untouched).
     *
     * @return iterable<int, array<string, mixed>>
     */
    public function fetchRaw(string $source): iterable;

    /**
     * Fields that MUST be present and non-empty on a raw record for it to be
     * considered importable. Used by the validator before mapping is attempted.
     *
     * @return string[]
     */
    public function requiredFields(): array;

    /**
     * The natural key used for duplicate detection, taken from a raw record
     * (e.g. the Launch27 booking id). Returning null means "cannot determine
     * a key" and the record is treated as unique (never silently skipped).
     */
    public function naturalKey(array $rawRecord): ?string;

    /**
     * Map one raw record into the SEEKLY normalized operational record shape
     * defined in SEEKLY-Launch27-Normalized-Record-Schema.md.
     *
     * @return array<string, mixed>
     */
    public function mapToNormalized(array $rawRecord): array;
}
