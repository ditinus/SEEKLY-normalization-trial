<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SEEKLY Sandbox &middot; Connector Import</title>
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
</head>
<body class="bg-light">
    <div class="container py-5" style="max-width: 760px;">
        <div class="mb-4">
            <h1 class="h3 mb-1">Connector Import Console</h1>
            <p class="text-muted">Run the Launch27 import pipeline against a CSV file or the built-in mock connector.</p>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="POST" action="{{ route('import.store') }}" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-3">
                        <label for="connector" class="form-label">Connector</label>
                        <select
                            id="connector"
                            name="connector"
                            class="form-select"
                            onchange="document.getElementById('file-field').classList.toggle('d-none', this.value !== 'csv')"
                        >
                            <option value="csv" @selected(old('connector', 'csv') === 'csv')>Launch27 &middot; CSV</option>
                            <option value="mock" @selected(old('connector') === 'mock')>Launch27 &middot; Mock</option>
                        </select>
                    </div>

                    <div class="mb-3" id="file-field">
                        <label for="file" class="form-label">CSV file</label>
                        <input type="file" id="file" name="file" class="form-control" accept=".csv,.txt">
                    </div>

                    <div class="form-check mb-3">
                        <input type="checkbox" id="dry_run" name="dry_run" value="1" class="form-check-input" @checked(old('dry_run'))>
                        <label for="dry_run" class="form-check-label">Dry run (no database writes)</label>
                    </div>

                    <button type="submit" class="btn btn-primary">Run import</button>
                </form>
            </div>
        </div>

        @if (session('summary'))
            @php $summary = session('summary'); @endphp
            <div class="card shadow-sm mb-4">
                <div class="card-header">Import summary &middot; batch {{ $summary['batch_id'] }}</div>
                <div class="card-body">
                    <div class="row text-center g-3">
                        <div class="col"><div class="fs-4">{{ $summary['received'] }}</div><div class="text-muted small">Received</div></div>
                        <div class="col"><div class="fs-4">{{ $summary['mapped'] }}</div><div class="text-muted small">Mapped</div></div>
                        <div class="col"><div class="fs-4 text-success">{{ $summary['imported'] }}</div><div class="text-muted small">Imported</div></div>
                        <div class="col"><div class="fs-4 text-warning">{{ $summary['skipped'] }}</div><div class="text-muted small">Skipped</div></div>
                        <div class="col"><div class="fs-4 text-danger">{{ $summary['failed'] }}</div><div class="text-muted small">Failed</div></div>
                    </div>
                    <div class="text-muted small mt-3">
                        Mode: {{ $summary['mode'] }} &middot;
                        {{ $summary['dry_run'] ? 'Dry run (no writes)' : 'Live import' }} &middot;
                        {{ $summary['duration_ms'] }}ms
                    </div>
                </div>
            </div>
        @endif

        @if (!empty(session('failedRows')))
            <div class="card shadow-sm mb-4">
                <div class="card-header">Validation errors</div>
                <ul class="list-group list-group-flush">
                    @foreach (session('failedRows') as $row)
                        <li class="list-group-item">
                            <strong>{{ $row['external_booking_id'] ?? '(no booking id)' }}</strong> &mdash; {{ $row['reason'] }}
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (!empty(session('skippedRows')))
            <div class="card shadow-sm mb-4">
                <div class="card-header">Skipped duplicates</div>
                <ul class="list-group list-group-flush">
                    @foreach (session('skippedRows') as $row)
                        <li class="list-group-item">
                            <strong>{{ $row['external_booking_id'] ?? '(no booking id)' }}</strong> &mdash; {{ $row['reason'] }}
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</body>
</html>
