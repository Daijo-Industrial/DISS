<?php

namespace App\Livewire\Requirements;

use App\Models\Requirement;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('new.layouts.app')]
class Index extends Component
{
    use WithPagination;

    public $paginationTheme = 'bootstrap';

    public string $search = '';

    public ?string $filterFreq = null;        // '', once, yearly, quarterly, monthly

    public ?string $filterApproval = null;    // '', '1', '0'

    public string $sort = 'name';

    public string $dir = 'asc';

    public string $viewMode = 'grid';         // grid | list

    public int $perPage = 12;

    protected $queryString = [
        'search' => ['except' => ''],
        'filterFreq' => ['except' => null],
        'filterApproval' => ['except' => null],
        'sort' => ['except' => 'name'],
        'dir' => ['except' => 'asc'],
        'viewMode' => ['except' => 'grid'],
        'perPage' => ['except' => 12],
    ];

    public function mount()
    {
        $this->viewMode = session('requirements.viewMode', 'grid');
    }

    public function updated($field)
    {
        if (in_array($field, ['search', 'filterFreq', 'filterApproval', 'perPage'])) {
            $this->resetPage();
        }
    }

    public function setViewMode(string $mode): void
    {
        if (in_array($mode, ['grid', 'list'], true)) {
            $this->viewMode = $mode;
            session()->put('requirements.viewMode', $mode);
        }
    }

    public function sortBy(string $field): void
    {
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

    public function render()
    {
        $query = Requirement::query()
            ->when($this->search !== '', function ($q) {
                $term = "%{$this->search}%";
                $q->where(function ($qq) use ($term) {
                    $qq->where('name', 'like', $term)
                        ->orWhere('code', 'like', $term);
                });
            })
            ->when($this->filterFreq, fn ($q, $f) => $q->where('frequency', $f))
            ->when(
                $this->filterApproval !== null && $this->filterApproval !== '',
                fn ($q) => $q->where('requires_approval', (int) $this->filterApproval)
            );

        $items = (clone $query)
            ->withCount(['assignments', 'uploads'])
            ->orderBy($this->sort, $this->dir)
            ->paginate($this->perPage);

        // Catalogue summary KPIs
        $kpi = [
            'total' => Requirement::count(),
            'approval' => Requirement::where('requires_approval', true)->count(),
            'recurring' => Requirement::where('frequency', '!=', 'once')->count(),
            'unassigned' => Requirement::doesntHave('assignments')->count(),
        ];

        return view('livewire.requirements.index', [
            'items' => $items,
            'kpi' => $kpi,
        ]);
    }
}
