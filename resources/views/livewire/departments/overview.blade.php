{{-- Departments Overview — Livewire component view --}}
@section('title', 'Departments — Compliance')
@section('page-title', 'Departments')
@section('page-subtitle', 'Compliance status per department')

<div class="space-y-6">

    {{-- ─── 1. Header Bar ────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-500 via-indigo-600 to-violet-600 shadow-lg shadow-indigo-200 shrink-0">
                <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5s.75 0 .75.75v1.5s0 .75-.75.75H9s-.75 0-.75-.75v-1.5s0-.75.75-.75zm0 5.25h1.5s.75 0 .75.75v1.5s0 .75-.75.75H9s-.75 0-.75-.75v-1.5s0-.75.75-.75zm0 5.25h1.5s.75 0 .75.75v1.5s0 .75-.75.75H9s-.75 0-.75-.75v-1.5s0-.75.75-.75zm4.5-10.5h1.5s.75 0 .75.75v1.5s0 .75-.75.75h-1.5s-.75 0-.75-.75v-1.5s0-.75.75-.75zm0 5.25h1.5s.75 0 .75.75v1.5s0 .75-.75.75h-1.5s-.75 0-.75-.75v-1.5s0-.75.75-.75zm0 5.25h1.5s.75 0 .75.75v1.5s0 .75-.75.75h-1.5s-.75 0-.75-.75v-1.5s0-.75.75-.75z" />
                </svg>
            </div>
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Departments Directory</h1>
                <p class="text-xs font-medium text-slate-500 mt-0.5">{{ $items->total() }} departments registered for compliance tracking</p>
            </div>
        </div>

        <a href="{{ route('compliance.dashboard') }}"
            class="inline-flex items-center gap-2 rounded-xl bg-white border border-slate-200/80 px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-50 shadow-sm transition-all active:scale-95">
            <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/></svg>
            Back to Dashboard
        </a>
    </div>

    {{-- ─── 2. Top Summary KPI Cards ────────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <x-compliance.stat-card
            title="Total Units"
            :value="$kpi['count']"
            icon="building-office"
            accent="blue"
            subtitle="Matching current filters"
        />

        <x-compliance.stat-card
            title="Fully Compliant"
            :value="$kpi['complete']"
            icon="shield-check"
            accent="emerald"
            subtitle="100% requirements met"
        />

        <x-compliance.stat-card
            title="Incomplete Units"
            :value="$kpi['incomplete']"
            icon="exclamation-triangle"
            accent="amber"
            subtitle="Has unmet requirements"
        />

        <x-compliance.stat-card
            title="Avg Compliance"
            :value="$kpi['avg'].'%' "
            icon="chart-bar"
            accent="indigo"
            subtitle="Selected dataset average"
        />
    </div>

    {{-- ─── 3. Controls & Toolbar ───────────────────────────────────── --}}
    <div class="rounded-2xl bg-white/90 backdrop-blur-xl border border-slate-200/80 p-4 shadow-sm flex flex-wrap items-center justify-between gap-4">
        {{-- Search Input --}}
        <div class="relative flex-1 min-w-[240px]">
            <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 h-4 w-4 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search by department name or code…"
                class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 bg-white text-xs font-medium text-slate-800 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all">
        </div>

        {{-- Filter Chips --}}
        <x-compliance.filter-chips
            :options="[
                ['value' => '', 'label' => 'All Buckets', 'count' => $kpi['count']],
                ['value' => '0-49', 'label' => 'Critical (0–49%)', 'color' => 'rose'],
                ['value' => '50-99', 'label' => 'Warning (50–99%)', 'color' => 'amber'],
                ['value' => '100', 'label' => '100% Complete', 'color' => 'emerald']
            ]"
            :selected="$bucket"
            wireModel="bucket"
        />

        <div class="flex items-center gap-3">
            {{-- Per Page Selector --}}
            <select wire:model.live="perPage" class="rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-700 py-2 px-3 outline-none focus:ring-2 focus:ring-indigo-500/20">
                <option value="12">12 per page</option>
                <option value="24">24 per page</option>
                <option value="48">48 per page</option>
            </select>

            {{-- View Mode Toggle (Grid vs List) --}}
            <div class="flex items-center p-1 rounded-xl bg-slate-100/90 border border-slate-200/70">
                <button type="button" wire:click="setViewMode('grid')"
                    title="Grid Card View"
                    class="p-1.5 rounded-lg transition-all {{ $viewMode === 'grid' ? 'bg-white text-indigo-600 shadow-xs font-bold' : 'text-slate-500 hover:text-slate-900' }}">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 8.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/></svg>
                </button>

                <button type="button" wire:click="setViewMode('list')"
                    title="Table List View"
                    class="p-1.5 rounded-lg transition-all {{ $viewMode === 'list' ? 'bg-white text-indigo-600 shadow-xs font-bold' : 'text-slate-500 hover:text-slate-900' }}">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm0 5.25h.007v.008H3.75V12zm0 5.25h.007v.008H3.75v-.008z"/></svg>
                </button>
            </div>
        </div>
    </div>

    {{-- ─── 4. Main View Container (Grid Card View vs Table List View) ─ --}}
    @if($viewMode === 'grid')
        {{-- GRID CARD VIEW --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($items as $row)
                @php
                    $p = $row['percent'];
                    $dept = $row['dept'];
                    $borderClass = $p >= 80 ? 'border-emerald-200/80 hover:border-emerald-300' : ($p < 50 ? 'border-rose-200/80 hover:border-rose-300' : 'border-amber-200/80 hover:border-amber-300');
                    $accentBg = $p >= 80 ? 'from-emerald-500/10 to-teal-500/5' : ($p < 50 ? 'from-rose-500/10 to-red-500/5' : 'from-amber-500/10 to-orange-500/5');
                @endphp

                <a href="{{ route('departments.compliance', $dept) }}" wire:key="grid-dept-{{ $dept->id }}"
                    class="relative overflow-hidden rounded-2xl bg-white/90 backdrop-blur-xl border {{ $borderClass }} p-5 shadow-sm hover:shadow-md transition-all duration-300 group flex flex-col justify-between">
                    
                    {{-- Ambient top glow --}}
                    <div class="absolute -right-8 -top-8 h-28 w-28 rounded-full bg-gradient-to-br {{ $accentBg }} blur-2xl group-hover:scale-150 transition-transform duration-500"></div>

                    <div>
                        {{-- Card Header --}}
                        <div class="flex items-start justify-between gap-3 relative z-10 mb-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 via-indigo-600 to-blue-600 text-white font-bold text-sm shadow-md shadow-indigo-200 shrink-0 group-hover:scale-105 transition-transform">
                                    {{ strtoupper(mb_substr($dept->name, 0, 2)) }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <h3 class="text-sm font-extrabold text-slate-900 group-hover:text-indigo-600 transition-colors truncate">
                                        {{ $dept->name }}
                                    </h3>
                                    <span class="px-1.5 py-0.2 rounded text-[10px] font-mono font-bold uppercase bg-slate-100 text-slate-500">
                                        {{ $dept->code ?? '—' }}
                                    </span>
                                </div>
                            </div>

                            <x-compliance.progress-ring
                                :percent="$p"
                                size="md"
                                :strokeWidth="6"
                            />
                        </div>
                    </div>

                    {{-- Card Footer --}}
                    <div class="relative z-10 pt-4 border-t border-slate-100 flex items-center justify-between">
                        <x-compliance.status-badge
                            :status="$p >= 100 ? 'compliant' : ($p < 50 ? 'critical' : 'warning')"
                            size="sm"
                        />

                        <span class="inline-flex items-center text-xs font-bold text-indigo-600 group-hover:translate-x-0.5 transition-transform">
                            View Compliance →
                        </span>
                    </div>
                </a>
            @empty
                <div class="col-span-full py-16 text-center rounded-2xl bg-white/90 border border-slate-200/80 shadow-sm">
                    <svg class="mx-auto h-10 w-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607Z"/></svg>
                    <p class="text-sm font-bold text-slate-700 mt-2">No departments found</p>
                    <p class="text-xs text-slate-400 mt-1">Try adjusting your search query or filter options.</p>
                    <button type="button" wire:click="$set('search', ''); $set('status', 'all'); $set('bucket', '')"
                        class="mt-4 px-3.5 py-1.5 rounded-xl bg-indigo-50 text-indigo-600 text-xs font-bold hover:bg-indigo-100 transition-colors">
                        Clear all filters
                    </button>
                </div>
            @endforelse
        </div>
    @else
        {{-- TABLE LIST VIEW --}}
        <div class="rounded-2xl bg-white/90 backdrop-blur-xl border border-slate-200/80 overflow-hidden shadow-sm">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-200/80 bg-slate-50/70">
                        <th class="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Department</th>
                        <x-compliance.sort-header field="code" label="Code" :currentSort="$sort" :currentDir="$dir" />
                        <x-compliance.sort-header field="percent" label="Compliance %" :currentSort="$sort" :currentDir="$dir" />
                        <th class="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Status</th>
                        <th class="px-5 py-3.5 text-right text-xs font-bold uppercase tracking-wider text-slate-500">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($items as $row)
                        @php
                            $p = $row['percent'];
                            $dept = $row['dept'];
                            $barColor = $p >= 80 ? 'bg-emerald-500' : ($p < 50 ? 'bg-rose-500' : 'bg-amber-500');
                        @endphp
                        <tr wire:key="list-dept-{{ $dept->id }}" class="hover:bg-slate-50/80 transition-colors group">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 to-blue-600 text-white font-bold text-xs shadow-xs shrink-0">
                                        {{ strtoupper(mb_substr($dept->name, 0, 2)) }}
                                    </div>
                                    <a href="{{ route('departments.compliance', $dept) }}" class="text-sm font-bold text-slate-900 group-hover:text-indigo-600 transition-colors">
                                        {{ $dept->name }}
                                    </a>
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <span class="font-mono text-xs font-bold text-slate-600 bg-slate-100 px-2 py-0.5 rounded">
                                    {{ $dept->code ?? '—' }}
                                </span>
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-3 w-48">
                                    <div class="flex-1 h-2.5 bg-slate-100 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full {{ $barColor }} transition-all" style="width: {{ $p }}%"></div>
                                    </div>
                                    <span class="text-xs font-extrabold text-slate-800 w-10 text-right">{{ $p }}%</span>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <x-compliance.status-badge
                                    :status="$p >= 100 ? 'compliant' : ($p < 50 ? 'critical' : 'warning')"
                                    size="sm"
                                />
                            </td>
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('departments.compliance', $dept) }}"
                                    class="inline-flex items-center gap-1 text-xs font-bold text-indigo-600 hover:text-indigo-800 transition-colors">
                                    Manage →
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-12 text-center text-xs text-slate-400">
                                No departments match your query.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif

    {{-- ─── 5. Pagination ────────────────────────────────────────────── --}}
    <div class="mt-6">
        {{ $items->links() }}
    </div>

</div>
