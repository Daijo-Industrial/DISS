<?php

namespace App\Livewire\Requirements;

use App\Models\ComplianceDepartment;
use App\Models\Requirement;
use App\Models\RequirementUpload;
use Livewire\Attributes\On;
use Livewire\Component;

class RecentUploads extends Component
{
    public $department = null;

    public ?Requirement $requirement = null;

    /** @var \Illuminate\Support\Collection */
    public $uploads;

    #[On('open-recent-uploads')]
    #[On('trigger-history-modal')]
    public function open($reqId = null, $deptId = null): void
    {
        // Handle Alpine JS object payload or direct Livewire positional args
        if (is_array($reqId)) {
            $requirementId = $reqId['reqId'] ?? null;
            $departmentId = $reqId['deptId'] ?? null;
        } else {
            $requirementId = $reqId;
            $departmentId = $deptId;
        }

        if ($departmentId && $requirementId) {
            $this->department = ComplianceDepartment::find($departmentId)
                ?? \App\Infrastructure\Persistence\Eloquent\Models\Department::findOrFail($departmentId);
            $this->requirement = Requirement::findOrFail($requirementId);
            $this->load();
        }

        $this->dispatch('show-recent-uploads');
    }

    public function load(): void
    {
        if (!$this->requirement || !$this->department) {
            $this->uploads = collect();
            return;
        }

        $this->uploads = RequirementUpload::query()
            ->with('uploadedBy')
            ->where('requirement_id', $this->requirement->id)
            ->where('scope_type', $this->department::class)
            ->where('scope_id', $this->department->id)
            ->latest()
            ->take(20)
            ->get();
    }

    public function render()
    {
        return view('livewire.requirements.recent-uploads');
    }
}
