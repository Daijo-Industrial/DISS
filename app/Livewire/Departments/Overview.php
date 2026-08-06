<?php

namespace App\Livewire\Departments;

use App\Models\ComplianceDepartment;
use App\Models\DepartmentComplianceSnapshot;
use App\Services\ComplianceService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('new.layouts.app')]
class Overview extends Component
{
    use WithPagination;

    public $paginationTheme = 'bootstrap';

    public string $search = '';

    public string $status = 'all';          // all|complete|incomplete

    public string $bucket = '';             // '', '0-49','50-99','100'

    public string $sort = 'name';           // name|code|percent

    public string $dir = 'asc';            // asc|desc

    public string $viewMode = 'grid';       // 'grid' | 'list'

    public int $perPage = 12;

    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => 'all'],
        'bucket' => ['except' => ''],
        'sort' => ['except' => 'name'],
        'dir' => ['except' => 'asc'],
        'viewMode' => ['except' => 'grid'],
        'perPage' => ['except' => 12],
    ];

    public function mount()
    {
        $this->viewMode = session('departments.viewMode', 'grid');
        $this->perPage = session('departments.perPage', 12);
    }

    public function updated($field)
    {
        if (in_array($field, ['search', 'status', 'bucket', 'perPage'])) {
            $this->resetPage();
        }
        if ($field === 'perPage') {
            session()->put('departments.perPage', $this->perPage);
        }
    }

    public function setViewMode(string $mode): void
    {
        if (in_array($mode, ['grid', 'list'], true)) {
            $this->viewMode = $mode;
            session()->put('departments.viewMode', $mode);
        }
    }

    public function sortBy(string $field): void
    {
        if (! in_array($field, ['name', 'code', 'percent'])) {
            return;
        }
        if ($this->sort === $field) {
            $this->dir = $this->dir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sort = $field;
            $this->dir = 'asc';
        }
        $this->resetPage();
    }

    public function toggleDir(): void
    {
        $this->dir = $this->dir === 'asc' ? 'desc' : 'asc';
        $this->resetPage();
    }

    public function render(ComplianceService $svc)
    {
        // Snapshot pre-computed map for instant query performance (zero N+1)
        $snapshots = DepartmentComplianceSnapshot::pluck('percent', 'department_id')->all();

        $query = ComplianceDepartment::query()
            ->when($this->search !== '', function ($q) {
                $term = "%{$this->search}%";
                $q->where(function ($qq) use ($term) {
                    $qq->where('name', 'like', $term)
                        ->orWhere('code', 'like', $term);
                });
            });

        // Compute percent for all matching departments efficiently
        $allDepts = $query->get();

        $rows = $allDepts->map(function (ComplianceDepartment $d) use ($snapshots, $svc) {
            $percent = $snapshots[$d->id] ?? (int) round($svc->getScopeCompliancePercent($d));

            return [
                'dept' => $d,
                'percent' => (int)$percent,
                'status' => $percent >= 100 ? 'Complete' : 'Incomplete',
            ];
        });

        // Status filter
        if ($this->status !== 'all') {
            $want = $this->status === 'complete' ? 'Complete' : 'Incomplete';
            $rows = $rows->filter(fn ($r) => $r['status'] === $want);
        }

        // Bucket filter
        if ($this->bucket !== '') {
            [$lo,$hi] = match ($this->bucket) {
                '0-49' => [0, 49],
                '50-99' => [50, 99],
                '100' => [100, 100],
                default => [0, 100],
            };
            $rows = $rows->filter(fn ($r) => $r['percent'] >= $lo && $r['percent'] <= $hi);
        }

        // Sorting
        $sortedRows = $rows->sortBy(function ($r) {
            return match ($this->sort) {
                'code' => $r['dept']->code ?? '',
                'percent' => $r['percent'],
                default => $r['dept']->name,
            };
        }, SORT_REGULAR, $this->dir === 'desc')->values();

        // KPIs calculated on full filtered set
        $totalCount = $sortedRows->count();
        $completeCount = $sortedRows->where('percent', 100)->count();
        $incompleteCount = $totalCount - $completeCount;
        $avgPercent = $totalCount ? (int) round($sortedRows->avg('percent')) : 0;
        $kpi = [
            'count' => $totalCount,
            'complete' => $completeCount,
            'incomplete' => $incompleteCount,
            'avg' => $avgPercent,
        ];

        // Manual pagination on sorted collection
        $currentPage = $this->getPage();
        $pagedSlice = $sortedRows->slice(($currentPage - 1) * $this->perPage, $this->perPage)->values();

        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $pagedSlice,
            $totalCount,
            $this->perPage,
            $currentPage,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
        );

        return view('livewire.departments.overview', [
            'items' => $paginator,
            'kpi' => $kpi,
        ]);
    }
}
