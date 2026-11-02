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
    #[Url(as: 'scope')]
    public string $scope = 'day'; // 'day', 'week', 'month', 'custom'

    #[Url(as: 'date')]
    public string $selectedDate = ''; // Y-m-d

    #[Url(as: 'month')]
    public string $selectedMonth = ''; // Y-m

    #[Url(as: 'start')]
    public string $startDate = ''; // Y-m-d

    #[Url(as: 'end')]
    public string $endDate = ''; // Y-m-d

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

        if (empty($this->selectedMonth)) {
            $this->selectedMonth = now()->format('Y-m');
        } else {
            try {
                $this->selectedMonth = Carbon::parse($this->selectedMonth . '-01')->format('Y-m');
            } catch (\Throwable $e) {
                $this->selectedMonth = now()->format('Y-m');
            }
        }

        if (empty($this->startDate)) {
            $this->startDate = now()->subDays(6)->toDateString();
        } else {
            try {
                $this->startDate = Carbon::parse($this->startDate)->toDateString();
            } catch (\Throwable $e) {
                $this->startDate = now()->subDays(6)->toDateString();
            }
        }

        if (empty($this->endDate)) {
            $this->endDate = now()->toDateString();
        } else {
            try {
                $this->endDate = Carbon::parse($this->endDate)->toDateString();
            } catch (\Throwable $e) {
                $this->endDate = now()->toDateString();
            }
        }

        if (! in_array($this->scope, ['day', 'week', 'month', 'custom'], true)) {
            $this->scope = 'day';
        }
    }

    public function setScope(string $scope): void
    {
        if (in_array($scope, ['day', 'week', 'month', 'custom'], true)) {
            $this->scope = $scope;
        }
    }

    public function getHeaderTitleProperty(): string
    {
        return __('fleet.operations.title_day');
    }

    public function getHeaderSubtitleProperty(): string
    {
        return __('fleet.operations.subtitle_day');
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
        if ($this->activityFilter === $filter) {
            $this->activityFilter = 'all';
        } else {
            $this->activityFilter = $filter;
        }
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
        $endOfWeek = $startOfWeek->copy()->addDays(6)->endOfDay();
        $todayStr = now()->toDateString();

        // Query all days in this 7-day strip that have defect findings
        $defectQuery = VehicleInspection::query()
            ->where('inspection_type', VehicleInspection::TYPE_CHECK_IN)
            ->where(function ($q) use ($startOfWeek, $endOfWeek) {
                $startStr = $startOfWeek->copy()->startOfDay()->toDateTimeString();
                $endStr = $endOfWeek->toDateTimeString();
                $q->whereBetween('checked_at', [$startStr, $endStr])
                    ->orWhere(function ($sub) use ($startStr, $endStr) {
                        $sub->whereNull('checked_at')
                            ->whereBetween('created_at', [$startStr, $endStr]);
                    });
            })
            ->where(function ($q) {
                $q->whereIn('severity', [VehicleInspection::SEVERITY_MINOR, VehicleInspection::SEVERITY_CRITICAL])
                    ->orWhere(function ($sub) {
                        $sub->whereNotNull('defect_notes')
                            ->where('defect_notes', '!=', '');
                    })
                    ->orWhereHas('parentCheckOut', function ($pq) {
                        $pq->whereIn('severity', [VehicleInspection::SEVERITY_MINOR, VehicleInspection::SEVERITY_CRITICAL])
                            ->orWhere(function ($sub) {
                                $sub->whereNotNull('defect_notes')
                                    ->where('defect_notes', '!=', '');
                            });
                    });
            });

        $this->filterVehicleQuery($defectQuery, 'vehicle');

        $defectDates = $defectQuery
            ->selectRaw('DISTINCT DATE(COALESCE(checked_at, created_at)) as defect_date')
            ->pluck('defect_date')
            ->map(fn ($d) => (string) $d)
            ->all();

        // Also check unclosed checkouts in this week with defects
        $unclosedDefectQuery = VehicleInspection::query()
            ->where('inspection_type', VehicleInspection::TYPE_CHECK_OUT)
            ->whereDoesntHave('checkInRecord')
            ->where(function ($q) use ($startOfWeek, $endOfWeek) {
                $startStr = $startOfWeek->copy()->startOfDay()->toDateTimeString();
                $endStr = $endOfWeek->toDateTimeString();
                $q->whereBetween('checked_at', [$startStr, $endStr])
                    ->orWhere(function ($sub) use ($startStr, $endStr) {
                        $sub->whereNull('checked_at')
                            ->whereBetween('created_at', [$startStr, $endStr]);
                    });
            })
            ->where(function ($q) {
                $q->whereIn('severity', [VehicleInspection::SEVERITY_MINOR, VehicleInspection::SEVERITY_CRITICAL])
                    ->orWhere(function ($sub) {
                        $sub->whereNotNull('defect_notes')
                            ->where('defect_notes', '!=', '');
                    });
            });

        $this->filterVehicleQuery($unclosedDefectQuery, 'vehicle');

        $unclosedDefectDates = $unclosedDefectQuery
            ->selectRaw('DISTINCT DATE(COALESCE(checked_at, created_at)) as defect_date')
            ->pluck('defect_date')
            ->map(fn ($d) => (string) $d)
            ->all();

        $allDefectDates = array_unique(array_merge($defectDates, $unclosedDefectDates));

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
                'has_defect' => in_array($dateStr, $allDefectDates, true),
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

    public function goToThisWeek(): void
    {
        $this->selectedDate = now()->toDateString();
    }

    public function previousMonth(): void
    {
        try {
            $carbon = Carbon::parse($this->selectedMonth . '-01')->subMonth();
            $this->selectedMonth = $carbon->format('Y-m');
        } catch (\Throwable $e) {
            $this->selectedMonth = now()->subMonth()->format('Y-m');
        }
    }

    public function nextMonth(): void
    {
        if ($this->canGoNextMonth) {
            try {
                $carbon = Carbon::parse($this->selectedMonth . '-01')->addMonth();
                if ($carbon->isFuture()) {
                    $this->selectedMonth = now()->format('Y-m');
                } else {
                    $this->selectedMonth = $carbon->format('Y-m');
                }
            } catch (\Throwable $e) {
                $this->selectedMonth = now()->format('Y-m');
            }
        }
    }

    public function goToThisMonth(): void
    {
        $this->selectedMonth = now()->format('Y-m');
    }

    public function getCanGoNextMonthProperty(): bool
    {
        try {
            $selected = Carbon::parse($this->selectedMonth . '-01')->startOfMonth();
            $current = now()->startOfMonth();

            return $selected->lt($current);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function setCustomPreset(string $preset): void
    {
        $today = now();
        switch ($preset) {
            case 'last_7_days':
                $this->startDate = $today->copy()->subDays(6)->toDateString();
                $this->endDate = $today->toDateString();
                break;
            case 'last_30_days':
                $this->startDate = $today->copy()->subDays(29)->toDateString();
                $this->endDate = $today->toDateString();
                break;
            case 'this_month':
                $this->startDate = $today->copy()->startOfMonth()->toDateString();
                $this->endDate = $today->toDateString();
                break;
            case 'previous_month':
                $prev = $today->copy()->subMonth();
                $this->startDate = $prev->startOfMonth()->toDateString();
                $this->endDate = $prev->endOfMonth()->toDateString();
                break;
        }
    }

    public function resolvePeriodRange(): array
    {
        $now = now();

        if ($this->scope === 'week') {
            try {
                $dateCarbon = Carbon::parse($this->selectedDate);
            } catch (\Throwable $e) {
                $dateCarbon = $now;
            }

            $start = $dateCarbon->copy()->startOfWeek(CarbonInterface::MONDAY)->startOfDay();
            $end = $dateCarbon->copy()->endOfWeek(CarbonInterface::SUNDAY)->endOfDay();
            $label = $start->translatedFormat('d M') . ' – ' . $end->translatedFormat('d M Y');

            return [$start, $end, $label];
        }

        if ($this->scope === 'month') {
            try {
                $monthCarbon = Carbon::parse($this->selectedMonth . '-01');
            } catch (\Throwable $e) {
                $monthCarbon = $now;
            }

            $start = $monthCarbon->copy()->startOfMonth()->startOfDay();
            $end = $monthCarbon->copy()->endOfMonth()->endOfDay();
            $label = $monthCarbon->translatedFormat('F Y');

            return [$start, $end, $label];
        }

        if ($this->scope === 'custom') {
            try {
                $start = Carbon::parse($this->startDate)->startOfDay();
            } catch (\Throwable $e) {
                $start = $now->copy()->subDays(6)->startOfDay();
            }

            try {
                $end = Carbon::parse($this->endDate)->endOfDay();
            } catch (\Throwable $e) {
                $end = $now->copy()->endOfDay();
            }

            if ($end->lt($start)) {
                $temp = $start;
                $start = $end->copy()->startOfDay();
                $end = $temp->copy()->endOfDay();
            }

            // Cap at 90 days to prevent excessive payload
            if ($start->diffInDays($end) > 90) {
                $start = $end->copy()->subDays(90)->startOfDay();
            }

            $label = $start->translatedFormat('d M Y') . ' – ' . $end->translatedFormat('d M Y');

            return [$start, $end, $label];
        }

        // Default: 'day'
        try {
            $dateCarbon = Carbon::parse($this->selectedDate);
        } catch (\Throwable $e) {
            $dateCarbon = $now;
        }

        $start = $dateCarbon->copy()->startOfDay();
        $end = $dateCarbon->copy()->endOfDay();

        if ($this->selectedDate === $now->toDateString()) {
            $label = __('fleet.operations.filter_today') . ' — ' . $dateCarbon->translatedFormat('l, d F Y');
        } elseif ($this->selectedDate === $now->copy()->subDay()->toDateString()) {
            $label = __('fleet.operations.filter_yesterday') . ' — ' . $dateCarbon->translatedFormat('l, d F Y');
        } else {
            $label = $dateCarbon->translatedFormat('l, d F Y');
        }

        return [$start, $end, $label];
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

    public function getPeriodLogProperty(): object
    {
        [$startDate, $endDate, $label] = $this->resolvePeriodRange();

        // 1. Completed trips in this period
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
                $dateKey = $retTime ? $retTime->toDateString() : ($checkIn->created_at ? $checkIn->created_at->toDateString() : 'unknown');

                return (object) [
                    'id' => $checkIn->id,
                    'type' => 'trip_completed',
                    'date_key' => $dateKey,
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

        // 2. Workshop services in this period
        $servicesQuery = ServiceRecord::query()
            ->whereBetween('service_date', [$startDate->toDateString(), $endDate->toDateString()]);

        $this->filterVehicleQuery($servicesQuery, 'vehicle');

        $services = $servicesQuery->with(['vehicle', 'items'])
            ->orderByDesc('service_date')
            ->get()
            ->map(function ($service) {
                $dateKey = $service->service_date ? Carbon::parse($service->service_date)->toDateString() : 'unknown';

                return (object) [
                    'id' => $service->id,
                    'type' => 'service',
                    'date_key' => $dateKey,
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
        $totalServiceCost = $services->sum('total_cost');

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

        // Group by date for multi-day views
        $groupedTimeline = $timelineEvents->groupBy('date_key')->map(function ($items, $dateStr) {
            try {
                $dateCarbon = Carbon::parse($dateStr);
            } catch (\Throwable $e) {
                $dateCarbon = now();
            }

            $dayDistance = $items->where('type', 'trip_completed')->sum('distance');
            $dayDefects = $items->where('type', 'trip_completed')->where('has_defect', true)->count();
            $dayServices = $items->where('type', 'service')->count();
            $dayTrips = $items->where('type', 'trip_completed')->count();

            if ($dateStr === now()->toDateString()) {
                $dateLabel = __('fleet.operations.filter_today') . ' — ' . $dateCarbon->translatedFormat('l, d F Y');
            } elseif ($dateStr === now()->subDay()->toDateString()) {
                $dateLabel = __('fleet.operations.filter_yesterday') . ' — ' . $dateCarbon->translatedFormat('l, d F Y');
            } else {
                $dateLabel = $dateCarbon->translatedFormat('l, d F Y');
            }

            return (object) [
                'date_string' => $dateStr,
                'date_carbon' => $dateCarbon,
                'date_label' => $dateLabel,
                'is_today' => $dateStr === now()->toDateString(),
                'is_yesterday' => $dateStr === now()->subDay()->toDateString(),
                'total_trips' => $dayTrips,
                'total_distance' => $dayDistance,
                'total_defects' => $dayDefects,
                'total_services' => $dayServices,
                'events' => $items->values(),
            ];
        });

        return (object) [
            'scope' => $this->scope,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'date_string' => $this->selectedDate,
            'date_carbon' => $startDate,
            'label' => $label,
            'is_today' => $this->isToday,
            'is_yesterday' => $this->isYesterday,
            'total_trips' => $totalTrips,
            'total_distance' => $totalDistance,
            'total_defects' => $totalDefects,
            'total_services' => $services->count(),
            'total_service_cost' => $totalServiceCost,
            'completed_trips' => $displayedTrips->values(),
            'services' => $displayedServices->values(),
            'timeline_events' => $timelineEvents,
            'grouped_timeline' => $groupedTimeline,
            'has_activity' => $timelineEvents->count() > 0,
            'has_any_day_activity' => ($totalTrips + $services->count()) > 0,
        ];
    }

    public function getDayLogProperty(): object
    {
        return $this->periodLog;
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
            'periodLog' => $this->periodLog,
        ]);
    }
}
