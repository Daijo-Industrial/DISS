<?php

namespace App\Livewire\Admin\Users;

use App\Application\Employee\UseCases\SearchEmployees;
use App\Application\User\DTOs\UserData;
use App\Application\User\UseCases\ChangeUserPassword;
use App\Application\User\UseCases\UpdateUser;
use App\Domain\Employee\Repositories\EmployeeRepository;
use App\Domain\User\Repositories\UserRepository;
use App\Infrastructure\Common\PermissionRegistry;
use App\Infrastructure\Persistence\Eloquent\Models\User as EloquentUser;
use App\Models\UserPageVisit;
use App\Presentation\Http\Requests\UserRequest;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class UserEdit extends Component
{
    public int $editingId;

    #[Url(history: true)]
    public string $tab = 'profile';

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public bool $active = true;

    public ?\DateTimeInterface $emailVerifiedAt = null;

    public ?\DateTimeInterface $createdAt = null;

    /** @var string[] */
    public array $selectedRoles = [];

    public array $availableRoles = [];

    public array $originalDirectPermissions = [];

    public array $selectedDirectPermissions = [];

    public ?int $employeeId = null;

    public string $employeeSearch = '';

    public array $employeeOptions = [];

    public ?string $selectedEmployeeLabel = null;

    protected array $roleDescriptions = [];

    public function mount(int $userId, UserRepository $users, EmployeeRepository $employees): void
    {
        $this->availableRoles = Role::query()->orderBy('name')->pluck('name')->toArray();
        $this->roleDescriptions = config('permission_groups.role_descriptions', []);

        $user = $users->findById($userId);

        if (! $user) {
            abort(404, 'User not found.');
        }

        $this->editingId = $user->id();
        $this->name = $user->name();
        $this->email = (string) $user->email();
        $this->active = $user->isActive();
        $this->selectedRoles = $user->roles();
        $this->emailVerifiedAt = $user->emailVerifiedAt();
        $this->createdAt = $user->createdAt();
        $this->employeeId = method_exists($user, 'employeeId') ? $user->employeeId() : null;

        if ($this->employeeId) {
            $employee = $employees->findById($this->employeeId);
            if ($employee) {
                $this->selectedEmployeeLabel = sprintf('%s - %s (%s)', $employee->nik(), $employee->name(), $employee->branch());
                $this->employeeSearch = $employee->nik() . ' - ' . $employee->name();
            }
        }

        $eloquent = EloquentUser::find($this->editingId);
        $this->originalDirectPermissions = $eloquent
            ? $eloquent->getDirectPermissions()->pluck('name')->toArray()
            : [];
        $this->selectedDirectPermissions = $this->originalDirectPermissions;
    }

    public function setTab(string $tab): void
    {
        $allowed = ['profile', 'roles', 'activity', 'security'];
        if (in_array($tab, $allowed, true)) {
            $this->tab = $tab;
        }
    }

    public function getGroupedRolesProperty(): array
    {
        $allModules = PermissionRegistry::getModules();
        $grouped = [];
        $assignedRoles = [];

        foreach ($allModules as $moduleName => $data) {
            $moduleRoles = array_keys($data['roles'] ?? []);
            if (! empty($moduleRoles)) {
                $validRoles = array_intersect($moduleRoles, $this->availableRoles);
                if (! empty($validRoles)) {
                    $grouped[$moduleName] = $validRoles;
                    $assignedRoles = array_merge($assignedRoles, $validRoles);
                }
            }
        }

        $others = array_diff($this->availableRoles, $assignedRoles);
        if (! empty($others)) {
            $grouped['Other'] = array_values($others);
        }

        return $grouped;
    }

    public function getRoleDescription(string $role): string
    {
        return $this->roleDescriptions[$role] ?? ucfirst(str_replace('-', ' ', $role));
    }

    protected function rules(): array
    {
        return UserRequest::updateRules($this->editingId);
    }

    protected function messages(): array
    {
        return UserRequest::messagesArray();
    }

    public function updatedEmployeeSearch(SearchEmployees $searchEmployees): void
    {
        $term = trim($this->employeeSearch);
        if ($term === '') {
            $this->employeeOptions = [];

            return;
        }

        $summaries = $searchEmployees->execute($term);
        $this->employeeOptions = array_map(function ($summary) {
            return [
                'id' => $summary->id,
                'nik' => $summary->nik,
                'name' => $summary->name,
                'branch' => $summary->branch,
                'dept_code' => $summary->deptCode,
            ];
        }, $summaries);
    }

    public function selectEmployee(int $employeeId): void
    {
        $option = collect($this->employeeOptions)->firstWhere('id', $employeeId);
        if (! $option) {
            return;
        }

        $this->employeeId = $option['id'];
        $this->selectedEmployeeLabel = sprintf('%s - %s (%s)', $option['nik'], $option['name'], $option['branch'] ?? '-');
        $this->employeeSearch = $option['nik'] . ' - ' . $option['name'];
        $this->employeeOptions = [];
    }

    public function clearEmployee(): void
    {
        $this->employeeId = null;
        $this->selectedEmployeeLabel = null;
        $this->employeeSearch = '';
        $this->employeeOptions = [];
    }

    public function saveProfile(UpdateUser $updateUser): void
    {
        $this->authorize('user.update');
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($this->editingId),
            ],
            'employeeId' => ['nullable', 'integer', 'exists:employees,id'],
            'active' => ['boolean'],
        ], UserRequest::messagesArray());

        $dto = new UserData(
            name: $this->name,
            email: $this->email,
            password: null,
            roles: $this->selectedRoles,
            active: $this->active,
            employeeId: $this->employeeId
        );

        try {
            $updateUser->execute($this->editingId, $dto);
        } catch (\DomainException $e) {
            $this->addError('email', $e->getMessage());

            return;
        }

        session()->flash('status', 'User profile updated successfully.');
    }

    public function saveRoles(UpdateUser $updateUser): void
    {
        $this->authorize('user.update');
        $this->validate([
            'selectedRoles' => ['nullable', 'array'],
            'selectedRoles.*' => ['string'],
        ]);

        $dto = new UserData(
            name: $this->name,
            email: $this->email,
            password: null,
            roles: $this->selectedRoles,
            active: $this->active,
            employeeId: $this->employeeId
        );

        $updateUser->execute($this->editingId, $dto);

        $eloquent = EloquentUser::find($this->editingId);
        if ($eloquent) {
            $eloquent->syncPermissions($this->selectedDirectPermissions);
        }

        session()->flash('status', 'Roles and permissions updated successfully.');
    }

    public function savePassword(ChangeUserPassword $changeUserPassword): void
    {
        $this->authorize('user.update');
        $this->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], UserRequest::messagesArray());

        $changeUserPassword->execute($this->editingId, $this->password);

        $this->password = '';
        $this->password_confirmation = '';

        session()->flash('status', 'Password updated successfully.');
    }

    public function toggleEmailVerification(): void
    {
        $this->authorize('user.update');
        $eloquent = EloquentUser::find($this->editingId);
        if (! $eloquent) {
            return;
        }

        if ($eloquent->email_verified_at) {
            $eloquent->email_verified_at = null;
            $eloquent->save();
            $this->emailVerifiedAt = null;
            session()->flash('status', 'Email marked as unverified.');
        } else {
            $eloquent->email_verified_at = now();
            $eloquent->save();
            $this->emailVerifiedAt = $eloquent->email_verified_at;
            session()->flash('status', 'Email verified successfully.');
        }
    }

    public function save(UpdateUser $updateUser): void
    {
        $this->authorize('user.update');
        $this->validate();

        $dto = new UserData(
            name: $this->name,
            email: $this->email,
            password: ! empty($this->password) ? $this->password : null,
            roles: $this->selectedRoles,
            active: $this->active,
            employeeId: $this->employeeId
        );

        try {
            $updateUser->execute($this->editingId, $dto);
        } catch (\DomainException $e) {
            $this->addError('email', $e->getMessage());

            return;
        }

        $eloquent = EloquentUser::find($this->editingId);
        if ($eloquent) {
            $eloquent->syncPermissions($this->selectedDirectPermissions);
        }

        session()->flash('success', 'User updated successfully.');
        $this->redirectRoute('admin.users.index');
    }

    #[Computed]
    public function isDormant(): bool
    {
        if (! $this->active) {
            return false;
        }

        $lastVisited = $this->visitStats['last_visited_at'];
        if ($lastVisited) {
            return Carbon::parse($lastVisited)->lt(now()->subDays(30));
        }

        return $this->createdAt ? Carbon::parse($this->createdAt)->lt(now()->subDays(30)) : false;
    }

    #[Computed]
    public function topVisitedPages(): array
    {
        return UserPageVisit::query()
            ->where('user_id', $this->editingId)
            ->orderByDesc('visit_count')
            ->limit(10)
            ->get()
            ->map(function (UserPageVisit $visit) {
                $parts = explode('.', $visit->route_name);
                $module = ucfirst(str_replace(['-', '_'], ' ', $parts[0] ?? $visit->route_name));
                $action = isset($parts[1]) ? ucfirst(str_replace(['-', '_'], ' ', $parts[1])) : 'Overview';

                return [
                    'route_name' => $visit->route_name,
                    'module' => $module,
                    'action' => $action,
                    'visit_count' => (int) $visit->visit_count,
                    'last_visited_at' => $visit->last_visited_at,
                ];
            })
            ->toArray();
    }

    #[Computed]
    public function visitStats(): array
    {
        $visits = UserPageVisit::query()->where('user_id', $this->editingId);

        return [
            'total_visits' => (int) $visits->sum('visit_count'),
            'last_visited_at' => $visits->max('last_visited_at'),
            'distinct_routes' => (int) $visits->count(),
        ];
    }

    public function render()
    {
        return view('livewire.admin.users.user-edit', [
            'topVisitedPages' => $this->topVisitedPages,
            'visitStats' => $this->visitStats,
            'isDormant' => $this->isDormant,
        ]);
    }
}
