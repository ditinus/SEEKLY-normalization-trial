# SEEKLY Normalized Operational Record — Schema (SOW §6.2)

**Task:** L27-062 (normalized record schema) · L27-063 (demo masking) · QA deliverable L27-282
**Status:** complete

Every imported Launch27 booking becomes a single, stable **SEEKLY operational record**. This
document is the canonical contract for that record. It is the projection consumed by exports, the
QA evidence pack, and dashboard glossaries.

## Source of truth

| Artifact | Path |
|----------|------|
| Machine-readable JSON Schema (draft-07) | `database/schemas/seekly-operational-record.schema.json` |
| PHP contract + validator | `app/Support/Launch27/SeeklyOperationalRecordSchema.php` |
| Record builder (projection) | `app/Services/Launch27/Intelligence/Classification/SeeklyOperationalRecord.php` |
| Field-by-field source mapping | `docs/SEEKLY-Launch27-Field-Mapping.md` |

`SeeklyOperationalRecord::fromEntities()` exposes two projections:

- `toSowRecord()` — the exact SOW §6.2 contract below (masked, investor-safe). Validated by
  `SeeklyOperationalRecordSchema::validate()`, which returns an empty array on conformance.
- `toArray()` — a richer internal superset (adds proof/SLA/risk detail, geo, counts) for SEEKLY
  dashboards. Not part of the published contract.

## Schema (version 1.0)

| Field | Type | Notes |
|-------|------|-------|
| `seekly_operational_record_id` | integer | SEEKLY intelligence row id. |
| `source_system` | string | Always `launch27`. |
| `source_booking_id` | string\|integer\|null | Launch27 booking id / external work-order id. |
| `source_booking_digest` | string\|null | Launch27 digest. |
| `portfolio_id` | string\|integer\|null | Active portfolio (passed by the export caller). |
| `customer_id` | string\|integer\|null | Launch27 customer id. |
| `customer_name_masked` | string\|null | Masked under demo masking (e.g. `Customer A`); raw name otherwise. |
| `vendor_id` | integer\|null | Assigned SEEKLY seller. |
| `team_id` | string\|integer\|null | Launch27 assigned team. |
| `service_name` | string\|null | |
| `service_category` | string\|null | |
| `service_frequency` | string\|null | |
| `scheduled_at` | string(date-time)\|null | ISO-8601. |
| `arrival_window_start` | string\|null | |
| `arrival_window_end` | string\|null | |
| `address_masked` | string\|null | Suburb-level only (e.g. `Cammeray NSW`); full street is never projected. |
| `launch27_status` | string\|null | Raw L27 booking status. |
| `seekly_lifecycle_status` | string\|null | Lifecycle projection (see enum). |
| `proof_eligibility` | string | `eligible` \| `not_eligible`. |
| `sla_eligibility` | string | `eligible` \| `not_eligible`. |
| `risk_eligibility` | string | `eligible` \| `not_eligible`. |
| `has_checklist` | boolean | |
| `has_time_tracking` | boolean | True when tracked minutes > 0. |
| `has_notes` | boolean | Customer or staff notes present. |
| `has_booking_images` | boolean | |
| `is_historical_record` | boolean | Service date pre-dates SEEKLY connection. |
| `is_future_record` | boolean | Scheduled-future or pending-vendor. |
| `created_at` | string(date-time)\|null | L27 created. |
| `updated_at` | string(date-time)\|null | L27 updated. |
| `synced_at` | string(date-time)\|null | Last SEEKLY sync. |

### `seekly_lifecycle_status` enum

`service_completed`, `scheduled`, `in_progress`, `cancelled`, `unassigned`, `pending_vendor`,
`pending_customer`, `data_incomplete`, `archived`.

Derived from the eligibility category (SOW §7.1) via `EligibilityTaxonomy::lifecycleStatus()`.

## Demo masking (L27-063)

When institutional demo masking is active (`InstitutionalDemoDataMasker::active()`), the record is
built masked by default: `customer_name_masked` becomes a deterministic role label (`Customer A`)
and `address_masked` collapses to suburb + state. No raw PII (email, phone, street, full name) ever
appears in the SOW projection. Masking can be forced on/off via the `$mask` argument to
`fromEntities()` for export contexts.

## Sample (unmasked)

```json
{
  "seekly_operational_record_id": 4988,
  "source_system": "launch27",
  "source_booking_id": "4988",
  "source_booking_digest": "launch27_ref_4988",
  "portfolio_id": "wts_launch27_pilot_demo",
  "customer_id": "cus_001",
  "customer_name_masked": "Customer A",
  "vendor_id": 12,
  "team_id": "team_a",
  "service_name": "Regular Cleaning",
  "service_category": "residential_cleaning",
  "service_frequency": "fortnightly",
  "scheduled_at": "2026-06-12T09:00:00+00:00",
  "arrival_window_start": "2026-06-12T09:00:00Z",
  "arrival_window_end": "2026-06-12T10:00:00Z",
  "address_masked": "Cammeray NSW",
  "launch27_status": "completed",
  "seekly_lifecycle_status": "service_completed",
  "proof_eligibility": "eligible",
  "sla_eligibility": "eligible",
  "risk_eligibility": "eligible",
  "has_checklist": true,
  "has_time_tracking": true,
  "has_notes": true,
  "has_booking_images": false,
  "is_historical_record": false,
  "is_future_record": false,
  "created_at": "2026-06-12T09:00:00+00:00",
  "updated_at": "2026-06-12T10:00:00+00:00",
  "synced_at": "2026-06-12T10:05:00+00:00"
}
```

> Note: SEEKLY uses numeric ids where the SOW §6.2 example used illustrative string ids
> (`op_001`, `cus_001`). The schema accepts both; numeric ids are the production values.
