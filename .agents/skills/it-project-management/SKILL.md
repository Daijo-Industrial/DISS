---
name: it-project-management
description: >-
  Guide and runbook for the IT Project & KPI Management headless REST API module in DISS.
  Use when developing, testing, querying, or maintaining IT projects, checkpoints, deadline
  extensions, Dual SLA calculations, and mobile API endpoints.
---

# IT Project & KPI Management Module

The IT Project & KPI Management module is an API-first, headless service in DISS providing real-time tracking, checkpoint accountability, dual-track SLA calculation, and mobile client access.

## Architecture & Directory Layout

- **Enums**: `app/Domain/Project/Enums/`
  (`ProjectStage`, `ProjectStatus`, `ProjectPriority`, `CheckpointStatus`, `ExtensionReasonCategory`, `CancellationCategory`, `KpiStatus`)
- **Domain Services**: `app/Domain/Project/Services/`
  - `KpiCalculatorService`: Dual SLA metrics, real-time health, PIC performance.
  - `ProjectWorkflowService`: Sequential stage machine, hold/resume duration adjustments, cancellation, Review -> Closure approval gate.
- **Observers**: `app/Domain/Project/Observers/`
  - `ProjectObserver`: Auto-generates `IT-YYYY-XXXX` code using `Project::withTrashed()` to prevent collisions against soft-deleted records; guards `original_due_date` immutability once past `PLANNING`.
  - `ProjectCheckpointObserver`: Automatically recalculates project `progress_percent` on checkpoint mutations.
- **Eloquent Models**: `app/Infrastructure/Persistence/Eloquent/Models/`
  (`Project`, `ProjectCheckpoint`, `CheckpointAttachment`, `ProjectDeadlineExtension`, `ProjectApproval`, `ProjectStageHistory`)
  - Compatibility aliases in `app/Models/Project.php` and `app/Models/ProjectCheckpoint.php`.
- **API Controllers**: `app/Http/Controllers/Api/V1/Project/`
  (`ProjectController`, `ProjectWorkflowController`, `CheckpointController`, `ProjectExtensionController`, `ProjectDashboardController`)
- **API Resources**: `app/Http/Resources/V1/Project/`
- **Form Requests**: `app/Http/Requests/Api/V1/Project/`

---

## Dual SLA Formulas

1. **Original SLA Adherence (Baseline Commitment)**:
   $$\text{Original SLA Rate} = \frac{\sum (\text{Finished} \le \text{original\_due\_date})}{\text{Total Completed Projects}} \times 100$$
2. **Revised Schedule Adherence (Operational Execution)**:
   $$\text{Revised Schedule Rate} = \frac{\sum (\text{Finished} \le \text{due\_date})}{\text{Total Completed Projects}} \times 100$$

`original_due_date` is locked once exiting `PLANNING`. All approved deadline extensions increment `deadline_extension_count` and create a `ProjectDeadlineExtension` record.

---

## State Machine & Governance Rules

- **Normal Stage Progression**:
  `DRAFT` $\rightarrow$ `INITIATION` $\rightarrow$ `PLANNING` $\rightarrow$ `DEVELOPMENT` $\rightarrow$ `MONITORING` $\rightarrow$ `REVIEW` $\rightarrow$ `CLOSURE`
- **Review $\rightarrow$ Closure Gate**:
  Closure strictly requires 100% of required checkpoints (`is_required = true`) to have `status = COMPLETED`. Incomplete checkpoints trigger a `422 Unprocessable Entity` domain exception.
- **Hold & Resume**:
  While `status = ON_HOLD`, all stage transitions and checkpoint completions are locked. Resuming calculates paused duration $\Delta t$ and optionally applies an automatic deadline extension without changing `original_due_date`.
- **Cancellation**:
  Requires a mandatory category (`CancellationCategory`). Cancelled projects are excluded from PIC On-Time delivery rate denominators, but tracked in portfolio cancellation rate statistics.

---

## REST API Endpoints (`/api/v1/`)

Protected by `auth:sanctum`:

### Projects
- `GET /api/v1/projects` (Paginated, filters: `stage`, `status`, `department_id`, `pic_id`, `priority`, `search`)
- `POST /api/v1/projects`
- `GET /api/v1/projects/{id}` (Composite detail with checkpoints, extensions, stage histories)
- `PUT /api/v1/projects/{id}`
- `DELETE /api/v1/projects/{id}` (Soft delete)

