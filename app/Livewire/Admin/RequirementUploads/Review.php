<?php

namespace App\Livewire\Admin\RequirementUploads;

use App\Models\RequirementUpload;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('new.layouts.app')]
class Review extends Component
{
    use WithPagination;

    public $paginationTheme = 'bootstrap';

    public string $status = 'pending'; // pending|approved|rejected|all

    public ?string $q = null;

    // Filter properties
    public ?string $date_from = null;

    public ?string $date_to = null;

    public ?string $mime_like = null;

    public bool $only_expiring = false;

    // View mode & selection
    public string $viewMode = 'kanban'; // kanban | table

    public array $selected = [];

    public int $perPage = 12;

    public string $sort = 'created_at';   // column

    public string $dir = 'desc';          // asc|desc

    // Modal / Slide-over state
    public ?int $uploadId = null;

    public ?string $review_notes = null;

    public ?array $active = null;

    protected $queryString = [
        'status' => ['except' => 'pending'],
        'q' => ['except' => null],
        'date_from' => ['except' => null],
        'date_to' => ['except' => null],
        'mime_like' => ['except' => null],
        'only_expiring' => ['except' => false],
        'sort' => ['except' => 'created_at'],
        'dir' => ['except' => 'desc'],
        'viewMode' => ['except' => 'kanban'],
        'perPage' => ['except' => 12],
    ];

    public function mount()
    {
        $this->viewMode = session('review.viewMode', 'kanban');
    }

    public function updating($field)
    {
        if (in_array($field, ['status', 'q', 'date_from', 'date_to', 'mime_like', 'only_expiring', 'perPage'])) {
            $this->resetPage();
        }
    }

    public function setViewMode(string $mode): void
    {
        if (in_array($mode, ['kanban', 'table'], true)) {
            $this->viewMode = $mode;
            session()->put('review.viewMode', $mode);
        }
    }

    public function sortBy(string $col): void
    {
        if ($this->sort === $col) {
            $this->dir = $this->dir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sort = $col;
            $this->dir = 'asc';
        }
        $this->resetPage();
    }

    public function togglePageSelection(bool $checked): void
    {
        if (! $checked) {
            $this->selected = [];
            return;
        }

        $pageIds = $this->baseQuery()->clone()->pluck('requirement_uploads.id')->all();
        $this->selected = array_values(array_unique(array_merge($this->selected, $pageIds)));
    }

    public function clearSelection(): void
    {
        $this->selected = [];
    }

    public function clearDateRange(): void
    {
        $this->date_from = null;
        $this->date_to = null;
        $this->resetPage();
    }

    public function setRange(string $key): void
    {
        $today = now()->toDateString();

        switch ($key) {
            case '7d':
                $this->date_from = now()->subDays(6)->toDateString();
                $this->date_to = $today;
                break;
            case '30d':
                $this->date_from = now()->subDays(29)->toDateString();
                $this->date_to = $today;
                break;
            case 'month':
                $this->date_from = now()->startOfMonth()->toDateString();
                $this->date_to = $today;
                break;
            default:
                return;
        }

        $this->resetPage();
    }

    public function openDecision(int $id): void
    {
        $u = $this->baseQuery()->where('requirement_uploads.id', $id)->firstOrFail();

        $this->uploadId = $id;
        $this->review_notes = $u->review_notes;

        $downloadUrl = URL::signedRoute('uploads.download', ['upload' => $u->id]);
        $previewUrl = URL::signedRoute('uploads.download', ['upload' => $u->id, 'preview' => 1]);

        $this->active = [
            'id' => $u->id,
            'original_name' => $u->original_name,
            'mime_type' => $u->mime_type,
            'size' => $u->size,
            'req_name' => $u->req_name,
            'req_code' => $u->req_code,
            'dept_name' => $u->dept_name ?? 'General',
            'dept_code' => $u->dept_code ?? '—',
            'valid_from' => optional($u->valid_from)->format('Y-m-d'),
            'valid_until' => optional($u->valid_until)->format('Y-m-d'),
            'download_url' => $downloadUrl,
            'preview_url' => $previewUrl,
        ];

        $this->dispatch('open-decision-modal');
    }

    public function applyPresetReason(string $reason): void
    {
        $this->review_notes = $reason;
    }

    public function approve(int $id): void
    {
        Gate::authorize('approve-requirements');
        $u = RequirementUpload::findOrFail($id);
        $u->status = 'approved';
        $u->review_notes = $this->review_notes;
        $u->save();

        $this->dispatch('upload:done');
        $this->reset(['uploadId', 'review_notes', 'active']);
        $this->dispatch('toast', type: 'success', message: 'Upload approved successfully.');
    }

    public function reject(int $id): void
    {
        Gate::authorize('approve-requirements');
        $u = RequirementUpload::findOrFail($id);
        $u->status = 'rejected';
        $u->review_notes = $this->review_notes;
        $u->save();

        $this->dispatch('upload:done');
        $this->reset(['uploadId', 'review_notes', 'active']);
        $this->dispatch('toast', type: 'warning', message: 'Upload rejected.');
    }

    public function quickReject(int $id, string $reason): void
    {
        Gate::authorize('approve-requirements');
        $u = RequirementUpload::findOrFail($id);
        $u->status = 'rejected';
        $u->review_notes = $reason;
        $u->save();

        $this->dispatch('upload:done');
        $this->dispatch('toast', type: 'warning', message: 'Upload rejected with note: '.$reason);
    }

