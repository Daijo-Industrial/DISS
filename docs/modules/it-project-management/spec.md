# SPEC-1 — IT Project & KPI Management System (DISS API-First Module)

## Background

The IT Department currently tracks project information and KPI delivery metrics through disparate Excel workbooks and a primitive, unmaintained prototype in the **Daijo Industrial Support System (DISS)** (`ProjectMaster` / `ProjectTrackerController`).

The current process suffers from critical limitations:
1. Project progress cannot be monitored centrally with real-time fidelity.
2. Management lacks a real-time overview of departmental and individual PIC delivery performance.
3. KPI calculations depend on manually maintained, mutable data.
4. Project deadlines and overdue milestones are frequently missed without automated alerts.
5. Checkpoints and verifiable completion evidence are tracked inconsistently.
6. There is no automated workflow gate governing project closure.
7. Deadline extensions occur ad-hoc without an audit trail, inadvertently masking scheduling slippage.
8. The existing legacy `ProjectMaster` prototype in DISS only tracks rudimentary string statuses without checkpoints, auto-progress, SLA adherence math, or API access.

### Solution: Headless, API-First DISS Module

The proposed solution is an **API-first, Headless IT Project & KPI Management module built directly within the existing DISS application (`~/projects/DISS`)**.

Frontend consumer applications—specifically a **native Android application** (and future internal portals)—will consume this module exclusively via **RESTful JSON APIs** under the existing `/api/v1/` prefix, authenticated via **Laravel Sanctum Bearer tokens**.

All business logic, role-based authorization, state machine transitions, dual-track KPI calculations (Original SLA vs. Revised Schedule), multipart file uploads, and audit trails reside strictly on the backend.

---

## DISS Integration Blueprint

Rather than spinning up an isolated greenfield server, integrating directly into DISS provides immediate enterprise advantages:

* **Authentication & Sanctum**: Reuses DISS's existing `auth:sanctum` pipeline and `AuthController` (`/api/v1/auth/login`, `/logout`, `/me`).
* **Master Data**: Links directly to DISS's existing `Department` and `User` entities.
* **Roles & Permissions**: Employs DISS's configured `spatie/laravel-permission` package.
* **Audit Logging**: Leverages DISS's configured `spatie/laravel-activitylog` package.
* **Infrastructure**: Runs seamlessly inside DISS's existing Docker Compose / Laravel Sail environment with MySQL 8.0, Redis, and Mailpit.
* **Legacy Migration**: Replaces and supersedes the old `project_masters` prototype with an automated data migration command.

---

## Requirements

### Must Have

#### R1 — User Authentication & Mobile-Friendly RBAC

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

#### R2 — Project Entity & Dual State Dimensions

Each project tracks two distinct dimensions: **Lifecycle Stage** and **Operational Status**.

##### Project Fields:
* **Identification**: Project ID (UUID `CHAR(36)`), Project Code (e.g. `IT-2026-0001`), Project Name, Description
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

#### R3 — Project Lifecycle & Status Transitions

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
Closure   ──► Requires Manager / Head Approval
```

##### 2. Operational Status Machine
```text
           ┌───────────► ON_HOLD ───────────┐
           │              (Manager/Head)     │ (Resume)
           │                                 ▼
[ACTIVE] ──┼────────────────────────────► [ACTIVE]
           │
           ├───────────► CANCELLED (Terminal)
           │              (Manager/Head, mandatory reason category)
           │
           └───────────► COMPLETED (Terminal, upon Closure approval)
