# SEEKLY ← Launch27 — Field-by-Field Mapping

**Purpose:** Satisfies SOW §6.1 / §6.2 and QA evidence-pack deliverable #2 (L27-040 / L27-281).
This is the authoritative field-by-field map from a raw Launch27/Automaid booking payload to the
SEEKLY **normalized operational record**.

**Generated:** 2026-06-18
**Scope:** Tasks L27-040 … L27-061 (SOW Section D).

---

## 1. Principles

- **Launch27 is the System of Record.** SEEKLY never writes back to Launch27. The mapper performs a
  read-only projection of the raw booking into SEEKLY-owned storage.
- **One mapper, one record.** All mapping lives in a single pure service,
  [`app/Services/Launch27/Mapping/Launch27FieldMapper.php`](../app/Services/Launch27/Mapping/Launch27FieldMapper.php)
  (`Launch27FieldMapper::map()`), so the mapping is testable and has exactly one source of truth.
- **Tolerant key resolution.** Booking payloads differ by tenant, so every field resolves through a
  list of candidate keys; a missing field maps to `null` / empty rather than failing the import.
- **Versioned.** Every record carries `mapper_version` (`Launch27FieldMapper::MAPPER_VERSION`) so a
  re-map can be detected.

## 2. Where the normalized record is stored

| Layer | Location | Notes |
|-------|----------|-------|
| Raw source snapshot | `institutional_ingests.raw_payload.booking_full` | Complete Launch27 payload (L27-030). |
| Raw source snapshot (mirror) | `l27_booking_intelligence.source_payload` | Persisted on the operational record. |
| **Normalized record (full)** | `l27_booking_intelligence.normalized_record` (JSON) | The full mapper output. |
| **Normalized record (promoted)** | `l27_booking_intelligence.*` columns | Queryable subset (see §3). |
| Proof / evidence / timing | `job_verifications` (+ `metadata.launch27`) | Checklist, images, time logs (L27-057/058/059). |
| Geo on the job | `buyer_jobs.latitude` / `longitude` | Set at import when present (L27-051). |
| Projection for exports / QA | `SeeklyOperationalRecord::fromEntities()` | Canonical shape consumed by exports. |

The mapper runs inside the interpretation pipeline
([`L27InterpretationPipeline`](../app/Services/Launch27/Intelligence/L27InterpretationPipeline.php)),
which already resolves the full raw payload, then persists the normalized record and the promoted
columns and feeds the proof layer.

## 3. Field-by-field map

Legend — **Store:** `JSON` = key in `normalized_record`; `col` = promoted column on
`l27_booking_intelligence`; `jobs` = `buyer_jobs`; `verif` = `job_verifications`.