### Workflow
- `POST /api/v1/projects/{id}/transition-stage`
- `POST /api/v1/projects/{id}/hold`
- `POST /api/v1/projects/{id}/resume`
- `POST /api/v1/projects/{id}/cancel`
- `POST /api/v1/projects/{id}/approve-closure`
- `POST /api/v1/projects/{id}/reject-closure`

### Checkpoints & Evidence
- `GET /api/v1/projects/{projectId}/checkpoints`
- `POST /api/v1/projects/{projectId}/checkpoints`
- `PUT /api/v1/checkpoints/{id}`
- `POST /api/v1/checkpoints/{id}/complete`
- `POST /api/v1/checkpoints/{id}/attachments` (Multipart file upload)
- `GET /api/v1/checkpoints/attachments/{attachmentId}/download` (Secure stream)

### Extensions & Dashboard
- `GET /api/v1/projects/{projectId}/extensions`
- `POST /api/v1/projects/{projectId}/extensions`
- `GET /api/v1/projects/dashboard/summary`
- `GET /api/v1/projects/dashboard/stage-distribution`
- `GET /api/v1/projects/dashboard/pic-leaderboard`
- `GET /api/v1/projects/dashboard/upcoming-deadlines`

---

## Legacy Prototype Migration

To import historical records from `project_masters`:
```bash
# Preview
php artisan diss:migrate-legacy-projects --dry-run

# Execute
php artisan diss:migrate-legacy-projects
```

---

## Testing & Quality Assurance

Run the module test suite:
```bash
./vendor/bin/pest tests/Unit/Domain/Project/ tests/Feature/Api/V1/ProjectApiTest.php
```

Format modified files:
```bash
./vendor/bin/pint app/Domain/Project/ app/Http/Controllers/Api/V1/Project/ app/Infrastructure/Persistence/Eloquent/Models/Project* tests/Unit/Domain/Project/ tests/Feature/Api/V1/ProjectApiTest.php
```

Run static analysis:
```bash
./vendor/bin/phpstan analyse app/Domain/Project/ app/Http/Controllers/Api/V1/Project/ app/Http/Requests/Api/V1/Project/ app/Http/Resources/V1/Project/ app/Infrastructure/Persistence/Eloquent/Models/Project* --memory-limit=2G
```

---

## Bruno API Testing & Git Cleanliness Conventions

A complete pre-configured Bruno collection is located at `docs/modules/it-project-management/bruno/`. Follow these architectural conventions for API testing in DISS:

### 1. Root Header Injection (Bypassing Fragile Folder Inheritance)
In Bruno, subfolder `auth: inherit` can fail to cascade collection-level Bearer tokens. Configure authentication directly at the collection root in `collection.bru`:
```bru
headers {
  Accept: application/json
  Authorization: Bearer {{token}}
}

auth {
  mode: none
}

script:pre-request {
  const token = bru.getEnvVar("token") || bru.getVar("token");
  const url = req.getUrl ? req.getUrl() : "";
  if (token && !url.includes("/auth/login")) {
    req.setHeader("Authorization", `Bearer ${token}`);
  }
}
```

### 2. Git Cleanliness via `vars:secret` in `Local.bru`
Never hardcode personal developer credentials or commit tokens to Git:
```bru
vars {
  baseUrl: http://localhost
  projectId: 
  checkpointId: 
  attachmentId: 
}
vars:secret [
  email,
  password,
  token,
  picId,
  departmentId
]
```
Variables declared in `vars:secret` are stored in Bruno's local application memory and encrypted machine storage (`~/.config/bruno`), keeping `Local.bru` on disk 100% clean with zero Git diffs.

### 3. Dual Variable Binding (`bru.setVar` + `bru.setEnvVar`)
To ensure dynamic UUIDs created in one request tab (`02. Create Project`) are accessible in other request tabs (`03. Get Project Detail`) when an environment is selected in Bruno GUI, bind variables to both scopes:
```javascript
script:post-response {
  if (res.body && res.body.data && res.body.data.id) {
    bru.setVar("projectId", res.body.data.id);
    bru.setEnvVar("projectId", res.body.data.id);
  }
}
```
List endpoints (`List Projects`, `List Checkpoints`) also include fallback auto-capture on `res.body.data[0].id` to allow immediate testing of existing records without re-running creation requests.
