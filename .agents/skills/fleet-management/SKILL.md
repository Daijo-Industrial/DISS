---
name: fleet-management
description: Domain knowledge, architecture, P2H daily inspection workflow, Indonesian license plate validation rules, legal compliance reminders (KIR & STNK), QR code physical stickers, and UI conventions for Fleet Management in DISS.
---

# Fleet Management & Vehicle Inspection (P2H)

## 1. Architecture & Persistence

### Models & Schema
- **`Vehicle`** (`vehicles`):
  - Primary Key: **UUID** (`HasUuids`).
  - Columns: `category` (`passenger`, `commercial_truck`, `pickup`, `other`; motorcycle excluded), `fuel_type` (`petrol`, `diesel`, `ev`), `requires_kir` (boolean, auto-enabled for `commercial_truck`).
  - Relations: `documents()`, `inspections()` (ordered by `checked_at DESC, id DESC`), `activeCheckOut()`, `services()`.
- **`VehicleDocument`** (`vehicle_documents`):
  - Types: `kir`, `stnk` (UI alias), `stnk_annual` (1-year tax), `stnk_five_year` (5-year plate/STNK), `insurance`, `other`.
  - Expiry Statuses: `expired` ($\le 0$ days), `critical` ($\le 7$ days), `warning` ($\le 30$ days), `valid` ($> 30$ days).
  - **Unified STNK Model**: SAMSAT STNK has dual expirations (PKB annual and 5-year plate). Backend atomically saves both `stnk_annual` and `stnk_five_year` within a `DB::transaction()`, sharing `document_number` and `attachment_path`.
- **`VehicleInspection`** (`vehicle_inspections`):
  - Columns: `vehicle_id` (UUID), `parent_inspection_id`, `inspection_type` (`check_out`, `check_in`), `checked_at`, `driver_name`, `created_by`, `odometer`, `fuel_percentage`, `trip_distance`, `checklist_results`, `severity`, `defect_notes`, `defect_photos`.
- **Foreign Keys**: `delivery_notes.vehicle_id`, `service_records.vehicle_id`, `vehicle_documents.vehicle_id`, `vehicle_inspections.vehicle_id` all use UUID.

## 2. Indonesian License Plate (TNKB) Standard

Centralized in `config/fleet.php`:
- **Format**: Perpol No. 7/2021 (`[1-2 Huruf Wilayah] [1-4 Digit Angka] [1-4 Huruf Seri]`). Number cannot start with 0.
- **Regex**: `^[A-Z]{1,2}\s[1-9][0-9]{0,3}\s[A-Z]{1,4}$` (`config('fleet.plate.regex')`).
- **Database Constraint**: Strict unique index on `vehicles.plate_number`.
- **Formatting**: Auto-spaced on blur/save (`b1234xyz` $\rightarrow$ `B 1234 XYZ`).
- **Registration Wizard (`App\Livewire\Vehicles\Form`)**:
  - Managers (`canManage = true`): 3-step wizard (Identity $\rightarrow$ Category & KIR $\rightarrow$ Specs & Summary).
  - Non-managers (`canManage = false`): Single step (plate number and driver name only).

## 3. Fleet Categories & P2H Passenger Restriction

- **Categories**: `passenger` (Avanza, Innova, MPV), `commercial_truck` (Engkel, Fuso, Wingbox), `pickup` (Grandmax, Carry), `other` (Forklift pool).
- **KIR Automation**: `commercial_truck` auto-sets `requires_kir = true`.
- **P2H Strictness**: Daily inspection (`/vehicles/{vehicle}/inspect` & `/vehicles/scan`) is **strictly passenger only**. Commercial trucks, pickups, and special vehicles do not use P2H workflows.

## 4. QR Code Sticker & Scanner Standard

- **Sticker Payload**: The QR code MUST contain **strictly the vehicle UUID** (`(string) $vehicle->id`), never full URLs. Ensures physical stickers remain valid across IP/domain changes.
- **Print Optimization**: Dedicated `@media print` layout hiding web navigation.
- **In-App Scanner (`/vehicles/scan`)**:
  - Powered by `html5-qrcode` (`window.Html5Qrcode`).
  - Audio/vibration feedback on detection.
  - Resolves raw UUID, URL with UUID, or license plate to `vehicles.inspect`.
  - Rejects non-passenger vehicles at scan resolution.

## 5. P2H Daily Inspection Workflow

