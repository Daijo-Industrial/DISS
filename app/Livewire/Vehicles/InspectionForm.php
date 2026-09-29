<?php

namespace App\Livewire\Vehicles;

use App\Enums\VehicleStatus;
use App\Infrastructure\Persistence\Eloquent\Models\Vehicle;
use App\Models\VehicleInspection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithFileUploads;

class InspectionForm extends Component
{
    use WithFileUploads;

    public Vehicle $vehicle;

    public string $type = VehicleInspection::TYPE_CHECK_OUT;

    public ?int $parent_inspection_id = null;

    public ?VehicleInspection $parentInspection = null;

    public string $driver_name = '';

    public int $odometer = 0;

    public int $fuel_percentage = 100;

    public array $checklist = [];

    public string $severity = VehicleInspection::SEVERITY_NONE;

    public ?string $defect_notes = null;

    public $photos = [];

    public ?string $trip_purpose = null;

    public function mount(Vehicle $vehicle, ?string $type = null): void
    {
        $this->vehicle = $vehicle;
        if ($type && in_array($type, [VehicleInspection::TYPE_CHECK_OUT, VehicleInspection::TYPE_CHECK_IN], true)) {
            $this->type = $type;
        } else {
            $this->type = $vehicle->is_out_on_trip
                ? VehicleInspection::TYPE_CHECK_IN
                : VehicleInspection::TYPE_CHECK_OUT;
        }

        // Inisialisasi checklist harian sesuai catatan
        $this->checklist = [
            'body' => [
                'label' => 'Bodi Eksterior',
                'description' => 'Kondisi fisik luar (baret, penyok, kebersihan kabin luar)',
                'status' => 'ok',
                'notes' => '',
            ],
            'tires' => [
                'label' => 'Kondisi Ban',
                'description' => 'Tekanan angin, keausan tapak, kondisi ban cadangan',
                'status' => 'ok',
                'notes' => '',
            ],
            'interior' => [
                'label' => 'Isi Dalam Mobil',
                'description' => 'Kebersihan interior, dongkrak, segitiga pengaman, toolkit, APAR',
                'status' => 'ok',
                'notes' => '',
            ],
            'battery_fuel' => [
                'label' => 'Baterai / Bensin',
                'description' => 'Level BBM mencukupi, voltase/indikator aki dan baterai normal',
                'status' => 'ok',
                'notes' => '',
            ],
            'headlights' => [
                'label' => 'Lampu Depan',
                'description' => 'Lampu dekat & lampu jauh berfungsi normal',
                'status' => 'ok',
                'notes' => '',
            ],
            'brake_lights' => [
                'label' => 'Lampu Rem',
                'description' => 'Lampu rem belakang menyala saat pedal diinjak',
                'status' => 'ok',
                'notes' => '',
            ],
            'turn_signals' => [
                'label' => 'Lampu Sein & Hazard',
                'description' => 'Lampu sein kiri, sein kanan, dan lampu hazard berfungsi normal',
                'status' => 'ok',
                'notes' => '',
            ],
        ];

        if ($this->type === VehicleInspection::TYPE_CHECK_IN) {
            $this->parentInspection = $vehicle->activeCheckOut;
            if ($this->parentInspection) {
                $this->parent_inspection_id = $this->parentInspection->id;
                $this->driver_name = $this->parentInspection->driver_name;
                $this->odometer = (int) $this->parentInspection->odometer;
                $this->fuel_percentage = (int) $this->parentInspection->fuel_percentage;
            } else {
                $this->odometer = (int) $vehicle->odometer;
                $this->driver_name = (string) $vehicle->driver_name;
            }
        } else {
            $this->driver_name = (string) ($vehicle->driver_name ?: '');
            $this->odometer = (int) $vehicle->odometer;
            $this->fuel_percentage = 100;
        }
    }

    public function switchType(string $newType): void
    {
        if (in_array($newType, [VehicleInspection::TYPE_CHECK_OUT, VehicleInspection::TYPE_CHECK_IN], true)) {
            $this->type = $newType;
            if ($this->type === VehicleInspection::TYPE_CHECK_IN) {
                $this->parentInspection = $this->vehicle->activeCheckOut;
                if ($this->parentInspection) {
                    $this->parent_inspection_id = $this->parentInspection->id;
                    $this->driver_name = $this->parentInspection->driver_name;
                    $this->odometer = (int) $this->parentInspection->odometer;
                    $this->fuel_percentage = (int) $this->parentInspection->fuel_percentage;
                } else {
                    $this->odometer = (int) $this->vehicle->odometer;
                    $this->driver_name = (string) $this->vehicle->driver_name;
                }
            } else {
                $this->parentInspection = null;
                $this->parent_inspection_id = null;
                $this->driver_name = (string) ($this->vehicle->driver_name ?: '');
                $this->odometer = (int) $this->vehicle->odometer;
                $this->fuel_percentage = 100;
            }
        }
    }

