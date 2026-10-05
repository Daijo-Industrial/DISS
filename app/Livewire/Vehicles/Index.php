<?php

namespace App\Livewire\Vehicles;

use App\Enums\VehicleStatus;
use App\Infrastructure\Persistence\Eloquent\Models\Vehicle;
use App\Models\ServiceRecord;
use App\Models\VehicleDocument;
use Illuminate\Database\QueryException;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $q = '';

    #[Url(as: 'st')]
    public string $status = 'all';

    #[Url(as: 'pp')]
    public int $perPage = 10;

    #[Url(as: 'sort')]
    public string $sort = 'plate_number';

    #[Url(as: 'dir')]
    public string $dir = 'asc';

    #[Url(as: 'cat')]
    public string $category = 'passenger';

    #[Url(as: 'cats')]
    public array $selectedCategories = ['passenger'];

    #[Url(as: 'tab')]
    public string $operationalTab = 'all'; // 'all', 'in_pool', 'on_trip', 'maintenance'

    #[Url(as: 'stats')]
    public array $selectedStatuses = ['in_pool', 'on_trip', 'maintenance'];

    #[Url(as: 'view')]
    public string $viewMode = 'table'; // 'grid' (Gallery Cards) or 'table' (Data Table)

    public bool $canManage = false;

    public bool $canInspect = false;

    public function mount(): void
    {
        $user = auth()->user();
        if (! ($user?->can('fleet.view') || $user?->can('fleet.manage') || $user?->can('fleet.inspect'))) {
            abort(403);
        }

        $this->canManage = $user?->can('fleet.manage') ?? false;
        $this->canInspect = $this->canManage || ($user?->can('fleet.inspect') ?? false);

        if (! empty($this->selectedCategories)) {
            $this->selectedCategories = array_values($this->selectedCategories);
            if (count($this->selectedCategories) === 1) {
                $this->category = $this->selectedCategories[0];
            } elseif (count($this->selectedCategories) === 4) {
                $this->category = 'all';
            } else {
                $this->category = 'custom';
            }
        } else {
            $this->selectedCategories = ['passenger'];
            $this->category = 'passenger';
        }

        if (! empty($this->selectedStatuses)) {
            if (count($this->selectedStatuses) === 1) {
                $this->operationalTab = $this->selectedStatuses[0];
            } elseif ($this->isDefaultActiveStatuses($this->selectedStatuses)) {
                $this->operationalTab = 'all';
            } elseif ($this->canManage && count($this->selectedStatuses) === 4) {
                $this->operationalTab = 'all';
            } else {
                $this->operationalTab = 'custom';
            }
        } else {
            if ($this->operationalTab === 'all') {
                $this->selectedStatuses = ['in_pool', 'on_trip', 'maintenance'];
            } elseif (in_array($this->operationalTab, ['in_pool', 'on_trip', 'maintenance', 'sold'], true)) {
                $this->selectedStatuses = [$this->operationalTab];
            } else {
                $this->selectedStatuses = ['in_pool', 'on_trip', 'maintenance'];
            }
        }
    }

    public function sortBy(string $field): void
    {
        $allowed = $this->canManage ? ['plate_number', 'driver_name', 'odometer', 'status', 'last_service_date'] : ['plate_number', 'driver_name'];

        if (! in_array($field, $allowed, true)) {
            return; // ignore disallowed sorts
        }

        if ($this->sort === $field) {
            $this->dir = $this->dir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sort = $field;
            $this->dir = 'asc';
        }
        $this->resetPage();
    }

    public function updatingQ()
    {
        $this->resetPage();
    }

    public function updatingStatus()
    {
        $this->resetPage();
    }

    public function updatingPerPage()
    {
        $this->resetPage();
    }

    public function deleteVehicle(string $id): void
    {
        // Only allow delete for managers with fleet.manage permission
        if (! $this->canManage) {
            abort(403);
        }

        $vehicle = Vehicle::findOrFail($id);

        try {
            $vehicle->delete(); // Soft delete if your model uses SoftDeletes
            session()->flash('success', 'Vehicle deleted.');
        } catch (QueryException $e) {
            // (Optional) Handle FK constraint or other DB issues gracefully
            session()->flash('error', 'Unable to delete this vehicle (it may have related records).');
        }

        // Reset pagination so you don’t land on an empty page after deletion
        $this->resetPage();
    }

    public function updatedSelectedCategories(): void
    {
        $this->selectedCategories = array_values($this->selectedCategories);
        if (count($this->selectedCategories) === 1) {
            $this->category = $this->selectedCategories[0];
        } elseif (count($this->selectedCategories) === 4 || empty($this->selectedCategories)) {
            $this->category = 'all';
        } else {
            $this->category = 'custom';
        }
        $this->resetPage();
    }

    public function updatedSelectedStatuses(): void
    {
        $this->selectedStatuses = array_values($this->selectedStatuses);
        if (count($this->selectedStatuses) === 1) {
            $this->operationalTab = $this->selectedStatuses[0];
        } elseif ($this->isDefaultActiveStatuses($this->selectedStatuses)) {
            $this->operationalTab = 'all';
        } elseif ($this->canManage && count($this->selectedStatuses) === 4) {
            $this->operationalTab = 'all';
        } elseif (empty($this->selectedStatuses)) {
            $this->selectedStatuses = ['in_pool', 'on_trip', 'maintenance'];
            $this->operationalTab = 'all';
        } else {
            $this->operationalTab = 'custom';
        }
        $this->resetPage();
    }

    public function updatedCategory($value): void
    {
        if ($value === 'all') {
            $this->selectedCategories = ['passenger', 'commercial_truck', 'pickup', 'other'];
        } elseif ($value !== 'custom') {
            $this->selectedCategories = [$value];
        }
        $this->resetPage();
    }

    public function setCategory(string $category): void
    {
        if (in_array($category, ['all', 'passenger', 'commercial_truck', 'pickup', 'other'], true)) {
            $this->category = $category;
            $this->selectedCategories = ($category === 'all')
                ? ['passenger', 'commercial_truck', 'pickup', 'other']
                : [$category];
            $this->resetPage();
        }
    }

    public function selectAllCategories(): void
    {
        $this->selectedCategories = ['passenger', 'commercial_truck', 'pickup', 'other'];
        $this->category = 'all';
        $this->resetPage();
    }

    public function resetCategoryFilter(): void
    {
        $this->selectedCategories = ['passenger'];
        $this->category = 'passenger';
        $this->resetPage();
    }

    public function updatedOperationalTab($value): void
    {
        if ($value === 'all') {
            $this->selectedStatuses = ['in_pool', 'on_trip', 'maintenance'];
        } elseif ($value !== 'custom') {
            $this->selectedStatuses = [$value];
        }
        $this->resetPage();
    }

    public function setOperationalTab(string $tab): void
    {
        $allowed = $this->canManage
            ? ['all', 'in_pool', 'on_trip', 'maintenance', 'sold']
            : ['all', 'in_pool', 'on_trip', 'maintenance'];

        if (in_array($tab, $allowed, true)) {
            $this->operationalTab = $tab;
            $this->selectedStatuses = ($tab === 'all')
                ? ['in_pool', 'on_trip', 'maintenance']
                : [$tab];
            $this->resetPage();
        }
    }

    public function selectAllStatuses(): void
    {
        $this->selectedStatuses = $this->canManage
            ? ['in_pool', 'on_trip', 'maintenance', 'sold']
            : ['in_pool', 'on_trip', 'maintenance'];
        $this->operationalTab = $this->canManage ? 'custom' : 'all';
        $this->resetPage();
    }

    public function resetStatusFilter(): void
    {
        $this->selectedStatuses = ['in_pool', 'on_trip', 'maintenance'];
        $this->operationalTab = 'all';
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->q = '';
        $this->status = 'all';
        $this->operationalTab = 'all';
        $this->selectedStatuses = ['in_pool', 'on_trip', 'maintenance'];
        $this->category = 'passenger';
        $this->selectedCategories = ['passenger'];
        $this->sort = 'plate_number';
        $this->dir = 'asc';
        $this->resetPage();
    }

    public function isDefaultActiveStatuses(array $statuses): bool
    {
        return count($statuses) === 3
            && in_array('in_pool', $statuses, true)
            && in_array('on_trip', $statuses, true)
            && in_array('maintenance', $statuses, true)
            && ! in_array('sold', $statuses, true);
    }

    public function isDefaultCategory(array $categories): bool
    {
        $vals = array_values($categories);

        return count($vals) === 1 && $vals[0] === 'passenger';
    }

    public function getHasCustomCategoryProperty(): bool
    {
        return $this->category !== 'passenger' || ! $this->isDefaultCategory($this->selectedCategories);
    }

    public function getHasCustomStatusProperty(): bool
    {
        return ! ($this->operationalTab === 'all' && $this->isDefaultActiveStatuses($this->selectedStatuses));
    }

    public function getHasActiveFiltersProperty(): bool
    {
        return $this->hasCustomCategory
            || $this->hasCustomStatus
            || $this->q !== ''
            || $this->sort !== 'plate_number'
            || $this->dir !== 'asc';
    }

    public function setViewMode(string $mode): void
    {
        if (in_array($mode, ['grid', 'table'], true)) {
            $this->viewMode = $mode;
        }
    }

    public function render()
    {
        // Ensure sort field is allowed for this role
        $allowed = $this->canManage ? ['plate_number', 'driver_name', 'odometer', 'status', 'last_service_date'] : ['plate_number', 'driver_name'];

        $sortField = in_array($this->sort, $allowed, true) ? $this->sort : 'plate_number';
        $sortDir = $this->dir === 'desc' ? 'desc' : 'asc';

        $query = Vehicle::query()
            ->select('vehicles.*')
            ->with(['activeCheckOut']);

        if ($this->canManage) {
            $query
                ->selectSub(ServiceRecord::select('service_date')->whereColumn('vehicle_id', 'vehicles.id')->orderByDesc('service_date')->limit(1), 'last_service_date')
                ->selectSub(ServiceRecord::select('odometer')->whereColumn('vehicle_id', 'vehicles.id')->orderByDesc('service_date')->limit(1), 'last_service_odometer')
                ->with([
                    'latestService' => fn ($q) => $q->withCount('items'),
                    'latestService.items' => fn ($q) => $q->limit(5),
                    'documents' => fn ($q) => $q->orderBy('expired_date'),
                ]);
        }

        $tab = ($this->operationalTab === 'sold' && ! $this->canManage) ? 'all' : $this->operationalTab;

        $query
            ->when(
                $this->q,
                fn ($q) => $q->where(function ($w) {
                    $w->where('plate_number', 'like', '%' . $this->q . '%')
                        ->orWhere('brand', 'like', '%' . $this->q . '%')
                        ->orWhere('model', 'like', '%' . $this->q . '%')
                        ->orWhere('driver_name', 'like', '%' . $this->q . '%');
                }),
            );

        // 1. Category filtering
        if (! empty($this->selectedCategories) && count($this->selectedCategories) < 4 && $this->category !== 'all') {
            $query->whereIn('category', $this->selectedCategories);
        } elseif ($this->category !== 'all' && $this->category !== 'custom') {
            $query->where('category', $this->category);
        }

        // 2. Operational Status filtering
        if (! empty($this->selectedStatuses) && ! $this->isDefaultActiveStatuses($this->selectedStatuses)) {
            $hasSold = in_array('sold', $this->selectedStatuses, true) && $this->canManage;
            $activeStatuses = array_values(array_diff($this->selectedStatuses, ['sold']));

            $query->where(function ($w) use ($hasSold, $activeStatuses) {
                $conditionUsed = false;

                if ($hasSold) {
                    $w->where(function ($sw) {
                        $sw->whereIn('status', ['sold', 'retired'])
                            ->orWhereNotNull('sold_at');
                    });
                    $conditionUsed = true;
                }

                if (! empty($activeStatuses)) {
                    $method = $conditionUsed ? 'orWhere' : 'where';
                    $w->$method(function ($aw) use ($activeStatuses) {
                        $aw->whereNotIn('status', ['sold', 'retired'])
                            ->whereNull('sold_at')
                            ->where(function ($cw) use ($activeStatuses) {
                                $first = true;
                                if (in_array('in_pool', $activeStatuses, true)) {
                                    $cw->where(function ($pw) {
                                        $pw->whereDoesntHave('activeCheckOut')->where('status', '!=', 'maintenance');
                                    });
                                    $first = false;
                                }
                                if (in_array('on_trip', $activeStatuses, true)) {
                                    $m = $first ? 'where' : 'orWhere';
                                    $cw->$m(function ($tw) {
                                        $tw->whereHas('activeCheckOut');
                                    });
                                    $first = false;
                                }
                                if (in_array('maintenance', $activeStatuses, true)) {
                                    $m = $first ? 'where' : 'orWhere';
                                    $cw->$m(function ($mw) {
                                        $mw->where('status', 'maintenance');
                                    });
                                }
                            });
                    });
                }
            });
        } else {
            $tab = ($this->operationalTab === 'sold' && ! $this->canManage) ? 'all' : $this->operationalTab;

            if ($tab === 'sold') {
                $query->where(function ($w) {
                    $w->whereIn('status', ['sold', 'retired'])
                        ->orWhereNotNull('sold_at');
                });
            } elseif ($tab !== 'all' && $tab !== 'custom') {
                $query->whereNotIn('status', ['sold', 'retired'])
                    ->whereNull('sold_at')
                    ->when($tab === 'in_pool', function ($w) {
                        $w->whereDoesntHave('activeCheckOut')->where('status', '!=', 'maintenance');
                    })
                    ->when($tab === 'on_trip', function ($w) {
                        $w->whereHas('activeCheckOut');
                    })
                    ->when($tab === 'maintenance', function ($w) {
                        $w->where('status', 'maintenance');
                    });
            } else {
                $query->whereNotIn('status', ['sold', 'retired'])
                    ->whereNull('sold_at');
            }
        }

        $query
            ->when($this->canManage && $this->status !== 'all', function ($q) {
                $q->where('status', VehicleStatus::from($this->status));
            })
            ->orderBy($sortField, $sortDir);

        // Active Fleet KPI Metrics
        $baseActiveQuery = Vehicle::query()
            ->whereNull('deleted_at')
            ->whereNotIn('status', ['sold', 'retired'])
            ->whereNull('sold_at')
            ->when(! empty($this->selectedCategories) && count($this->selectedCategories) < 4 && $this->category !== 'all', fn ($q) => $q->whereIn('category', $this->selectedCategories))
            ->when($this->category !== 'all' && $this->category !== 'custom' && empty($this->selectedCategories), fn ($q) => $q->where('category', $this->category));

        $totalVehicles = (clone $baseActiveQuery)->count();
        $onTripVehicles = (clone $baseActiveQuery)->whereHas('activeCheckOut')->count();
        $maintenanceVehicles = (clone $baseActiveQuery)->where('status', 'maintenance')->count();
        $inPoolVehicles = max(0, $totalVehicles - $onTripVehicles - $maintenanceVehicles);

        $soldCount = $this->canManage
            ? Vehicle::query()
                ->whereNull('deleted_at')
                ->where(function ($w) {
                    $w->whereIn('status', ['sold', 'retired'])
                        ->orWhereNotNull('sold_at');
                })
                ->when(! empty($this->selectedCategories) && count($this->selectedCategories) < 4 && $this->category !== 'all', fn ($q) => $q->whereIn('category', $this->selectedCategories))
                ->when($this->category !== 'all' && $this->category !== 'custom' && empty($this->selectedCategories), fn ($q) => $q->where('category', $this->category))
                ->count()
            : 0;

        $complianceAlerts = $this->canManage
            ? VehicleDocument::with('vehicle')
                ->whereHas('vehicle', fn ($q) => $q->whereNull('deleted_at')->whereNotIn('status', ['sold', 'retired'])->whereNull('sold_at'))
                ->where('expired_date', '<=', now()->addDays(30))
                ->orderBy('expired_date')
                ->get()
            : collect();

        $metrics = [
            'total' => $totalVehicles,
            'on_trip' => $onTripVehicles,
            'in_pool' => $inPoolVehicles,
            'maintenance' => $maintenanceVehicles,
            'sold' => $soldCount,
            'alerts' => $complianceAlerts->count(),
        ];

        return view('livewire.vehicles.index', [
            'vehicles' => $query->paginate($this->perPage),
            'canManage' => $this->canManage,
            'fullFeature' => $this->canManage,
            'complianceAlerts' => $complianceAlerts,
            'metrics' => $metrics,
            'hasCustomCategory' => $this->hasCustomCategory,
            'hasCustomStatus' => $this->hasCustomStatus,
            'hasActiveFilters' => $this->hasActiveFilters,
        ]);
    }
}
