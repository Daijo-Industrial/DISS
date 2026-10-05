---
name: fleet-management
description: Domain knowledge, architecture, P2H daily inspection workflow, Indonesian license plate validation rules, legal compliance reminders (KIR & STNK), QR code physical stickers, responsive view engine, and Livewire UI conventions for the Fleet Management & Vehicle module in DISS.
---

# Fleet Management, Vehicle Inspection (P2H) & Legal Compliance

This skill documents domain architecture, database schemas, P2H inspection workflows, Indonesian license plate standards, compliance reminders, QR code physical sticker specifications, and UI conventions for the Fleet Management module in Daijo Industrial System (DISS).

---

## 1. Architecture & Database Design

The fleet module integrates directly with the existing `Vehicle` system rather than duplicating tables:

### Core Tables & Models

1. **`vehicles`** (Master armada unit)
   - Primary Key: **UUID** (`char(36)` string) using `Illuminate\Database\Eloquent\Concerns\HasUuids`.
   - Migration: `2026_09_29_150732_change_vehicles_id_to_uuid.php`.
   - Extended columns:
     - `category`: `in:passenger,commercial_truck,pickup,other` (Note: `motorcycle` is explicitly excluded from company fleet).
     - `fuel_type`: `in:petrol,diesel,ev`.
     - `requires_kir`: `boolean` (Default: `false`, auto-enabled for `commercial_truck`).
   - Model: `App\Infrastructure\Persistence\Eloquent\Models\Vehicle` (aliased by `App\Models\Vehicle`).
   - Relationships:
     - `documents()`: HasMany `VehicleDocument`.
     - `inspections()`: HasMany `VehicleInspection`, sorted deterministically by `orderByDesc('created_at')->orderByDesc('id')`.
     - `activeCheckOut()`: HasOne `VehicleInspection` where `inspection_type = 'check_out'` and has no corresponding `check_in`.
     - `services()`: HasMany `ServiceRecord`.
     - `latestService()`: HasOne `ServiceRecord` latest by `service_date`.
   - Accessors: `region_name`, `category_label`, `fuel_type_label`, `display_name`, `is_out_on_trip`.

2. **Foreign Key Tables (All Converted to UUID)**:
   - `delivery_notes.vehicle_id`: UUID (`char(36)`).
   - `service_records.vehicle_id`: UUID (`char(36)`).
   - `vehicle_documents.vehicle_id`: UUID (`char(36)`).
   - `vehicle_inspections.vehicle_id`: UUID (`char(36)`).

3. **`vehicle_documents`** (Legalitas KIR, STNK, Asuransi)
   - Columns: `vehicle_id` (foreign UUID), `document_type`, `document_number`, `expired_date`, `last_renewed_date`, `attachment_path`, `notes`.
   - Types: `kir`, `stnk` (unified UI alias), `stnk_annual` (pajak 1 tahun), `stnk_five_year` (plat & STNK 5 tahun), `insurance`, `other`.
   - Model: `App\Models\VehicleDocument`.
   - Statuses: `expired` (sisa $\le 0$ hari), `critical` ($\le 7$ hari), `warning` ($\le 30$ hari), `valid` ($> 30$ hari).
   - **Unified STNK Architecture**:
     - SAMSAT issues a single physical STNK document with two sheets: Lembar Pajak Tahunan (PKB/SWDKLLJ) and Lembar STNK/Plat Kaleng (5 Tahunan).
     - The UI presents a consolidated **"STNK & Pajak Kendaraan"** card with dual-date tracking and a single file upload modal.
     - Backend atomically saves both `stnk_annual` and `stnk_five_year` records within a `DB::transaction()`, sharing the same `document_number` and `attachment_path` without schema migrations.
     - `deleteStnk()` cleans up both records and storage file; `deleteDocument()` checks sibling references before unlinking shared attachments.

4. **`vehicle_inspections`** (Inspeksi Harian P2H - Pemeliharaan Pemeriksaan Harian)
   - Columns: `vehicle_id` (foreign UUID), `parent_inspection_id`, `inspection_type` (`check_out` | `check_in`), `checked_at` (`timestamp`, explicit departure/return time), `driver_name`, `created_by`, `odometer`, `fuel_percentage`, `trip_distance`, `checklist_results` (`json`), `severity` (`none` | `minor` | `critical_grounded`), `defect_notes`, `defect_photos` (`json`), `inspector_id`.
   - Model: `App\Models\VehicleInspection`.
   - Accessor fallback: `$inspection->checked_at` automatically defaults to `created_at` if null.
   - Relationship sorting: `$vehicle->inspections()` ordered deterministically by `orderByDesc('checked_at')->orderByDesc('id')`.

