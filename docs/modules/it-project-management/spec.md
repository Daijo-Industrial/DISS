# SPEC-1 — IT Project & KPI Management System

| Metadata | Specification Details |
| :--- | :--- |
| **Document ID** | SPEC-1 |
| **Module** | IT Project & KPI Management (Headless REST API) |
| **Status** | **IMPLEMENTED / ACTIVE** |
| **Contract Spec** | [`openapi.yaml`](openapi.yaml) (OpenAPI 3.0.3) |
| **Test Suite** | `tests/Unit/Domain/Project/`, `tests/Feature/Api/V1/ProjectApiTest.php` |
| **Migration Tool** | `php artisan diss:migrate-legacy-projects` |

---

## Background

The IT Department previously tracked project progress and KPI delivery metrics through disparate Excel workbooks and a primitive, unmaintained prototype in DISS (`ProjectMaster` / `ProjectTrackerController`).

That process suffered from critical limitations:
1. Project progress could not be monitored centrally with real-time fidelity.
2. Management lacked a real-time overview of departmental and individual PIC delivery performance.
3. KPI calculations depended on manually maintained, mutable data.
4. Project deadlines and overdue milestones were frequently missed without automated alerts.
5. Checkpoints and verifiable completion evidence were tracked inconsistently.
6. There was no automated workflow gate governing project closure.
7. Deadline extensions occurred ad-hoc without an audit trail, inadvertently masking scheduling slippage.
8. The legacy `ProjectMaster` prototype in DISS only tracked rudimentary string statuses without checkpoints, auto-progress, SLA adherence math, or API access.

### Solution: Headless, API-First DISS Module

The **IT Project & KPI Management module** is an API-first, headless service built directly within the existing DISS application.

Frontend consumer applications—specifically a native Android application (and internal portals)—consume this module exclusively via **RESTful JSON APIs** under the `/api/v1/` prefix, authenticated via **Laravel Sanctum Bearer tokens**.

All business logic, role-based authorization, state machine transitions, dual-track KPI calculations (Original SLA vs. Revised Schedule), multipart file uploads, and audit trails reside strictly on the backend.

---

## DISS Integration Blueprint

Rather than spinning up an isolated greenfield server, integrating directly into DISS provides immediate enterprise advantages:

* **Authentication & Sanctum**: Reuses DISS's existing `auth:sanctum` pipeline and `AuthController` (`/api/v1/auth/login`, `/logout`, `/me`).
* **Master Data**: Links directly to DISS's existing `Department` and `User` entities.
* **Roles & Permissions**: Employs DISS's configured `spatie/laravel-permission` package.
* **Audit Logging**: Leverages DISS's configured `spatie/laravel-activitylog` package.
* **Infrastructure**: Runs seamlessly inside DISS's existing Docker Compose / Laravel Sail environment with MySQL 8.4, Redis, and Mailpit.
* **Legacy Migration**: Replaces and supersedes the old `project_masters` prototype with an automated data migration command.

---

## Requirements

### R1 — User Authentication & Mobile-Friendly RBAC

The API relies on DISS's existing **Laravel Sanctum Bearer token** infrastructure:

* **Roles (Spatie Permission)**:
  * `IT Staff`
  * `IT Manager`
  * `IT Head`
  * `Admin`

| Capability | Staff | Manager | Head | Admin |
| :--- | :---: | :---: | :---: | :---: |
| Authenticate via Sanctum Bearer token | ✓ | ✓ | ✓ | ✓ |
| View projects & dashboard endpoints | ✓ | ✓ | ✓ | ✓ |
| Create project | ✓ | ✓ | ✓ | ✓ |
| Edit assigned project details | ✓ | ✓ | ✓ | ✓ |
| Checkpoint completion & evidence upload | ✓ | ✓ | ✓ | ✓ |
| Assign / Reassign PIC | - | ✓ | ✓ | ✓ |
| Approve / Reject deadline extensions | - | ✓ | ✓ | ✓ |
| Put project On-Hold / Resume project | - | ✓ | ✓ | ✓ |
| Cancel project | - | ✓ | ✓ | ✓ |
| Approve / Reject Review → Closure | - | ✓ | ✓ | ✓ |
| Execute workflow override (with logged reason) | - | - | ✓ | ✓ |
| Manage users & system configuration | - | - | - | ✓ |

