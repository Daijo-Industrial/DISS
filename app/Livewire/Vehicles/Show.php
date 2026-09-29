<?php

namespace App\Livewire\Vehicles;

use App\Infrastructure\Persistence\Eloquent\Models\Vehicle;
use App\Models\ServiceRecord;
use App\Models\VehicleDocument;
use App\Models\VehicleInspection;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Show extends Component
{
    use WithFileUploads, WithPagination;

    public Vehicle $vehicle;

    public string $tab = 'services'; // 'services', 'inspections', 'documents'

    // Service filters
    public string $year = 'all';

    public string $workshop = '';

    public int $perPage = 20;

    public bool $canManage = false;

    // Inspection filters
    public string $inspectionType = 'all';

    // Document modal
    public bool $showDocModal = false;

    public string $doc_type = VehicleDocument::TYPE_STNK_ANNUAL;

    public string $doc_number = '';

    public string $expired_date = '';

    public ?string $last_renewed_date = null;

    public string $notes = '';

    public $attachment = null;

    // QR Code Sticker modal
    public bool $showQrModal = false;

    public ?string $qrCodeBase64 = null;

    public function mount(Vehicle $vehicle)
    {
        $this->vehicle = $vehicle->load([
            'latestService' => fn ($q) => $q->with('items'),
            'activeCheckOut',
        ]);

        $user = auth()->user();
        $this->canManage = $user?->hasRole('super-admin') || ($user?->department?->name === 'PERSONALIA');

        // Check if tab is requested via query param
        if (request()->has('tab') && in_array(request('tab'), ['services', 'inspections', 'documents'], true)) {
            $this->tab = request('tab');
        }
    }

    public function setTab(string $tab): void
    {
        if (in_array($tab, ['services', 'inspections', 'documents'], true)) {
            $this->tab = $tab;
            $this->resetPage();
        }
    }

    public function openDocModal(?string $type = null): void
    {
        $this->resetValidation();
        $this->doc_type = $type ?: ($this->vehicle->requires_kir ? VehicleDocument::TYPE_KIR : VehicleDocument::TYPE_STNK_ANNUAL);
        $this->doc_number = '';
        $this->expired_date = '';
        $this->last_renewed_date = null;
        $this->notes = '';
        $this->attachment = null;
        $this->showDocModal = true;
    }

    public function closeDocModal(): void
    {
        $this->showDocModal = false;
        $this->resetValidation();
    }

    public function openQrModal(): void
    {
        try {
            $qrCodeObj = new QrCode(
                data: (string) $this->vehicle->id,
                errorCorrectionLevel: ErrorCorrectionLevel::High,
                size: 260,
                margin: 8
            );
            $writer = new PngWriter;
            $qrCodeResult = $writer->write($qrCodeObj);
            $this->qrCodeBase64 = base64_encode($qrCodeResult->getString());
        } catch (\Throwable $e) {
            $this->qrCodeBase64 = null;
        }

        $this->showQrModal = true;
    }

    public function closeQrModal(): void
    {
        $this->showQrModal = false;
    }

    public function saveDocument(): void
    {
        if (! $this->canManage) {
            abort(403);
        }

        $this->validate([
            'doc_type' => ['required', 'string', 'in:kir,stnk_annual,stnk_five_year,insurance,other'],
            'doc_number' => ['nullable', 'string', 'max:100'],
            'expired_date' => ['required', 'date'],
            'last_renewed_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
            'attachment' => ['nullable', 'file', 'max:10240'], // 10MB
        ]);

        $filePath = null;
        if ($this->attachment) {
            $filePath = $this->attachment->store('vehicle-documents/' . $this->vehicle->id, 'public');
        }

        VehicleDocument::create([
            'vehicle_id' => $this->vehicle->id,
            'document_type' => $this->doc_type,
            'document_number' => $this->doc_number ?: null,
            'expired_date' => $this->expired_date,
            'last_renewed_date' => $this->last_renewed_date ?: null,
            'attachment_path' => $filePath,
            'notes' => $this->notes ?: null,
            'created_by' => auth()->id(),
        ]);

        session()->flash('success', 'Dokumen legalitas berhasil disimpan.');
        $this->showDocModal = false;
        $this->vehicle->refresh();
    }

    public function deleteDocument(int $id): void
    {
        if (! $this->canManage) {
            abort(403);
        }

        $doc = VehicleDocument::where('vehicle_id', $this->vehicle->id)->findOrFail($id);
        $doc->delete();

        session()->flash('success', 'Dokumen berhasil dihapus.');
        $this->vehicle->refresh();
    }

    public function deleteService(int $id): void
    {
        if (! $this->canManage) {
            abort(403);
        }

        $record = ServiceRecord::where('vehicle_id', $this->vehicle->id)->findOrFail($id);

        try {
            DB::transaction(function () use ($record) {
                $record->delete();
            });

            session()->flash('success', 'Service record deleted.');
        } catch (\Throwable $e) {
            session()->flash('error', 'Failed to delete service record.');
        }

        $this->resetPage();
        $this->vehicle = $this->vehicle->fresh()->load([
            'latestService' => fn ($q) => $q->with('items'),
            'activeCheckOut',
        ]);
    }

    public function render()
    {
        // 1. Service Records
        $baseServices = ServiceRecord::query()
            ->with('items')
            ->where('vehicle_id', $this->vehicle->id);

        $records = (clone $baseServices)
            ->when($this->year !== 'all', fn ($q) => $q->whereYear('service_date', $this->year))
            ->when($this->workshop !== '', fn ($q) => $q->where('workshop', 'like', '%' . $this->workshop . '%'))
            ->orderByDesc('service_date')
            ->paginate($this->perPage);

        $lifetimeCost = (clone $baseServices)->sum('total_cost');
        $ytdCost = (clone $baseServices)->whereYear('service_date', now()->year)->sum('total_cost');

        // 2. Inspections
        $inspections = VehicleInspection::query()
            ->with(['inspector', 'parentCheckOut'])
            ->where('vehicle_id', $this->vehicle->id)
            ->when($this->inspectionType !== 'all', fn ($q) => $q->where('inspection_type', $this->inspectionType))
            ->orderByDesc('created_at')
            ->paginate(15);

        // 3. Documents
        $documents = VehicleDocument::query()
            ->with('creator')
            ->where('vehicle_id', $this->vehicle->id)
            ->orderBy('expired_date')
            ->get();

        return view('livewire.vehicles.show', compact('records', 'lifetimeCost', 'ytdCost', 'inspections', 'documents'));
    }
}
