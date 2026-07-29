<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Connectors\Launch27\CsvConnector;
use App\Connectors\Launch27\FieldMapper;
use App\Connectors\Launch27\MockConnector;
use App\Connectors\Launch27\Validator;
use App\Http\Requests\ImportRequest;
use App\Services\ImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Throwable;

class ImportController extends Controller
{
    public function __construct(
        private ImportService $importService,
        private FieldMapper $mapper,
        private Validator $validator,
    ) {
    }

    public function create(): View
    {
        return view('import.create');
    }

    public function store(ImportRequest $request): RedirectResponse
    {
        $connector = $request->isMock()
            ? new MockConnector($this->mapper, $this->validator)
            : new CsvConnector(
                (string) $request->file('file')?->getRealPath(),
                $this->mapper,
                $this->validator,
            );

        try {
            $result = $this->importService->run($connector, $request->isDryRun());
        } catch (Throwable $e) {
            return redirect()
                ->route('import.create')
                ->with('error', 'Import failed: '.$e->getMessage());
        }

        return redirect()->route('import.create')
            ->with('success', $request->isDryRun() ? 'Dry run completed.' : 'Import completed.')
            ->with('summary', $result->toSummary())
            ->with('failedRows', array_map(fn ($row) => $row->toArray(), $result->failedRows))
            ->with('skippedRows', array_map(fn ($row) => $row->toArray(), $result->skippedRows));
    }
}
