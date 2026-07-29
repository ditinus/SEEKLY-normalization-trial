# SEEKLY Sandbox Connector Framework — Technical Trial

This repository is the deliverable for the SEEKLY Sandbox Connectors paid technical trial. It
implements a small, reusable connector architecture and proves it against the Launch27-style
sample data provided by SEEKLY, and against a second, unrelated connector (a mock Stripe payment
connector) to demonstrate the design generalizes.

This is **not** the full Stage 2 connector framework. It is intentionally scoped to the trial
brief: one reusable interface, one CSV driver, raw preservation, one linked normalized record,
dry-run, validation, duplicate detection, one sync log, and mode separation — built so it can be
*extended* into the full Stage 2 scope (dashboards, QA matrix, data quality scoring, consent
preview, lender/API preview) without rework.

## Why the architecture looks the way it does

The trial brief says the existing SEEKLY Sandbox already has a working raw → normalized pipeline
for Launch27, and the goal of Stage 2 is to bring it under a **reusable connector contract**
without breaking what's already signed off (proof, evidence, settlement, audit pack, exports).
That constraint drove every decision below:

1. **The contract is the product, not the Launch27 driver.** `ConnectorDriverInterface`,
   `ConnectorStorageInterface`, and `RecordValidatorInterface` in `app/Contracts/` contain no
   Launch27-specific logic. Launch27 is one implementation of the contract, not the contract
   itself. This is what lets a future Stripe/Xero/bank-feed/live connector plug into the same
   pipeline (`ConnectorImportService`) with zero changes to shared code.
2. **Raw data is a separate, append-only table from normalized data**, linked by a foreign key
   (`normalized_operational_records.raw_connector_record_id`), never the same row updated in
   place. This mirrors the client's own stated principle in
   `docs/reference/SEEKLY-Launch27-Field-Mapping.md`: raw payload lives in its own store, the
   normalized record is a separate projection, and a promoted-columns + full-JSON pattern keeps
   the normalized table both queryable and lossless.
3. **The normalized record's field names deliberately match**
   `docs/reference/SEEKLY-Launch27-Normalized-Record-Schema.md` (`source_booking_id`,
   `customer_name_masked`, `seekly_lifecycle_status`, `proof_eligibility`, etc.) so this driver's
   output is a drop-in match for the schema SEEKLY already treats as canonical, rather than a
   parallel shape that would need translating later.
4. **Validation, deduplication, and storage are swappable, not hard-wired.** A connector supplies
   its own `RecordValidatorInterface` implementation (business rules differ per source system),
   but duplicate detection and storage are shared services that any driver can reuse as-is.

## What's implemented

| Trial requirement | Where |
|---|---|
| Reusable connector interface | `app/Contracts/ConnectorDriverInterface.php`, `ConnectorStorageInterface.php`, `RecordValidatorInterface.php` |
| Launch27-style CSV connector driver | `app/Services/Connectors/Launch27/Launch27CsvDriver.php` + `Launch27FieldMapper.php` |
| Raw payload preservation | `raw_connector_records` table (`app/Models/RawConnectorRecord.php`) — payload stored as untouched JSON, never overwritten |
| Normalized record linked to raw | `normalized_operational_records.raw_connector_record_id` → `raw_connector_records.id` (1:1, enforced by a DB foreign key + unique constraint) |
| Dry-run import | `ConnectorImportService::run($source, dryRun: true)` — validates and maps but writes nothing |
| Required-field validation | `app/Services/Connectors/Launch27/Launch27Validator.php` (+ date/amount sanity checks) |
| Duplicate detection | `app/Services/Connectors/DuplicateDetector.php` — natural-key based, checks both the current batch and prior imports, so re-running an import is always safe |
| Sync/import log (received/imported/mapped/skipped/failed) | `connector_sync_logs` table, written once per run by `ConnectorImportService` |
| Connector mode separation (mock / csv / future live) | `app/Support/Connectors/ConnectorMode.php`; proven with two live examples below (CSV mode for Launch27, mock mode for Stripe) |
| Second connector proving the interface generalizes | `app/Services/Connectors/Stripe/StripePaymentMockDriver.php` — a different connector type (`PAYMENT_PROCESSOR`), different mode (`mock`), running through the *same* `ConnectorImportService` with no shared-code changes |

### Deliberately out of scope for this trial

No dashboard/UI, no QA matrix, no data quality scoring, no consent/permission preview, no
verified revenue readiness preview, no lender/API output preview, no live API integrations.
These are Stage 2/3 scope per the SOW and would sit on top of this same contract without
requiring it to change shape.

## How it fits into the existing Laravel Sandbox

- **Drop-in, not a rewrite.** `app/Contracts/*` and `app/Services/Connectors/*` are additive.
  Nothing here touches or requires changing the parts of the Sandbox that already read directly
  from source tables (proof, evidence, settlement, audit pack, exports) — those can keep working
  exactly as they do today while new/rebuilt features are pointed at the connector layer instead.
