<?php

namespace App\Livewire\Vehicles;

use App\Infrastructure\Persistence\Eloquent\Models\Vehicle;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Scan QR Armada P2H')]
class Scan extends Component
{
    public string $manualInput = '';

    public ?string $errorMessage = null;

    public function mount(): void
    {
        $user = auth()->user();
        if (! ($user?->can('fleet.inspect') || $user?->can('fleet.manage') || $user?->can('fleet.view'))) {
            abort(403);
        }
    }

    /**
     * Resolve scanned QR code (camera payload).
     * ONLY matches based on UUID (raw UUID or URL containing UUID).
     * No fallback to license plate number, brand, model, or driver name.
     */
    public function resolve(string $code)
    {
        $this->errorMessage = null;
        $cleanCode = trim($code);

        if (empty($cleanCode)) {
            $this->errorMessage = __('fleet.scanner.not_found_alert');
            $this->dispatch('scan-failed');

            return;
        }

        // Only extract and match by UUID (e.g. raw UUID or embedded in URL from physical QR sticker)
        if (preg_match('/[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}/', $cleanCode, $matches)) {
            $uuid = strtolower($matches[0]);
            $vehicle = Vehicle::where('id', $uuid)->first();
            if ($vehicle) {
                return $this->redirect(route('vehicles.inspect', ['vehicle' => $vehicle->id]), navigate: true);
            }
        }

        // If not a valid UUID or vehicle with this UUID does not exist, fail immediately without fallback
        $this->errorMessage = __('fleet.scanner.not_found_code', ['code' => $cleanCode]);
        $this->dispatch('scan-failed');
    }

    /**
     * Manual search: only searches by plate number or vehicle details (brand, model, driver).
     * Explicitly rejects UUID queries.
     */
    public function searchManual()
    {
        $this->errorMessage = null;
        $searchTerm = trim($this->manualInput);

        if (empty($searchTerm)) {
            $this->errorMessage = __('fleet.scanner.not_found_alert');

            return;
        }

        // Explicitly disallow search by UUID
        if (preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/', $searchTerm)) {
            $this->errorMessage = __('fleet.scanner.uuid_not_allowed');

            return;
        }

        $normalizedSearch = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $searchTerm));

        // 1. Exact plate match (with or without spaces)
        $vehicle = Vehicle::whereNotIn('status', ['sold', 'retired'])
            ->where(function ($q) use ($normalizedSearch, $searchTerm) {
                if (! empty($normalizedSearch)) {
                    $q->whereRaw("REPLACE(UPPER(plate_number), ' ', '') = ?", [$normalizedSearch]);
                }
                $q->orWhere('plate_number', $searchTerm);
            })->first();

        if ($vehicle) {
            return $this->redirect(route('vehicles.inspect', ['vehicle' => $vehicle->id]), navigate: true);
        }

        // 2. Partial match on plate number or vehicle details (brand, model, driver_name)
        $matches = Vehicle::whereNotIn('status', ['sold', 'retired'])
            ->where(function ($q) use ($searchTerm, $normalizedSearch) {
                if (! empty($normalizedSearch)) {
                    $q->whereRaw("REPLACE(UPPER(plate_number), ' ', '') LIKE ?", ['%' . $normalizedSearch . '%']);
                }
                $q->orWhere('plate_number', 'like', '%' . $searchTerm . '%')
                    ->orWhere('brand', 'like', '%' . $searchTerm . '%')
                    ->orWhere('model', 'like', '%' . $searchTerm . '%')
                    ->orWhere('driver_name', 'like', '%' . $searchTerm . '%');
            })
            ->get();

        if ($matches->count() === 1) {
            return $this->redirect(route('vehicles.inspect', ['vehicle' => $matches->first()->id]), navigate: true);
        }

        if ($matches->isEmpty()) {
            $this->errorMessage = __('fleet.scanner.not_found_code', ['code' => $searchTerm]);
        }
    }

    /**
     * Directly select a vehicle from the manual picker list.
     */
    public function selectVehicle(string $id)
    {
        return $this->redirect(route('vehicles.inspect', ['vehicle' => $id]), navigate: true);
    }

    public function clearManualSearch()
    {
        $this->manualInput = '';
        $this->errorMessage = null;
    }

    public function render()
    {
        $searchTerm = trim($this->manualInput);
        $normalizedSearch = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $searchTerm));

        $query = Vehicle::query()
            ->whereNotIn('status', ['sold', 'retired'])
            ->orderBy('plate_number');

        if (! empty($searchTerm)) {
            // If user typed a UUID, return no results as UUID searching is disabled
            if (preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/', $searchTerm)) {
                $query->whereRaw('1 = 0');
            } else {
                $query->where(function ($q) use ($searchTerm, $normalizedSearch) {
                    if (! empty($normalizedSearch)) {
                        $q->whereRaw("REPLACE(UPPER(plate_number), ' ', '') LIKE ?", ['%' . $normalizedSearch . '%']);
                    }
                    $q->orWhere('plate_number', 'like', '%' . $searchTerm . '%')
                        ->orWhere('brand', 'like', '%' . $searchTerm . '%')
                        ->orWhere('model', 'like', '%' . $searchTerm . '%')
                        ->orWhere('driver_name', 'like', '%' . $searchTerm . '%');
                });
            }
        }

        $vehicles = $query->take(12)->get();

        return view('livewire.vehicles.scan', [
            'recentVehicles' => $vehicles,
            'isSearching' => ! empty($searchTerm),
        ]);
    }
}
