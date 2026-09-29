<?php

namespace App\Livewire\Vehicles;

use App\Enums\VehicleStatus;
use App\Infrastructure\Persistence\Eloquent\Models\Vehicle;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Form extends Component
{
    public ?Vehicle $vehicle = null;

    public string $driver_name = '';

    public string $plate_number = '';

    public ?string $brand = null;

    public ?string $model = null;

    public string $category = 'passenger';

    public string $fuel_type = 'petrol';

    public bool $requires_kir = false;

    public ?int $year = null;

    public ?string $vin = null;

    public int $odometer = 0;

    public string $status = 'active';

    public bool $fullFeature = false;

    public ?string $sold_at = null;

    public function updatedCategory(string $value): void
    {
        if ($value === 'commercial_truck') {
            $this->requires_kir = true;
        }
    }

    public function updatedPlateNumber(string $value): void
    {
        $this->plate_number = $this->normalizePlateNumber($value);
    }

    protected function rules(): array
    {
        $plateRegex = config('fleet.plate.regex', '^[A-Z]{1,2}\s[1-9][0-9]{0,3}\s[A-Z]{1,4}$');

        $baseRules = [
            'driver_name' => ['nullable', 'string', 'max:255'],
            'plate_number' => [
                'required',
                'string',
                'max:20',
                'regex:/' . $plateRegex . '/',
                Rule::unique('vehicles', 'plate_number')
                    ->ignore($this->vehicle?->id)
                    ->whereNull('deleted_at'),
            ],
        ];

        if (! $this->fullFeature) {
            return $baseRules;
        }

        return array_merge($baseRules, [
            'brand' => ['nullable', 'string', 'max:80'],
            'model' => ['nullable', 'string', 'max:120'],
            'category' => ['required', 'string', 'in:passenger,commercial_truck,pickup,other'],
            'fuel_type' => ['required', 'string', 'in:petrol,diesel,ev'],
            'requires_kir' => ['boolean'],
            'year' => ['nullable', 'integer', 'min:1900', 'max:' . (now()->year + 1)],
            'vin' => ['nullable', 'string', 'max:50'],
            'odometer' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', Rule::in(array_column(VehicleStatus::cases(), 'value'))],
            'sold_at' => ['nullable', 'date', 'before_or_equal:today', 'required_if:status,sold'],
        ]);
    }

    protected function messages(): array
    {
        return [
            'plate_number.required' => 'Plat nomor kendaraan wajib diisi.',
            'plate_number.regex' => 'Format plat nomor harus sesuai standar Indonesia (contoh: B 1234 XYZ). Terdiri dari 1-2 huruf wilayah, 1-4 digit angka, dan 1-4 huruf seri.',
            'plate_number.unique' => 'Plat nomor ini sudah terdaftar pada armada lain.',
        ];
    }

    public function mount(?Vehicle $vehicle): void
    {
        $user = auth()->user();
        $this->fullFeature = $user !== null && (
            $user->hasRole('super-admin') || ($user->department?->name === 'PERSONALIA')
        );

        if ($vehicle?->exists) {
            $this->vehicle = $vehicle;

            // Fill only the fields allowed for this role
            $fields = $this->fullFeature
                ? ['driver_name', 'plate_number', 'brand', 'model', 'category', 'fuel_type', 'requires_kir', 'year', 'vin', 'odometer', 'status', 'sold_at']
                : ['driver_name', 'plate_number'];

            $data = Arr::only($vehicle->toArray(), $fields);

            $this->fill($data);
        }
    }

    public function save(): void
    {
        $this->plate_number = $this->normalizePlateNumber($this->plate_number);

        $this->validate();

        $payload = [
            'driver_name' => $this->driver_name,
            'plate_number' => $this->plate_number,
            'brand' => $this->brand,
            'model' => $this->model,
            'category' => $this->category,
            'fuel_type' => $this->fuel_type,
            'requires_kir' => (bool) $this->requires_kir,
            'year' => $this->year ?: null,
            'vin' => $this->vin ?: null,
            'odometer' => (int) ($this->odometer ?: 0),
            'status' => $this->status,
            'sold_at' => $this->status === 'sold' ? $this->sold_at ?? now()->toDateString() : null,
        ];

        $allowedKeys = $this->fullFeature
            ? array_keys($payload)
            : ['driver_name', 'plate_number']; // hard guard against mass assignment

        // Keep only allowed keys
        $data = array_intersect_key($payload, array_flip($allowedKeys));

        // Enforce safe default status for non-fullfeature creates
        if (! $this->fullFeature && ! $this->vehicle?->exists) {
            $data['status'] = 'active';
        }

        DB::transaction(function () use ($data) {
            if ($this->vehicle?->exists) {
                $this->vehicle->update($data);
                session()->flash('success', 'Vehicle updated.');
            } else {
                $this->vehicle = Vehicle::create($data);
                session()->flash('success', 'Vehicle created.');
            }
        });

        if (! $this->fullFeature) {
            $this->redirectRoute('vehicles.index', navigate: true);

            return;
        }

        $this->redirectRoute('vehicles.show', ['vehicle' => $this->vehicle], navigate: true);
    }

    public function delete(): void
    {
        if (! $this->fullFeature) {
            abort(403);
        }

        if ($this->vehicle?->exists) {
            $this->vehicle->delete();
            session()->flash('success', 'Vehicle deleted.');
            $this->redirectRoute('vehicles.index', navigate: true);
        }
    }

    public function render(): View
    {
        return view('livewire.vehicles.form', [
            'fullFeature' => $this->fullFeature,
        ]);
    }

    /**
     * Normalize plate number into standard spaced format (e.g. "b1234xyz" -> "B 1234 XYZ").
     */
    private function normalizePlateNumber(string $plate): string
    {
        $clean = strtoupper(trim(preg_replace('/\s+/', ' ', $plate)));
        $noSpace = str_replace(' ', '', $clean);

        if (preg_match('/^([A-Z]{1,2})([1-9][0-9]{0,3})([A-Z]{1,4})$/', $noSpace, $matches)) {
            return $matches[1] . ' ' . $matches[2] . ' ' . $matches[3];
        }

        return $clean;
    }
}