---

## 2. Indonesian License Plate (TNKB) Standard & Centralized Config

All plate formatting, regex patterns, and region mappings are centralized in **`config/fleet.php`**:

### Regulatory Rules (Perpol No. 7 Tahun 2021)
- Format: `[1-2 Huruf Wilayah] [1-4 Digit Angka] [1-4 Huruf Seri]`
- Angka nomor polisi: 1 sampai 4 digit (`1`–`9999`), **tidak boleh diawali angka 0**.
- Regex: `^[A-Z]{1,2}\s[1-9][0-9]{0,3}\s[A-Z]{1,4}$`
- Access via: `config('fleet.plate.regex')`.

### Region Auto-Detection
- Mapped in `config('fleet.plate_regions')` covering 40+ Indonesian Samsat registration areas (Jadetabek `B`, Bandung `D`, Surabaya `L`, Bali `DK`, dll).
- Resolvable via Model Accessor: `$vehicle->region_name`.

### Strict Uniqueness & Database Constraint
- Migration: `2026_10_05_170000_enforce_unique_plate_number_on_vehicles_table.php`.
- Enforces strict database-level unique index on `vehicles.plate_number` to prevent any duplicate registration across the fleet.

### Input Normalization & Real-Time Formatting
- Handled in `App\Livewire\Vehicles\Form` and `form.blade.php`:
  - Real-time auto-spacing and formatting on typing (`b1234xyz` &rarr; `B 1234 XYZ`), seamlessly inserting spaces between wilayah, number, and suffix without jumping or backspace fighting.
  - Real-time validation and duplicate check: once regex matches, Livewire checks uniqueness against existing vehicles in the database, displaying instant visual confirmation or duplicate error.
  - Automatically normalizes on blur and save.

### Stepper-like Vehicle Registration & Edit Wizard (`App\Livewire\Vehicles\Form`)
- **3-Step Wizard Architecture (for Managers)**:
  - **Step 1: Identitas & Status Armada**: TNKB plate number with real-time formatting & regex check, driver assignment, operational status pills, conditional sale date (`sold_at` if marked sold), and vehicle profile photo upload.
  - **Step 2: Kategori & Regulasi**: Visual category selection cards (auto-toggling KIR for `commercial_truck`), fuel type selection (`petrol`, `diesel`, `ev`), and KIR compliance toggle.
  - **Step 3: Spesifikasi Teknis & Ringkasan**: Brand, model, year, VIN, odometer, and a reactive confirmation preview card before final submission.
- **Granular RBAC Adaptation**:
  - Full managers (`canManage = true`) navigate the full 3-step wizard (`totalSteps = 3`) via `goToStep()`, `nextStep()`, and `previousStep()`.
  - Non-managers (`canManage = false`) are restricted to a single step (`totalSteps = 1`) containing only plate number and driver name without wizard navigation controls.
- **Fail-safe Validation Navigation**:
  - Step transitions validate intermediate inputs (`validateStep1()`, `validateStep2()`).
  - Directly calling `save()` validates all rules and automatically navigates `$currentStep` to whichever step contains the first validation error.

---

## 3. Fleet Categories, KIR Compliance & P2H Passenger Restriction

- **Kategori Armada**:
  - `passenger`: Mobil Penumpang (Avanza, Innova, Sedan, MPV operasional).
  - `commercial_truck`: Truk / Mobil Gede (Engkel, Fuso, Wingbox, Mobil Niaga).
  - `pickup`: Pick-up / Mobil Bak (Grandmax, Carry, L300).
  - `other`: Lainnya / Fasilitas Pool Khusus (Forklift pool, dll).
- **KIR Automation Rule**:
  - Memilih `commercial_truck` otomatis mengeset `requires_kir = true` di Alpine.js dan Livewire.
- **P2H Passenger Strictness Rule**:
  - Pemeriksaan harian P2H (`/vehicles/{vehicle}/inspect` & `/vehicles/scan`) **HANYA** berlaku untuk kategori `passenger`.
  - Armada non-passenger (`commercial_truck`, `pickup`, `other`) dilarang melakukan inspeksi P2H dan tidak menampilkan tombol aksi P2H di Card View, Table View, maupun Cockpit.

---

## 4. Physical QR Code Sticker & In-App Camera Scanner Standard

