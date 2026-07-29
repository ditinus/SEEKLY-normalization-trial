# SEEKLY Sandbox Connector Framework

Laravel 12 technical trial for Launch27-style CSV import into SEEKLY.

This repo contains the working import application. For design explanation (SOLID, mapping strategy, feature rationale, flow diagrams), see:

---

## What this project does

- Import Launch27 bookings from CSV (or mock data)
- Store raw payload as received
- Map to a normalized booking record linked to the raw row
- Validate required fields, detect duplicates, support dry-run
- Write an import / sync log with counts
- Support connector modes: Mock, CSV, Live (placeholder)

---

## Requirements

- PHP 8.3+
- Composer
- MySQL

---

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Configure database in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=seekly_sandbox_connectors
DB_USERNAME=root
DB_PASSWORD=
```

Then:

```bash
php artisan migrate
```

---

## Project structure

```
app/
  Connectors/
    Contracts/ConnectorInterface.php
    DTO/NormalizedRecord.php, ImportResult.php, ImportError.php
    Launch27/
      CsvConnector.php
      MockConnector.php
      LiveConnector.php
      FieldMapper.php
      Validator.php
  Services/
    ImportService.php
    DuplicateService.php
    RawPayloadService.php
  Models/
    RawImport.php, Booking.php, ImportLog.php
  Http/Controllers/ImportController.php
  Http/Requests/ImportRequest.php
  Console/Commands/ImportLaunch27Command.php

resources/views/import/create.blade.php
docs/
  Technical-Approach.md
  reference/
```

---

## How to run

### Web UI

```bash
php artisan serve
```

Open `/import` — choose Mock or CSV, optionally enable dry-run, then run import.

### Artisan

```bash
php artisan import:launch27 path/to/bookings.csv
php artisan import:launch27 path/to/bookings.csv --dry-run
php artisan import:launch27 --mock --dry-run
```

---

## Import flow (short)

```
Source (CSV / Mock)
  → Connector fetch
  → Field mapping
  → Validation
  → Duplicate check
  → Raw payload + Booking (skipped in dry-run)
  → Import log
```

---

## Tests

```bash
php artisan test
```

Tests use SQLite in-memory (`phpunit.xml`). Local app DB remains MySQL via `.env`.

---

## Documentation

| File | Purpose |
|------|---------|
| [docs/Technical-Approach.md](docs/Technical-Approach.md) | Design approach for client / technical review |

