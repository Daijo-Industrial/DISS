<?php

namespace App\Livewire\Vehicles;

use App\Infrastructure\Persistence\Eloquent\Models\Vehicle;
use App\Models\ServiceRecord;
use App\Models\VehicleInspection;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Livewire\Attributes\Url;
use Livewire\Component;

class Operations extends Component
{
    #[Url(as: 'date')]
    public string $selectedDate = ''; // Y-m-d

    #[Url(as: 'q')]
    public string $q = '';

    #[Url(as: 'cat')]
    public string $category = 'all'; // 'all', 'passenger', 'commercial_truck', 'pickup', 'other'

    public bool $canManage = false;

    public bool $canViewCosts = false;

    public function mount(): void
    {
        $user = auth()->user();
        if (! $user?->can('fleet.manage')) {
            abort(403);
        }

        $this->canManage = true;
        $this->canViewCosts = $user->can('fleet.view-costs') ?? true;

        if (empty($this->selectedDate)) {
            $this->selectedDate = now()->toDateString();
        } else {
            try {
                $this->selectedDate = Carbon::parse($this->selectedDate)->toDateString();
            } catch (\Throwable $e) {
                $this->selectedDate = now()->toDateString();
            }
        }
    }

    public function setDatePreset(string $preset): void
    {
        if ($preset === 'today') {
            $this->selectedDate = now()->toDateString();
        } elseif ($preset === 'yesterday') {
            $this->selectedDate = now()->subDay()->toDateString();
        }
    }

    public function previousDay(): void
    {
        try {
            $this->selectedDate = Carbon::parse($this->selectedDate)->subDay()->toDateString();
        } catch (\Throwable $e) {
            $this->selectedDate = now()->subDay()->toDateString();
        }
    }

    public function nextDay(): void
    {
        if ($this->canGoNextDay) {
            try {
                $this->selectedDate = Carbon::parse($this->selectedDate)->addDay()->toDateString();
            } catch (\Throwable $e) {
                $this->selectedDate = now()->toDateString();
            }
        }
    }

    public function getIsTodayProperty(): bool
    {
        return $this->selectedDate === now()->toDateString();
    }

    public function getIsYesterdayProperty(): bool
    {
        return $this->selectedDate === now()->subDay()->toDateString();
    }

