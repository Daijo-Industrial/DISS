---
name: user-management
description: Domain knowledge, user repository architecture, page visit telemetry, dormant user tracking (>30d), and tabbed admin edit conventions in DISS.
---

# User Management & Activity Telemetry

## 1. Domain & Persistence Architecture

- **`User` Entity** (`App\Domain\User\Entities\User`): Immutability helpers (`emailVerifiedAt`, `lastVisitedAt`, `createdAt`).
- **`UserRepository`** (`App\Domain\User\Repositories\UserRepository`): Interface for pagination with `onlyDormant`, and `countDormantUsers(int $days = 30)`.
- **`EloquentUserRepository`** (`App\Infrastructure\Persistence\Eloquent\Repositories\EloquentUserRepository`): Subquery optimization:
  `(SELECT MAX(last_visited_at) FROM user_page_visits WHERE user_page_visits.user_id = users.id) as last_visited_at`
- **DTO**: `App\Application\User\DTOs\UserWithEmployeeSummary`.

## 2. Activity Telemetry & Dormancy Rules

- **Table**: `user_page_visits` (`user_id`, `route_name`, `visit_count`, `last_visited_at`).
- **Middleware**: `TrackPageVisits` records page hits and timestamps on named routes.
- **Dormancy Condition**:
  An account is dormant if `is_active = true` AND:
  - `last_visited_at < now() - 30 days`, OR
  - `last_visited_at IS NULL` AND `created_at < now() - 30 days`.
- **UI Indicators**: Relative timestamps (`• Active <diffForHumans>` vs `• Dormant`) and filter chip `[⚠️ X Dormant (>30d)]`.

## 3. User Edit Layout & Tabs

- **Layout**: Extends `new.layouts.app` wrapped in `max-w-6xl mx-auto`.
- **Header**: Initial avatar, Name, Email, Status badges, and `← Back to Users`.
- **4-Tab Interface** (`#[Url(history: true)] public string $tab = 'profile'`):
  1. `profile`: Identity, Employee linkage search, Active toggle switch.
  2. `roles`: Module-grouped role chips, legacy direct permissions notice.
  3. `activity`: Telemetry metrics (Last Active, Total Hits, Distinct Routes), Top 10 routes.
  4. `security`: Password reset, direct email verification override.

## 4. Runbooks

```bash
# Run user administration & telemetry tests
docker exec diss-laravel.test-1 php artisan test --filter=UserIndexTest
```