---

### R2 — Project Entity & Dual State Dimensions

Each project tracks two distinct dimensions: **Lifecycle Stage** and **Operational Status**.

##### Project Fields:
* **Identification**: Project ID (UUID `CHAR(36)`), Project Code (e.g. `IT-2026-0001` via `Project::withTrashed()`), Project Name, Description
* **Stakeholders**:
  * Requester Name (`VARCHAR`)
  * `requester_department_id` (`BIGINT UNSIGNED FK` $\rightarrow$ `departments.id`)
  * `primary_pic_id` (`BIGINT UNSIGNED FK` $\rightarrow$ `users.id`, exactly one PIC)
  * `created_by` / `updated_by` (`BIGINT UNSIGNED FK` $\rightarrow$ `users.id`)
* **Priority**: `LOW`, `MEDIUM`, `HIGH`, `CRITICAL`
* **Lifecycle Stage (`stage`)**: `DRAFT`, `INITIATION`, `PLANNING`, `DEVELOPMENT`, `MONITORING`, `REVIEW`, `CLOSURE`
* **Operational Status (`status`)**: `ACTIVE`, `ON_HOLD`, `CANCELLED`, `COMPLETED`
* **Dates & Deadlines**:
  * Start Date
  * `original_due_date` (*Immutable baseline set upon exiting Planning; never overwritten*)
  * `due_date` (*Current approved deadline*)
  * Actual Finished Date
  * Paused At, Resumed At, Cancelled At, Closed At
* **Metrics**:
  * Progress Percentage (computed automatically from required checkpoints)
  * Deadline Extension Count (integer, default 0)
* **Metadata & Audit**:
  * Hold Reason (when On-Hold)
  * Cancellation Category & Notes (when Cancelled)
  * Created At, Updated At, Deleted At (Soft Deletes)

---

### R3 — Project Lifecycle & Status Transitions

##### 1. Lifecycle State Machine
```text
Draft
  ↓
Initiation
  ↓
Planning  ──► [Locks original_due_date]
  ↓
Development
  ↓
Monitoring
  ↓
Review ──(Rejected)──► Monitoring (or Development with logged reason)
  ↓
Closure   ──► Requires Manager / Head Approval (100% required checkpoints completed)
```

##### 2. Operational Status Machine
```text
           ┌───────────► ON_HOLD ───────────┐
           │              (Manager/Head)    │ (Resume)
           │                                ▼
[ACTIVE] ──┼────────────────────────────► [ACTIVE]
           │
           ├───────────► CANCELLED (Terminal)
           │              (Manager/Head, mandatory reason category)
           │
           └───────────► COMPLETED (Terminal, upon Closure approval)
```

Rules:
* While `status = ON_HOLD`, all checkpoint completions and stage transitions are rejected (`HTTP 422`).
* Closure strictly requires 100% of required checkpoints (`is_required = true`) to have `status = COMPLETED`.

---

### R4 — Checkpoints & Evidence Upload

Each project contains ordered checkpoints.

Fields:
* Checkpoint ID (UUID), Project ID (`foreignUuid`)
* Title, Description, Sequence Order
* Required Flag (`is_required`: boolean, default `true`)
* Status (`PENDING`, `COMPLETED`, `SKIPPED`)
* Due Date, Completed Date
* Checked By (`BIGINT UNSIGNED FK` $\rightarrow$ `users.id`)
* Evidence Attachments (multi-file support: images, PDFs, docs)
* Notes