Managed by `App\Livewire\Vehicles\InspectionForm`:
1. **Passenger Guard**: Aborts `403` if vehicle is not `passenger`.
2. **Driver Assignment**: TomSelect roster with free text entry. Stored in `vehicle_inspections.driver_name`; never overwrites master `$vehicle->driver_name`.
3. **Trip Context**: `created_by`, `trip_purpose` (suggestion chips via `config('fleet.trip_purposes')`), and `checked_at` (explicit departure/return time).
4. **Auto-Switching**: Detects `$vehicle->is_out_on_trip` to default to `check_in`; otherwise `check_out`.
5. **Check-out**: Logs initial odometer, fuel %, and 7 checklist points (body, tires, odometer, cabin, battery/fuel, brake lights, headlights/signals).
6. **Check-in**: Logs return odometer and fuel. Calculates `trip_distance = return_odometer - departure_odometer`.
7. **Tri-State Inspection Telemetry**:
   - **Paired** (`$isCheckIn && $parent`): Departure telemetry from `$parent`, return from `$ins`. Distance is `$ins->trip_distance`.
   - **Direct Check-in** (`$isCheckIn && !$parent`): Departure telemetry is strictly `null` (`$depTime = null`, `$depOdo = null`, `$depFuel = null`); badge displays `[Check-in Langsung]` and Departure Card displays empty state (`fleet.show.no_departure_data`). Never fall back to `$ins` telemetry for departure.
   - **Unclosed Check-out** (`!$isCheckIn`): Departure telemetry from `$ins`; Return Card displays unclosed state (`fleet.show.unclosed_trip`).
8. **Odometer Sanitization**: UI formats with thousand dots; backend strictly sanitizes to integer `(int) preg_replace('/\D/', '', $value)`.
9. **Fuel Gauge Assistant**:
   - Quick needle presets: $E$ (0%), $1/4$ (25%), $1/2$ (50%), $3/4$ (75%), $F$ (100%).
   - Bar segments formula: `fuel_percentage = round((selectedBar / totalBars) * 100)`.
10. **Defect Severity**:
   - Defaults to `minor` (vehicle remains operational).
   - `critical_grounded` automatically locks vehicle status to `VehicleStatus::MAINTENANCE`.
   - Defect handover pre-fills workshop service form via `?vehicle_id=X&defect=Y`.

## 6. Automated Expiry Reminders

- **Cron**: `php artisan fleet:check-reminders` (scheduled daily at `07:30`).
- **Thresholds**: H-30, H-14, H-7, and expired (`expired_date <= today`).
- **Notification**: `VehicleDocumentExpiryNotification` (mail and in-app database bell).
- **Testing Recipient Scoping**: `fleet:check-reminders` strictly queries users with the `super-admin` role or `PERSONALIA`/`GA` departments. In tests using `DatabaseTransactions`, unseeded test users created via `User::factory()->create()` must be explicitly assigned the `super-admin` role (`$this->user->assignRole('super-admin')`) to receive the notification.

## 7. UI Conventions & Responsiveness

- **Dedicated Live Radar (`/vehicles/radar` - `vehicles/radar.blade.php`)**:
  - Gated by `fleet.view` or `fleet.manage`.
  - **Ultra-Lean Live Cards**: Strictly 1 calm neutral card (`bg-white border-slate-200`) per vehicle on trip via `Vehicle::whereHas('activeCheckOut')`. Displays plate, model, elapsed duration pill, driver, purpose, and subtle footer (departure time, PIC inspector, and link). Odometer, fuel gauges, and loud amber borders are removed to eliminate visual noise on first landing.
  - **Inspector Fallback Invariant**: When displaying the inspector name in cards and logs, resolve in priority order: `{{ $trip->created_by ?: ($trip->inspector?->name ?? '—') }}`.
  - **Historical Orphan Checkouts Alert**: Calm collapsible alert for older unclosed checkouts not belonging to current `activeCheckOut`, with direct links to vehicle inspection history. Strictly restricted to users authorized to inspect vehicles (`fleet.inspect`); viewers without inspect capability do not see this banner.
  - Auto-refresh polling (`wire:poll.30s`) and manual refresh trigger.
