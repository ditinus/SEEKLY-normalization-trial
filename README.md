# SEEKLY Sandbox Connector Framework — Technical Trial

## Project Overview

This project demonstrates a reusable Laravel import architecture for Launch27 CSV imports, using the synthetic sample data and preparation documents supplied by SEEKLY.

The trial focuses on validating:

- Architecture quality and Laravel structure
- Import safety
- Raw payload preservation
- Normalized operational records
- Required-field validation
- Duplicate detection
- Sync / import logging
- Future connector extensibility

---

## Trial Scope

| Deliverable | Status |
|-------------|--------|
| Reusable Connector Interface | ✓ |
| Launch27 CSV Connector | ✓ |
| Raw Payload Preservation | ✓ |
| Normalized Operational Record | ✓ |
| Dry Run Import | ✓ |
| Required Field Validation | ✓ |
| Duplicate Detection | ✓ |
| Import / Sync Log | ✓ |
| Connector Mode Separation (Mock / CSV / Live placeholder) | ✓ |
| Laravel-ready structure for Sandbox integration | ✓ |

---

## Architecture

High-level flow as implemented:

```
CSV / Mock source
        ↓
   Connector (fetch)
        ↓
   Field mapping
        ↓
   Validation
        ↓
   Duplicate check
        ↓
   Raw payload storage  ←── skipped in dry-run
        ↓
   Normalized booking   ←── skipped in dry-run
        ↓
   Import log (always written)
```

**Connector** — Reads source rows (CSV file or mock data) through a shared `ConnectorInterface`.

**Field mapping** — Converts Launch27 fields into a `NormalizedRecord` DTO via `FieldMapper`, guided by the client field-mapping and normalized-record schema documents.

**Validation** — Checks required fields on the mapped record. Invalid rows are counted as failed and do not stop the rest of the batch.

**Duplicate check** — Uses connector name + source booking id. Duplicates are skipped safely.

**Raw payload storage** — Stores the original row as JSON (with checksum and batch id) before / alongside normalization.

**Normalized record** — One `Booking` linked back to its `RawImport` for full traceability.

**Import log** — One log row per run with received, mapped, imported, skipped, and failed counts.

Core orchestration lives in `ImportService`. Controllers and the Artisan command stay thin and only choose a connector, then call the service.

---

## Connector Modes

The architecture supports:

| Mode | Implementation |
|------|----------------|
| **Mock** | Built-in sample rows — useful for demos without a file |
| **CSV** | Launch27-style CSV driver (primary trial deliverable) |

Additional drivers can be added by implementing `ConnectorInterface`. The import workflow in `ImportService` does not need to change.

---

## Raw Payload Preservation

Every imported row is stored in `raw_imports` before (and independently of) how it is later mapped.

This supports:

- Audit trails
- Debugging failed or unexpected mappings
- Future remapping when mapping rules evolve
- Data recovery without re-reading the original file

The payload is kept as received. It is not mutated after storage.

---

## Normalized Record

Each valid Launch27 booking becomes one standardized SEEKLY operational record (`bookings`), shaped from the supplied normalized-record schema.

That gives the Sandbox a consistent internal structure regardless of whether the source was CSV, mock data, or a future live API.

Every booking references its `raw_import_id`, so normalized data can always be traced back to the original payload.

This trial intentionally uses a focused subset of the full production schema (core booking identity, service fields, status, presence flags, simplified eligibility, and `mapper_version`). Address/geo, team/vendor assignment, and detailed checklist/time/image arrays are left out of scope for the trial.

---

## Field Mapping

Mapping follows the direction of the supplied Launch27 field-mapping document and is isolated in `FieldMapper`.

Launch27 CSV columns (for example `id`, `customer_id`, `service_date`, `service_name`, checklist and time-tracking signals) are converted into SEEKLY-oriented fields such as `source_booking_id`, `customer_id`, `scheduled_at`, and eligibility flags.

Mapping stays separate from import orchestration, so the same rules can be reused by a future live connector.

---

## Validation

Required fields are validated after mapping. Examples of failures:

- Missing booking id
- Missing customer
- Missing or invalid service date
- Missing service name

Invalid records are **not** imported. They are recorded as failed in the import result / log, and processing continues for the remaining rows.

---

## Duplicate Detection

Duplicates are identified by **connector + source booking id** against previously stored raw imports (and within the same run, including dry-run).

When a duplicate is found:

- The row is skipped
- The reason is recorded
- The import continues

No exception is thrown for duplicates.

---

## Dry Run Mode

Dry-run runs the same pipeline — fetch, map, validate, duplicate check, and summary — **without writing** raw imports or bookings.

Use it to verify:

- Validation outcomes
- Mapping behaviour
- Duplicate detection
- Import summary counts

before committing data. An import log entry is still created so the dry-run itself remains auditable.

---

## Import Log

Every run (including dry-run) writes one `import_logs` record with:

- Records received
- Mapped
- Imported
- Skipped
- Failed
- Connector, mode, batch id, timestamps, and status

This gives clear visibility into each execution.

---

## UI

A simple Blade screen is provided at `resources/views/import/create.blade.php`.

- The UI is intentionally basic.
- It exists only to demonstrate the import workflow (connector choice, CSV upload, dry-run, summary).
- Backend architecture is the focus of this trial, not frontend design.
- The screen can be replaced by the existing SEEKLY frontend when this work is integrated into the Sandbox.

CLI is also available:

```bash
php artisan import:launch27 path/to/bookings.csv
php artisan import:launch27 path/to/bookings.csv --dry-run
php artisan import:launch27 --mock --dry-run
```

---

## Laravel Integration

The code follows standard Laravel conventions (service classes, Form Requests, Eloquent models, migrations, Artisan commands, Blade).

It is designed to sit alongside the existing Sandbox with minimal disruption: new tables and services, a clear connector contract, and no dependency on rebuilding signed-off areas.

---

## Future Extension

Without changing the overall import workflow, the same structure can support:

- Live Launch27 API connectors
- Webhook-driven imports
- Scheduled sync jobs
- Additional connector drivers (e.g. payments / accounting)
- Queue-based background processing for larger volumes

---

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
# configure MySQL in .env, then:
php artisan migrate
php artisan test
```
---

## Design approach (for client review)

SOLID principles, field-mapping strategy, feature rationale, and flow diagrams:

**[docs/Technical-Approach.md](docs/Technical-Approach.md)**