### Physical QR Code Sticker Standard
Driver scans the physical sticker attached to the vehicle dashboard / steering wheel to open the inspection form:
- **Payload Rule**: The QR code MUST contain **strictly the vehicle UUID** (`(string) $vehicle->id`), not the full URL or metadata. This ensures physical stickers never break or become stale when server IP or domain changes.
- **Generation**: Powered by `endroid/qr-code` in `App\Livewire\Vehicles\Show` (`openQrModal()`), output as Base64 SVG or PNG with High Error Correction (`ErrorCorrectionLevel::High`).
- **Print Optimization (`@media print`)**:
  - Isolated sticker styling that hides the rest of the web page during `window.print()`.
  - Crisp high-contrast border with vehicle plate number, model, and UUID printed underneath for physical verification.

### In-App Camera Scanner (`/vehicles/scan`)
- **Route**: `Route::get('/vehicles/scan', \App\Livewire\Vehicles\Scan::class)->name('vehicles.scan')`.
- **Component**: `App\Livewire\Vehicles\Scan` (`resources/views/livewire/vehicles/scan.blade.php`).
- **Engine**: Powered by `html5-qrcode` bundled into Vite (`window.Html5Qrcode`).
- **Features**:
  - Live animated laser HUD reticle with camera switch (back/front) & torch/flashlight control.
  - Audio (Web Audio API 880Hz chime) and haptic vibration feedback on successful QR detection.
  - Resolves raw UUID, URL with UUID, or vehicle license plate into `vehicles.inspect`.
  - Manual fallback search for dirty/damaged physical stickers or blocked camera permissions.
- **Passenger Enforcement**:
  - `resolve(string $code)`: Scanned QR strictly checks `category === 'passenger'`. If non-passenger, sets error message `__('fleet.scanner.passenger_only')`, dispatches `scan-failed` to re-arm camera, and halts navigation.
  - `searchManual()`: Exact plate search rejects non-passenger vehicles; partial match query strictly filters `where('category', 'passenger')`.
  - `selectVehicle(string $id)`: Validates that selected vehicle is `passenger` before redirecting.
  - `render()`: Quick picker query is scoped strictly to `->where('category', 'passenger')`. Non-passenger buttons are omitted from the template.

---

## 5. P2H Daily Inspection Workflow (Two-Phase P2H)

Managed by `App\Livewire\Vehicles\InspectionForm` (`resources/views/livewire/vehicles/inspection-form.blade.php`):

1. **Passenger Category Guard**:
   - `mount()` enforces `$vehicle->category === 'passenger'`. If non-passenger, aborts `403 Forbidden` (`__('fleet.inspection.passenger_only')`).
2. **Operational Driver Selection (TomSelect)**:
   - Configurable roster in `config('fleet.drivers')`.
   - UI uses TomSelect with `create: true`, allowing one-tap selection from the preset roster or typing custom driver names.
   - **Persisted Default Driver**: `$vehicle->driver_name` stores the master designated driver (assigned/edited exclusively by users with `fleet.manage`). P2H inspection submissions record the trip driver into `vehicle_inspections.driver_name` and NEVER overwrite `$vehicle->driver_name`.
   - Scoped styling in `resources/css/app.css` (`.ts-fleet-driver`).
3. **Mandatory Header Fields**:
   - `created_by`: Required string for the inspector/creator name.
   - `trip_purpose`: Required string for route and business trip purpose.
   - `checked_at`: Required datetime (`Y-m-d\TH:i`, initial default: `now()`). Allows inspectors to explicitly specify departure time (for check-out) or return time (for check-in) instead of relying solely on system `created_at`.
4. **Auto-Detect & Switching**:
   - On load, automatically detects if the vehicle is currently on a trip (`$vehicle->is_out_on_trip`). If out, defaults to `check_in`; otherwise `check_out`.
   - One-tap switch buttons (`switchType('check_out')` / `switchType('check_in')`) allow instant mode changes without re-entering form data.
5. **Check-out (Sebelum Berangkat)**:
   - Mencatat Odometer awal, BBM %, dan 7 titik checklist: bodi, ban, km/odometer, isi mobil/kabin, baterai/aki & bensin, lampu rem, lampu depan & sein.
6. **Check-in (Kepulangan)**:
   - Mencatat Odometer akhir & BBM sisa.
   - Otomatis menghitung jarak tempuh (`trip_distance = odometer_kembali - odometer_berangkat`) dan menautkan ke `parent_inspection_id`.
7. **Critical Defect Grounding**:
   - Jika terdapat temuan `critical_grounded`, status kendaraan otomatis dikunci menjadi `VehicleStatus::MAINTENANCE`, mencegah keberangkatan baru sampai diservis.
