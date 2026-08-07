<?php

namespace App\Livewire\Requirements;

use App\Models\ComplianceDepartment;
use App\Models\Requirement;
use App\Models\RequirementUpload;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

class Upload extends Component
{
    use WithFileUploads;

    public $department = null; // scope model

    public ?int $requirementId = null;

    #[Validate('required|file|max:20480')] // 20MB; mimes checked at runtime
    public $file;

    public $valid_from;

    #[On('open-upload')]
    #[On('trigger-upload-modal')]
    public function open($reqId = null, $deptId = null): void
    {
        // Handle Alpine JS object payload or direct Livewire positional args
        if (is_array($reqId)) {
            $this->requirementId = $reqId['reqId'] ?? null;
            $departmentId = $reqId['deptId'] ?? null;
        } else {
            $this->requirementId = $reqId;
            $departmentId = $deptId;
        }

        $this->resetErrorBag();
        $this->resetValidation();
        $this->file = null;
        $this->valid_from = now()->toDateString();

        if ($departmentId) {
            $this->department = ComplianceDepartment::find($departmentId)
                ?? \App\Infrastructure\Persistence\Eloquent\Models\Department::findOrFail($departmentId);
        }

        $this->dispatch('show-upload-modal');
    }

    public function save()
    {
        $req = Requirement::findOrFail($this->requirementId);

        // MIME check
        if ($req->allowed_mimetypes) {
            $this->validate(['file' => 'file|mimetypes:' . implode(',', $req->allowed_mimetypes)]);
        }

        $disk = 'public';
        $scopeClass = $this->department ? $this->department::class : ComplianceDepartment::class;
        $scopeId = $this->department ? $this->department->id : 0;

        $folder = sprintf(
            'requirements/%s/%d/%s',
            str_replace('\\', '_', $scopeClass),
            $scopeId,
            $req->code
        );
        $path = $this->file->store($folder, $disk);

        $upload = RequirementUpload::create([
            'requirement_id' => $req->id,
            'scope_type' => $scopeClass,
            'scope_id' => $scopeId,
            'path' => $path,
            'original_name' => $this->file->getClientOriginalName(),
            'mime_type' => $this->file->getMimeType(),
            'size' => $this->file->getSize(),
            'uploaded_by' => Auth::id(),
            'valid_from' => $this->valid_from,
            'valid_until' => $req->validity_days ? now()->parse($this->valid_from)->addDays($req->validity_days) : null,
            'status' => $req->requires_approval ? 'pending' : 'approved',
        ]);

        $this->dispatch('hide-upload-modal');
        $this->dispatch('upload:done');
        $this->dispatch('toast', type: 'success', message: 'File uploaded successfully');
    }

    public function render()
    {
        return view('livewire.requirements.upload');
    }
}
