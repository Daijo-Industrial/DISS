---
name: user-management
description: Domain knowledge, user repository architecture, page visit telemetry (user_page_visits), dormant user tracking (>30d), and tabbed admin edit conventions in DISS.
---

# User Management & Activity Telemetry

## 1. Domain & Persistence Architecture
- **Layering**:
  - `App\Domain\User\Entities\User`: Pure domain entity with immutability helpers (`emailVerifiedAt`, `lastVisitedAt`, `createdAt`).
  - `App\Domain\User\Repositories\UserRepository`: Interface for user persistence, pagination with `onlyDormant`, and `countDormantUsers(int $days = 30)`.
  - `App\Infrastructure\Persistence\Eloquent\Repositories\EloquentUserRepository`: Eloquent implementation with subquery optimization:
    `(SELECT MAX(last_visited_at) FROM user_page_visits WHERE user_page_visits.user_id = users.id) as last_visited_at`
  - `App\Application\User\DTOs\UserWithEmployeeSummary`: Flat DTO for presentation, including timestamps.

## 2. Activity Telemetry & Dormancy Rules
- **Table**: `user_page_visits` (`user_id`, `route_name`, `visit_count`, `last_visited_at`).
- **Telemetry Middleware**: `TrackPageVisits` updates visit counts and timestamps via upsert on named routes.
- **Dormancy Condition**:
  An account is considered dormant if:
  1. `is_active = true`, AND
  2. `last_visited_at < now() - 30 days` OR (`last_visited_at IS NULL` AND `created_at < now() - 30 days`).
- **User Index UI**:
  - Show subtle relative time: `• Active <diffForHumans>` vs `• Dormant (Never visited)` / `• Dormant (<diffForHumans>)`.
  - Filter chip: `[⚠️ X Dormant (>30d)]`.

## 3. User Edit Layout & Tabbed Workflow
- **Layout**: Extend `new.layouts.app`, wrap in `max-w-6xl mx-auto`.
- **Header**: User initial avatar, Name, Email, Verified status badge, Active status pill, Dormant badge, joined date, and `← Back to Users`.
- **4-Tab Interface** (`#[Url(history: true)] public string $tab = 'profile'`):
  1. `profile`: Name, Email, Employee linkage search, Active toggle switch, `saveProfile()`.
  2. `roles`: Module-grouped role chips, legacy direct permissions notice, `saveRoles()`.
  3. `activity`: Telemetry metrics (Last Active, Total Hits, Distinct Routes), Dormant notice, Top 10 routes.
  4. `security`: Password reset form (`savePassword()`), direct administrative email verification override (`toggleEmailVerification()`).

## 4. Development & Testing Runbooks
```bash
# Run user administration & telemetry test suite (11 tests, 48 assertions)
docker exec diss-laravel.test-1 php artisan test --filter=UserIndexTest
```