- **Dedicated Daily Operations Cockpit (`/vehicles/operations` - `vehicles/operations.blade.php`)**:
  - Gated by `fleet.manage`.
  - **Weekly Day-Strip Navigation**: 7-day Monday–Sunday strip with week steppers (`previousWeek()` / `nextWeek()`), future days disabled, calendar picker, and dynamic `[Hari Ini]` button.
  - **Unified Timeline Rail**: Continuous vertical spine (`border-l-2`) with color-coded nodes (🟢 normal, 🔴 defect, 🟣 service, 🔵 direct check-in). Merges `TYPE_CHECK_IN` and `ServiceRecord` into `$timelineEvents` sorted by timestamp descending, terminated by start-of-day anchor.
  - **Unified Single-Layer Timeline Cards**: Eliminates redundant accordion toggles and collapsible drawers to remove click friction. Each completed trip is a single clean card:
    - Header: Time badge (`H:i WIB`), span (`08:00 → 10:30 (2j 30m)`) or `[Check-in Langsung]`, and health badge (`P2H Normal` or `Defek Dilaporkan`).
    - Body: Vehicle avatar, plate (with `whitespace-nowrap`), model, driver, purpose, and defect note (`⚠️ Catatan kerusakan: ...`). Defect photo thumbnails are removed from the timeline feed to keep it lean, accessible via vehicle profile cockpit.
    - Unified Footer Ledger Bar: Trip metrics and inspector in one clean footer row:
      - Paired trip: `+50 km (50,000 → 50,050 km) • BBM 70% (-10%) • Petugas: Gate Security • [Lihat Unit →]`.
      - Direct check-in: `15,000 km • BBM 80% • Petugas: PIC • [Lihat Unit →]`.
  - **Zero Duplication**: Ongoing trips are strictly exclusive to `/vehicles/radar`.
- **Vehicle Index (`vehicles/index.blade.php`)**:
  - Category filter defaults to `passenger`.
  - Filter drawer (`x-collapse`) houses operational status and gallery sorting.
  - Table view features column header sorting and interactive multi-select popovers for Category and Status.
- **Vehicle Detail Cockpit (`vehicles/show.blade.php`)**:
  - Permanent 2-column split cockpit (`max-w-7xl` with `lg:col-span-4` sticky cockpit and `lg:col-span-8` tab details).
  - **Contextual Back Link**: Dynamically returns user to their origin view (`from=operations` returns to Daily Operations, `from=radar` returns to Live Radar, default to Catalog).
  - **Single Source of Truth for Active Trip**: When on trip, live activity status (elapsed time, driver, departed time, odo, purpose) and the primary `[Check-in]` action are centralized in the Left Sticky Cockpit, eliminating duplicate banners from Tab 1.
  - **Mobile Table Ergonomics**: Tables in Tab 1 (P2H) and Tab 3 (Services) are wrapped with `overflow-x-auto` to allow smooth horizontal scrolling on mobile viewports without squishing columns.
  - **Specs Card**: Houses Brand/Model, VIN (if present), Fuel Type, Assigned Driver, and Odometer.
  - **P2H Logbook Filter Reset**: Empty search states display a contextual message and quick reset button (`resetP2hFilters()`).
- **Universal Lightbox**: Modal image preview (`$dispatch('open-lightbox', { src, title, subtitle })`).
- **Live Operational Radar (`/vehicles/radar`)**: Dedicated telemetry screen featuring a unified Live capsule with an animated green beacon (`animate-ping`), uppercase `LIVE` badge, live timestamp (`Diperbarui: HH:mm:ss WIB`), automatic 30s background poll, and loading-spin manual refresh.
- **Bilingual Parity**: All P2H UI strings must have matching keys in `lang/id/fleet.php` and `lang/en/fleet.php`.
- **Permissions (`PermissionRegistry.php`)**:
  - `fleet.view`: View fleet lists and cockpit overview.
  - `fleet.inspect`: Operational inspector (access `/vehicles/scan`, quick check-in/out buttons in index/cockpit, submit P2H logs).
  - `fleet.documents`: View legal documents tab and download files.
  - `fleet.view-costs`: View financial figures and service costs.
  - `fleet.manage`: Administrative access (create, edit, delete, document management, QR print, daily operations cockpit).
  - **Inspection Decoupling Invariant**: `$canInspect` in `Vehicles\Index` & `Vehicles\Show` strictly checks `$user->can('fleet.inspect')` and must never inherit from `fleet.manage`. Users with `fleet.manage` without `fleet.inspect` do not see quick-action P2H buttons, but retain full access to view inspection logs and the `/vehicles/operations` cockpit.

## 8. Runbooks

```bash
# Run fleet inspection and compliance test suite
docker exec diss-laravel.test-1 php artisan test --filter=VehicleInspectionAndComplianceTest

# Check document reminders manually
docker exec diss-laravel.test-1 php artisan fleet:check-reminders

# Format modified fleet files
docker exec diss-laravel.test-1 ./vendor/bin/pint app/Infrastructure/Common/PermissionRegistry.php app/Services/NavigationService.php app/Livewire/Vehicles/Index.php app/Livewire/Vehicles/Scan.php app/Livewire/Vehicles/InspectionForm.php app/Livewire/Vehicles/Show.php tests/Feature/VehicleInspectionAndComplianceTest.php
```
