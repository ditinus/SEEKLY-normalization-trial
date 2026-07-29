<?php

namespace Tests\Feature\Connectors;

use App\Models\NormalizedOperationalRecord;
use App\Models\RawConnectorRecord;
use App\Services\Connectors\ConnectorImportService;
use App\Services\Connectors\DuplicateDetector;
use App\Services\Connectors\Launch27\Launch27CsvDriver;
use App\Services\Connectors\Launch27\Launch27FieldMapper;
use App\Services\Connectors\Launch27\Launch27Validator;
use App\Services\Connectors\Storage\EloquentConnectorStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Exercises the trial's core claims against the real sample CSV supplied by
 * the client (storage/app/samples/launch27-bookings-sample.csv): raw
 * preservation, normalization linkage, required-field validation, duplicate
 * detection, dry-run safety, and sync log accuracy.
 */
class Launch27CsvConnectorTest extends TestCase
{
    use RefreshDatabase;

    private function makeService(): ConnectorImportService
    {
        $driver = new Launch27CsvDriver(new Launch27FieldMapper());
        $storage = new EloquentConnectorStorage();
        $validator = new Launch27Validator($driver->requiredFields());
        $duplicateDetector = new DuplicateDetector($storage);

        return new ConnectorImportService($driver, $storage, $validator, $duplicateDetector);
    }

    private function samplePath(): string
    {
        return base_path('storage/app/samples/launch27-bookings-sample.csv');
    }

    public function test_dry_run_validates_and_maps_without_writing_to_storage(): void
    {
        $log = $this->makeService()->run($this->samplePath(), dryRun: true);

        $this->assertSame(50, $log['records_received']);
        $this->assertSame(0, $log['records_imported']);
        $this->assertGreaterThan(0, $log['records_mapped']);
        $this->assertSame(0, RawConnectorRecord::count());
        $this->assertSame(0, NormalizedOperationalRecord::count());
    }

    public function test_real_import_preserves_raw_payload_and_links_normalized_record(): void
    {
        $log = $this->makeService()->run($this->samplePath(), dryRun: false);

        $this->assertSame(RawConnectorRecord::count(), NormalizedOperationalRecord::count());
        $this->assertSame($log['records_imported'], RawConnectorRecord::count());

        $normalized = NormalizedOperationalRecord::with('rawRecord')->firstOrFail();

        $this->assertSame($normalized->raw_connector_record_id, $normalized->rawRecord->id);
        $this->assertSame(
            $normalized->rawRecord->raw_payload['customer_name'],
            'Sarah Mitchell',
            'Raw payload must be preserved exactly as received, unmasked.'
        );
        $this->assertNotSame(
            $normalized->rawRecord->raw_payload['customer_name'],
            $normalized->customer_name_masked,
            'Normalized record must diverge from raw via masking, proving raw is never overwritten.'
        );
    }

    public function test_required_field_validation_fails_incomplete_rows(): void
    {
        $log = $this->makeService()->run($this->samplePath(), dryRun: false);

        // The sample file contains a row with a blank id, a row with a blank
        // service_date, and a row with an unparseable service_date (2026-13-45).
        $this->assertSame(3, $log['records_failed']);
    }

    public function test_duplicate_rows_are_detected_within_a_single_batch(): void
    {
        // The sample file repeats booking 4901 and 4902 verbatim at the end.
        $log = $this->makeService()->run($this->samplePath(), dryRun: false);

        $this->assertSame(2, $log['records_skipped']);
    }

    public function test_re_running_the_same_import_skips_every_previously_imported_record(): void
    {
        $first = $this->makeService()->run($this->samplePath(), dryRun: false);
        $second = $this->makeService()->run($this->samplePath(), dryRun: false);

        $this->assertSame(0, $second['records_imported']);
        $this->assertSame($first['records_imported'] + $first['records_skipped'], $second['records_skipped']);
        $this->assertSame(
            $first['records_imported'],
            RawConnectorRecord::count(),
            'Re-import must never create duplicate raw records.'
        );
    }

    public function test_sync_log_is_persisted_with_accurate_counts(): void
    {
        $this->makeService()->run($this->samplePath(), dryRun: false);

        $this->assertDatabaseHas('connector_sync_logs', [
            'connector' => 'launch27',
            'connector_type' => 'JOB_SYSTEM',
            'mode' => 'csv',
            'dry_run' => false,
            'records_received' => 50,
            'records_imported' => 45,
            'records_mapped' => 45,
            'records_skipped' => 2,
            'records_failed' => 3,
        ]);
    }
}
