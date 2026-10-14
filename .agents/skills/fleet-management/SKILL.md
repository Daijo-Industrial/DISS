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
7. **Odometer Sanitization**: UI formats with thousand dots; backend strictly sanitizes to integer `(int) preg_replace('/\D/', '', $value)`.
8. **Fuel Gauge Assistant**:
   - Quick needle presets: $E$ (0%), $1/4$ (25%), $1/2$ (50%), $3/4$ (75%), $F$ (100%).
   - Bar segments formula: `fuel_percentage = round((selectedBar / totalBars) * 100)`.
9. **Defect Severity**:
   - Defaults to `minor` (vehicle remains operational).
   - `critical_grounded` automatically locks vehicle status to `VehicleStatus::MAINTENANCE`.
   - Defect handover pre-fills workshop service form via `?vehicle_id=X&defect=Y`.

## 6. Automated Expiry Reminders

- **Cron**: `php artisan fleet:check-reminders` (scheduled daily at `07:30`).
- **Thresholds**: H-30, H-14, H-7, and expired (`expired_date <= today`).
- **Notification**: `VehicleDocumentExpiryNotification` (mail and in-app database bell).

## 7. UI Conventions & Responsiveness

- **Vehicle Index (`vehicles/index.blade.php`)**:
  - Category filter defaults to `passenger`.
  - Filter drawer (`x-collapse`) houses operational status and gallery sorting.
  - Table view features column header sorting and interactive multi-select popovers for Category and Status.
- **Vehicle Detail Cockpit (`vehicles/show.blade.php`)**:
  - Initial load presents focused `max-w-xl` cockpit. Expanding tabs transitions to `max-w-7xl` 2-column layout (`lg:col-span-4` sticky cockpit, `lg:col-span-8` tab details).
  - Tab quick access card shows real-time badge counts for P2H, Documents, and Services.
- **Universal Lightbox**: Modal image preview (`$dispatch('open-lightbox', { src, title, subtitle })`).
- **Bilingual Parity**: All P2H UI strings must have matching keys in `lang/id/fleet.php` and `lang/en/fleet.php`.
- **Permissions (`PermissionRegistry.php`)**:
  - `fleet.view`: View fleet lists and cockpit overview.
  - `fleet.inspect`: Access scanner (`/vehicles/scan`) and submit P2H logs.
  - `fleet.documents`: View legal documents tab and download files.
  - `fleet.view-costs`: View financial figures and service costs.
  - `fleet.manage`: Full administrative access (create, edit, delete, document management, QR print).

## 8. Runbooks

```bash
# Run fleet inspection and compliance test suite
docker exec diss-laravel.test-1 php artisan test --filter=VehicleInspectionAndComplianceTest

# Check document reminders manually
docker exec diss-laravel.test-1 php artisan fleet:check-reminders

# Format modified fleet files
docker exec diss-laravel.test-1 ./vendor/bin/pint app/Infrastructure/Common/PermissionRegistry.php app/Services/NavigationService.php app/Livewire/Vehicles/Index.php app/Livewire/Vehicles/Scan.php app/Livewire/Vehicles/InspectionForm.php app/Livewire/Vehicles/Show.php tests/Feature/VehicleInspectionAndComplianceTest.php
```