    public function bulkApprove(): void
    {
        Gate::authorize('approve-requirements');
        RequirementUpload::whereIn('id', $this->selected)->update([
            'status' => 'approved',
        ]);
        $this->clearSelection();
        $this->dispatch('toast', type: 'success', message: 'Approved selected uploads.');
    }

    public function bulkReject(): void
    {
        Gate::authorize('approve-requirements');
        RequirementUpload::whereIn('id', $this->selected)->update([
            'status' => 'rejected',
        ]);
        $this->clearSelection();
        $this->dispatch('toast', type: 'warning', message: 'Rejected selected uploads.');
    }

    public function exportCsv()
    {
        $cols = [
            'requirements.code as req_code',
            'requirements.name as req_name',
            DB::raw('COALESCE(cd.code, d.code) as dept_code'),
            DB::raw('COALESCE(cd.name, d.name) as dept_name'),
            'requirement_uploads.original_name',
            'requirement_uploads.mime_type',
            'requirement_uploads.size',
            'requirement_uploads.status',
            'requirement_uploads.valid_from',
            'requirement_uploads.valid_until',
            'requirement_uploads.created_at',
        ];

        $data = $this->baseQuery()->clone()->get($cols);

        return response()->streamDownload(function () use ($data) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Requirement Code', 'Requirement', 'Dept Code', 'Department', 'File', 'MIME', 'Size', 'Status', 'Valid From', 'Valid Until', 'Uploaded']);
            foreach ($data as $r) {
                fputcsv($out, [
                    $r->req_code, $r->req_name, $r->dept_code ?? '—', $r->dept_name ?? 'General',
                    $r->original_name, $r->mime_type, $r->size, $r->status,
                    optional($r->valid_from)?->toDateString(), optional($r->valid_until)?->toDateString(),
                    $r->created_at->toDateTimeString(),
                ]);
            }
            fclose($out);
        }, 'requirement-uploads.csv');
    }

    private function baseQuery()
    {
        // Join requirement + both compliance_departments and legacy departments using COALESCE
        $q = RequirementUpload::query()
            ->select([
                'requirement_uploads.*',
                'requirements.name as req_name',
                'requirements.code as req_code',
                DB::raw('COALESCE(cd.name, d.name, "General") as dept_name'),
                DB::raw('COALESCE(cd.code, d.code, "—") as dept_code'),
            ])
            ->join('requirements', 'requirements.id', '=', 'requirement_uploads.requirement_id')
            ->leftJoin('compliance_departments as cd', function ($j) {
                $j->on('cd.id', '=', 'requirement_uploads.scope_id')
                    ->where('requirement_uploads.scope_type', '=', \App\Models\ComplianceDepartment::class);
            })
            ->leftJoin('departments as d', function ($j) {
                $j->on('d.id', '=', 'requirement_uploads.scope_id')
                    ->where('requirement_uploads.scope_type', '=', \App\Infrastructure\Persistence\Eloquent\Models\Department::class);
            });

        if ($this->status !== 'all') {
            $q->where('requirement_uploads.status', $this->status);
        }

        if ($this->q) {
            $term = "%{$this->q}%";
            $q->where(function ($qq) use ($term) {
                $qq->where('requirement_uploads.original_name', 'like', $term)
                    ->orWhere('requirements.name', 'like', $term)
                    ->orWhere('requirements.code', 'like', $term)
                    ->orWhere('cd.name', 'like', $term)
                    ->orWhere('cd.code', 'like', $term)
                    ->orWhere('d.name', 'like', $term)
                    ->orWhere('d.code', 'like', $term);
            });
        }

        if ($this->date_from) {
            $q->whereDate('requirement_uploads.created_at', '>=', $this->date_from);
        }
        if ($this->date_to) {
            $q->whereDate('requirement_uploads.created_at', '<=', $this->date_to);
        }

        if ($this->mime_like) {
            $q->where('requirement_uploads.mime_type', 'like', "%{$this->mime_like}%");
        }

        if ($this->only_expiring) {
            $q->whereNotNull('requirement_uploads.valid_until')
                ->whereBetween('requirement_uploads.valid_until', [now(), now()->addDays(30)]);
        }

        // sorting
        $allowed = ['requirements.name', 'dept_name', 'status', 'valid_until', 'created_at'];
        $col = in_array($this->sort, $allowed, true) ? $this->sort : 'created_at';
        $dir = $this->dir === 'asc' ? 'asc' : 'desc';
        $q->orderBy($col, $dir);

        return $q;
    }

    public function render()
    {
        $rows = $this->baseQuery()->paginate($this->perPage);

        // Fetch counts per status for filter badges & Kanban columns
        $counts = [
            'pending' => RequirementUpload::where('status', 'pending')->count(),
            'approved' => RequirementUpload::where('status', 'approved')->count(),
            'rejected' => RequirementUpload::where('status', 'rejected')->count(),
            'all' => RequirementUpload::count(),
        ];

        // Kanban datasets if in kanban view mode
        $kanbanColumns = [];
        if ($this->viewMode === 'kanban') {
            $kanbanColumns = [
                'pending' => $this->baseQuery()->clone()->where('requirement_uploads.status', 'pending')->take(15)->get(),
                'approved' => $this->baseQuery()->clone()->where('requirement_uploads.status', 'approved')->take(15)->get(),
                'rejected' => $this->baseQuery()->clone()->where('requirement_uploads.status', 'rejected')->take(15)->get(),
            ];
        }

        return view('livewire.admin.requirement-uploads.review', [
            'rows' => $rows,
            'counts' => $counts,
            'kanbanColumns' => $kanbanColumns,
        ]);
    }
}
