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

    public bool $isSearching = false;

    /**
     * Resolve scanned QR code or manual input to redirect to P2H inspection form.
     */
    public function resolve(string $code)
    {
        $this->errorMessage = null;
        $cleanCode = trim($code);

        if (empty($cleanCode)) {
            $this->errorMessage = 'Silakan pindai stiker QR armada atau masukkan nomor polisi.';

            return;
        }

        // 1. Try to extract UUID (e.g. raw UUID or embedded in URL)
        if (preg_match('/[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}/', $cleanCode, $matches)) {
            $uuid = strtolower($matches[0]);
            $vehicle = Vehicle::where('id', $uuid)->first();
            if ($vehicle) {
                return $this->redirect(route('vehicles.inspect', ['vehicle' => $vehicle->id]), navigate: true);
            }
        }

        // 2. Try match by plate number (raw or cleaned)
        $normalizedInput = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $cleanCode));
        $vehicle = Vehicle::all()->first(function ($v) use ($normalizedInput, $cleanCode) {
            $plateClean = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $v->plate_number));

            return $plateClean === $normalizedInput || strcasecmp($v->plate_number, $cleanCode) === 0;
        });

        if ($vehicle) {
            return $this->redirect(route('vehicles.inspect', ['vehicle' => $vehicle->id]), navigate: true);
        }

        $this->errorMessage = "Armada dengan kode/plat '{$cleanCode}' tidak ditemukan dalam sistem DISS.";
    }

    public function searchManual()
    {
        return $this->resolve($this->manualInput);
    }

    public function render()
    {
        $recentVehicles = Vehicle::query()
            ->whereNull('deleted_at')
            ->whereNotIn('status', ['sold', 'retired'])
            ->orderBy('plate_number')
            ->take(8)
            ->get();

        return view('livewire.vehicles.scan', [
            'recentVehicles' => $recentVehicles,
        ]);
    }
}
