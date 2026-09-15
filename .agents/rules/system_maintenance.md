# Proactive Context & Documentation Maintenance Rule

This rule governs the behavior of AI coding assistants working in the **Daijo Industrial System (DISS)** repository.

---

## Mandatory Rule: Proactive Rule & Context Updates

Whenever you make changes to this codebase, you **MUST proactively update** the relevant documentation in `.agents/rules/` and `.agents/skills/` to reflect the latest state of the project before completing your task. Do not wait for the user to explicitly ask you to update documentation.

### Triggers for Updating Documentation:
1. **Schema & Models**:
   - When adding, altering, or removing Eloquent models, migrations, table columns, or relationships.
2. **SAP Data Import & File Mappings**:
   - When new SAP export files, criteria, column names, sanitization rules, or table mappings are modified or added (update `.agents/skills/supplier-evaluation/SKILL.md`).
3. **Domain Services & Business Logic**:
   - When updating calculation logic, scoring rules (e.g. `SupplierScoringService`), grade thresholds, or import pipelines (`SapEvaluationImportService`).
4. **Routes & Access Control**:
   - When introducing new routes, modifying role requirements (e.g. `super-admin`, `admin`, permissions), or updating middleware.
5. **Views & User Workflows**:
   - When changing UI flows in `resources/views/purchasing/` or modifying form structures.

### Guidelines for Updates:
- Keep core conventions in `.agents/rules/core_conventions.md` brief (~30 lines) to minimize prompt overhead on every turn.
- Put deep module-specific runbooks into their respective on-demand skills in `.agents/skills/<module>/SKILL.md`.
- Preserve file path links in standard markdown format (`[Label](file:///absolute/path)`).
