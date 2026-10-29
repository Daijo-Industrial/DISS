# Proactive Documentation Maintenance

Proactively update `.agents/rules/` and `.agents/skills/` when modifying code. Do not wait for user prompting.

## Triggers
- **Schema & Models**: New or altered models, migrations, table columns, relationships.
- **SAP Ingestion**: New export files, column mappings, or sanitization rules (`supplier-evaluation`, `sap-sync-forecast`).
- **Domain Logic**: Calculation services, SLAs, scoring formulas, state transitions.
- **Routes & RBAC**: New endpoints, permissions, role gates, middleware.
- **Workflows & UI**: Component contracts, form structures, navigation state.

## Conventions
- **Global Rules** (`.agents/rules/core_conventions.md`): Keep brief (~50 lines) to minimize token overhead on every turn.
- **Domain Skills** (`.agents/skills/<module>/SKILL.md`): House deep module runbooks, schemas, and formulas.
- **Ponytail Standard**: Document current state only; omit conversational preambles and past refactor history.
- **Portable Links**: Use repository-relative paths (`[Label](path/to/file)`) or inline backticks; never machine-specific `file:///` URLs.