Attachment Rules:
* Endpoints support standard `multipart/form-data` uploads directly from mobile camera or file picker.
* Stored securely via Laravel Storage disk with strict MIME inspection.
* Download endpoints provide authenticated streams or signed download URLs.

---

### R5 — Automatic Progress Calculation

Project progress is calculated server-side strictly from required checkpoints:

$$\text{Progress \%} = \begin{cases} 0\% & \text{if Total Required} = 0 \\ \left(\frac{\text{Completed Required Checkpoints}}{\text{Total Required Checkpoints}}\right) \times 100 & \text{otherwise} \end{cases}$$

Client applications cannot submit or override progress percentage; `ProjectCheckpointObserver` computes and synchronizes it automatically.

---

### R6 — Dual-Track KPI Engine & Health Calculation

Calculated deterministically from transactional records by `KpiCalculatorService`:

##### 1. Real-Time Project Health Status (`kpi_status`)
* **OVERDUE**: $\text{Today} > \text{due\_date}$
* **AT_RISK**: $\text{Today} \ge (\text{due\_date} - 7\text{ days})$ AND $\text{Progress} < 80\%$
* **ON_TRACK**: Active and within schedule
* **ON_HOLD**: Excluded from active Overdue calculation

##### 2. Dual SLA Adherence Metrics (At Completion)
* **Original SLA Adherence (Baseline Commitment)**:
  $$\text{Original SLA On-Time} = (\text{finished\_date} \le \text{original\_due\_date})$$
  $$\text{Original SLA Rate} = \frac{\sum (\text{Finished} \le \text{original\_due\_date})}{\text{Total Completed Projects}} \times 100$$

* **Revised Schedule Adherence (Operational Execution)**:
  $$\text{Revised Schedule On-Time} = (\text{finished\_date} \le \text{due\_date})$$
  $$\text{Revised Schedule Rate} = \frac{\sum (\text{Finished} \le \text{due\_date})}{\text{Total Completed Projects}} \times 100$$

##### 3. PIC On-Time Rate
$$\text{PIC On-Time Rate} = \frac{\text{PIC Completed Projects On-Time (Revised or Original)}}{\text{PIC Total Completed Projects (excluding Cancelled)}} \times 100$$

##### 4. Portfolio Attrition
$$\text{Cancellation Rate} = \frac{\text{Cancelled Projects}}{\text{Total Projects Started}} \times 100$$

---

### R7 — On-Hold and Cancellation Operations

##### On-Hold Projects:
* `POST /api/v1/projects/{id}/hold`: Requires `hold_reason`. Freezes project from overdue alert queues.
* `POST /api/v1/projects/{id}/resume`: Computes pause duration ($\Delta t$). Accepts `extend_due_date` flag to increment `due_date` by $\Delta t$ with system-logged reason `"On-Hold Pause Extension"`. `original_due_date` remains strictly unchanged.

##### Cancelled Projects:
* `POST /api/v1/projects/{id}/cancel`: Requires `cancellation_reason_category`. Excluded from PIC On-Time denominator; tracked in Cancellation statistics.

---

### R8 — Dashboard & Reporting Endpoints

Pre-aggregated KPI cards, chart datasets, and paginated lists:
* `GET /api/v1/projects/dashboard/summary` (Executive counts, Dual SLA rates, Overdue count, Extension average)
* `GET /api/v1/projects/dashboard/stage-distribution` (Counts per stage for mobile pie/bar charts)
* `GET /api/v1/projects/dashboard/pic-leaderboard` (PIC performance table with sorting)
* `GET /api/v1/projects/dashboard/upcoming-deadlines` (Projects due within 7, 14, 30 days)

All listing endpoints support pagination and filtering:
`?page=1&per_page=15&stage=DEVELOPMENT&status=ACTIVE&search=MES`

---

### R9 — Legacy Project Data Migration

Automated artisan migration command ports historical records:
```bash
# Dry run preview
php artisan diss:migrate-legacy-projects --dry-run

# Production execution
php artisan diss:migrate-legacy-projects
```
Parses legacy `project_masters` rows, creates modern `projects` and `project_stage_history` records, maps string PIC names to DISS user accounts where matches exist, and preserves historical dates. The old `ProjectTrackerController` is deprecated.

