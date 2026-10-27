---
name: attendance-evaluation
description: Domain knowledge, JPayroll attendance mapping (CT vs Izin), Cuti Izin Excel imports, evaluation score recalculation, and penalty rules in DISS.
---

# Employee Attendance & Performance Evaluation

## 1. Domain Entities & Invariants

- **`AttendanceRecord`** (`App\Models\AttendanceRecord`):
  - Daily attendance record per NIK (`shift_date`, `alpha`, `telat`, `cuti`, `izin`, `sakit`).
  - `cuti`: Annual leave (*Cuti Tahunan*), sourced from JPayroll API `data['CT']`. Carries **0 penalty**.
  - `izin`: Leave of absence / permit. Carries **-2 points penalty** per day. **Not provided by JPayroll API**; defaults to `0`. Sourced exclusively from monthly Excel summary reports.
  - `alpha` (-5 points), `telat` (-0.5 points), `sakit` (-1 point).

- **`EvaluationData`** (`App\Models\EvaluationData`):
  - Monthly evaluation snapshot per employee (`NIK`, `Month`, `Alpha`, `Telat`, `Izin`, `Sakit`, `total`).
  - Attendance figures in `evaluation_datas` are snapshots of `SUM(field)` from `attendance_records` for that period.

## 2. JPayroll Attendance Synchronization

- **Sync Service**: `App\Services\Payroll\AttendanceSync` and `AttendanceDto`.
- **API Field Translation**:
  ```php
  cuti: (int) ($r['CT'] ?? 0),
  izin: 0, // JPayroll API does not supply izin; never guess keys like $r['IZ']
  ```

## 3. Cuti Izin Excel Import Workflow

- **Excel Report**: Monthly Attendance Summary Report from JPayroll (`Cuti Izin [Month Year].xls` or `.xlsx`).
  - Row 5: Period string (e.g., `PERIOD : 2026/09 - 2026/09`).
  - Row 9+: Data rows (Column B = Employee NIK, Column E = Total `Cuti Izin` count).
- **Service**: `App\Domain\Attendance\Services\ImportCutiIzinService`.
  - Parses period and NIK / count pairs.
  - Distributes the monthly count across daily `attendance_records` for that month so `SUM(izin)` matches the Excel aggregate.
  - Automatically recalculates affected `evaluation_datas` and updates total evaluation scores.
- **CLI & Web Endpoints**:
  - Artisan: `php artisan attendance:import-cuti-izin "path/to/file.xls"`
  - Web: `POST /evaluation/import-cuti-izin` (super-admin only).

## 4. Evaluation Score Recalculation

- When attendance records are modified or imported, evaluation snapshots and totals must be recalculated.
- **Service**: `EvaluationScoreCalculatorService` (`calculateTotal` / `calculateTotalOld`).
- **Endpoint**: `POST /evaluation/recalculate` (super-admin only).
  - Re-aggregates live attendance metrics from `attendance_records`.
  - Recalculates total scores based on grading rubrics and attendance penalties.

## 5. Verification Runbooks

```bash
# Test attendance sync, cuti izin import, and evaluation recalculation
./vendor/bin/sail test tests/Feature/ImportCutiIzinTest.php tests/Feature/EvaluationRecalculateTest.php tests/Feature/SyncEmployeeDataTest.php
```
