# Bootstrap to 100% Tailwind CSS Migration: Future Roadmap

> **Current Status**: **Phase 1 (Foundation & Quick Wins)** is **Completed**.
> **Phases 2 through 5** are designated as the **Future Development Roadmap** to be picked up in subsequent iterations.

---

## 1. Executive Summary

| Milestone | Status | Scope |
| :--- | :--- | :--- |
| **Phase 1: Foundation & Quick Wins** | **✅ COMPLETED** | Upgraded `<x-modal>`, modernized all `auth/passwords/*`, `auth/verify`, `admin/updateemail`, `admin/specifications`, and `setting/formatrequest*` views. |
| **Phase 2: Legacy Modals Overhaul** | **⏳ FUTURE ROADMAP** | Convert 37 remaining modals in `resources/views/partials/` from Bootstrap `.modal` to `<x-modal>` Alpine component. |
| **Phase 3: Module Screens Modernization** | **⏳ FUTURE ROADMAP** | Migrate `inspection-form` wizards, Purchasing supplier evaluation (criteria 1–6), QA/QC reports, and Project Tracker. |
| **Phase 4: DataTables Decoupling** | **⏳ FUTURE ROADMAP** | Replace `datatables.net-bs5` theme with clean Tailwind table styles in `resources/css/datatables.css`. |
| **Phase 5: Full Bootstrap Decommissioning** | **⏳ FUTURE ROADMAP** | Drop `bootstrap` and `sass` packages, remove `@import 'bootstrap/scss/bootstrap'` from `app.scss`, and remove `app.scss` from Vite. |

---

## 2. Completed in Phase 1

1. **Reusable Modal Foundation (`<x-modal>`)**:
   - Location: `resources/views/components/modal.blade.php`
   - Supports both Livewire (`wire:model`) and standard Blade/Alpine.js (`name="modal-id"`, triggered with `@click="$dispatch('open-modal', 'modal-id')"`).
   - Features backdrop blur (`backdrop-blur-sm`), snappy 200ms entry, Escape key dismiss, and focus trapping.
2. **Auth Views Modernized (100% Tailwind)**:
   - `resources/views/auth/passwords/email.blade.php`
   - `resources/views/auth/passwords/reset.blade.php`
   - `resources/views/auth/passwords/confirm.blade.php`
   - `resources/views/auth/verify.blade.php`
3. **Admin Settings Modernized**:
   - `resources/views/admin/updateemail.blade.php`
   - `resources/views/admin/specifications/index.blade.php`
   - `resources/views/partials/add-specification-modal.blade.php`
4. **Setting Evaluation Format Requests Modernized (100% Tailwind)**:
   - `resources/views/setting/formatrequestyayasan.blade.php`
   - `resources/views/setting/formatrequestallinperpanjangan.blade.php`
   - `resources/views/setting/formatrequestmagang.blade.php`
   - `resources/views/setting/formatrequestallin.blade.php`

---

## 3. Future Roadmap Milestones

### Phase 2: Legacy Modals Overhaul (`resources/views/partials/`)
Convert remaining 37 Bootstrap modal files to `<x-modal name="..." maxWidth="...">`:
- **Defect & Department**:
  - `add-defect-modal.blade.php` & `add-defect-category-modal.blade.php`
  - `add-department-modal.blade.php` & `edit-department-modal.blade.php`
  - `add-new-line-modal.blade.php` & `edit-line-modal.blade.php`
  - `edit-user-modal.blade.php`, `add-user-modal.blade.php`
- **Purchase Request & Workflow**:
  - `approval-modal.blade.php`, `rejection-modal.blade.php`, `cancel-modal.blade.php`
  - `delete-pr-modal.blade.php`, `delete-forever-pr-modal.blade.php`
  - `pr-sign-submit-modal.blade.php`
- **Evaluation & Complex Forms**:
  - `edit-evaluation-modal.blade.php`
  - `edit-form-overtime-modal.blade.php`
  - `upload-evaluation-excel-modal.blade.php`

### Phase 3: Module Screens Modernization
Convert views with `.row`, `.col-md-*`, `.card`, and `.form-control` to Tailwind:
1. **Livewire Inspection Form Wizards** (`livewire/inspection-form/*`):
   - `step-header.blade.php`, `step-detail.blade.php`, `step-dimensions.blade.php`, `step-problem.blade.php`, `dashboard.blade.php`
2. **Purchasing Supplier Evaluation** (`purchasing/evaluationsupplier/*`):
   - `kriteria1.blade.php` through `kriteria6.blade.php`
   - `purchasing/purchasing_landing.blade.php`
3. **QA/QC Reports** (`qaqc/reports/*`):
   - `adjustindex.blade.php`, `adjustformview.blade.php`, `createdetail.blade.php`, `edit-detail.blade.php`, `monthlyreport.blade.php`
4. **Project Tracker & Monthly Budget** (`projecttracker/*`, `monthly-budget/*`):
   - `projecttracker/create.blade.php`, `detail.blade.php`, `index.blade.php`
   - `monthly-budget/summary/*` & `monthly-budget/reports/*`

### Phase 4: DataTables Decoupling
1. In `resources/sass/app.scss`, isolate and replace:
   ```scss
   @import 'datatables.net-bs5/css/dataTables.bootstrap5.min.css';
   @import 'datatables.net-buttons-bs5/css/buttons.bootstrap5.min.css';
   @import 'datatables.net-select-bs5/css/select.bootstrap5.css';
   ```
2. Create `resources/css/datatables.css` styling the search input, length selector, sorting headers, and pagination using clean Tailwind utilities.
3. Verify Yajra DataTables in:
   - `hrd/importantDocs`
   - `administration/evaluation-data-weekly`
   - `qaqc/reports/index`
   - `evaluation/index`

### Phase 5: Complete Bootstrap & Sass Removal
1. Remove `@import 'bootstrap/scss/bootstrap'` from `app.scss`.
2. Update `new/layouts/app.blade.php` Vite bundle:
   ```blade
   {{-- From --}}
   @vite(['resources/css/app.css', 'resources/sass/app.scss', 'resources/js/app.js'])

   {{-- To --}}
   @vite(['resources/css/app.css', 'resources/js/app.js'])
   ```
3. Run `npm uninstall bootstrap` and `npm uninstall sass` (if SCSS utilities are consolidated into `resources/css/app.css`).
4. Run `npm run build` to confirm clean production asset compilation.