8. **Handover ke Bengkel**:
   - Temuan P2H dapat langsung dialihkan ke form servis (`App\Livewire\Services\Form`) dengan pre-fill otomatis via query params `?vehicle_id=X&defect=Y`.

---

## 6. Automated Expiry Reminders (`fleet:check-reminders`)

- **Command**: `php artisan fleet:check-reminders` (dijadwalkan di `app/Console/Kernel.php` setiap hari jam `07:30`).
- **Ambang Batas**: H-30, H-14, H-7, dan saat kadaluarsa (`expired_date <= today`).
- **Notification**: `App\Notifications\VehicleDocumentExpiryNotification` (Email & Database in-app bell notification).
- **Target Penerima**: Tim Personalia / GA, Super Admin, dan driver penanggung jawab unit.

---

## 7. UI Conventions, Responsiveness & Field Ergonomics (Ponytail & KISS)

### Responsive View Engine (`resources/views/livewire/vehicles/index.blade.php`)
- **Fleet Type Filter (Category)**:
  - Defaults to `passenger` (`$category = 'passenger'`).
  - Dropdown options: `passenger` (Mobil Penumpang), `commercial_truck` (Truk), `pickup` (Pick-up), `other` (Lainnya), `all` (Semua Tipe Armada).
  - KPI Metrics (`$baseMetricsQuery`) are dynamically scoped to the selected category (unless `'all'`), ensuring operational status pill counts (`Semua`, `Di Pool`, `On-Trip`, `Perawatan`) accurately reflect the filtered category.
- **Initially Hidden Available Filters & Status Dropdown (`vehicles/index.blade.php`)**:
  - The search input, "Filter" toggle button, and compact per-page dropdown (`10 / hal`, `20 / hal`, `50 / hal`, without redundant `"Baris:"` label) remain visible in the main bar.
  - Operational status pills were replaced with an operational status dropdown (`Semua Status`, `Di Pool`, `On-Trip`, `Perawatan`, `Terjual` for managers) housed inside the collapsible filter drawer (`showFilters: false` in Alpine `x-data`, using `x-collapse` and `x-cloak`).
  - Sort fields ("Urutkan Berdasarkan" & "Arah Urutan") are conditionally shown inside the filter drawer only when in Gallery Grid view (`x-show="viewMode === 'grid'"`).
  - In Table view (`x-show="viewMode === 'table'"`), table headers (`Armada / Plat`, `Driver`, `Odometer`, `Status Operasional`, `Servis Terakhir`) feature interactive selectable column sorting (`wire:click="sortBy('...')"` with ascending/descending directional icons).
  - **Interactive Multi-Select Table Column Popovers**:
    - `Kategori` and `Status Operasional` table column headers open interactive Alpine popovers (`catFilterOpen`, `statusFilterOpen`) allowing users to check 1 or more options simultaneously (`$selectedCategories`, `$selectedStatuses`) with color badges and count indicators.
    - Features 1-tap quick actions: **"Semua"** (select all), **"Reset"** (restore default/clear), and **"Tutup"**.
    - Full two-way synchronization with legacy drawer dropdowns (`$category` and `$operationalTab` reflect `'custom'` when multi-selected) and highlights the filter button indicator dot.
    - Seamlessly composes with keyword search (`$q`), column sorting (`$sort`), and pagination (`$perPage`).
  - An active indicator dot highlights the "Filter" button whenever non-default filters are active.

### Cockpit & Legal Documents View (`resources/views/livewire/vehicles/show.blade.php`)
- **Indonesian TNKB Plate Chassis**: Monospace bold plate badge with metallic bolt styling.
- **Initially Hidden Collapsible Right Layout (`showRightLayout`)**:
  - By default on initial load (`/vehicles/{id}`), the heavy right layout (P2H inspection tables, legal document cards, workshop service history) is initially hidden (`showRightLayout = false`), with the vehicle cockpit taking the full space of a focused `max-w-xl` container (`w-full`).
  - Expanding any detail tab or clicking "Buka Detail" dynamically transitions the container to `max-w-7xl` with a 2-column split (`lg:col-span-4` sticky cockpit on the left, `lg:col-span-8` tab details on the right).
  - The segmented tab control is kept clean and dedicated solely to tabs (P2H, Documents, Services), with panel toggling managed cleanly from the top action bar or the cockpit quick-access card.
  - Mounting with an explicit query parameter (e.g. `?tab=documents` from compliance alerts) or setting a tab via `setTab('...')` automatically opens the right layout (`showRightLayout = true`).
  - **Always Accessible Tab Counts**:
    - **Top Action Bar**: Toggle button beside VIN displaying total tab count (`Tutup Panel Detail` / `Buka Detail`).
    - **Cockpit Tab Quick Access Card**: Prominent 3-tab card (`Riwayat & Dokumen`) displaying real-time counts and badges for P2H (`$inspections->total()`), Documents (`$documents->count()` with expired/warning indicator), and Services (`$records->total()`). Clicking any tab immediately opens the right layout on that exact tab.
    - **Glance & Health Shortcuts**: Clicking Last Service, Document Compliance, or P2H Inspection rows smoothly opens the right layout on that tab.
