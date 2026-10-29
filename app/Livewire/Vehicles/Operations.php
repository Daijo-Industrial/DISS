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

    #[Url(as: 'view')]
    public string $activityFilter = 'all'; // 'all', 'trips', 'defects', 'services'

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

    public function setCategory(string $category): void
    {
        $this->category = $category;
    }

    public function setActivityFilter(string $filter): void
    {
        $this->activityFilter = $filter;
    }

    public function resetFilters(): void
    {
        $this->q = '';
        $this->category = 'all';
        $this->activityFilter = 'all';
    }

    public function selectDate(string $date): void
    {
        try {
            $parsed = Carbon::parse($date);
            if (! $parsed->isFuture()) {
                $this->selectedDate = $parsed->toDateString();
            }
        } catch (\Throwable $e) {
            // Keep current date on parse error
        }
    }

    public function goToToday(): void
    {
        $this->selectedDate = now()->toDateString();
    }

    public function previousWeek(): void
    {
        try {
            $this->selectedDate = Carbon::parse($this->selectedDate)->subWeek()->toDateString();
        } catch (\Throwable $e) {
            $this->selectedDate = now()->subWeek()->toDateString();
        }
    }

    public function nextWeek(): void
    {
        if ($this->canGoNextWeek) {
            try {
                $next = Carbon::parse($this->selectedDate)->addWeek();
                if ($next->isFuture()) {
                    $this->selectedDate = now()->toDateString();
                } else {
                    $this->selectedDate = $next->toDateString();
                }
            } catch (\Throwable $e) {
                $this->selectedDate = now()->toDateString();
            }
        }
    }

    public function getCanGoNextWeekProperty(): bool
    {
        try {
            $selected = Carbon::parse($this->selectedDate);
            $startOfCurrentWeek = now()->startOfWeek(CarbonInterface::MONDAY);
            $startOfSelectedWeek = $selected->copy()->startOfWeek(CarbonInterface::MONDAY);

            return $startOfSelectedWeek->lt($startOfCurrentWeek);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function getWeekDaysProperty(): array
    {
        try {
            $selected = Carbon::parse($this->selectedDate);
        } catch (\Throwable $e) {
            $selected = now();
        }

        $startOfWeek = $selected->copy()->startOfWeek(CarbonInterface::MONDAY);
        $todayStr = now()->toDateString();

        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $day = $startOfWeek->copy()->addDays($i);
            $dateStr = $day->toDateString();
            $isFuture = $day->isFuture();
            $isSelected = $dateStr === $this->selectedDate;
            $isToday = $dateStr === $todayStr;

            $days[] = [
                'date' => $dateStr,
                'day_number' => $day->format('j'),
                'day_short' => $day->translatedFormat('D'),
                'is_selected' => $isSelected,
                'is_today' => $isToday,
                'is_future' => $isFuture,
            ];
        }

        return $days;
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
                $isDirectCheckIn = is_null($departure);
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
                    'is_direct_check_in' => $isDirectCheckIn,
                    'vehicle' => $checkIn->vehicle,
                    'driver_name' => $checkIn->driver_name ?: ($departure?->driver_name ?? '—'),
                    'trip_purpose' => $checkIn->trip_purpose ?: ($departure?->trip_purpose ?? '—'),
                    'dep_time' => $depTime,
                    'ret_time' => $retTime,
                    'duration_text' => $durationText,
                    'dep_odometer' => $isDirectCheckIn ? null : $departure?->odometer,
                    'ret_odometer' => $checkIn->odometer,
                    'distance' => $isDirectCheckIn ? null : ($checkIn->trip_distance ?? 0),
                    'dep_fuel' => $isDirectCheckIn ? null : $departure?->fuel_percentage,
                    'ret_fuel' => $checkIn->fuel_percentage,
                    'has_defect' => $hasDefect,
                    'defect_severity' => $defectSeverity,
                    'defect_notes' => $defectNotes,
                    'defect_photos' => $defectPhotos,
                    'inspector_name' => $checkIn->created_by ?: ($checkIn->inspector?->name ?? '—'),
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

        // Filter displayed items according to selected activity filter
        $displayedTrips = match ($this->activityFilter) {
            'defects' => $completedTrips->where('has_defect', true),
            'services' => collect(),
            default => $completedTrips,
        };

        $displayedServices = match ($this->activityFilter) {
            'trips', 'defects' => collect(),
            default => $services,
        };

        // Merge completed trips and services into a unified chronological timeline
        $timelineEvents = $displayedTrips->concat($displayedServices)->sortByDesc(function ($item) {
            if ($item->type === 'trip_completed') {
                return $item->ret_time ? $item->ret_time->timestamp : 0;
            }

            return $item->service_date ? Carbon::parse($item->service_date)->timestamp : 0;
        })->values();

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
            'completed_trips' => $displayedTrips->values(),
            'services' => $displayedServices->values(),
            'timeline_events' => $timelineEvents,
            'has_activity' => $timelineEvents->count() > 0,
            'has_any_day_activity' => ($totalTrips + $services->count()) > 0,
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

    public function getLiveActiveTripsProperty()
    {
        $query = Vehicle::query()
            ->whereNull('deleted_at')
            ->whereNotIn('status', ['sold', 'retired'])
            ->whereNull('sold_at')
            ->whereHas('activeCheckOut');

        if ($this->category !== 'all') {
            $query->where('category', $this->category);
        }

        if ($this->q !== '') {
            $term = $this->q;
            $query->where(function ($w) use ($term) {
                $w->where('plate_number', 'like', '%' . $term . '%')
                    ->orWhere('brand', 'like', '%' . $term . '%')
                    ->orWhere('model', 'like', '%' . $term . '%')
                    ->orWhere('driver_name', 'like', '%' . $term . '%');
            });
        }

        return $query->with(['activeCheckOut.inspector'])
            ->get()
            ->map(function ($vehicle) {
                $checkout = $vehicle->activeCheckOut;
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
                $checkout->vehicle = $vehicle;

                return $checkout;
            })
            ->sortByDesc(fn ($checkout) => $checkout->checked_at ?? $checkout->created_at)
            ->values();
    }

    public function render()
    {
        return view('livewire.vehicles.operations', [
            'canManage' => $this->canManage,
            'canViewCosts' => $this->canViewCosts,
            'activityFilter' => $this->activityFilter,
            'metrics' => $this->metrics,
            'liveActiveTrips' => $this->isToday ? $this->liveActiveTrips : collect(),
            'dayLog' => $this->dayLog,
        ]);
    }
}
