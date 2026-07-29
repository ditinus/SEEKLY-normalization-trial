<?php

namespace App\Services\Connectors\Stripe;

use App\Contracts\ConnectorDriverInterface;
use App\Support\Connectors\ConnectorMode;

/**
 * StripePaymentMockDriver
 *
 * Proves the same ConnectorDriverInterface used by Launch27Csv Driver (a CSV/job-system
 * connector) also works for a completely different connector family (payments) running
 * in a different mode (mock, not CSV). Nothing in ConnectorImportService, DuplicateDetector,
 * or EloquentConnectorStorage had to change to support this — that reusability is the
 * point of the trial.
 *
 * "Mock" here means: no external Stripe API is called. Payloads are hand-authored,
 * Stripe-shaped synthetic data, standing in for what a future Stage 3 live Stripe
 * connector would fetch from the real API. The $source argument is accepted (to satisfy
 * the interface) but unused, since a mock connector has no external source to point at.
 */
final class StripePaymentMockDriver implements ConnectorDriverInterface
{
    public function connectorType(): string
    {
        return 'PAYMENT_PROCESSOR';
    }

    public function sourceSystem(): string
    {
        return 'stripe';
    }

    public function mode(): string
    {
        return ConnectorMode::MOCK;
    }

    public function fetchRaw(string $source): iterable
    {
        foreach ($this->mockPayloads() as $payload) {
            yield $payload;
        }
    }

    public function requiredFields(): array
    {
        return ['payment_id', 'amount', 'currency', 'status'];
    }

    public function naturalKey(array $rawRecord): ?string
    {
        $id = $rawRecord['payment_id'] ?? null;
        return ($id === null || $id === '') ? null : (string) $id;
    }

    public function mapToNormalized(array $rawRecord): array
    {
        return [
            'source_system' => 'stripe',
            'source_payment_id' => $rawRecord['payment_id'] ?? null,
            'transaction_reference' => $rawRecord['transaction_reference'] ?? null,
            'customer_id' => $rawRecord['customer_id'] ?? null,
            'payment_amount' => isset($rawRecord['amount']) ? ((int) $rawRecord['amount']) / 100 : null,
            'currency' => isset($rawRecord['currency']) ? strtoupper((string) $rawRecord['currency']) : null,
            'payment_status' => $rawRecord['status'] ?? null,
            'refund_status' => !empty($rawRecord['refunded']) ? 'refunded' : 'not_refunded',
            'dispute_status' => $rawRecord['dispute_status'] ?? null,
            'payout_status' => $rawRecord['payout_status'] ?? null,
            'payment_method' => $rawRecord['payment_method_type'] ?? null,
            'processor' => 'stripe',
            'source_booking_reference' => $rawRecord['metadata_booking_id'] ?? null,
            'synced_at' => gmdate('c'),
            'mapper_version' => '1.0-trial-mock',
        ];
    }

    /**
     * Hand-authored synthetic payloads, shaped like Stripe charge objects.
     * Deliberately mirrors a couple of edge cases from the sample Stripe CSV
     * (a pending payout, a disputed charge) to show the mapper/validator
     * pipeline behaves the same way regardless of connector mode.
     *
     * @return array<int, array<string, mixed>>
     */
    private function mockPayloads(): array
    {
        return [
            [
                'payment_id' => 'mock_ch_0001',
                'transaction_reference' => 'mock_txn_0001',
                'customer_id' => 'mock_cus_0001',
                'amount' => 19800,
                'currency' => 'aud',
                'status' => 'succeeded',
                'refunded' => false,
                'dispute_status' => null,
                'payout_status' => 'paid',
                'payment_method_type' => 'card',
                'metadata_booking_id' => 'mock_booking_0001',
            ],
            [
                'payment_id' => 'mock_ch_0002',
                'transaction_reference' => 'mock_txn_0002',
                'customer_id' => 'mock_cus_0002',
                'amount' => 45000,
                'currency' => 'aud',
                'status' => 'succeeded',
                'refunded' => false,
                'dispute_status' => 'needs_response',
                'payout_status' => 'pending',
                'payment_method_type' => 'card',
                'metadata_booking_id' => 'mock_booking_0002',
            ],
            [
                'payment_id' => 'mock_ch_0003',
                'transaction_reference' => 'mock_txn_0003',
                'customer_id' => 'mock_cus_0003',
                'amount' => 0,
                'currency' => 'aud',
                'status' => '', // Deliberately missing -> exercises required-field validation.
                'refunded' => false,
                'dispute_status' => null,
                'payout_status' => null,
                'payment_method_type' => 'card',
                'metadata_booking_id' => 'mock_booking_0003',
            ],
        ];
    }
}
