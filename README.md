# SEEKLY Sandbox Connector Framework — Technical Trial

A Laravel 12 connector import framework demonstrating clean architecture,
raw-data preservation, normalization, duplicate detection, dry-run imports
and sync logging for a Launch27-style CSV source — built to validate the
approach for SEEKLY's Stage 2 connector contract.

## Architecture

```
app/
  Connectors/
    ConnectorMode.php               # mock | csv | live
    ImportLogStatus.php             # completed | dry_run | failed
    Contracts/
      ConnectorInterface.php        # fetch() / map() / validate()
    DTO/
      NormalizedRecord.php          # connector-agnostic mapped shape
      ImportResult.php              # outcome of one ImportService::run()
      ImportError.php               # one skipped/failed row + reason
    Launch27/
      CsvConnector.php              # reads a Launch27 CSV file
      MockConnector.php             # synthetic in-memory rows, same pipeline
      LiveConnector.php             # Stage 3 placeholder (throws, not implemented)
      FieldMapper.php               # raw row -> NormalizedRecord
      Validator.php                 # required-field / shape validation

  Services/
    ImportService.php               # orchestrates the pipeline
    DuplicateService.php            # persisted duplicate lookups
    RawPayloadService.php           # the only writer of RawImport rows

  Models/
    RawImport.php                   # raw_imports  (immutable payload)
    Booking.php                     # bookings     (normalized record)
    ImportLog.php                   # import_logs  (per-run counts)

  Http/
    Controllers/ImportController.php
    Requests/ImportRequest.php

  Console/Commands/ImportLaunch27Command.php
```

### Why it's shaped this way

- **`ConnectorInterface`** is the only thing `ImportService` depends on. It
  exposes `fetch()`, `map()` and `validate()` — the three responsibilities
  every future connector (a live Launch27 API, Stripe, Xero, Open Banking)
  must implement. `ImportService` never checks "if csv / if mock"; it just
  calls the interface. `CsvConnector`, `MockConnector` and the `LiveConnector`
  placeholder all satisfy it identically, so swapping the connector at the
  console command or controller is the only place that decision is made.
- **Raw preservation is a dedicated service.** `RawPayloadService` is the
  only class that writes to `raw_imports`, and it never updates a row after
  creating it — the JSON payload plus a SHA-256 checksum give a tamper-evident
  record of exactly what a connector received, independent of how it was
  later mapped.
- **`Booking` always has a `raw_import_id`.** The FK is `unique()` and
  `restrictOnDelete()`, so the database itself enforces the 1:1 traceability
  from a normalized record back to its raw source — you can't delete a
  `RawImport` while a `Booking` still references it, and a `RawImport` can
  never back more than one `Booking`.
- **Mapping and validation are separate, both stateless.** `FieldMapper`
  only knows how to convert a Launch27 row into a `NormalizedRecord`.
  `Validator` only knows the required-field rules for that DTO. Neither
  knows about CSV files, HTTP, or persistence — they're reusable as-is by a
  future Launch27 API connector.
- **Duplicate detection is two-layered.** `DuplicateService` checks
  previously *persisted* `raw_imports` (connector + external booking id).
  `ImportService` additionally tracks ids seen earlier in the *current* run,
  so a dry run — which never writes to the database — still catches
  duplicate rows within the same file.
- **Validation never stops the batch.** Every row is evaluated independently;
  failures and duplicates are collected into the `ImportResult` alongside
  successes, so one bad row can't block the other 49.
- **Every run produces exactly one `ImportLog`**, dry run or not, giving an
  audit trail of every import attempt with its received/mapped/imported/
  skipped/failed counts.

## Running the CSV import

```bash
php artisan import:launch27 storage/app/bookings.csv
php artisan import:launch27 storage/app/bookings.csv --dry-run
php artisan import:launch27 --mock --dry-run   # no file needed
```

The command prints a summary table plus every validation failure and
duplicate skip with its reason.

## Web UI

Visit `/import` for a minimal Bootstrap 5 form to pick a connector
(Launch27 CSV or Mock), optionally upload a CSV, toggle dry run, and submit.
The result page shows the same received/mapped/imported/skipped/failed
summary plus per-row validation errors, backed by standard Laravel session
flash data.

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
# configure DB_* in .env for a MySQL database, then:
php artisan migrate
```

## Tests

```bash
php artisan test
```

Tests run against an in-memory SQLite database (see `phpunit.xml`) and cover
dry-run behaviour, raw payload preservation + linkage, validation, duplicate
detection (including within a single dry-run batch), the mock connector, and
the upload UI.

## What was deliberately left out of scope

This is a trial, not the full Stage 2 framework:

- `LiveConnector` is a placeholder that throws — proving the interface
  supports it without building real API/OAuth integration.
- No authentication/authorization on the `/import` route; a production
  version would gate it behind the app's existing auth.
- No queueing — imports run synchronously, which is fine for CSV files of
  this size but would move to a queued job for larger/live sources.
- Customer PII (name, email, phone, address) is never stored in the
  normalized record, only a stable masked pseudonym and an opaque customer
  reference — matching the synthetic, privacy-conscious nature of the sample
  data.
