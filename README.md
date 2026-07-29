# SEEKLY Sandbox Connector Framework — Technical Trial

Laravel 12 trial for importing Launch27-style CSV bookings into SEEKLY's
sandbox. Covers raw payload storage, normalization, validation, duplicates,
dry-run, and sync logging.

## Structure

```
app/
  Connectors/
    Contracts/ConnectorInterface.php
    DTO/NormalizedRecord.php, ImportResult.php, ImportError.php
    ConnectorMode.php, ImportLogStatus.php
    Launch27/
      CsvConnector.php
      MockConnector.php
      LiveConnector.php   # placeholder
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
```

## Flow

1. Controller / Artisan builds a connector (CSV or Mock)
2. `ImportService::run()` walks each row:
   - map → validate → duplicate check → persist (unless dry-run)
3. One `ImportLog` is written with received / mapped / imported / skipped / failed

Connectors share `ConnectorInterface` so ImportService does not care whether
the source is a file, mock data, or (later) a live API.

### Field notes

Aligned with SEEKLY naming where it matters for review (`source_booking_id`,
`customer_id`, eligibility flags, `mapper_version`). Eligibility is simplified:

- proof = completed + has checklist
- sla / risk = completed

Out of trial scope: address/geo, team/vendor, checklist detail arrays,
digest/portfolio ids, production eligibility classifier.

## Usage

```bash
php artisan import:launch27 storage/app/bookings.csv
php artisan import:launch27 storage/app/bookings.csv --dry-run
php artisan import:launch27 --mock --dry-run
```

Web UI: `/import`

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan test
```

## Limitations

- Live connector not implemented
- No auth on `/import`
- Imports run synchronously
- Customer PII is masked on the booking record