---

# RESTful API Contract

All endpoints follow DISS's API response format established in `App\Http\Controllers\Api\V1\Controller`:

### Success Response:
```json
{
  "success": true,
  "message": "Operation completed successfully",
  "data": { ... }
}
```

### Error Response (`HTTP 422 / 401 / 403 / 404 / 500`):
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "due_date": ["The due date must be a date after start date."]
  }
}
```

### Route Map (`/api/v1/`)

Full schema and mock responses are documented in [`openapi.yaml`](openapi.yaml).

| Method | Endpoint | Description | Roles |
| :--- | :--- | :--- | :--- |
| **Projects** | | | |
| `GET` | `/projects` | Paginated project list with filters | Staff+ |
| `POST` | `/projects` | Create a new project | Staff+ |
| `GET` | `/projects/{id}` | Complete composite project detail | Staff+ |
| `PUT` | `/projects/{id}` | Update project metadata | PIC, Manager+ |
| `DELETE` | `/projects/{id}` | Soft delete project | Manager+ |
| **Workflow** | | | |
| `POST` | `/projects/{id}/transition-stage` | Advance or revert stage | PIC, Manager+ |
| `POST` | `/projects/{id}/hold` | Put project On-Hold | Manager+ |
| `POST` | `/projects/{id}/resume` | Resume On-Hold project | Manager+ |
| `POST` | `/projects/{id}/cancel` | Cancel project with category | Manager+ |
| `POST` | `/projects/{id}/approve-closure`| Approve Review → Closure | Manager+ |
| `POST` | `/projects/{id}/reject-closure` | Reject Review → Closure | Manager+ |
| **Checkpoints & Evidence**| | | |
| `GET` | `/projects/{projectId}/checkpoints` | List project checkpoints | Staff+ |
| `POST` | `/projects/{projectId}/checkpoints` | Add new checkpoint | PIC, Manager+ |
| `PUT` | `/checkpoints/{id}` | Update checkpoint | PIC, Manager+ |
| `DELETE`| `/checkpoints/{id}` | Remove checkpoint | PIC, Manager+ |
| `POST` | `/checkpoints/{id}/complete` | Mark checkpoint completed | PIC, Manager+ |
| `POST` | `/checkpoints/{id}/attachments`| Upload evidence (multipart) | PIC, Manager+ |
| `GET` | `/checkpoints/attachments/{attachmentId}/download` | Stream / download file evidence | Staff+ |
| **Extensions**| | | |
| `GET` | `/projects/{projectId}/extensions` | Extension request history | Staff+ |
| `POST` | `/projects/{projectId}/extensions` | Request / log deadline extension | PIC, Manager+ |
| **KPI & Dashboard**| | | |
| `GET` | `/projects/dashboard/summary` | KPI cards (Dual SLA, Counts, Overdue) | Staff+ |
| `GET` | `/projects/dashboard/stage-distribution`| Stage breakdown counts | Staff+ |
| `GET` | `/projects/dashboard/pic-leaderboard` | PIC KPI rankings | Staff+ |
| `GET` | `/projects/dashboard/upcoming-deadlines`| Projects due soon | Staff+ |

---

# Architecture & Directory Structure in DISS

```text
DISS/
├── app/
│   ├── Console/Commands/
│   │   └── MigrateLegacyProjectsCommand.php
│   ├── Domain/
│   │   └── Project/
│   │       ├── Enums/
│   │       │   ├── CancellationCategory.php
│   │       │   ├── CheckpointStatus.php
│   │       │   ├── ExtensionReasonCategory.php
│   │       │   ├── KpiStatus.php
│   │       │   ├── ProjectPriority.php
│   │       │   ├── ProjectStage.php
│   │       │   └── ProjectStatus.php
│   │       ├── Observers/
│   │       │   ├── ProjectCheckpointObserver.php
│   │       │   └── ProjectObserver.php
│   │       └── Services/
│   │           ├── KpiCalculatorService.php
│   │           └── ProjectWorkflowService.php
│   ├── Infrastructure/
│   │   └── Persistence/
│   │       └── Eloquent/
│   │           └── Models/
│   │               ├── CheckpointAttachment.php
│   │               ├── Project.php
│   │               ├── ProjectApproval.php
│   │               ├── ProjectCheckpoint.php
│   │               ├── ProjectDeadlineExtension.php
│   │               └── ProjectStageHistory.php
│   └── Http/
│       ├── Controllers/Api/V1/Project/
│       │   ├── CheckpointController.php
│       │   ├── ProjectController.php
│       │   ├── ProjectDashboardController.php
│       │   ├── ProjectExtensionController.php
│       │   └── ProjectWorkflowController.php
│       ├── Requests/Api/V1/Project/
│       │   ├── ApproveClosureRequest.php
│       │   ├── CancelProjectRequest.php
│       │   ├── HoldProjectRequest.php
│       │   ├── RejectClosureRequest.php
│       │   ├── ResumeProjectRequest.php
│       │   ├── StoreCheckpointRequest.php
│       │   ├── StoreExtensionRequest.php
│       │   ├── StoreProjectRequest.php
│       │   ├── TransitionStageRequest.php
│       │   ├── UpdateCheckpointRequest.php
│       │   └── UpdateProjectRequest.php
│       └── Resources/V1/Project/
│           ├── CheckpointAttachmentResource.php
│           ├── CheckpointResource.php
│           ├── DashboardSummaryResource.php
│           ├── ProjectDeadlineExtensionResource.php
│           ├── ProjectDetailResource.php
│           ├── ProjectResource.php
│           └── ProjectStageHistoryResource.php
├── database/
│   └── migrations/
│       ├── 2026_10_09_120000_create_it_projects_table.php
│       ├── 2026_10_09_120001_create_it_project_checkpoints_table.php
│       ├── 2026_10_09_120002_create_it_checkpoint_attachments_table.php
│       ├── 2026_10_09_120003_create_it_project_deadline_extensions_table.php
│       ├── 2026_10_09_120004_create_it_project_approvals_table.php
│       └── 2026_10_09_120005_create_it_project_stage_histories_table.php
└── routes/
    └── api.php (Mounts /api/v1/projects and /api/v1/checkpoints under auth:sanctum)