    public function setChecklistStatus(string $key, string $status): void
    {
        if (isset($this->checklist[$key])) {
            $this->checklist[$key]['status'] = $status;
            $this->recalculateSeverity();
        }
    }

    public function recalculateSeverity(): void
    {
        $hasIssue = false;
        foreach ($this->checklist as $item) {
            if (($item['status'] ?? 'ok') === 'issue') {
                $hasIssue = true;
                break;
            }
        }

        if ($hasIssue) {
            if ($this->severity === VehicleInspection::SEVERITY_NONE) {
                $this->severity = VehicleInspection::SEVERITY_MINOR;
            }
        } else {
            $this->severity = VehicleInspection::SEVERITY_NONE;
            $this->defect_notes = null;
        }
    }

    protected function rules(): array
    {
        $minKm = $this->type === VehicleInspection::TYPE_CHECK_IN && $this->parentInspection
            ? $this->parentInspection->odometer
            : $this->vehicle->odometer;

        return [
            'driver_name' => ['required', 'string', 'max:255'],
            'odometer' => ['required', 'integer', 'min:' . $minKm],
            'fuel_percentage' => ['required', 'integer', 'min:0', 'max:100'],
            'trip_purpose' => ['nullable', 'string', 'max:500'],
            'severity' => ['required', 'string', 'in:none,minor,critical_grounded'],
            'defect_notes' => [$this->severity !== VehicleInspection::SEVERITY_NONE ? 'required' : 'nullable', 'string', 'max:1000'],
            'photos.*' => ['nullable', 'image', 'max:5120'], // Max 5MB per image
        ];
    }

    protected function messages(): array
    {
        return [
            'driver_name.required' => 'Nama pengemudi / driver wajib diisi.',
            'odometer.required' => 'Nilai KM Odometer wajib diisi.',
            'odometer.min' => 'KM Odometer tidak boleh lebih kecil dari KM sebelumnya (:min km).',
            'defect_notes.required' => 'Catatan kerusakan wajib diisi jika ada temuan masalah pada kendaraan.',
        ];
    }

    public function save()
    {
        $this->validate();

        $photoPaths = [];
        if (! empty($this->photos)) {
            foreach ($this->photos as $photo) {
                $photoPaths[] = $photo->store('vehicle-inspections/' . date('Y/m'), 'public');
            }
        }

        DB::transaction(function () use ($photoPaths) {
            $inspection = VehicleInspection::create([
                'vehicle_id' => $this->vehicle->id,
                'parent_inspection_id' => $this->parent_inspection_id,
                'inspection_type' => $this->type,
                'driver_name' => $this->driver_name,
                'inspector_id' => auth()->id() ?? 1,
                'odometer' => $this->odometer,
                'fuel_percentage' => $this->fuel_percentage,
                'checklist_results' => $this->checklist,
                'severity' => $this->severity,
                'defect_notes' => $this->defect_notes,
                'defect_photos' => $photoPaths ?: null,
                'trip_purpose' => $this->trip_purpose,
            ]);

            // Update master vehicle
            $this->vehicle->odometer = $this->odometer;
            if ($this->driver_name) {
                $this->vehicle->driver_name = $this->driver_name;
            }

            // Atur status kendaraan jika kritis (grounded)
            if ($this->severity === VehicleInspection::SEVERITY_CRITICAL) {
                $this->vehicle->status = VehicleStatus::MAINTENANCE;
            } elseif ($this->type === VehicleInspection::TYPE_CHECK_IN && $this->vehicle->status === VehicleStatus::MAINTENANCE && $this->severity === VehicleInspection::SEVERITY_NONE) {
                // Biarkan status tetap jika sebelumnya di-maintenance secara manual, atau kembalikan ke active jika aman
            }

            $this->vehicle->save();
        });

        if ($this->type === VehicleInspection::TYPE_CHECK_OUT) {
            session()->flash('success', sprintf('Pemeriksaan Check-out selesai! Armada %s siap beroperasi.', $this->vehicle->plate_number));
        } else {
            $kmDelta = $this->parentInspection ? max(0, $this->odometer - $this->parentInspection->odometer) : 0;
            session()->flash('success', sprintf('Pemeriksaan Check-in selesai! Armada %s kembali ke pool (Jarak tempuh trip: %d km).', $this->vehicle->plate_number, $kmDelta));
        }

        return redirect()->route('vehicles.show', $this->vehicle);
    }

    public function render()
    {
        return view('livewire.vehicles.inspection-form')->layout('new.layouts.app');
    }
}
