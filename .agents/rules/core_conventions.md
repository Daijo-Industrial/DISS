# Core Architecture & Coding Conventions

- **Stack**: PHP 8.3, Laravel 10/11, MySQL 8.4, Redis, Tailwind CSS + Bootstrap 5, Boxicons (`bx bx-*`).
- **Environment**: Laravel Sail / Docker (`diss-laravel.test-1`, `diss-mysql-1`).
- **Layering**:
  - `app/Domain/<Feature>/Services/`: Business and calculation logic.
  - `app/Infrastructure/Persistence/Eloquent/Models/`: Persistence models.
  - `app/Models/`: Legacy aliases extending infrastructure models.
- **Authorization & Role Privacy**:
  - Spatie `laravel-permission` (`$user->hasRole(...)`). `super-admin` bypasses all checks.
  - **User Portal Privacy**: Never expose roles, role pills, or permission lists in user-facing views (profile, settings, topbar). Regular users experience authorization implicitly via feature access. Inspection of abilities/roles is strictly restricted to `super-admin` admin views.
- **Ponytail Standard**:
  - YAGNI: Build the minimum that works; zero unrequested abstractions or dependencies.
  - Favor native PHP/Laravel built-ins (e.g. stream filters for large files).
  - Tag intentional simplifications with `// ponytail:`.
  - **Documentation Ponytail**: State only current technical invariants. Zero conversational preamble ("This rule governs..."), zero historical refactor narratives ("X was replaced by Y", "presets were removed"), and zero audit/compliance bloat. Keep documentation dense, minimal, and high-signal.
- **Modular Skills**: Deep domain runbooks reside on-demand in `.agents/skills/<module>/SKILL.md`.

## Development Invariants
- **No Production Frontend Builds**: Never run `npm run build` in dev; Vite HMR is active.
- **Targeted Code Styling**: NEVER run `vendor/bin/pint` without specific file arguments (legacy repo will time out). Always pass specific modified file paths.
- **Testing Invariants**:
  - **Livewire Escaping**: Pass `escape: false` when asserting strings with `&` (`assertSee('A & B', escape: false)`).
  - **HTTP Response HTML**: Laravel `TestResponse` lacks `assertSeeHtml()`; use `$response->assertSee('<tag>', false)`.
  - **Indonesian Locale**: `config('app.locale')` is `'id'`. Evaluate `Carbon::diffForHumans()` dynamically in tests.
  - **Database Transactions**: NEVER use `RefreshDatabase` (160+ legacy tables collide). Always use `DatabaseTransactions`.
  - **Pest Scoping**: In Pest, `Feature/` tests inherit `Tests\TestCase` via `tests/Pest.php`. Never declare `uses(Tests\TestCase::class)` in `Feature/` (causes fatal duplicate test case error); declare only `uses(DatabaseTransactions::class)`. In `Unit/` tests needing DB, declare `uses(Tests\TestCase::class, DatabaseTransactions::class)`.
  - **Model Default Reload**: Call `$model->refresh()` before asserting fields backed by DB defaults (e.g. `stage = 'DRAFT'`).
- **Sequential Unique Code Invariant (Soft-Deletable Models)**:
  - When auto-generating sequential codes (`IT-YYYY-XXXX`, `VND-YYYY-XXXX`) in observers, NEVER count active rows (`whereYear()->count() + 1`). DB `UNIQUE` indexes include soft-deleted rows. Always query `Model::withTrashed()`, find the highest numeric suffix, and wrap generation in a `do...while (exists)` uniqueness loop.
- **Documentation Link Portability**:
  - Never use machine-absolute file URLs (`file:///home/...`). Always use repo-relative paths (`[Label](path/to/file)`) or inline backticks (`AuthController`).
- **Bruno API Collection Hygiene**:
  - All `.bru` files must end with a single POSIX newline (`\n`).
  - No invisible Unicode (`\u200b`, UTF-8 BOM `\xef\xbb\xbf`) or non-whitespace tokens outside block declarations.
- **OpenAPI Specification Standards**:
  - **Canonical Key Ordering**: `openapi` → `info` → `servers` → `tags` → `security` → `paths` → `components`.
  - **Complete Envelopes**: All status codes (`200`, `201`, `401`, `403`, `404`, `422`, `429`, `500`) require both schema and realistic mock examples.
  - **Zero Real PII**: Use RFC 2606 synthetic placeholders (e.g. `it.lead@example.com`).
  - **Minimalist Security**: Enforce security technically (`403`, `429`, HTTPS), but omit verbose compliance or audit text (e.g. avoid `(ISO 27001)`). Keep descriptions concise and behavior-focused.

## UI & Navigation Standards
- **Responsive Drawer**: Fullscreen on `< sm`, PC-width (`w-72` / 288px) on `sm:` to `lg:` with `bg-slate-950/60 backdrop-blur-sm`. In `w-72` drawers, brand flex containers must use `min-w-0 flex-1 truncate block` and `<aside>` must use `overflow-hidden` so close button (`shrink-0`) is never pushed out. Floating overlays (e.g. IT Concierge `z-[100]`) hide when drawer opens (`x-show="!sidebarOpen"`).
- **Dashboard & Quick Access**: `/home` shows greeting, Quick Access shortcuts, and Recent Requests/Approval Queue. `NavigationService::getQuickAccessItems($user)` uses smart defaults and cold-start fallback (up to 6 items). `NavPinController::MAX_PINS = 8`.
- **Admin Layout**: `new.layouts.admin-settings` is strictly for directory listings. Entity edit/create views extend `new.layouts.app` wrapped in `max-w-6xl mx-auto` with a back button.
- **Minimalist Aesthetic**:
  - Calm neutral cards (`slate-50/60`), subtle status pills, no loud stacked warning banners.
  - Contextual action triggers; no duplicate buttons across multiple headers.
  - Soft geometry (`rounded-2xl`), subtle rings (`ring-1 ring-slate-900/5`), `Plus Jakarta Sans` typography hierarchy over high-contrast fills.
