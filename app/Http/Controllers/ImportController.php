<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Connectors\Launch27\CsvConnector;
use App\Connectors\Launch27\FieldMapper;
use App\Connectors\Launch27\MockConnector;
use App\Connectors\Launch27\Validator;
use App\Http\Requests\ImportRequest;
use App\Services\ImportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Throwable;

/**
 * ImportController
 */
final class ImportController extends Controller
{
    public function __construct(
        private readonly ImportService $importService,
        private readonly FieldMapper $mapper,
        private readonly Validator $validator,
    ) {
    }

    /**
     * create
     *
     * @return View
     */
    public function create(): View
    {
        return view('import.create');
    }

    /**
     * store
     */
    public function store(ImportRequest $request): RedirectResponse
    /**
     * store
     */
    {
        return redirect()->route('import.create')
            ->with('success', $request->isDryRun() ? 'Dry run completed.' : 'Import completed.')
            ->with('summary', $result->toSummary())
            ->with('failedRows', array_map(static fn ($row) => $row->toArray(), $result->failedRows))
            ->with('skippedRows', array_map(static fn ($row) => $row->toArray(), $result->skippedRows));
    }
}