- **Bringing an existing pipeline under the contract is an adapter, not a migration.** To bring
  the *real* Launch27 pipeline under this contract, the existing interpretation pipeline's
  fetch/map logic would be wrapped in a class implementing `ConnectorDriverInterface` (a
  `Launch27LiveDriver` alongside this trial's `Launch27CsvDriver`), reusing the real
  `Launch27FieldMapper`/`Launch27StatusMapper` already in the production codebase instead of the
  trial's simplified mapper. `ConnectorImportService`, `DuplicateDetector`, and the storage layer
  would not need to change.
- **Storage is intentionally generic.** `EloquentConnectorStorage::saveNormalized()` writes the
  full normalized array into a JSON column and promotes a handful of columns for querying — the
  same pattern the client's own field-mapping doc describes. This is why the Stripe mock connector
  (a completely different record shape) writes through the identical storage class with no
  changes: only the promoted-column subset differs, the JSON is always complete.
- **Adding a fourth connector type (e.g. bank feed) means:** one new driver class implementing
  `ConnectorDriverInterface`, one validator, and (optionally) new promoted columns on a
  normalized-record table if that data category needs its own queryable shape. No changes to
  `ConnectorImportService`, `DuplicateDetector`, or the sync-log mechanism.

## Data flow

```
CSV / mock payload
  -> Driver::fetchRaw()            (source-shaped, untouched)
  -> DuplicateDetector             (natural key, batch + storage)
  -> RecordValidatorInterface      (required fields, format checks)
  -> Driver::mapToNormalized()     (source -> SEEKLY schema)
  -> [dry-run: stop here, log preview]
  -> Storage::saveRaw()            (raw_connector_records, immutable)
  -> Storage::saveNormalized()     (normalized_operational_records, FK to raw)
  -> Storage::saveSyncLog()        (connector_sync_logs)
```

## Running it

```bash
composer install
cp .env.example .env   # defaults to sqlite
php artisan key:generate
php artisan migrate

# Dry run against the sample Launch27 CSV (validates + maps, writes nothing)
php artisan connectors:import-launch27-csv storage/app/samples/launch27-bookings-sample.csv --dry-run

# Real import
php artisan connectors:import-launch27-csv storage/app/samples/launch27-bookings-sample.csv

# Re-running is safe — every previously imported row is skipped as a duplicate, not re-inserted
php artisan connectors:import-launch27-csv storage/app/samples/launch27-bookings-sample.csv

# A second connector (different type + mode) through the same pipeline
php artisan connectors:import-stripe-mock --dry-run
php artisan connectors:import-stripe-mock
```

Automated tests (`php artisan test`) cover dry-run safety, raw preservation, normalization
linkage, required-field validation, duplicate detection (including re-import), sync log accuracy,
and the second connector running through the shared pipeline.

## What the sample data exercises

`storage/app/samples/launch27-bookings-sample.csv` (as supplied by SEEKLY) is deliberately messy,
and the pipeline is built to handle it rather than assume clean input:

- **Exact duplicate rows** (bookings `4901` and `4902` repeated at the end of the file) →
  caught by `DuplicateDetector`, skipped, never double-imported.
- **Missing required fields** — a blank `id`, a blank `service_date` → caught by
  `Launch27Validator`, counted as `failed`, never silently dropped or partially imported.
- **An invalid date** (`2026-13-45`) → fails validation with a specific reason rather than
  crashing the import or being coerced into a wrong date.
- **Inconsistent formatting** (currency as `$180.00` / `1,250.00` / `A$` / lowercase `aud`,
  booleans as `true`/`TRUE`/`1`/`yes`/`Y`) → tolerated by the validator/mapper rather than
  rejected outright, since these are real-world source-system inconsistencies, not data errors.

Running the real import against this file currently produces: **50 received, 45 imported, 45
mapped, 2 skipped (duplicates), 3 failed (validation)** — asserted directly in
`tests/Feature/Connectors/Launch27CsvConnectorTest.php`.

## Known limitations (by design, for a 4–6 hour trial)

- The Launch27 field mapper here is a simplified, trial-scope version. It intentionally does not
  reproduce every rule in the production `Launch27FieldMapper` (e.g. tolerant multi-key
  resolution across tenant payload variants, checklist/time-tracking/proof-layer feeds,
  `mapper_version` re-map detection at scale) — it maps the fields the sample CSV and normalized
  schema require to prove the connector contract, not the full production mapping surface.
- No UI/dashboard. The trial is a backend/architecture proof; a Connected Systems dashboard,
  QA matrix, and health view are Stage 2 deliverables that would read from
  `connector_sync_logs` / `normalized_operational_records` without needing schema changes.
- The Stripe connector is intentionally `mock` mode only (hand-authored synthetic payloads, no
  CSV parsing, no live API) — it exists solely to prove the interface isn't Launch27-specific,
  not to be a second full connector.
- `vendor_id`, `is_historical_record`, and a few other normalized-schema fields are left `null`/
  `false` where the trial's CSV or scope doesn't provide enough context to derive them safely
  (documented inline in `Launch27FieldMapper::map()`), rather than guessed.
- No authentication/authorization layer — out of scope for a sandbox trial with no live
  credentials involved.

## Project structure

```
app/Contracts/                          Connector interface, storage interface, validator interface
app/Support/Connectors/                 ConnectorMode, RecordOutcome value objects
app/Services/Connectors/
  ConnectorImportService.php            Orchestrates fetch -> dedupe -> validate -> map -> store -> log
  DuplicateDetector.php                 Natural-key duplicate detection (batch + storage)
  Storage/EloquentConnectorStorage.php  Eloquent implementation of ConnectorStorageInterface
  Launch27/                             Launch27 CSV driver, field mapper, validator
  Stripe/                               Mock payment connector (proves interface reuse)
app/Models/                             RawConnectorRecord, NormalizedOperationalRecord, ConnectorSyncLog
app/Console/Commands/                   connectors:import-launch27-csv, connectors:import-stripe-mock
database/migrations/                    raw_connector_records, normalized_operational_records, connector_sync_logs
storage/app/samples/                    Sample CSVs supplied by SEEKLY (synthetic, no real customer data)
docs/reference/                         SEEKLY-supplied schema + field-mapping reference docs
tests/Feature/Connectors/               End-to-end coverage of the claims above
```