    public function getCanGoNextDayProperty(): bool
    {
        try {
            $date = Carbon::parse($this->selectedDate);

            return ! $date->isToday() && ! $date->isFuture();
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function filterVehicleQuery($query, string $vehicleRelation = 'vehicle')
    {
        $query->whereHas($vehicleRelation, function ($vq) {
            $vq->whereNull('deleted_at')
                ->whereNotIn('status', ['sold', 'retired'])
                ->whereNull('sold_at');

            if ($this->category !== 'all') {
                $vq->where('category', $this->category);
            }
        });

        if ($this->q !== '') {
            $term = $this->q;
            $query->whereHas($vehicleRelation, function ($vq) use ($term) {
                $vq->where(function ($w) use ($term) {
                    $w->where('plate_number', 'like', '%' . $term . '%')
                        ->orWhere('brand', 'like', '%' . $term . '%')
                        ->orWhere('model', 'like', '%' . $term . '%')
                        ->orWhere('driver_name', 'like', '%' . $term . '%');
                });
            });
        }

        return $query;
    }

    public function getLiveActiveTripsProperty()
    {
        $query = VehicleInspection::query()
            ->where('inspection_type', VehicleInspection::TYPE_CHECK_OUT)
            ->whereDoesntHave('checkInRecord');

        $this->filterVehicleQuery($query, 'vehicle');

        return $query->with(['vehicle', 'inspector'])
            ->orderByDesc('checked_at')
            ->get()
            ->map(function ($checkout) {
                $departureTime = $checkout->checked_at ?? $checkout->created_at;
                $elapsedMinutes = $departureTime ? (int) $departureTime->diffInMinutes(now()) : 0;

                if ($elapsedMinutes < 60) {
                    $elapsedText = $elapsedMinutes . ' ' . __('fleet.operations.minutes');
                } elseif ($elapsedMinutes < 1440) {
                    $hours = floor($elapsedMinutes / 60);
                    $mins = $elapsedMinutes % 60;
                    $elapsedText = $hours . ' ' . __('fleet.operations.hours') . ($mins > 0 ? ' ' . $mins . ' ' . __('fleet.operations.minutes') : '');
                } else {
                    $days = floor($elapsedMinutes / 1440);
                    $hours = floor(($elapsedMinutes % 1440) / 60);
                    $elapsedText = $days . ' ' . __('fleet.operations.days') . ($hours > 0 ? ' ' . $hours . ' ' . __('fleet.operations.hours') : '');
                }

                $checkout->elapsed_text = $elapsedText;
                $checkout->elapsed_minutes = $elapsedMinutes;

                return $checkout;
            });
    }

    public function getDayLogProperty(): object
    {
        $dateStr = $this->selectedDate;
        try {
            $dateCarbon = Carbon::parse($dateStr);
        } catch (\Throwable $e) {
            $dateCarbon = now();
            $dateStr = $dateCarbon->toDateString();
        }

        $startDate = $dateCarbon->copy()->startOfDay();
        $endDate = $dateCarbon->copy()->endOfDay();

        // 1. Completed trips on this specific day (check_in occurred today)
        $inspectionsQuery = VehicleInspection::query()
            ->where('inspection_type', VehicleInspection::TYPE_CHECK_IN)
            ->whereBetween('checked_at', [$startDate, $endDate]);

        $this->filterVehicleQuery($inspectionsQuery, 'vehicle');

        $completedTrips = $inspectionsQuery->with(['vehicle', 'inspector', 'parentCheckOut.inspector'])
            ->orderByDesc('checked_at')
            ->get()
            ->map(function ($checkIn) {
                $departure = $checkIn->parentCheckOut;
                $depTime = $departure ? ($departure->checked_at ?? $departure->created_at) : null;
                $retTime = $checkIn->checked_at ?? $checkIn->created_at;

                $durationText = ($depTime && $retTime)
                    ? $depTime->diffForHumans($retTime, ['syntax' => CarbonInterface::DIFF_ABSOLUTE, 'parts' => 2])
                    : null;

                $hasDefect = ($checkIn->severity && $checkIn->severity !== VehicleInspection::SEVERITY_NONE)
                    || ($departure && $departure->severity && $departure->severity !== VehicleInspection::SEVERITY_NONE);

                $defectSeverity = ($checkIn->severity && $checkIn->severity !== VehicleInspection::SEVERITY_NONE)
                    ? $checkIn->severity
                    : ($departure?->severity ?? VehicleInspection::SEVERITY_NONE);

                $defectNotes = $checkIn->defect_notes ?: $departure?->defect_notes;
                $defectPhotos = ! empty($checkIn->defect_photos) ? $checkIn->defect_photos : ($departure?->defect_photos ?? []);

                return (object) [
                    'id' => $checkIn->id,
                    'type' => 'trip_completed',
                    'vehicle' => $checkIn->vehicle,
                    'driver_name' => $checkIn->driver_name ?: ($departure?->driver_name ?? '—'),
                    'trip_purpose' => $departure?->trip_purpose ?? '—',
                    'dep_time' => $depTime,
                    'ret_time' => $retTime,
                    'duration_text' => $durationText,
                    'dep_odometer' => $departure?->odometer,
                    'ret_odometer' => $checkIn->odometer,
                    'distance' => $checkIn->trip_distance ?? 0,
                    'dep_fuel' => $departure?->fuel_percentage,
                    'ret_fuel' => $checkIn->fuel_percentage,
                    'has_defect' => $hasDefect,
                    'defect_severity' => $defectSeverity,
                    'defect_notes' => $defectNotes,
                    'defect_photos' => $defectPhotos,
                    'inspector_name' => $checkIn->inspector?->name ?: ($checkIn->created_by ?? '—'),
                    'check_in' => $checkIn,
                    'check_out' => $departure,
                ];
            });

        // 2. Workshop services on this specific day
        $servicesQuery = ServiceRecord::query()
            ->whereDate('service_date', $dateStr);

        $this->filterVehicleQuery($servicesQuery, 'vehicle');

        $services = $servicesQuery->with(['vehicle', 'items'])
            ->orderByDesc('service_date')
            ->get()
            ->map(function ($service) {
                return (object) [
                    'id' => $service->id,
                    'type' => 'service',
                    'vehicle' => $service->vehicle,
                    'workshop' => $service->workshop ?: __('fleet.operations.partner_workshop'),
                    'odometer' => $service->odometer,
                    'total_cost' => $service->total_cost,
                    'notes' => $service->notes,
                    'items_count' => $service->items->count(),
                    'created_by' => $service->created_by,
                    'service_date' => $service->service_date,
                    'service_record' => $service,
                ];
            });

        $totalDistance = $completedTrips->sum('distance');
        $totalDefects = $completedTrips->where('has_defect', true)->count();
        $totalTrips = $completedTrips->count();

        if ($this->isToday) {
            $label = __('fleet.operations.filter_today') . ' — ' . $dateCarbon->translatedFormat('l, d F Y');
        } elseif ($this->isYesterday) {
            $label = __('fleet.operations.filter_yesterday') . ' — ' . $dateCarbon->translatedFormat('l, d F Y');
        } else {
            $label = $dateCarbon->translatedFormat('l, d F Y');
        }

        return (object) [
            'date_string' => $dateStr,
            'date_carbon' => $dateCarbon,
            'label' => $label,
            'is_today' => $this->isToday,
            'is_yesterday' => $this->isYesterday,
            'total_trips' => $totalTrips,
            'total_distance' => $totalDistance,
            'total_defects' => $totalDefects,
            'total_services' => $services->count(),
            'completed_trips' => $completedTrips->values(),
            'services' => $services->values(),
            'has_activity' => ($totalTrips + $services->count()) > 0,
        ];
    }

    public function getMetricsProperty(): array
    {
        $baseActiveQuery = Vehicle::query()
            ->whereNull('deleted_at')
            ->whereNotIn('status', ['sold', 'retired'])
            ->whereNull('sold_at');

        $totalVehicles = (clone $baseActiveQuery)->count();
        $onTripVehicles = (clone $baseActiveQuery)->whereHas('activeCheckOut')->count();
        $maintenanceVehicles = (clone $baseActiveQuery)->where('status', 'maintenance')->count();
        $inPoolVehicles = max(0, $totalVehicles - $onTripVehicles - $maintenanceVehicles);

        return [
            'total' => $totalVehicles,
            'on_trip' => $onTripVehicles,
            'in_pool' => $inPoolVehicles,
            'maintenance' => $maintenanceVehicles,
        ];
    }

    public function render()
    {
        return view('livewire.vehicles.operations', [
            'canManage' => $this->canManage,
            'canViewCosts' => $this->canViewCosts,
            'metrics' => $this->metrics,
            'liveActiveTrips' => $this->liveActiveTrips,
            'dayLog' => $this->dayLog,
        ]);
    }
}
