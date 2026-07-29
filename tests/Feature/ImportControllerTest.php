<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\RawImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

final class ImportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_import_form_is_accessible(): void
    {
        $this->get('/import')->assertOk()->assertSee('Connector Import Console');
    }

    public function test_uploading_a_csv_runs_the_import_and_flashes_a_summary(): void
    {
        $file = UploadedFile::fake()->createWithContent(
            'bookings.csv',
            file_get_contents(base_path('tests/Fixtures/launch27-sample.csv')),
        );

        $response = $this->post('/import', [
            'connector' => 'csv',
            'file' => $file,
        ]);

        $response->assertRedirect(route('import.create'));
        $response->assertSessionHas('summary.imported', 2);
        $this->assertSame(2, Booking::count());
        $this->assertSame(2, RawImport::count());
    }

    public function test_mock_connector_can_be_run_in_dry_run_mode_from_the_ui(): void
    {
        $response = $this->post('/import', [
            'connector' => 'mock',
            'dry_run' => '1',
        ]);

        $response->assertRedirect(route('import.create'));
        $this->assertSame(0, Booking::count());
        $this->assertSame(0, RawImport::count());
    }

    public function test_csv_connector_requires_a_file(): void
    {
        $response = $this->post('/import', ['connector' => 'csv']);

        $response->assertSessionHasErrors('file');
    }
}
