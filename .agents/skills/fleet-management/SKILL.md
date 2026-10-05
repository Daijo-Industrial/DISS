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
   - Columns: `vehicle_id` (foreign UUID), `parent_inspection_id`, `inspection_type` (`check_out` | `check_in`), `driver_name`, `odometer`, `fuel_percentage`, `trip_distance`, `checklist_results` (`json`), `severity` (`none` | `minor` | `critical_grounded`), `defect_notes`, `defect_photo_path`, `inspected_by_user_id`.
   - Model: `App\Models\VehicleInspection`.

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

### Input Normalization & Masking
- Handled in `App\Livewire\Vehicles\Form` via `normalizePlateNumber()`:
  - Uppercases input and strips invalid characters.
  - Automatically splits glued inputs without spaces (e.g. `b1234xyz` &rarr; `B 1234 XYZ`).
  - Automatically triggers on blur and Livewire `save()`.

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
   - Scoped styling in `resources/css/app.css` (`.ts-fleet-driver`).
3. **Mandatory Header Fields**:
   - `created_by`: Required string for the inspector/creator name.
   - `trip_purpose`: Required string for route and business trip purpose.
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
- **Conditional P2H Action Buttons**:
  - Primary CTA buttons (`P2H Check-out` / `Check-in Pulang ke Pool`) in Grid cards and Data Table rows are strictly guarded with `@if (!$v->is_sold && $v->category === 'passenger')`.
  - Non-passenger units render no P2H action button.
- **Default for Tablets & Mobile (`< 1280px` / iPad portrait 768px-820px, landscape 1024px-1180px, Android tabs)**:
  - Defaults to **Grid View (Galeri Kartu)**.
  - Large thumb-friendly 44px primary action buttons (`P2H Check-out` / `Check-in Pulang ke Pool`).
  - Active trip banner displaying driver name, purpose, and departure timestamp.
- **Default for Laptop Screens (`>= 1280px`)**:
  - Defaults to high-density **Table View (Data Table)**.
- **Alpine.js Instant Switcher**:
  - Both views are rendered in the DOM; switching is instantaneous (**0ms**) without server roundtrip.
  - User selection is saved to `localStorage('diss_vehicle_view_mode')` and cleanly mirrored to URL `?view=grid` or `?view=table` using `history.replaceState`.
  - Resizing or device rotation auto-adapts unless the user manually chose a view mode.
- **Livewire 3 Integration Invariant**:
  - Livewire 3 components strictly require **one single root HTML element**. Never place `<style>` or `<script>` tags outside the root `div` inside partials to prevent `MultipleRootElementsDetectedException`.
  - All 3rd-party library custom CSS (e.g. TomSelect `.ts-fleet-driver`) must be placed in `resources/css/app.css`.

### Apple-Inspired Minimalist UI Standard (`resources/views/livewire/vehicles/index.blade.php`)
- **Clean Typography Header**:
  - Direct title with an inline muted count pill (e.g., `12 unit`).
  - Omit verbose multi-line subtitles and redundant category badges above the title.
  - Action buttons styled as sleek pill buttons (`[Pindai QR]` subtle secondary, `[+ Tambah]` dark primary).
- **Segmented Status Pill Control**:
  - Replaces heavy, space-consuming KPI cards with a horizontal Apple-style segmented pill control track (`Semua`, `Di Pool`, `On-Trip`, `Perawatan`).
  - Active tab uses a crisp white card style with subtle shadow; inactive tabs use muted text.
  - Features color-coded status dots (Emerald for Pool, Amber for On-Trip, Rose for Maintenance) and count badges.
- **Compact Notification Strip**:
  - Compliance expiry warnings (KIR/STNK) are displayed as a single-line notification strip with an alert icon and direct drill-down link (`Lihat Dokumen →`), removing multi-badge tag clutter.
- **Clickable Card Touch Targets & Condensed Metadata**:
  - The entire card container is an interactive touch target navigating directly to the Cockpit (`route('vehicles.show', $v)`).
  - Metadata is condensed into a single clean line: `👤 Driver • Odometer km` with subtle dot indicators for STNK and KIR.
  - Secondary buttons ("Detail Cockpit", "Servis") are eliminated from the card face.
  - Only a single primary action button sits at the footer (`[ Check-in (Pulang ke Pool) → ]` or `[ P2H Check-out → ]`).
- **Data Table Row Navigation**:
  - Entire table row is clickable directly to Cockpit; the action column only contains the quick P2H action button.

### Cockpit & Legal Documents View (`resources/views/livewire/vehicles/show.blade.php`)
- **Indonesian TNKB Plate Chassis**: Monospace bold plate badge with metallic bolt styling.
- **Glance & Health Shortcuts**: Quick links to latest service record and document compliance status overview.
- **Unified 2-Card Legal Documents Tab (`show-tab-documents.blade.php`)**:
  - **Card 1: KIR (Uji Berkala)**: Automatic commercial/passenger mandate display.
  - **Card 2: STNK & Pajak Kendaraan**: Consolidates Annual Tax (PKB 1-Yr) and 5-Year Plate Renewal into a single card with dual-date rows, document number, unified Lightbox preview, and single renewal trigger.
  - **Other Documents (`Dokumen Lainnya`)**: Collapsible section for vehicle insurance/policies, BPKB, etc.
  - **Document Archive (`Arsip Dokumen`)**: Complete historical renewal audit trail table.
- **Unified Modal (`show-modals.blade.php`)**:
  - Dynamically switches to dual expiration date pickers (`stnk_annual_expired_date` & `stnk_five_year_expired_date`) when `stnk` is selected, pre-populating existing dates and sharing a single file attachment.

### Layout Navigation Drawer (`resources/views/new/layouts/app.blade.php`)
- **Breakpoint**: Drawer active on `< 1024px` (`lg:hidden`), providing full-width screen real estate for tablets in portrait mode.
- **Auto-Close Behavior**: Closes on link click (`@click="if ($event.target.closest('a')) sidebarOpen = false"`), backdrop click, or Escape key.
- **Thumb Ergonomics**: Mobile topbar hamburger button is aligned on the **right** for comfortable one-handed thumb reach on tall screens (iPhone XR).

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
# Run fleet inspection and compliance test suite (25 tests, 219 assertions)
docker exec diss-laravel.test-1 php artisan test --filter=VehicleInspectionAndComplianceTest

# Check document reminders manually
docker exec diss-laravel.test-1 php artisan fleet:check-reminders

# Format code with Laravel Pint (ALWAYS target specific files to prevent timeout)
docker exec diss-laravel.test-1 ./vendor/bin/pint app/Infrastructure/Common/PermissionRegistry.php app/Services/NavigationService.php app/Livewire/Vehicles/Index.php app/Livewire/Vehicles/Scan.php app/Livewire/Vehicles/InspectionForm.php app/Livewire/Vehicles/Show.php tests/Feature/VehicleInspectionAndComplianceTest.php
```

> [!WARNING]
> **Vite HMR Active**: NEVER run `npm run build` or production frontend compilation commands in the development environment. Vite HMR is running live and serving assets over LAN/IP.

