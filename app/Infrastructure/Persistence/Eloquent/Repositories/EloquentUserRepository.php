<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\User\Entities\User as UserEntity;
use App\Domain\User\Repositories\UserRepository;
use App\Domain\User\ValueObjects\Email;
use App\Infrastructure\Persistence\Eloquent\Models\User as UserModel;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class EloquentUserRepository implements UserRepository
{
    public function findById(int $id): ?UserEntity
    {
        $model = UserModel::with('roles')->find($id);

        return $model ? $this->toEntity($model) : null;
    }

    public function findByEmail(string $email): ?UserEntity
    {
        $model = UserModel::with('roles')->where('email', $email)->first();

        return $model ? $this->toEntity($model) : null;
    }

    public function findByEmployeeId(int $employeeId): ?UserEntity
    {
        $model = UserModel::with('roles')
            ->where('employee_id', $employeeId)
            ->first();

        return $model ? $this->toEntity($model) : null;
    }

    public function paginate(
        int $perPage,
        ?string $search = null,
        ?bool $onlyActive = null,
        ?bool $onlyDormant = null
    ): LengthAwarePaginator {
        $query = UserModel::query()
            ->select([
                'users.id',
                'users.name',
                'users.email',
                'users.is_active',
                'users.employee_id',
                'users.email_verified_at',
                'users.created_at',
            ])
            ->selectSub(
                'SELECT MAX(last_visited_at) FROM user_page_visits WHERE user_page_visits.user_id = users.id',
                'last_visited_at'
            )
            ->with(['roles:id,name']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('users.name', 'like', "%{$search}%")
                    ->orWhere('users.email', 'like', "%{$search}%");
            });
        }

        if (! is_null($onlyActive)) {
            $query->where('users.is_active', $onlyActive);
        }

        if ($onlyDormant) {
            $threshold = now()->subDays(30);
            $query->where('users.is_active', true)
                ->where('users.created_at', '<=', $threshold)
                ->where(function ($q) use ($threshold) {
                    $q->whereRaw('(SELECT MAX(last_visited_at) FROM user_page_visits WHERE user_page_visits.user_id = users.id) IS NULL OR (SELECT MAX(last_visited_at) FROM user_page_visits WHERE user_page_visits.user_id = users.id) <= ?', [$threshold]);
                });
        }

        $paginator = $query->paginate($perPage);

        $entities = $paginator->getCollection()
            ->map(fn (UserModel $model) => $this->toEntity($model));

        return new LengthAwarePaginator(
            items: $entities,
            total: $paginator->total(),
            perPage: $paginator->perPage(),
            currentPage: $paginator->currentPage(),
            options: [
                'path' => request()->url(),
                'query' => request()->query(),
            ],
        );
    }

    public function create(UserEntity $user, string $plainPassword): UserEntity
    {
        $model = new UserModel;
        $model->name = $user->name();
        $model->email = (string) $user->email();
        $model->is_active = $user->isActive();
        $model->password = Hash::make($plainPassword);
        $model->employee_id = $user->employeeId();
        $model->save();

        // set ID back to entity if you want
        return $this->toEntity($model->fresh('roles'));
    }

    public function update(UserEntity $user): UserEntity
    {
        $model = UserModel::findOrFail($user->id());
        $model->name = $user->name();
        $model->email = (string) $user->email();
        $model->is_active = $user->isActive();
        $model->employee_id = $user->employeeId();
        $model->save();

        return $this->toEntity($model->fresh('roles'));
    }

    public function delete(int $id): void
    {
        UserModel::findOrFail($id)->delete();
    }

    public function setRoles(UserEntity $user, array $roleNames): void
    {
        $model = UserModel::findOrFail($user->id());
        $model->syncRoles($roleNames);
    }

    public function changeUserPassword(int $userId, string $plainPassword): void
    {
        $model = UserModel::findOrFail($userId);
        $model->password = Hash::make($plainPassword);
        $model->save();
    }

    public function countDormantUsers(int $days = 30): int
    {
        $threshold = now()->subDays($days);

        return UserModel::query()
            ->where('is_active', true)
            ->where('created_at', '<=', $threshold)
            ->where(function ($q) use ($threshold) {
                $q->whereRaw('(SELECT MAX(last_visited_at) FROM user_page_visits WHERE user_page_visits.user_id = users.id) IS NULL OR (SELECT MAX(last_visited_at) FROM user_page_visits WHERE user_page_visits.user_id = users.id) <= ?', [$threshold]);
            })
            ->count();
    }

    private function toEntity(UserModel $model): UserEntity
    {
        $lastVisitedAt = null;
        if (! empty($model->last_visited_at)) {
            $lastVisitedAt = is_string($model->last_visited_at)
                ? Carbon::parse($model->last_visited_at)
                : $model->last_visited_at;
        }

        return new UserEntity(
            id: $model->id,
            name: $model->name,
            email: new Email($model->email),
            active: $model->is_active,
            roles: $model->roles->pluck('name')->all(),
            employeeId: $model->employee_id,
            emailVerifiedAt: $model->email_verified_at,
            lastVisitedAt: $lastVisitedAt,
            createdAt: $model->created_at,
        );
    }
}
