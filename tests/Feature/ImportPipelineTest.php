<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Connectors\ConnectorMode;
use App\Connectors\ImportLogStatus;
use App\Connectors\Launch27\CsvConnector;
use App\Connectors\Launch27\FieldMapper;
use App\Connectors\Launch27\MockConnector;
use App\Connectors\Launch27\Validator;
use App\Models\Booking;
use App\Models\ImportLog;
use App\Models\RawImport;
use App\Services\ImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportPipelineTest extends TestCase
{
    use RefreshDatabase;

    private ImportService $importService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importService = app(ImportService::class);
    }

    public function test_dry_run_processes_rows_without_writing_to_the_database(): void
    {
        $result = $this->importService->run($this->csvConnector(), dryRun: true);

        $this->assertSame(5, $result->receivedCount);
        $this->assertSame(2, $result->importedCount);
        $this->assertSame(1, $result->skippedCount);
        $this->assertSame(2, $result->failedCount);

        $this->assertSame(0, RawImport::count());
        $this->assertSame(0, Booking::count());
        $this->assertSame(1, ImportLog::count());
        $this->assertSame(ImportLogStatus::DryRun, ImportLog::first()->status);
    }

    public function test_real_import_preserves_raw_payload_and_links_the_booking(): void
    {
        $result = $this->importService->run($this->csvConnector());

        $this->assertSame(2, $result->importedCount);
        $this->assertSame(2, RawImport::count());
        $this->assertSame(2, Booking::count());

        $booking = Booking::query()->where('source_booking_id', '5001')->firstOrFail();
        $raw = RawImport::query()->findOrFail($booking->raw_import_id);

        $this->assertSame('5001', $raw->external_reference);
        $this->assertSame('5001', $raw->payload['id']);
        $this->assertSame($raw->id, $booking->rawImport->id);
        $this->assertNotEmpty($raw->checksum);
        $this->assertSame('cus_2001', $booking->customer_id);
        $this->assertSame(FieldMapper::MAPPER_VERSION, $booking->mapper_version);
        $this->assertSame('not_eligible', $booking->proof_eligibility);
        $this->assertSame('not_eligible', $booking->sla_eligibility);
        $this->assertSame('not_eligible', $booking->risk_eligibility);
    }

    public function test_completed_booking_with_checklist_is_proof_eligible(): void
    {
        $this->importService->run($this->csvConnector());

        $booking = Booking::query()->where('source_booking_id', '5002')->firstOrFail();

        $this->assertSame('eligible', $booking->proof_eligibility);
        $this->assertSame('eligible', $booking->sla_eligibility);
        $this->assertSame('eligible', $booking->risk_eligibility);
        $this->assertTrue($booking->has_checklist);
        $this->assertTrue($booking->has_time_tracking);
    }

    public function test_validation_failures_are_reported_but_do_not_stop_the_import(): void
    {
        $result = $this->importService->run($this->csvConnector());

        $this->assertSame(2, $result->failedCount);
        $reasons = array_map(fn ($row) => $row->reason, $result->failedRows);

        $this->assertStringContainsString('Missing Booking ID', implode(' | ', $reasons));
        $this->assertStringContainsString('Missing Service Date', implode(' | ', $reasons));
    }

    public function test_duplicate_rows_are_skipped_on_a_second_run(): void
    {
        $this->importService->run($this->csvConnector());
        $second = $this->importService->run($this->csvConnector());

        $this->assertSame(0, $second->importedCount);
        $this->assertSame(3, $second->skippedCount);
        $this->assertSame(2, Booking::count());
    }

    public function test_mock_connector_runs_through_the_same_pipeline_as_the_csv_connector(): void
    {
        $connector = new MockConnector(new FieldMapper(), new Validator());
        $result = $this->importService->run($connector);

        $this->assertSame(ConnectorMode::Mock, $result->mode);
        $this->assertSame('launch27', $result->connector);
        $this->assertGreaterThan(0, $result->importedCount);
    }

    private function csvConnector(): CsvConnector
    {
        return new CsvConnector(
            base_path('tests/Fixtures/launch27-sample.csv'),
            new FieldMapper(),
            new Validator(),
        );
    }
}
