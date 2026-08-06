{{-- Department Compliance Detail — Livewire component view --}}
@section('title', $department->name . ' — Compliance')
@section('page-title', $department->name)
@section('page-subtitle', 'Document compliance requirements breakdown')

<div class="space-y-6">

    {{-- ─── 1. Page Header ───────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500 via-indigo-600 to-blue-600 shadow-lg shadow-indigo-200 shrink-0 font-extrabold text-white text-lg">
                {{ strtoupper(mb_substr($department->name, 0, 2)) }}
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">{{ $department->name }}</h1>
                    @if ($department->code)
                        <span class="px-2 py-0.5 rounded text-xs font-mono font-bold uppercase bg-slate-100 text-slate-600">
                            {{ $department->code }}
                        </span>
                    @endif
                </div>
                <p class="text-xs font-medium text-slate-500 mt-0.5">Assigned document requirements and validation status</p>
            </div>
        </div>

        <div class="flex items-center gap-4">
            {{-- Department Overall Score Ring --}}
            <div class="flex items-center gap-3 p-2 rounded-2xl bg-white border border-slate-200/80 shadow-xs">
                <x-compliance.progress-ring
                    :percent="$percent"
                    size="md"
                    :strokeWidth="7"
                />
                <div class="pr-2">
                    <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Overall Score</span>
                    <span class="text-sm font-extrabold {{ $percent >= 80 ? 'text-emerald-600' : ($percent < 50 ? 'text-rose-600' : 'text-amber-600') }}">
                        {{ $percent >= 80 ? 'Compliant' : ($percent < 50 ? 'Critical' : 'Warning') }}
                    </span>
                </div>
            </div>

            <a href="{{ route('departments.index') }}"
                class="inline-flex items-center gap-2 rounded-xl bg-white border border-slate-200/80 px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-50 shadow-sm transition-all active:scale-95">
                <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
                Departments
            </a>
        </div>
    </div>

    {{-- ─── 2. Filter Toolbar ────────────────────────────────────────── --}}
    <div class="rounded-2xl bg-white/90 backdrop-blur-xl border border-slate-200/80 p-4 shadow-sm flex flex-wrap items-center justify-between gap-4">
        {{-- Search Input --}}
        <div class="relative flex-1 min-w-[220px]">
            <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 h-4 w-4 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search requirement by code or name…"
                class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 bg-white text-xs font-medium text-slate-800 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all">
        </div>

        {{-- Filter Chips --}}
        <x-compliance.filter-chips
            :options="[
                ['value' => 'all', 'label' => 'All Status', 'count' => count($this->filteredSortedRows)],
                ['value' => 'missing', 'label' => 'Action Needed', 'count' => count($this->urgentRows), 'color' => 'rose'],
                ['value' => 'pending', 'label' => 'Pending Review', 'count' => count($this->pendingRows), 'color' => 'amber'],
                ['value' => 'ok', 'label' => 'Compliant', 'count' => count($this->okRows), 'color' => 'emerald']
            ]"
            :selected="$status"
            wireModel="status"
        />

        <div class="flex items-center gap-3">
            {{-- Sort Select --}}
            <select wire:model.live="sort" class="rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-700 py-2 px-3 outline-none focus:ring-2 focus:ring-indigo-500/20">
                <option value="code">Sort by Code</option>
                <option value="name">Sort by Name</option>
                <option value="percent">Sort by %</option>
                <option value="expires">Sort by Expiration</option>
            </select>

            {{-- Unmet Toggle Switch --}}
            <label class="flex items-center gap-2.5 cursor-pointer select-none pl-2 border-l border-slate-200">
                <div class="relative">
                    <input type="checkbox" wire:model.live="onlyUnmet" class="sr-only peer">
                    <div class="w-9 h-5 rounded-full bg-slate-200 peer-checked:bg-rose-600 transition-colors"></div>
                    <div class="absolute top-0.5 left-0.5 h-4 w-4 rounded-full bg-white shadow-sm transition-transform peer-checked:translate-x-4"></div>
                </div>
                <span class="text-xs font-bold text-slate-600">Unmet Only</span>
            </label>
        </div>
    </div>

    {{-- ─── 3. Requirements Progressive Urgency Grouping ────────────── --}}

    {{-- SECTION 1: Action Needed / Missing Requirements --}}
    @if(count($this->urgentRows) > 0)
        <div class="rounded-2xl bg-white/90 backdrop-blur-xl border border-rose-200/80 overflow-hidden shadow-sm">
            <div class="px-5 py-3.5 bg-rose-50/60 border-b border-rose-100 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="h-2.5 w-2.5 rounded-full bg-rose-500 animate-ping"></span>
                    <h3 class="text-xs font-extrabold text-rose-800 uppercase tracking-wider">Action Needed ({{ count($this->urgentRows) }})</h3>
                </div>
                <span class="text-[11px] font-bold text-rose-600">Upload document required to reach compliance</span>
            </div>

            <div class="divide-y divide-slate-100">
                @foreach($this->urgentRows as $r)
                    @include('livewire.departments.partials.requirement-row', ['r' => $r, 'department' => $department])
                @endforeach
            </div>
        </div>
    @endif

    {{-- SECTION 2: Pending Approval Submissions --}}
    @if(count($this->pendingRows) > 0)
        <div class="rounded-2xl bg-white/90 backdrop-blur-xl border border-amber-200/80 overflow-hidden shadow-sm">
            <div class="px-5 py-3.5 bg-amber-50/60 border-b border-amber-100 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="h-2.5 w-2.5 rounded-full bg-amber-500"></span>
                    <h3 class="text-xs font-extrabold text-amber-800 uppercase tracking-wider">Pending Review ({{ count($this->pendingRows) }})</h3>
                </div>
                <span class="text-[11px] font-bold text-amber-600">Under admin review</span>
            </div>

            <div class="divide-y divide-slate-100">
                @foreach($this->pendingRows as $r)
                    @include('livewire.departments.partials.requirement-row', ['r' => $r, 'department' => $department])
                @endforeach
            </div>
        </div>
    @endif

    {{-- SECTION 3: Compliant & Met Requirements --}}
    @if(count($this->okRows) > 0)
        <div class="rounded-2xl bg-white/90 backdrop-blur-xl border border-emerald-200/80 overflow-hidden shadow-sm">
            <div class="px-5 py-3.5 bg-emerald-50/60 border-b border-emerald-100 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                    <h3 class="text-xs font-extrabold text-emerald-800 uppercase tracking-wider">Compliant Requirements ({{ count($this->okRows) }})</h3>
                </div>
                <span class="text-[11px] font-bold text-emerald-600">Requirement met</span>
            </div>

            <div class="divide-y divide-slate-100">
                @foreach($this->okRows as $r)
                    @include('livewire.departments.partials.requirement-row', ['r' => $r, 'department' => $department])
                @endforeach
            </div>
        </div>
    @endif

    {{-- Empty State --}}
    @if(count($this->filteredSortedRows) === 0)
        <div class="py-16 text-center rounded-2xl bg-white/90 border border-slate-200/80 shadow-sm">
            <svg class="mx-auto h-10 w-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607Z"/></svg>
            <p class="text-sm font-bold text-slate-700 mt-2">No requirements match your filters</p>
            <p class="text-xs text-slate-400 mt-1">Try clearing your search term or selecting a different status filter.</p>
            <button type="button" wire:click="$set('search', ''); $set('status', 'all'); $set('onlyUnmet', false)"
                class="mt-4 px-3.5 py-1.5 rounded-xl bg-indigo-50 text-indigo-600 text-xs font-bold hover:bg-indigo-100 transition-colors">
                Clear all filters
            </button>
        </div>
    @endif

</div>

@push('modals')
    {{-- Sub-components (upload modal, recent uploads modal) --}}
    <livewire:requirements.upload :key="'uploader-' . $department->id" />
    <livewire:requirements.recent-uploads />
@endpush
