---
name: it-project-management
description: Guide and runbook for the IT Project & KPI Management headless REST API module in DISS.
---

# IT Project & KPI Management

API-first headless service providing project tracking, checkpoint governance, dual-track SLA metrics, and Bruno/OpenAPI specifications.

## Architecture & Directory Layout

- **Enums**: `app/Domain/Project/Enums/`
  (`ProjectStage`, `ProjectStatus`, `ProjectPriority`, `CheckpointStatus`, `ExtensionReasonCategory`, `CancellationCategory`, `KpiStatus`)
- **Domain Services**: `app/Domain/Project/Services/`
  - `KpiCalculatorService`: Dual SLA metrics, health indicators, PIC performance.
  - `ProjectWorkflowService`: Sequential stage machine, hold/resume duration adjustments, cancellation, closure gate.
- **Observers**: `app/Domain/Project/Observers/`
  - `ProjectObserver`: Generates `IT-YYYY-XXXX` via `Project::withTrashed()` to prevent duplicate key collisions; locks `original_due_date` past `PLANNING`.
  - `ProjectCheckpointObserver`: Recalculates `progress_percent` on checkpoint mutations.
- **Models**: `app/Infrastructure/Persistence/Eloquent/Models/`
  (`Project`, `ProjectCheckpoint`, `CheckpointAttachment`, `ProjectDeadlineExtension`, `ProjectApproval`, `ProjectStageHistory`)
  - Legacy aliases in `app/Models/`.
- **API Controllers**: `app/Http/Controllers/Api/V1/Project/`
- **Resources & Requests**: `app/Http/Resources/V1/Project/`, `app/Http/Requests/Api/V1/Project/`

## Dual SLA Formulas

1. **Original SLA Adherence (Baseline Commitment)**:
   $$\text{Original SLA Rate} = \frac{\sum (\text{Finished} \le \text{original\_due\_date})}{\text{Total Completed Projects}} \times 100$$
2. **Revised Schedule Adherence (Operational Execution)**:
   $$\text{Revised Schedule Rate} = \frac{\sum (\text{Finished} \le \text{due\_date})}{\text{Total Completed Projects}} \times 100$$

`original_due_date` is immutable once exiting `PLANNING`. Approved deadline extensions increment `deadline_extension_count` and create a `ProjectDeadlineExtension`.

## State Machine & Governance Rules

- **Stage Progression**:
  `DRAFT` $\rightarrow$ `INITIATION` $\rightarrow$ `PLANNING` $\rightarrow$ `DEVELOPMENT` $\rightarrow$ `MONITORING` $\rightarrow$ `REVIEW` $\rightarrow$ `CLOSURE`
- **Review $\rightarrow$ Closure Gate**:
  Closure strictly requires 100% of required checkpoints (`is_required = true`) to have `status = COMPLETED`. Incomplete checkpoints throw a `422 Unprocessable Entity` exception.
- **Hold & Resume**:
  Stage transitions and checkpoints lock when `status = ON_HOLD`. Resuming calculates paused duration $\Delta t$ and optionally applies a deadline extension without touching `original_due_date`.
- **Cancellation**:
  Requires `CancellationCategory`. Cancelled projects are excluded from PIC On-Time delivery rate denominators.

## REST API Endpoints (`/api/v1/`, `auth:sanctum`)

Full OpenAPI 3.0.3 spec: `docs/modules/it-project-management/openapi.yaml`.

- **Projects**: `GET|POST /api/v1/projects`, `GET|PUT|DELETE /api/v1/projects/{id}`
- **Workflow**: `POST /api/v1/projects/{id}/(transition-stage|hold|resume|cancel|approve-closure|reject-closure)`
- **Checkpoints**: `GET|POST /api/v1/projects/{projectId}/checkpoints`, `PUT /api/v1/checkpoints/{id}`, `POST /api/v1/checkpoints/{id}/complete`, `POST /api/v1/checkpoints/{id}/attachments`, `GET /api/v1/checkpoints/attachments/{attachmentId}/download`
- **Extensions & Dashboard**: `GET|POST /api/v1/projects/{projectId}/extensions`, `GET /api/v1/projects/dashboard/(summary|stage-distribution|pic-leaderboard|upcoming-deadlines)`

## Testing & Quality Assurance

```bash
# Pest test suite
./vendor/bin/pest tests/Unit/Domain/Project/ tests/Feature/Api/V1/ProjectApiTest.php

# Code styling (targeted files)
./vendor/bin/pint app/Domain/Project/ app/Http/Controllers/Api/V1/Project/ app/Infrastructure/Persistence/Eloquent/Models/Project* tests/Unit/Domain/Project/ tests/Feature/Api/V1/ProjectApiTest.php

# Static analysis
./vendor/bin/phpstan analyse app/Domain/Project/ app/Http/Controllers/Api/V1/Project/ app/Http/Requests/Api/V1/Project/ app/Http/Resources/V1/Project/ app/Infrastructure/Persistence/Eloquent/Models/Project* --memory-limit=2G

# Legacy prototype migration
php artisan diss:migrate-legacy-projects --dry-run
php artisan diss:migrate-legacy-projects
```

## Bruno Collection & Mock Server Standards

Bruno collection location: `docs/modules/it-project-management/bruno/`.

### 1. Root Header Injection (`collection.bru`)
Bypasses Bruno folder inheritance quirks by enforcing headers and token injection at collection root:
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

### 2. Secret Hygiene in `Local.bru`
Credentials and runtime tokens use `vars:secret` so files stay 100% clean with zero Git diffs:
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

### 3. Dual Variable Binding (`bru.setVar` + `bru.setEnvVar`)
Captures generated IDs across both GUI environment and collection variable scopes:
```javascript
script:post-response {
  if (res.body && res.body.data && res.body.data.id) {
    bru.setVar("projectId", res.body.data.id);
    bru.setEnvVar("projectId", res.body.data.id);
  }
}
```

### 4. File Syntax Validation Script
Run to verify POSIX newlines and absence of UTF-8 BOM or zero-width spaces (`\u200b`):
```bash
python3 -c "
import os
for root, _, files in os.walk('docs/modules/it-project-management/bruno'):
    for f in files:
        if f.endswith('.bru'):
            p = os.path.join(root, f)
            with open(p, 'rb') as fp: c = fp.read()
            assert not c.startswith(b'\xef\xbb\xbf'), f'BOM in {p}'
            assert b'\xe2\x80\x8b' not in c, f'Zero-width space in {p}'
            assert c.endswith(b'\n'), f'Missing newline in {p}'
print('All .bru files verified clean!')
"
```

### 5. Mock Server from OpenAPI Spec
1. Open Bruno Desktop $\rightarrow$ Click `+` next to **Mock Servers**.
2. Set **Source** to **OpenAPI Spec** $\rightarrow$ Select `docs/modules/it-project-management/openapi.yaml`.
3. Click **Create** $\rightarrow$ **Start Server** (`http://localhost:3000/api/v1`).
