{{-- Requirements Index — Livewire component view --}}
@section('title', 'Requirements Catalogue — Compliance')
@section('page-title', 'Requirements Catalogue')
@section('page-subtitle', 'Manage document compliance requirements master catalogue')

<div class="space-y-6">

    {{-- ─── 1. Header Bar ────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-violet-500 via-purple-600 to-indigo-600 shadow-lg shadow-purple-200 shrink-0">
                <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v2.25A2.25 2.25 0 0113.5 21.75h-9a2.25 2.25 0 01-2.25-2.25V5.25A2.25 2.25 0 014.5 3h9a2.25 2.25 0 012.25 2.25v2.25M7.5 7.5h13.5m-13.5 4.5h13.5m-13.5 4.5h13.5"/></svg>
            </div>
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Requirements Catalogue</h1>
                <p class="text-xs font-medium text-slate-500 mt-0.5">{{ $items->total() }} document requirement definitions in catalogue</p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('requirements.assign') }}"
                class="inline-flex items-center gap-2 rounded-xl bg-white border border-slate-200/80 px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-50 shadow-sm transition-all active:scale-95">
                <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244"/></svg>
                Assign to Depts
            </a>
            <a href="{{ route('requirements.create') }}"
                class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 text-xs font-bold shadow-sm shadow-indigo-200 transition-all active:scale-95">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                New Requirement
            </a>
        </div>
    </div>

    {{-- ─── 2. Top Summary KPI Cards ────────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <x-compliance.stat-card
            title="Catalogue Master"
            :value="$kpi['total']"
            icon="clipboard-document-list"
            accent="purple"
            subtitle="Total requirement definitions"
        />

        <x-compliance.stat-card
            title="Approval Required"
            :value="$kpi['approval']"
            icon="shield-check"
            accent="indigo"
            subtitle="Admin review required"
        />

        <x-compliance.stat-card
            title="Recurring Schedule"
            :value="$kpi['recurring']"
            icon="arrow-path"
            accent="emerald"
            subtitle="Yearly, quarterly, monthly"
        />

        <x-compliance.stat-card
            title="Unassigned Units"
            :value="$kpi['unassigned']"
            icon="exclamation-triangle"
            accent="amber"
            subtitle="Not assigned to any dept"
        />
    </div>

    {{-- ─── 3. Controls & Toolbar ───────────────────────────────────── --}}
    <div class="rounded-2xl bg-white/90 backdrop-blur-xl border border-slate-200/80 p-4 shadow-sm flex flex-wrap items-center justify-between gap-4">
        {{-- Search Input --}}
        <div class="relative flex-1 min-w-[240px]">
            <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 h-4 w-4 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search requirement by code or name…"
                class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 bg-white text-xs font-medium text-slate-800 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all">
        </div>

        {{-- Frequency Filter Chips --}}
        <x-compliance.filter-chips
            :options="[
                ['value' => '', 'label' => 'All Frequencies'],
                ['value' => 'once', 'label' => 'One-time', 'color' => 'slate'],
                ['value' => 'yearly', 'label' => 'Yearly', 'color' => 'indigo'],
                ['value' => 'quarterly', 'label' => 'Quarterly', 'color' => 'amber'],
                ['value' => 'monthly', 'label' => 'Monthly', 'color' => 'emerald']
            ]"
            :selected="$filterFreq"
            wireModel="filterFreq"
        />

        <div class="flex items-center gap-3">
            {{-- Approval Filter Select --}}
            <select wire:model.live="filterApproval" class="rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-700 py-2 px-3 outline-none focus:ring-2 focus:ring-indigo-500/20">
                <option value="">Approval: All</option>
                <option value="1">Approval Required</option>
                <option value="0">Auto-approved</option>
            </select>

            {{-- Per Page Select --}}
            <select wire:model.live="perPage" class="rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-700 py-2 px-3 outline-none focus:ring-2 focus:ring-indigo-500/20">
                <option value="12">12 per page</option>
                <option value="24">24 per page</option>
                <option value="48">48 per page</option>
            </select>

            {{-- View Mode Toggle --}}
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

    {{-- ─── 4. Main View Area (Grid Card View vs Table List View) ────── --}}
    @if($viewMode === 'grid')
        {{-- GRID CARD VIEW --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($items as $r)
                @php
                    $freqBadge = match($r->frequency) {
                        'yearly' => 'bg-indigo-100 text-indigo-800',
                        'quarterly' => 'bg-amber-100 text-amber-800',
                        'monthly' => 'bg-emerald-100 text-emerald-800',
                        default => 'bg-slate-100 text-slate-700',
                    };
                @endphp

                <div wire:key="grid-req-{{ $r->id }}"
                    class="rounded-2xl bg-white/90 backdrop-blur-xl border border-slate-200/80 p-5 shadow-sm hover:shadow-md transition-all duration-300 group flex flex-col justify-between">
                    
                    <div>
                        {{-- Card Header --}}
                        <div class="flex items-start justify-between gap-3 mb-3">
                            <span class="font-mono text-xs font-bold text-slate-700 bg-slate-100 px-2 py-0.5 rounded">
                                {{ $r->code }}
                            </span>

                            <div class="flex items-center gap-1.5">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold {{ $freqBadge }}">
                                    {{ ucfirst($r->frequency) }}
                                </span>
                                @if($r->requires_approval)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-violet-50 text-violet-700 border border-violet-200">
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751A11.959 11.959 0 0112 2.714z"/></svg>
                                        Approval
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Requirement Title & Description --}}
                        <h3 class="text-sm font-extrabold text-slate-900 group-hover:text-indigo-600 transition-colors mb-1">
                            {{ $r->name }}
                        </h3>

                        @if ($r->description)
                            <p class="text-xs text-slate-500 line-clamp-2 mb-3">
                                {{ $r->description }}
                            </p>
                        @endif
                    </div>

                    {{-- Card Footer --}}
                    <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-600">
                            {{ $r->assignments_count }} department(s) assigned
                        </span>

                        <div class="flex items-center gap-2">
                            <a href="{{ route('requirements.departments', $r) }}"
                                class="inline-flex items-center gap-1 text-xs font-bold text-slate-600 hover:text-indigo-600 transition-colors p-1">
                                Depts
                            </a>
                            <a href="{{ route('requirements.edit', $r) }}"
                                class="inline-flex items-center gap-1 rounded-xl bg-indigo-50 text-indigo-700 hover:bg-indigo-100 px-3 py-1.5 text-xs font-bold transition-all">
                                Edit
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-16 text-center rounded-2xl bg-white/90 border border-slate-200/80 shadow-sm">
                    <svg class="mx-auto h-10 w-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                    <p class="text-sm font-bold text-slate-700 mt-2">No requirement definitions found</p>
                    <p class="text-xs text-slate-400 mt-1">Try adjusting your search filters or add a new requirement.</p>
                    <a href="{{ route('requirements.create') }}"
                        class="mt-4 inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-indigo-600 text-white text-xs font-bold hover:bg-indigo-700 transition-colors">
                        Add New Requirement
                    </a>
                </div>
            @endforelse
        </div>
    @else
        {{-- TABLE LIST VIEW --}}
        <div class="rounded-2xl bg-white/90 backdrop-blur-xl border border-slate-200/80 overflow-hidden shadow-sm">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-200/80 bg-slate-50/70">
                        <x-compliance.sort-header field="code" label="Code" :currentSort="$sort" :currentDir="$dir" />
                        <x-compliance.sort-header field="name" label="Requirement Name" :currentSort="$sort" :currentDir="$dir" />
                        <x-compliance.sort-header field="frequency" label="Frequency" :currentSort="$sort" :currentDir="$dir" />
                        <x-compliance.sort-header field="min_count" label="Min Files" :currentSort="$sort" :currentDir="$dir" />
                        <th class="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Approval</th>
                        <th class="px-5 py-3.5 text-right text-xs font-bold uppercase tracking-wider text-slate-500">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($items as $r)
                        <tr wire:key="list-req-{{ $r->id }}" class="hover:bg-slate-50/80 transition-colors group">
                            <td class="px-5 py-4">
                                <span class="font-mono text-xs font-bold text-slate-700 bg-slate-100 px-2 py-0.5 rounded">
                                    {{ $r->code }}
                                </span>
                            </td>
                            <td class="px-4 py-4">
                                <p class="text-sm font-bold text-slate-900 group-hover:text-indigo-600 transition-colors">{{ $r->name }}</p>
                                @if($r->description)
                                    <p class="text-xs text-slate-400 truncate max-w-md">{{ $r->description }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-700">
                                    {{ ucfirst($r->frequency) }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-xs font-bold text-slate-700">
                                {{ $r->min_count }} file(s)
                            </td>
                            <td class="px-5 py-4">
                                @if($r->requires_approval)
                                    <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-bold bg-violet-50 text-violet-700 border border-violet-200">
                                        Required
                                    </span>
                                @else
                                    <span class="text-xs text-slate-400">Auto</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right space-x-2">
                                <a href="{{ route('requirements.departments', $r) }}"
                                    class="inline-flex items-center text-xs font-bold text-slate-600 hover:text-indigo-600 transition-colors">
                                    Depts
                                </a>
                                <a href="{{ route('requirements.edit', $r) }}"
                                    class="inline-flex items-center gap-1 rounded-xl bg-indigo-50 text-indigo-700 hover:bg-indigo-100 px-3 py-1.5 text-xs font-bold transition-all">
                                    Edit
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-xs text-slate-400">
                                No requirements match your query.
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
