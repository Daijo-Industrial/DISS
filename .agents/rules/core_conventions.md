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
  - Deep domain runbooks are kept in `.agents/skills/<module>/SKILL.md` (e.g. `supplier-evaluation`) to preserve token economy.