| Task | Launch27 source key(s) | Normalized field(s) | Store | Transform |
|------|------------------------|---------------------|-------|-----------|
| L27-041 | `id`, `digest`, `forecasted_from_booking.*` | `booking_id`, `external_reference`, `digest` | JSON + `l27_external_work_order_id` | External ref via `Launch27BookingIdentity::externalWorkOrderId()` (handles recurring series). |
| L27-042 | `user`/`customer`/`client`.`{id,name,first_name,last_name,email,phone}`, `customer_email` | `customer_id`, `customer_name`, `customer_email`, `customer_phone` | JSON | Blocks merged first-wins; email lower-cased. |
| L27-043 | `service_date`, `forecasted_from_booking.service_date` | `service_date`, `scheduled_time`, `scheduled_at` | JSON + `scheduled_at` | Parsed; date `Y-m-d`, time `H:i`, ISO-8601. |
| L27-044 | `arrival_window_start/end`, `arrival_start/end`, `arrival_window` | `arrival_window_start`, `arrival_window_end` | JSON + `arrival_window_start/end` | Single `"9:00 AM - 11:00 AM"` string is split on `-`. |
| L27-045 | `services[0].name/title`, `summary`, `title`, `service_name` | `service_name` | JSON | First service preferred, then booking summary. |
| L27-046 | `services[0].category(.name)`, `service_category`, `category` | `service_category` | JSON + `service_category` | Replaces the historical default `category_id = 0`; category **name** preserved (SEEKLY taxonomy IDs are not forced). |
| L27-047 | `frequency(.name)`, `recurrence`, `recurring`, `services[0].frequency` | `service_frequency` | JSON + `service_frequency` | e.g. Weekly / Fortnightly / One-off. |
| L27-048 | `duration`, `service_duration_minutes`, `services[0].duration` | `service_duration_minutes` | JSON + `service_duration_minutes` | Numeric → int minutes. |
| L27-049 | `summary.{subtotal,services_total,tax,tax_total,total,currency}` | `service_price`, `tax_amount`, `total_amount`, `currency` | JSON | Subtotal falls back to `total − tax`. |
| L27-050 | `address.{line,address_line_1/2,street,suburb,city,town,state,region,province,postcode/post_code/postal_code/zip,country}` | `address_line`, `city`, `state`, `postcode`, `country` | JSON | Structured parts; composes a line when no explicit line key. Completes the missing **state**. |
| L27-051 | `address.{latitude,lat,longitude,lng,long}`, top-level `lat/lng` | `latitude`, `longitude` | JSON + `latitude/longitude` cols + `buyer_jobs.latitude/longitude` | Numeric only. |
| L27-052 | `teams[0].{id,name,members/staff[]}`, `team_requested_id`, `team_with_key_id`, `staff[]`, `assignees[]` | `assigned_team_id`, `assigned_team_name`, `assigned_staff[]`, `assigned_staff_count` | JSON | Per-staff list normalized to `{id,name}`. |
| L27-053 | `completed`, `active`, `booking_status` | `booking_status`, `launch27_booking_status`, `is_active`, `is_completed`, `is_cancelled` | JSON | Via `Launch27StatusMapper::map()`. |
| L27-054 | `cancellation_reason`, `cancel_reason`, `cancellation.reason`, `cancelled_at` | `cancellation_reason`, `cancelled_at` | JSON + `cancellation_reason` | |
| L27-055 | `customer_notes`/`customer_note`/`notes`, `staff_notes`/`internal_notes`/`staff_note` | `customer_notes`, `staff_notes` | JSON | Structured (previously only concatenated into job description). |
| L27-056 | `custom_fields` (list `{code/id,value}` **or** map) | `custom_fields{}` | JSON | Normalized to a keyed map; answers also synced into `answers` at import. |
| L27-057 | `checklist_items`, `checklist.items`, `checklist`, `checklists` | `has_checklist`, `checklist_total`, `checklist_completed`, `checklist_status`, `checklist_entries[]` | JSON + cols + `verif.checklist_complete` / `metadata.launch27` | Counts complete vs total; `complete` only when all items done. |
| L27-058 | `time_logs`, `booking_time_logs`, `time_tracking`, `time_entries` (`check_in/started_at/clock_in`, `check_out/ended_at/clock_out`, `minutes`) | `time_entries[]`, `time_tracked_minutes`, `first_check_in_at`, `last_check_out_at` | JSON + `time_tracked_minutes` + `verif.check_in_time/check_out_time` / `metadata.launch27` | Minutes summed; derived from in/out when absent. |
| L27-059 | `images`, `photos`, `booking_images`, `attachments`, `has_images/has_photos`, `image_count` | `has_images`, `image_count`, `images[]` (metadata only) | JSON + cols + `verif.evidence_count` / `metadata.launch27` | Only URLs / ids / type / timestamp captured — never binary. |
| L27-060 | `alerts`, `flags`, `warnings` | `alerts[]`, `alerts_count` | JSON + `alerts_count` | Normalized to `{code,message,severity}`. |
| L27-061 | `created_at`/`created`/`booked_at`, `updated_at`/`modified_at`, sync time | `l27_created_at`, `l27_updated_at`, `synced_at` | JSON + `l27_created_at`/`l27_updated_at` cols + `mapped_at` | ISO-8601. `synced_at` = map time; `mapped_at` = persist time. |

### Proof-layer feed (L27-057/058/059)

L27 checklist / image / time-tracking data is always recorded under
`job_verifications.metadata['launch27']` for traceability. The concrete proof counters
(`evidence_count`, `checklist_complete`, `check_in_time`, `check_out_time`) are filled from L27
**only when SEEKLY captured nothing itself** — native SEEKLY work-log evidence always takes
precedence and is never overwritten.

## 4. Re-mapping existing data

The mapper runs on every new import and webhook. To (re)build the normalized record for **already
imported** bookings, re-run the interpretation pipeline — the existing command already does this:

```bash
php artisan launch27:recalculate-intelligence --portfolio=launch27       # whole portfolio
php artisan launch27:recalculate-intelligence --seller=<sellerId>         # one seller
```

## 5. Verification

- Unit test: `tests/Unit/Launch27/Launch27FieldMapperTest.php` (`php artisan test --filter=Launch27FieldMapperTest`).
- Inspect a record: `SELECT job_id, service_category, latitude, longitude, has_checklist, image_count, normalized_record FROM l27_booking_intelligence ORDER BY id DESC LIMIT 1\G`.
- Admin UI: **Launch27 Intelligence → Bookings** (`/admin/launch27-intelligence/bookings`).

---

*Related: `docs/SEEKLY-Launch27-Scope-of-Work-Task-Status.md` (Section D). The normalized record
schema object is L27-062; eligibility / proof / SLA classification (L27-032…035) is layered on top
of these mapped fields by `OperationalRecordClassifier`.*