```

---

# Implementation Status

All 6 implementation phases are **completed and verified in production**:

- **Phase 1 — Database Migrations & Models [Completed]**: UUID primary keys, soft deletes, foreign constraints to `users` and `departments`, and backed PHP 8.2 enums.
- **Phase 2 — Core Domain Services & Observers [Completed]**: `KpiCalculatorService` (Dual SLA rates, health statuses), `ProjectWorkflowService` (stage machine, hold/resume duration adjustments, closure gates), and `ProjectObserver` (code generation via `withTrashed()`, `original_due_date` immutability).
- **Phase 3 — RESTful API Endpoints & Resources [Completed]**: Full CRUD, workflow transitions, closure approvals, and Form Request validations under `app/Http/Controllers/Api/V1/Project/`.
- **Phase 4 — File Uploads & Dashboards [Completed]**: Multipart attachment uploads in `CheckpointController`, secure download streaming, and aggregated dashboard endpoints.
- **Phase 5 — Legacy Migration & Compatibility [Completed]**: `php artisan diss:migrate-legacy-projects` with dry-run support, importing legacy `project_masters` data.
- **Phase 6 — Testing & Verification [Completed]**: Comprehensive Pest test suite (`tests/Unit/Domain/Project/`, `tests/Feature/Api/V1/ProjectApiTest.php`), PHPStan level 5 analysis, and complete Bruno collection (`docs/modules/it-project-management/bruno/`).