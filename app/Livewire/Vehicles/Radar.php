<?php

namespace App\Livewire\Vehicles;

use App\Infrastructure\Persistence\Eloquent\Models\Vehicle;
use App\Models\VehicleInspection;
use Livewire\Attributes\Url;
use Livewire\Component;

class Radar extends Component
{
    #[Url(as: 'q')]
    public string $q = '';

    #[Url(as: 'cat')]
    public string $category = 'all'; // 'all', 'passenger', 'commercial_truck', 'pickup', 'other'

    public bool $canManage = false;

    public function mount(): void
    {
        $user = auth()->user();
        if (! $user?->can('fleet.view') && ! $user?->can('fleet.manage')) {
            abort(403);
        }

        $this->canManage = $user->can('fleet.manage') ?? false;
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

    public function getOrphanCheckoutsProperty()
    {
        $activeCheckoutIds = Vehicle::query()
            ->whereNull('deleted_at')
            ->whereNotIn('status', ['sold', 'retired'])
            ->whereNull('sold_at')
            ->whereHas('activeCheckOut')
            ->with('activeCheckOut')
            ->get()
            ->pluck('activeCheckOut.id')
            ->filter();

        return VehicleInspection::query()
            ->where('inspection_type', VehicleInspection::TYPE_CHECK_OUT)
            ->whereDoesntHave('checkInRecord')
            ->whereNotIn('id', $activeCheckoutIds)
            ->with(['vehicle', 'inspector'])
            ->orderByDesc('checked_at')
            ->get();
    }

    public function render()
    {
        return view('livewire.vehicles.radar', [
            'metrics' => $this->metrics,
            'liveActiveTrips' => $this->liveActiveTrips,
            'orphanCheckouts' => $this->orphanCheckouts,
            'canManage' => $this->canManage,
        ]);
    }
}
