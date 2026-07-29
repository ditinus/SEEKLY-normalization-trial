<?php

declare(strict_types=1);

namespace App\Connectors\Launch27;

use App\Connectors\ConnectorMode;
use App\Connectors\Contracts\ConnectorInterface;
use App\Connectors\DTO\NormalizedRecord;

class MockConnector implements ConnectorInterface
{
    public function __construct(
        private FieldMapper $mapper,
        private Validator $validator,
    ) {
    }

    public function name(): string
    {
        return 'launch27';
    }

    public function mode(): ConnectorMode
    {
        return ConnectorMode::Mock;
    }

    public function fetch(): iterable
    {
        yield [
            'id' => 'MOCK-1001',
            'customer_id' => 'cus_mock_1',
            'service_date' => now()->addDays(2)->toDateString(),
            'scheduled_time' => '09:00',
            'service_name' => 'Regular Cleaning',
            'service_category' => 'residential_cleaning',
            'frequency' => 'Weekly',
            'booking_status' => 'scheduled',
            'completed' => 'false',
            'cancelled_at' => '',
            'checklist_total' => '10',
            'time_tracked_minutes' => '0',
            'customer_notes' => 'Gate code is 4521.',
            'staff_notes' => '',
            'total' => '210.00',
            'currency' => 'AUD',
        ];

        yield [
            'id' => 'MOCK-1002',
            'customer_id' => 'cus_mock_2',
            'service_date' => now()->subDays(3)->toDateString(),
            'scheduled_time' => '13:00',
            'service_name' => 'Deep Clean',
            'service_category' => 'residential_cleaning',
            'frequency' => 'One-off',
            'booking_status' => 'completed',
            'completed' => 'true',
            'cancelled_at' => '',
            'checklist_total' => '18',
            'time_tracked_minutes' => '145',
            'customer_notes' => '',
            'staff_notes' => 'Extra time on kitchen.',
            'total' => '365.00',
            'currency' => 'AUD',
        ];

        // intentionally incomplete — used to exercise validation failures
        yield [
            'id' => '',
            'customer_id' => 'cus_mock_3',
            'service_date' => '',
            'service_name' => 'Office Cleaning',
            'service_category' => 'commercial_cleaning',
            'booking_status' => 'unassigned',
            'completed' => 'false',
            'total' => '150.00',
            'currency' => 'AUD',
        ];
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
