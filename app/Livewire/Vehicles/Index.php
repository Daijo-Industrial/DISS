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

    #[Url(as: 'cat')]
    public string $category = 'passenger';

    #[Url(as: 'tab')]
    public string $operationalTab = 'all'; // 'all', 'in_pool', 'on_trip', 'maintenance'

    #[Url(as: 'view')]
    public string $viewMode = 'table'; // 'grid' (Gallery Cards) or 'table' (Data Table)

    public function updatingCategory()
    {
        $this->resetPage();
    }

    public function setCategory(string $category): void
    {
        if (in_array($category, ['all', 'passenger', 'commercial_truck', 'pickup', 'other'], true)) {
            $this->category = $category;
            $this->resetPage();
        }
    }

    public function updatingOperationalTab()
    {
        $this->resetPage();
    }

    public function setOperationalTab(string $tab): void
    {
        $allowed = $this->canManage
            ? ['all', 'in_pool', 'on_trip', 'maintenance', 'sold']
            : ['all', 'in_pool', 'on_trip', 'maintenance'];

        if (in_array($tab, $allowed, true)) {
            $this->operationalTab = $tab;
            $this->resetPage();
        }
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
            )
            ->when($this->category !== 'all', function ($q) {
                $q->where('category', $this->category);
            })
            ->when($tab === 'sold', function ($q) {
                $q->where(function ($w) {
                    $w->whereIn('status', ['sold', 'retired'])
                        ->orWhereNotNull('sold_at');
                });
            }, function ($q) use ($tab) {
                // All active operational tabs strictly exclude sold and retired units
                $q->whereNotIn('status', ['sold', 'retired'])
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
            })
            ->when($this->canManage && $this->status !== 'all', function ($q) {
                $q->where('status', VehicleStatus::from($this->status));
            })
            ->orderBy($sortField, $sortDir);

        // Active Fleet KPI Metrics
        $baseActiveQuery = Vehicle::query()
            ->whereNull('deleted_at')
            ->whereNotIn('status', ['sold', 'retired'])
            ->whereNull('sold_at')
            ->when($this->category !== 'all', fn ($q) => $q->where('category', $this->category));

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
                ->when($this->category !== 'all', fn ($q) => $q->where('category', $this->category))
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
        ]);
    }
}
