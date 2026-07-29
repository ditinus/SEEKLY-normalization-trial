<?php

namespace Tests\Feature\Connectors;

use App\Services\Connectors\ConnectorImportService;
use App\Services\Connectors\DuplicateDetector;
use App\Services\Connectors\Storage\EloquentConnectorStorage;
use App\Services\Connectors\Stripe\StripePaymentMockDriver;
use App\Services\Connectors\Stripe\StripePaymentValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Proves the same ConnectorImportService/DuplicateDetector/EloquentConnectorStorage
 * trio used by the Launch27 CSV connector also runs a different connector type
 * (PAYMENT_PROCESSOR) in a different mode (mock) with zero changes to shared code.
 */
class StripeMockConnectorTest extends TestCase
{
    use RefreshDatabase;

    public function test_mock_connector_runs_through_the_shared_pipeline(): void
    {
        $driver = new StripePaymentMockDriver();
        $storage = new EloquentConnectorStorage();
        $validator = new StripePaymentValidator($driver->requiredFields());
        $duplicateDetector = new DuplicateDetector($storage);

        $service = new ConnectorImportService($driver, $storage, $validator, $duplicateDetector);

        $log = $service->run('mock', dryRun: false);

        $this->assertSame('mock', $log['mode']);
        $this->assertSame('PAYMENT_PROCESSOR', $log['connector_type']);
        $this->assertSame(3, $log['records_received']);
        $this->assertSame(2, $log['records_imported'], 'One mock payload is missing status and should fail validation.');
        $this->assertSame(1, $log['records_failed']);
    }
}