```

Rules:
* While `status = ON_HOLD`, all checkpoint completions and stage transitions are rejected (`HTTP 422`).
* Overrides of normal stage transitions require `IT_HEAD` or `ADMIN` role with mandatory reason logged to the audit trail.

---

#### R4 — Checkpoints & Mobile Evidence Upload

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

Mobile Attachment Requirements:
* Endpoints support standard `multipart/form-data` uploads directly from Android camera or file picker.
* Stored securely via Laravel Storage disk with strict MIME inspection.
* Download endpoints provide authenticated, signed temporary URLs for secure viewing on mobile devices.

---

#### R5 — Automatic Progress Calculation

Project progress is calculated server-side strictly from required checkpoints:

$$\text{Progress \%} = \begin{cases} 0\% & \text{if Total Required} = 0 \\ \left(\frac{\text{Completed Required Checkpoints}}{\text{Total Required Checkpoints}}\right) \times 100 & \text{otherwise} \end{cases}$$

Client applications cannot submit or override progress percentage; the API computes and serializes it in the project payload.

---

#### R6 — Dual-Track KPI Engine & Health Calculation

The API calculates project KPIs deterministically from transactional records.

##### 1. Real-Time Project Health Status (`kpi_status`)
* **OVERDUE**: $\text{Today} > \text{due\_date}$
* **AT_RISK**: $\text{Today} \ge (\text{due\_date} - 7\text{ days})$ AND $\text{Progress} < 80\%$
* **ON_TRACK**: Active and within schedule
* **ON_HOLD**: Excluded from active Overdue calculation and alerts

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

#### R7 — Handling of On-Hold and Cancelled Projects via API

##### On-Hold Projects:
* `POST /api/v1/projects/{id}/hold`: Requires `hold_reason`. Freezes project from overdue alert queues.
* `POST /api/v1/projects/{id}/resume`: Computes pause duration ($\Delta t$). Accepts `extend_due_date` flag to increment `due_date` by $\Delta t$ with system-logged reason `"On-Hold Pause Extension"`. `original_due_date` remains strictly unchanged.

##### Cancelled Projects:
* `POST /api/v1/projects/{id}/cancel`: Requires `cancellation_reason_category`. Excluded from PIC On-Time denominator; tracked in Cancellation statistics.

---

#### R8 — Dashboard & Reporting Endpoints

Mobile-optimized endpoints providing pre-aggregated KPI cards, chart datasets, and paginated lists:

* `GET /api/v1/dashboard/summary` (Executive counts, Dual SLA rates, Overdue count, Extension average)
* `GET /api/v1/dashboard/stage-distribution` (Counts per stage for mobile pie/bar charts)
* `GET /api/v1/dashboard/pic-leaderboard` (PIC performance table with sorting)
* `GET /api/v1/dashboard/upcoming-deadlines` (Projects due within 7, 14, 30 days)

All listing endpoints support mobile pagination:
`?page=1&per_page=15&stage=DEVELOPMENT&status=ACTIVE&search=MES`

---

#### R9 — Project Detail & Closure Approvals

Detail endpoint (`GET /api/v1/projects/{id}`) returns a composite JSON object:
* Core metadata + PIC profile
* Stage history & transition permissions
* Checkpoint list with attachment metadata
* Extension history
* Audit activity trail

**Closure Approval**:
* `POST /api/v1/projects/{id}/approve-closure`: Validates 100% required checkpoints completed, finished date set, and evidence attached.
* `POST /api/v1/projects/{id}/reject-closure`: Reverts project to `MONITORING` with reviewer feedback.

---

#### R10 — Legacy Project Data Migration

The DISS database currently holds records in `project_masters` and `project_historys`.
* The system shall provide an artisan migration command:
  ```bash
  php artisan diss:migrate-legacy-projects
  ```
* This command parses legacy rows, creates modern `projects` and `project_stage_history` records, maps string PIC names to DISS user accounts where matches exist, and preserves historical dates.
* The legacy `ProjectTrackerController` will be formally marked deprecated.

---

# RESTful API Contract

All endpoints follow DISS's existing API response format established in `App\Http\Controllers\Api\V1\Controller`:

### Success Response:
```json
{
  "success": true,
  "message": "Operation completed successfully",
  "data": { ... }
}
```

### Error Response (`HTTP 422 / 403 / 404 / 500`):
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

| Method | Endpoint | Description | Roles |
| :--- | :--- | :--- | :--- |
| **Projects** | | | |
| `GET` | `/projects` | Paginated project list with filters | Staff+ |
| `POST` | `/projects` | Create a new project | Staff+ |
| `GET` | `/projects/{id}` | Complete project detail | Staff+ |
| `PUT` | `/projects/{id}` | Update project metadata | PIC, Manager+ |
| `DELETE` | `/projects/{id}` | Soft delete project | Manager+ |
| **Workflow** | | | |
| `POST` | `/projects/{id}/transition-stage` | Advance or revert stage | PIC, Manager+ |
| `POST` | `/projects/{id}/hold` | Put project On-Hold | Manager+ |
| `POST` | `/projects/{id}/resume` | Resume On-Hold project | Manager+ |
| `POST` | `/projects/{id}/cancel` | Cancel project with reason | Manager+ |
| `POST` | `/projects/{id}/approve-closure`| Approve Review → Closure | Manager+ |
| `POST` | `/projects/{id}/reject-closure` | Reject Review → Closure | Manager+ |
| **Extensions**| | | |
| `POST` | `/projects/{id}/extensions` | Request / approve deadline extension | PIC (req), Manager (appr) |
| `GET` | `/projects/{id}/extensions` | Extension history | Staff+ |
| **Checkpoints**| | | |
| `GET` | `/projects/{id}/checkpoints` | List project checkpoints | Staff+ |
| `POST` | `/projects/{id}/checkpoints` | Add new checkpoint | PIC, Manager+ |
| `PUT` | `/checkpoints/{id}` | Update checkpoint | PIC, Manager+ |
| `POST` | `/checkpoints/{id}/complete` | Mark checkpoint completed | PIC, Manager+ |
| `POST` | `/checkpoints/{id}/attachments`| Upload evidence (multipart) | PIC, Manager+ |
| `GET` | `/attachments/{id}/download` | Get signed temporary download URL | Staff+ |
| **KPI & Dashboard**| | | |
| `GET` | `/dashboard/summary` | KPI cards (Dual SLA, Counts, Overdue) | Staff+ |
| `GET` | `/dashboard/stage-distribution`| Stage breakdown counts | Staff+ |
| `GET` | `/dashboard/pic-leaderboard` | PIC KPI rankings | Staff+ |
| `GET` | `/dashboard/upcoming-deadlines`| Projects due soon | Staff+ |

---

# Architecture & Directory Structure in DISS

Following DISS's established Clean Architecture / Domain conventions:

```text
~/projects/DISS/
├── app/
│   ├── Domain/
│   │   └── Project/
│   │       ├── Enums/
│   │       │   ├── ProjectStage.php
│   │       │   ├── ProjectStatus.php
│   │       │   ├── ProjectPriority.php
│   │       │   ├── CheckpointStatus.php
│   │       │   └── ExtensionReasonCategory.php
│   │       └── Services/
│   │           ├── KpiCalculatorService.php
│   │           ├── ProjectWorkflowService.php
│   │           └── LegacyProjectMigrator.php
│   ├── Infrastructure/
│   │   └── Persistence/
│   │       └── Eloquent/
│   │           └── Models/
│   │               ├── Project.php
│   │               ├── ProjectCheckpoint.php
│   │               ├── CheckpointAttachment.php
│   │               ├── ProjectDeadlineExtension.php
│   │               ├── ProjectApproval.php
│   │               └── ProjectStageHistory.php
│   └── Http/
│       ├── Controllers/Api/V1/
│       │   ├── ProjectController.php
│       │   ├── ProjectWorkflowController.php
│       │   ├── CheckpointController.php
│       │   ├── AttachmentController.php
│       │   ├── ProjectExtensionController.php
│       │   └── ProjectDashboardController.php
│       ├── Requests/Api/V1/
│       │   ├── StoreProjectRequest.php
│       │   ├── UpdateProjectRequest.php
│       │   ├── TransitionStageRequest.php
│       │   ├── HoldProjectRequest.php
│       │   ├── CancelProjectRequest.php
│       │   └── StoreExtensionRequest.php
│       └── Resources/V1/
│           ├── ProjectResource.php
│           ├── ProjectDetailResource.php
│           ├── CheckpointResource.php
│           └── DashboardSummaryResource.php
├── database/
│   └── migrations/
│       ├── YYYY_MM_DD_HHMMSS_create_it_projects_table.php
│       ├── YYYY_MM_DD_HHMMSS_create_it_project_checkpoints_table.php
│       ├── YYYY_MM_DD_HHMMSS_create_it_checkpoint_attachments_table.php
│       ├── YYYY_MM_DD_HHMMSS_create_it_project_deadline_extensions_table.php
│       ├── YYYY_MM_DD_HHMMSS_create_it_project_approvals_table.php
│       └── YYYY_MM_DD_HHMMSS_create_it_project_stage_histories_table.php
└── routes/
    └── api.php (Mounts /api/v1/projects/* under auth:sanctum)
```

---

# Implementation Phases in DISS

### Phase 1 — Database Migrations & Models
1. Create migration files for `projects`, `project_checkpoints`, `checkpoint_attachments`, `project_deadline_extensions`, `project_approvals`, and `project_stage_history`.
2. Connect `foreignId` relations to DISS's existing `users` and `departments`.
3. Create Eloquent models in `App\Infrastructure\Persistence\Eloquent\Models\` with UUID traits and `softDeletes`.
4. Create PHP 8.2+ backed Enums in `App\Domain\Project\Enums\`.

### Phase 2 — Core Domain Services
1. Implement `KpiCalculatorService` with Dual SLA calculations, PIC rate formulas, and real-time health checks (`ON_TRACK`, `AT_RISK`, `OVERDUE`).
2. Implement `ProjectWorkflowService` with stage machine guards, hold/resume duration math, and Review $\rightarrow$ Closure validation gates.
3. Implement `ProjectObserver` to guarantee `original_due_date` immutability.

### Phase 3 — RESTful API Endpoints & Resources
1. Implement Form Requests with validation rules.
2. Implement API Resources for clean, decoupled JSON serialization.
3. Implement `ProjectController`, `ProjectWorkflowController`, `CheckpointController`, and `ProjectExtensionController` extending DISS's base `Controller`.
4. Mount routes in `routes/api.php` under `auth:sanctum`.

### Phase 4 — File Uploads & Dashboard
1. Implement multipart evidence upload in `AttachmentController` with MIME validation and secure signed download URLs.
2. Implement `ProjectDashboardController` for mobile summary cards, stage breakdowns, and PIC leaderboards.

### Phase 5 — Legacy Data Migration & Deprecation
1. Create `diss:migrate-legacy-projects` artisan command to port existing rows from `project_masters` to the new `projects` table.
2. Mark `ProjectTrackerController` as deprecated.

### Phase 6 — Automated Testing & Quality Checks
1. Write Pest PHP test cases for KPI math, stage transitions, and API endpoints.
2. Run `composer pint` (formatting) and `composer stan` (Larastan static analysis) to ensure standard compliance with DISS CI/CD.