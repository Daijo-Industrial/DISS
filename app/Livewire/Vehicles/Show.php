<?php

namespace App\Livewire\Vehicles;

use App\Infrastructure\Persistence\Eloquent\Models\Vehicle;
use App\Models\ServiceRecord;
use App\Models\VehicleDocument;
use App\Models\VehicleInspection;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Show extends Component
{
    use WithFileUploads, WithPagination;

    public Vehicle $vehicle;

    #[Url]
    public string $tab = 'inspections'; // 'services', 'inspections', 'documents'

    // Service filters
    public string $year = 'all';

    public string $workshop = '';

    public int $perPage = 20;

    public bool $canManage = false;

    public bool $canInspect = false;

    public bool $canViewDocuments = false;

    public bool $canViewCosts = false;

    // Inspection filters
    public string $inspectionType = 'all';

    public string $inspectionSeverity = 'all'; // 'all', 'defects_only', 'critical_only'

    public function updatedInspectionType(): void
    {
        $this->resetPage();
    }

    public function updatedInspectionSeverity(): void
    {
        $this->resetPage();
    }

    public function updatedYear(): void
    {
        $this->resetPage();
    }

    public function updatedWorkshop(): void
    {
        $this->resetPage();
    }

    // Document modal
    public bool $showDocModal = false;

    public string $doc_type = VehicleDocument::TYPE_STNK;

    public string $doc_number = '';

    public string $expired_date = '';

    public ?string $stnk_annual_expired_date = null;

    public ?string $stnk_five_year_expired_date = null;

    public ?string $last_renewed_date = null;

    public bool $is_initial_stnk = false;

    public bool $renew_five_year = false;

    public string $notes = '';

    public $attachment = null;

    // QR Code Sticker modal
    public bool $showQrModal = false;

    public ?string $qrCodeBase64 = null;

    // Vehicle Profile Photo modal & lightbox
    public bool $showPhotoModal = false;

    public bool $showLightbox = false;

    public $new_photo = null;

    public function mount(Vehicle $vehicle): void
    {
        $user = auth()->user();
        if (! ($user?->can('fleet.view') || $user?->can('fleet.manage') || $user?->can('fleet.inspect'))) {
            abort(403);
        }

        $this->vehicle = $vehicle->load([
            'latestService' => fn ($q) => $q->with('items'),
            'activeCheckOut',
        ]);

        $this->canManage = $user?->can('fleet.manage') ?? false;
        $this->canInspect = $this->canManage || ($user?->can('fleet.inspect') ?? false);
        $this->canViewDocuments = $this->canManage || ($user?->can('fleet.documents') ?? false);
        $this->canViewCosts = $this->canManage || ($user?->can('fleet.view-costs') ?? false);

        if ($this->tab === 'documents' && ! $this->canViewDocuments) {
            $this->tab = 'inspections';
        }

        // Check if tab is requested via query param
        if (request()->has('tab') && in_array(request('tab'), ['services', 'inspections', 'documents'], true)) {
            $requestedTab = request('tab');
            if ($requestedTab === 'documents' && ! $this->canViewDocuments) {
                $this->tab = 'inspections';
            } else {
                $this->tab = $requestedTab;
            }
        }

        $this->generateQrCode();
    }

    public function generateQrCode(): ?string
    {
        if ($this->qrCodeBase64) {
            return $this->qrCodeBase64;
        }

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

        return $this->qrCodeBase64;
    }

    public function printQrSticker(): void
    {
        $qr = $this->generateQrCode();
        $this->dispatch('print-qr-sticker', qrBase64: $qr);
    }

    public function setTab(string $tab): void
    {
        if ($tab === 'documents' && ! $this->canViewDocuments) {
            $this->tab = 'inspections';

            return;
        }

        if (in_array($tab, ['services', 'inspections', 'documents'], true)) {
            $this->tab = $tab;
            $this->resetPage();
        }
    }

    public function openDocModal(?string $type = null): void
    {
        if (! $this->canManage) {
            abort(403);
        }

        $this->resetValidation();
        $this->doc_type = $type ?: VehicleDocument::TYPE_STNK;
        $this->doc_number = '';
        $this->expired_date = '';
        $this->stnk_annual_expired_date = null;
        $this->stnk_five_year_expired_date = null;
        $this->last_renewed_date = null;
        $this->notes = '';
        $this->attachment = null;
        $this->is_initial_stnk = false;
        $this->renew_five_year = false;

        $stnkAnnual = $this->vehicle->documents()->where('document_type', VehicleDocument::TYPE_STNK_ANNUAL)->latest('expired_date')->first();
        $stnkFiveYear = $this->vehicle->documents()->where('document_type', VehicleDocument::TYPE_STNK_FIVE_YEAR)->latest('expired_date')->first();

        if ($this->doc_type === VehicleDocument::TYPE_STNK || in_array($this->doc_type, [VehicleDocument::TYPE_STNK_ANNUAL, VehicleDocument::TYPE_STNK_FIVE_YEAR], true)) {
            $this->doc_type = VehicleDocument::TYPE_STNK;

            if (! $stnkAnnual && ! $stnkFiveYear) {
                // Initial STNK registration: focus on 5-year plate date; 1-year tax defaults to +1 year from now; renewal date is omitted
                $this->is_initial_stnk = true;
                $this->renew_five_year = true;
                $this->doc_number = '';
                $this->stnk_five_year_expired_date = now()->addYears(5)->toDateString();
                $this->stnk_annual_expired_date = now()->addYear()->toDateString();
                $this->last_renewed_date = null;
            } else {
                // Routine renewal: focus on renewal/payment date; next 1-year expiry defaults to +1 year from renewal date
                $this->is_initial_stnk = false;
                $this->renew_five_year = false;
                $this->doc_number = (string) ($stnkAnnual?->document_number ?: $stnkFiveYear?->document_number ?: '');
                $this->last_renewed_date = now()->toDateString();
                $this->stnk_annual_expired_date = now()->addYear()->toDateString();
                $this->stnk_five_year_expired_date = $stnkFiveYear?->expired_date?->format('Y-m-d') ?: now()->addYears(5)->toDateString();
            }
        }

        $this->showDocModal = true;
    }

    public function updatedDocType($value): void
    {
        if ($value === VehicleDocument::TYPE_STNK) {
            $stnkAnnual = $this->vehicle->documents()->where('document_type', VehicleDocument::TYPE_STNK_ANNUAL)->latest('expired_date')->first();
            $stnkFiveYear = $this->vehicle->documents()->where('document_type', VehicleDocument::TYPE_STNK_FIVE_YEAR)->latest('expired_date')->first();

            if (! $stnkAnnual && ! $stnkFiveYear) {
                $this->is_initial_stnk = true;
                $this->renew_five_year = true;
                $this->stnk_five_year_expired_date = $this->stnk_five_year_expired_date ?: now()->addYears(5)->toDateString();
                $this->stnk_annual_expired_date = $this->stnk_annual_expired_date ?: now()->addYear()->toDateString();
                $this->last_renewed_date = null;
            } else {
                $this->is_initial_stnk = false;
                $this->renew_five_year = false;
                $this->doc_number = $this->doc_number ?: (string) ($stnkAnnual?->document_number ?: $stnkFiveYear?->document_number ?: '');
                $this->last_renewed_date = $this->last_renewed_date ?: now()->toDateString();
                $this->stnk_annual_expired_date = Carbon::parse($this->last_renewed_date)->addYear()->toDateString();
                $this->stnk_five_year_expired_date = $stnkFiveYear?->expired_date?->format('Y-m-d') ?: now()->addYears(5)->toDateString();
            }
        }
    }

    public function updatedLastRenewedDate($value): void
    {
        if (! $this->is_initial_stnk && $value) {
            try {
                $parsed = Carbon::parse($value);
                $this->stnk_annual_expired_date = $parsed->copy()->addYear()->toDateString();
                if ($this->renew_five_year) {
                    $this->stnk_five_year_expired_date = $parsed->copy()->addYears(5)->toDateString();
                }
            } catch (\Throwable $e) {
                // ignore parsing exceptions
            }
        }
    }

    public function updatedRenewFiveYear($value): void
    {
        if ($value) {
            try {
                $base = $this->last_renewed_date ? Carbon::parse($this->last_renewed_date) : now();
                $this->stnk_five_year_expired_date = $base->addYears(5)->toDateString();
            } catch (\Throwable $e) {
                $this->stnk_five_year_expired_date = now()->addYears(5)->toDateString();
            }
        } else {
            $stnkFiveYear = $this->vehicle->documents()->where('document_type', VehicleDocument::TYPE_STNK_FIVE_YEAR)->latest('expired_date')->first();
            $this->stnk_five_year_expired_date = $stnkFiveYear?->expired_date?->format('Y-m-d');
        }
    }

    public function closeDocModal(): void
    {
        $this->showDocModal = false;
        $this->resetValidation();
    }

    public function openQrModal(): void
    {
        $this->generateQrCode();
        $this->showQrModal = true;
    }

    public function closeQrModal(): void
    {
        $this->showQrModal = false;
    }

    public function openPhotoModal(): void
    {
        if (! $this->canManage) {
            abort(403);
        }
        $this->resetValidation();
        $this->new_photo = null;
        $this->showPhotoModal = true;
    }

    public function closePhotoModal(): void
    {
        $this->showPhotoModal = false;
        $this->new_photo = null;
        $this->resetValidation();
    }

    public function saveVehiclePhoto(): void
    {
        if (! $this->canManage) {
            abort(403);
        }

        $this->validate([
            'new_photo' => ['required', 'image', 'max:5120'],
        ]);

        if ($this->vehicle->image_path && Storage::disk('public')->exists($this->vehicle->image_path)) {
            Storage::disk('public')->delete($this->vehicle->image_path);
        }

        $path = $this->new_photo->store('vehicles/photos', 'public');
        $this->vehicle->update(['image_path' => $path]);

        session()->flash('success', __('fleet.messages.photo_updated'));
        $this->showPhotoModal = false;
        $this->new_photo = null;
        $this->vehicle->refresh();
    }

    public function deleteVehiclePhoto(): void
    {
        if (! $this->canManage) {
            abort(403);
        }

        if ($this->vehicle->image_path && Storage::disk('public')->exists($this->vehicle->image_path)) {
            Storage::disk('public')->delete($this->vehicle->image_path);
        }

        $this->vehicle->update(['image_path' => null]);

        session()->flash('success', __('fleet.messages.photo_deleted'));
        $this->showPhotoModal = false;
        $this->vehicle->refresh();
    }

    public function openLightbox(): void
    {
        if ($this->vehicle->image_path) {
            $this->showLightbox = true;
        }
    }

    public function closeLightbox(): void
    {
        $this->showLightbox = false;
    }

    public function saveDocument(): void
    {
        if (! $this->canManage) {
            abort(403);
        }

        if ($this->doc_type === VehicleDocument::TYPE_STNK) {
            if ($this->is_initial_stnk) {
                $this->validate([
                    'doc_type' => ['required', 'string', 'in:stnk,kir,stnk_annual,stnk_five_year,insurance,other'],
                    'doc_number' => ['nullable', 'string', 'max:100'],
                    'stnk_annual_expired_date' => ['required', 'date'],
                    'stnk_five_year_expired_date' => ['required', 'date'],
                    'notes' => ['nullable', 'string', 'max:500'],
                    'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
                ]);

                DB::transaction(function () {
                    $filePath = null;
                    if ($this->attachment) {
                        $filePath = $this->attachment->store('vehicle-documents/' . $this->vehicle->id, 'public');
                    }

                    // 1. Initial Annual STNK record (last_renewed_date is null for first-time entry)
                    VehicleDocument::create([
                        'vehicle_id' => $this->vehicle->id,
                        'document_type' => VehicleDocument::TYPE_STNK_ANNUAL,
                        'document_number' => $this->doc_number ?: null,
                        'expired_date' => $this->stnk_annual_expired_date,
                        'last_renewed_date' => null,
                        'attachment_path' => $filePath,
                        'notes' => $this->notes ?: null,
                        'created_by' => auth()->id(),
                    ]);

                    // 2. Initial 5-Year STNK record (last_renewed_date is null for first-time entry)
                    VehicleDocument::create([
                        'vehicle_id' => $this->vehicle->id,
                        'document_type' => VehicleDocument::TYPE_STNK_FIVE_YEAR,
                        'document_number' => $this->doc_number ?: null,
                        'expired_date' => $this->stnk_five_year_expired_date,
                        'last_renewed_date' => null,
                        'attachment_path' => $filePath,
                        'notes' => $this->notes ?: null,
                        'created_by' => auth()->id(),
                    ]);
                });
            } else {
                $rules = [
                    'doc_type' => ['required', 'string', 'in:stnk,kir,stnk_annual,stnk_five_year,insurance,other'],
                    'doc_number' => ['nullable', 'string', 'max:100'],
                    'last_renewed_date' => ['required', 'date'],
                    'stnk_annual_expired_date' => ['required', 'date'],
                    'notes' => ['nullable', 'string', 'max:500'],
                    'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
                ];

                if ($this->renew_five_year) {
                    $rules['stnk_five_year_expired_date'] = ['required', 'date'];
                }

                $this->validate($rules);

                DB::transaction(function () {
                    $filePath = null;
                    if ($this->attachment) {
                        $filePath = $this->attachment->store('vehicle-documents/' . $this->vehicle->id, 'public');
                    }

                    // Preserve previous attachment if none was newly uploaded
                    if (! $filePath) {
                        $existing = VehicleDocument::where('vehicle_id', $this->vehicle->id)
                            ->whereIn('document_type', [VehicleDocument::TYPE_STNK_ANNUAL, VehicleDocument::TYPE_STNK_FIVE_YEAR])
                            ->whereNotNull('attachment_path')
                            ->latest('expired_date')
                            ->first();
                        $filePath = $existing?->attachment_path;
                    }

                    // 1. Annual STNK record
                    VehicleDocument::create([
                        'vehicle_id' => $this->vehicle->id,
                        'document_type' => VehicleDocument::TYPE_STNK_ANNUAL,
                        'document_number' => $this->doc_number ?: null,
                        'expired_date' => $this->stnk_annual_expired_date,
                        'last_renewed_date' => $this->last_renewed_date,
                        'attachment_path' => $filePath,
                        'notes' => $this->notes ?: null,
                        'created_by' => auth()->id(),
                    ]);

                    // 2. 5-Year STNK record (only created if 5-year cycle was selected)
                    if ($this->renew_five_year) {
                        VehicleDocument::create([
                            'vehicle_id' => $this->vehicle->id,
                            'document_type' => VehicleDocument::TYPE_STNK_FIVE_YEAR,
                            'document_number' => $this->doc_number ?: null,
                            'expired_date' => $this->stnk_five_year_expired_date,
                            'last_renewed_date' => $this->last_renewed_date,
                            'attachment_path' => $filePath,
                            'notes' => $this->notes ?: null,
                            'created_by' => auth()->id(),
                        ]);
                    }
                });
            }

            session()->flash('success', __('fleet.messages.document_saved'));
            $this->showDocModal = false;
            $this->vehicle->refresh();

            return;
        }

        $this->validate([
            'doc_type' => ['required', 'string', 'in:stnk,kir,stnk_annual,stnk_five_year,insurance,other'],
            'doc_number' => ['nullable', 'string', 'max:100'],
            'expired_date' => ['required', 'date'],
            'last_renewed_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'], // 10MB
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

        session()->flash('success', __('fleet.messages.document_saved'));
        $this->showDocModal = false;
        $this->vehicle->refresh();
    }

    public function deleteDocument(int $id): void
    {
        if (! $this->canManage) {
            abort(403);
        }

        $doc = VehicleDocument::where('vehicle_id', $this->vehicle->id)->findOrFail($id);

        if ($doc->attachment_path && Storage::disk('public')->exists($doc->attachment_path)) {
            $otherReference = VehicleDocument::where('id', '!=', $doc->id)
                ->where('attachment_path', $doc->attachment_path)
                ->exists();

            if (! $otherReference) {
                Storage::disk('public')->delete($doc->attachment_path);
            }
        }

        $doc->delete();

        session()->flash('success', __('fleet.messages.document_deleted'));
        $this->vehicle->refresh();
    }

    public function deleteStnk(): void
    {
        if (! $this->canManage) {
            abort(403);
        }

        $stnkDocs = VehicleDocument::where('vehicle_id', $this->vehicle->id)
            ->whereIn('document_type', [VehicleDocument::TYPE_STNK_ANNUAL, VehicleDocument::TYPE_STNK_FIVE_YEAR])
            ->get();

        foreach ($stnkDocs as $doc) {
            if ($doc->attachment_path && Storage::disk('public')->exists($doc->attachment_path)) {
                $otherReference = VehicleDocument::whereNotIn('id', $stnkDocs->pluck('id'))
                    ->where('attachment_path', $doc->attachment_path)
                    ->exists();

                if (! $otherReference) {
                    Storage::disk('public')->delete($doc->attachment_path);
                }
            }
            $doc->delete();
        }

        session()->flash('success', __('fleet.messages.document_deleted'));
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

        $availableYears = (clone $baseServices)
            ->selectRaw('DISTINCT YEAR(service_date) as yr')
            ->orderByDesc('yr')
            ->pluck('yr')
            ->filter()
            ->values()
            ->all();

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
            ->when($this->inspectionSeverity === 'defects_only', fn ($q) => $q->whereIn('severity', ['minor', 'critical_grounded']))
            ->when($this->inspectionSeverity === 'critical_only', fn ($q) => $q->where('severity', 'critical_grounded'))
            ->orderByDesc('created_at')
            ->paginate(15);

        // 3. Documents
        $documents = VehicleDocument::query()
            ->with('creator')
            ->where('vehicle_id', $this->vehicle->id)
            ->orderBy('expired_date')
            ->get();

        return view('livewire.vehicles.show', compact('records', 'lifetimeCost', 'ytdCost', 'inspections', 'documents', 'availableYears'));
    }
}
