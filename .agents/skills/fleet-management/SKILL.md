---
name: fleet-management
description: Domain knowledge, architecture, P2H daily inspection workflow, Indonesian license plate validation rules, legal compliance reminders (KIR & STNK), and Livewire UI conventions for the Fleet Management & Vehicle module in DISS.
---

# Fleet Management, Vehicle Inspection (P2H) & Legal Compliance

This skill documents domain architecture, database schemas, P2H inspection workflows, Indonesian license plate standards, compliance reminders, and UI conventions for the Fleet Management module in Daijo Industrial System (DISS).

---

## 1. Architecture & Database Design

The fleet module integrates directly with the existing `Vehicle` system rather than duplicating tables:

### Core Tables
1. **`vehicles`** (Master armada unit)
   - Primary Key: **UUID** (`char(36)` string) using `Illuminate\Database\Eloquent\Concerns\HasUuids`.
   - Extended columns:
     - `category`: `in:passenger,commercial_truck,pickup,other` (Note: `motorcycle` is explicitly excluded from company fleet).
     - `fuel_type`: `in:petrol,diesel,ev`.
     - `requires_kir`: `boolean` (Default: `false`, auto-enabled for `commercial_truck`).
   - Model: `App\Infrastructure\Persistence\Eloquent\Models\Vehicle` (aliased by `App\Models\Vehicle`).
   - Relationships: `documents()`, `inspections()`, `activeCheckOut()`, `services()`.
   - Accessors: `region_name`, `category_label`, `fuel_type_label`, `display_name`, `is_out_on_trip`.
   - QR Code Physical Sticker: Encodes strictly the vehicle UUID `(string) $vehicle->id` with High Error Correction.

2. **`vehicle_documents`** (Legalitas KIR, STNK, Asuransi)
   - Columns: `vehicle_id` (foreign UUID), `document_type`, `document_number`, `expired_date`, `document_file_path`, `notes`.
   - Types: `kir`, `stnk_annual` (1 tahun), `stnk_five_year` (5 tahun), `insurance`.
   - Model: `App\Models\VehicleDocument`.
   - Statuses: `expired` (sisa $\le 0$ hari), `warning` ($\le 30$ hari), `valid` ($> 30$ hari).

3. **`vehicle_inspections`** (Inspeksi Harian P2H - Pemeliharaan Pemeriksaan Harian)
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

## 3. Fleet Categories & KIR Compliance Automation

- **Kategori Armada**:
  - `passenger`: Mobil Penumpang (Avanza, Innova, Sedan, MPV operasional).
  - `commercial_truck`: Truk / Mobil Gede (Engkel, Fuso, Wingbox, Mobil Niaga).
  - `pickup`: Pick-up / Mobil Bak (Grandmax, Carry, L300).
  - `other`: Lainnya / Fasilitas Pool Khusus (Forklift pool, dll).
- **KIR Automation Rule**:
  - Memilih `commercial_truck` otomatis mengeset `requires_kir = true` di Alpine.js dan Livewire.

---

## 4. P2H Daily Inspection Workflow (Two-Phase P2H)

Managed by `App\Livewire\Vehicles\InspectionForm` (`resources/views/livewire/vehicles/inspection-form.blade.php`):

1. **Check-out (Sebelum Berangkat)**:
   - Mencatat Odometer awal, BBM %, dan 7 titik checklist: bodi, ban, km/odometer, isi mobil/kabin, baterai/aki & bensin, lampu rem, lampu depan & sein.
2. **Check-in (Kepulangan)**:
   - Mencatat Odometer akhir & BBM sisa.
   - Otomatis menghitung jarak tempuh (`trip_distance = odometer_kembali - odometer_berangkat`) dan menautkan ke `parent_inspection_id`.
3. **Critical Defect Grounding**:
   - Jika terdapat temuan `critical_grounded`, status kendaraan otomatis dikunci menjadi `VehicleStatus::MAINTENANCE`, mencegah keberangkatan baru sampai diservis.
4. **Handover ke Bengkel**:
   - Temuan P2H dapat langsung dialihkan ke form servis (`App\Livewire\Services\Form`) dengan pre-fill otomatis via query params `?vehicle_id=X&defect=Y`.

---

## 5. Automated Expiry Reminders (`fleet:check-reminders`)

- **Command**: `php artisan fleet:check-reminders` (dijadwalkan di `app/Console/Kernel.php` setiap hari jam `07:30`).
- **Ambang Batas**: H-30, H-14, H-7, dan saat kadaluarsa (`expired_date <= today`).
- **Notification**: `App\Notifications\VehicleDocumentExpiryNotification` (Email & Database in-app bell notification).
- **Target Penerima**: Tim Personalia / GA, Super Admin, dan driver penanggung jawab unit.

---

## 6. UI Conventions (Ponytail Standard & KISS)

- **Layout**: Clean enterprise minimalist, Single-Column Studio layout without visual noise or walls of text.
- **Plate Input**: Indonesian plate chassis with monospace bold typography, `RI` emblem, and compact region pill (`DKI Jakarta (B)`).
- **Category & Fuel Controls**: Visual icon cards for category and crisp segmented pill buttons for fuel (`Bensin`, `Solar`, `EV`).
- **Access Control**:
  - `$fullFeature = $user->hasRole('super-admin') || ($user->department?->name === 'PERSONALIA');`
  - Non-fullFeature users are hard-guarded to only edit `driver_name` and `plate_number`.
- **Livewire 3 SPA Transitions**:
  - Always use `$this->redirectRoute(..., navigate: true)` to avoid full page reloads.

---

## 7. Development & Testing Runbooks

All tests run inside the Docker Sail container (`diss-laravel.test-1`):

```bash
# Run fleet inspection and compliance test suite
docker exec diss-laravel.test-1 php artisan test --filter=VehicleInspectionAndComplianceTest

# Check document reminders manually
docker exec diss-laravel.test-1 php artisan fleet:check-reminders

# Format code with Laravel Pint (target specific files)
docker exec diss-laravel.test-1 ./vendor/bin/pint config/fleet.php app/Livewire/Vehicles/Form.php resources/views/livewire/vehicles/form.blade.php
```
