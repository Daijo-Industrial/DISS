# Core Architecture & Coding Conventions

- **Stack**: PHP 8.3, Laravel 10/11, MySQL 8.4, Redis, Tailwind CSS + Bootstrap 5, Boxicons (`bx bx-*`).
- **Environment**: Runs via Laravel Sail / Docker (`diss-laravel.test-1`, `diss-mysql-1`).
- **Layering**:
  - `app/Domain/<Feature>/Services/`: Business and calculation logic.
  - `app/Infrastructure/Persistence/Eloquent/Models/`: Concrete persistence models.
  - `app/Models/`: Legacy models (extend infrastructure counterparts).
- **Authorization**: Spatie `laravel-permission` (`$user->hasRole(...)`). `super-admin` has global bypass.
- **Ponytail Standard**:
  - YAGNI: Build the minimum that works; no unrequested abstractions or dependencies.
  - Favor native PHP / Laravel built-ins (e.g., native stream filters for large file parsing).
  - Tag intentional simplifications with `// ponytail:`.
- **Modular Skills**:
  - Deep domain runbooks are kept in `.agents/skills/<module>/SKILL.md` (e.g. `fleet-management`, `supplier-evaluation`, `sap-sync-forecast`) to preserve token economy.
- **Development Invariants**:
  - **No Production Frontend Builds**: Do NOT run `npm run build` in the development environment; Vite HMR is running actively.
  - **Targeted Code Styling**: NEVER run `vendor/bin/pint` without specific file arguments. The legacy repository contains hundreds of unformatted files and will time out; always pass specific modified file paths.