- **Unified 2-Card Legal Documents Tab (`show-tab-documents.blade.php`)**:
  - **Card 1: KIR (Uji Berkala)**: Automatic commercial/passenger mandate display.
  - **Card 2: STNK & Pajak Kendaraan**: Consolidates Annual Tax (PKB 1-Yr) and 5-Year Plate Renewal into a single card with dual-date rows, document number, unified Lightbox preview, and single renewal trigger.
  - **Other Documents (`Dokumen Lainnya`)**: Collapsible section for vehicle insurance/policies, BPKB, etc.
  - **Document Archive (`Arsip Dokumen`)**: Complete historical renewal audit trail table.
- **Unified Modal (`show-modals.blade.php`)**:
  - Dynamically switches to dual expiration date pickers (`stnk_annual_expired_date` & `stnk_five_year_expired_date`) when `stnk` is selected, pre-populating existing dates and sharing a single file attachment.
### Universal Photo Lightbox (`resources/views/components/universal-lightbox.blade.php`)
- **Reactive 0ms Alpine.js Modal**: Replaces direct storage URLs (`target="_blank"`) across all vehicle views.
- **Trigger**: Dispatched anywhere via `@click="$dispatch('open-lightbox', { src: url, title: caption, subtitle: sub })"`.
- **Coverage**: Hero vehicle profile photos, P2H checklist point photos, P2H defect photos, document attachments (KIR/STNK), and inspection temporary photo previews.

### Access Control & Granular RBAC (`app/Infrastructure/Common/PermissionRegistry.php`)
- **`fleet.view`**: View fleet lists and basic vehicle identity/cockpit overview.
- **`fleet.inspect`**: Access camera scanner (`/vehicles/scan`) and submit P2H daily inspection logs (Check-out & Check-in). Post-submission redirects to `vehicles.index` with a standby re-scan CTA.
- **`fleet.documents`**: View Legal Documents tab (`show-tab-documents`), STNK & KIR records, and download/preview attachments in Lightbox. (Hidden from basic inspectors).
- **`fleet.view-costs`**: View financial figures (Rupiah line-item costs, YTD cost, lifetime cost in `show-tab-services`). (Hidden from basic inspectors).
- **`fleet.manage`**: Full administrative access: register/edit/delete vehicles, upload profile photos, manage legal documents, record workshop services, and print QR stickers.
- **Role Assignments**:
  - Full Access: `admin`, `hr`, `hrd`, `hrd-manager`, `operations`, `logistics`, `manager` have all fleet permissions.
  - Operational/Field Staff: `inspector`, `fleet-inspector`, `driver`, `staff` have `['fleet.view', 'fleet.inspect']`.
- **Inspector Mobile/Tablet Navigation (`NavigationService.php`)**:
  - For users with `fleet.inspect` who lack `fleet.manage`, the sidebar menu item dynamically directs to `route('vehicles.scan')` labeled as *"P2H Armada"*, providing instant 1-tap scanner access.

---

## 8. Development & Testing Runbooks

All tests and tools run inside the Docker Sail container (`diss-laravel.test-1`):

```bash
# Run fleet inspection and compliance test suite (31 tests, 334 assertions)
docker exec diss-laravel.test-1 php artisan test --filter=VehicleInspectionAndComplianceTest

# Check document reminders manually
docker exec diss-laravel.test-1 php artisan fleet:check-reminders

# Format code with Laravel Pint (ALWAYS target specific files to prevent timeout)
docker exec diss-laravel.test-1 ./vendor/bin/pint app/Infrastructure/Common/PermissionRegistry.php app/Services/NavigationService.php app/Livewire/Vehicles/Index.php app/Livewire/Vehicles/Scan.php app/Livewire/Vehicles/InspectionForm.php app/Livewire/Vehicles/Show.php tests/Feature/VehicleInspectionAndComplianceTest.php
```

> [!WARNING]
> **Vite HMR Active**: NEVER run `npm run build` or production frontend compilation commands in the development environment. Vite HMR is running live and serving assets over LAN/IP.

