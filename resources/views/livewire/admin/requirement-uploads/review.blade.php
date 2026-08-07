{{-- Requirement Uploads Review — Admin Livewire component view --}}
@section('title', 'Review Uploads — Compliance')
@section('page-title', 'Review Uploads')
@section('page-subtitle', 'Manage and verify department document submissions')

<div x-data="{ decisionPanelOpen: false }"
    @open-decision-modal.window="decisionPanelOpen = true"
    @close-decision-modal.window="decisionPanelOpen = false"
    class="space-y-6">

    {{-- ─── 1. Header Bar ────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500 via-indigo-600 to-blue-600 shadow-lg shadow-indigo-200 shrink-0">
                <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751A11.959 11.959 0 0112 2.714z"/></svg>
            </div>
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Review Submissions Queue</h1>
                <p class="text-xs font-medium text-slate-500 mt-0.5">Showing {{ $rows->total() }} submissions requiring verification</p>
            </div>
        </div>

        <button wire:click="exportCsv"
            class="inline-flex items-center gap-2 rounded-xl bg-white border border-slate-200/80 px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-50 shadow-sm transition-all active:scale-95">
            <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
            Export Data CSV
        </button>
    </div>

    {{-- ─── 2. Controls & Toolbar ───────────────────────────────────── --}}
    <div class="rounded-2xl bg-white/90 backdrop-blur-xl border border-slate-200/80 p-4 shadow-sm space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-4">
            {{-- Search Input --}}
            <div class="relative flex-1 min-w-[240px]">
                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 h-4 w-4 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                <input type="text" wire:model.live.debounce.300ms="q" placeholder="Search file name, requirement, department…"
                    class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 bg-white text-xs font-medium text-slate-800 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all">
            </div>

            {{-- Status Filter Chips --}}
            <x-compliance.filter-chips
                :options="[
                    ['value' => 'pending', 'label' => 'Pending Review', 'count' => $counts['pending'], 'color' => 'amber'],
                    ['value' => 'approved', 'label' => 'Approved', 'count' => $counts['approved'], 'color' => 'emerald'],
                    ['value' => 'rejected', 'label' => 'Rejected', 'count' => $counts['rejected'], 'color' => 'rose'],
                    ['value' => 'all', 'label' => 'All Submissions', 'count' => $counts['all']]
                ]"
                :selected="$status"
                wireModel="status"
            />

            {{-- View Mode Toggle (Kanban vs Table) --}}
            <div class="flex items-center p-1 rounded-xl bg-slate-100/90 border border-slate-200/70">
                <button type="button" wire:click="setViewMode('kanban')"
                    title="Kanban Board View"
                    class="p-1.5 rounded-lg transition-all {{ $viewMode === 'kanban' ? 'bg-white text-indigo-600 shadow-xs font-bold' : 'text-slate-500 hover:text-slate-900' }}">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 4.5v15m6-15v15m-10.875 0h15.75c.621 0 1.125-.504 1.125-1.125V5.625c0-.621-.504-1.125-1.125-1.125H4.125C3.504 4.5 3 5.004 3 5.625v12.75c0 .621.504 1.125 1.125 1.125z"/></svg>
                </button>

                <button type="button" wire:click="setViewMode('table')"
                    title="Table List View"
                    class="p-1.5 rounded-lg transition-all {{ $viewMode === 'table' ? 'bg-white text-indigo-600 shadow-xs font-bold' : 'text-slate-500 hover:text-slate-900' }}">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm0 5.25h.007v.008H3.75V12zm0 5.25h.007v.008H3.75v-.008z"/></svg>
                </button>
            </div>
        </div>

        {{-- Sub-filters Row: MIME, Date range, Expiring toggle --}}
        <div class="flex flex-wrap items-center justify-between gap-3 pt-3 border-t border-slate-100 text-xs">
            <div class="flex flex-wrap items-center gap-3">
                {{-- MIME Type Filter --}}
                <select wire:model.live="mime_like" class="rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-700 py-1.5 px-3 outline-none focus:ring-2 focus:ring-indigo-500/20">
                    <option value="">All file types</option>
                    <option value="pdf">PDF documents</option>
                    <option value="image">Image scans</option>
                    <option value="spread">Spreadsheets (Excel)</option>
                    <option value="word">Word documents</option>
                </select>

                {{-- Date Range --}}
                <div class="flex items-center gap-1.5 bg-slate-50 px-2 py-1 rounded-xl border border-slate-200">
                    <span class="text-slate-400 font-bold uppercase text-[10px]">Date</span>
                    <input type="date" wire:model.live="date_from" class="bg-transparent text-slate-700 text-xs outline-none">
                    <span class="text-slate-300">→</span>
                    <input type="date" wire:model.live="date_to" class="bg-transparent text-slate-700 text-xs outline-none">

                    <button type="button" wire:click="setRange('7d')" class="px-2 py-0.5 rounded text-[10px] font-bold bg-white text-indigo-600 shadow-2xs border">7d</button>
                    <button type="button" wire:click="setRange('30d')" class="px-2 py-0.5 rounded text-[10px] font-bold bg-white text-indigo-600 shadow-2xs border">30d</button>
                    <button type="button" wire:click="clearDateRange" class="text-indigo-600 hover:underline font-bold text-[10px] ml-1">Clear</button>
                </div>
            </div>

            {{-- Expiring <= 30d Toggle --}}
            <label class="flex items-center gap-2 cursor-pointer select-none">
                <div class="relative">
                    <input type="checkbox" wire:model.live="only_expiring" class="sr-only peer">
                    <div class="w-8 h-4 rounded-full bg-slate-200 peer-checked:bg-rose-500 transition-colors"></div>
                    <div class="absolute top-0.5 left-0.5 h-3 w-3 rounded-full bg-white shadow-xs transition-transform peer-checked:translate-x-4"></div>
                </div>
                <span class="text-xs font-bold text-slate-600">Expiring ≤ 30d</span>
            </label>
        </div>
    </div>

    {{-- ─── 3. Main View Area (Kanban Board vs Table List) ───────────── --}}
    @if($viewMode === 'kanban')
        {{-- KANBAN BOARD VIEW --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- COLUMN 1: PENDING REVIEW --}}
            <div class="rounded-2xl bg-slate-50/70 border border-slate-200/80 p-4 space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="h-2.5 w-2.5 rounded-full bg-amber-500"></span>
                        <h3 class="text-xs font-extrabold text-slate-800 uppercase tracking-wider">Pending Review</h3>
                    </div>
                    <span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 text-xs font-extrabold">
                        {{ count($kanbanColumns['pending'] ?? []) }}
                    </span>
                </div>

                <div class="space-y-3">
                    @forelse($kanbanColumns['pending'] ?? [] as $u)
                        @include('livewire.admin.requirement-uploads.partials.kanban-card', ['u' => $u])
                    @empty
                        <div class="py-12 text-center text-xs text-slate-400 border border-dashed border-slate-200 rounded-xl bg-white">
                            No pending submissions
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- COLUMN 2: APPROVED --}}
            <div class="rounded-2xl bg-slate-50/70 border border-slate-200/80 p-4 space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                        <h3 class="text-xs font-extrabold text-slate-800 uppercase tracking-wider">Approved</h3>
                    </div>
                    <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-xs font-extrabold">
                        {{ count($kanbanColumns['approved'] ?? []) }}
                    </span>
                </div>

                <div class="space-y-3">
                    @forelse($kanbanColumns['approved'] ?? [] as $u)
                        @include('livewire.admin.requirement-uploads.partials.kanban-card', ['u' => $u])
                    @empty
                        <div class="py-12 text-center text-xs text-slate-400 border border-dashed border-slate-200 rounded-xl bg-white">
                            No approved submissions
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- COLUMN 3: REJECTED --}}
            <div class="rounded-2xl bg-slate-50/70 border border-slate-200/80 p-4 space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="h-2.5 w-2.5 rounded-full bg-rose-500"></span>
                        <h3 class="text-xs font-extrabold text-slate-800 uppercase tracking-wider">Rejected</h3>
                    </div>
                    <span class="px-2 py-0.5 rounded-full bg-rose-100 text-rose-800 text-xs font-extrabold">
                        {{ count($kanbanColumns['rejected'] ?? []) }}
                    </span>
                </div>

                <div class="space-y-3">
                    @forelse($kanbanColumns['rejected'] ?? [] as $u)
                        @include('livewire.admin.requirement-uploads.partials.kanban-card', ['u' => $u])
                    @empty
                        <div class="py-12 text-center text-xs text-slate-400 border border-dashed border-slate-200 rounded-xl bg-white">
                            No rejected submissions
                        </div>
                    @endforelse
                </div>
            </div>

        </div>
    @else
        {{-- TABLE LIST VIEW --}}
        <div class="rounded-2xl bg-white/90 backdrop-blur-xl border border-slate-200/80 overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/80 border-b border-slate-200/80 text-xs font-bold text-slate-500 uppercase tracking-wider">
                            <td class="px-4 py-3.5 w-10">
                                <input type="checkbox" wire:click="togglePageSelection($event.target.checked)"
                                    class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 w-4 h-4 cursor-pointer">
                            </td>
                            <x-compliance.sort-header field="requirements.name" label="Requirement" :currentSort="$sort" :currentDir="$dir" />
                            <x-compliance.sort-header field="dept_name" label="Department" :currentSort="$sort" :currentDir="$dir" />
                            <th class="px-4 py-3.5">File Details</th>
                            <x-compliance.sort-header field="status" label="Status" :currentSort="$sort" :currentDir="$dir" />
                            <x-compliance.sort-header field="valid_until" label="Validity" :currentSort="$sort" :currentDir="$dir" />
                            <th class="px-4 py-3.5 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($rows as $u)
                            @php
                                $daysLeft = $u->valid_until ? now()->diffInDays($u->valid_until, false) : null;
                            @endphp
                            <tr wire:key="upload-row-{{ $u->id }}"
                                class="hover:bg-slate-50/70 transition-colors {{ in_array($u->id, $selected) ? 'bg-indigo-50/40' : '' }}">
                                <td class="px-4 py-4">
                                    <input type="checkbox" wire:model.live="selected" value="{{ $u->id }}"
                                        class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 w-4 h-4 cursor-pointer">
                                </td>
                                <td class="px-4 py-4">
                                    <p class="text-sm font-bold text-slate-900">{{ $u->req_name }}</p>
                                    <span class="font-mono text-xs font-bold text-slate-500 bg-slate-100 px-1.5 py-0.5 rounded">
                                        {{ $u->req_code }}
                                    </span>
                                </td>
                                <td class="px-4 py-4">
                                    <p class="text-sm font-semibold text-slate-800">{{ $u->dept_name }}</p>
                                    <p class="text-xs text-slate-400">{{ $u->dept_code }}</p>
                                </td>
                                <td class="px-4 py-4">
                                    <p class="text-sm font-bold text-slate-800 truncate max-w-[200px]" title="{{ $u->original_name }}">
                                        {{ $u->original_name }}
                                    </p>
                                    <p class="text-[11px] text-slate-400 mt-0.5">
                                        {{ Str::limit(str_replace('application/', '', $u->mime_type), 15) }} ·
                                        {{ number_format($u->size / 1024, 1) }} KB
                                    </p>
                                </td>
                                <td class="px-4 py-4">
                                    <x-compliance.status-badge
                                        :status="$u->status === 'approved' ? 'compliant' : ($u->status === 'pending' ? 'pending' : 'critical')"
                                        size="sm"
                                    />
                                </td>
                                <td class="px-4 py-4">
                                    <p class="text-xs font-semibold text-slate-700">
                                        {{ $u->valid_from?->format('d M Y') ?? '—' }} → {{ $u->valid_until?->format('d M Y') ?? '—' }}
                                    </p>
                                    @if (!is_null($daysLeft))
                                        <span class="inline-flex mt-0.5 rounded px-1.5 py-0.5 text-[10px] font-bold {{ $daysLeft < 0 ? 'bg-rose-100 text-rose-700' : ($daysLeft <= 14 ? 'bg-amber-100 text-amber-800' : 'text-slate-500') }}">
                                            {{ $daysLeft < 0 ? 'Expired' : "Expires in {$daysLeft}d" }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-4 text-right space-x-2">
                                    <a href="{{ URL::signedRoute('uploads.download', ['upload' => $u->id]) }}" target="_blank"
                                        class="inline-flex items-center justify-center p-2 rounded-xl border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 transition-colors shadow-2xs"
                                        title="Download document">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                                    </a>

                                    @can('approve-requirements')
                                        <button type="button" wire:click="openDecision({{ $u->id }})"
                                            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-xs shadow-indigo-200 transition-all active:scale-95">
                                            Decide
                                        </button>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-16 text-center text-xs text-slate-400">
                                    No submission records match your search query.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-5 py-4 border-t border-slate-100 flex justify-between items-center bg-slate-50/50">
                <p class="text-xs font-medium text-slate-500">
                    Showing {{ $rows->firstItem() ?? 0 }}–{{ $rows->lastItem() ?? 0 }} of {{ $rows->total() ?? 0 }} submissions
                </p>
                {{ $rows->links() }}
            </div>
        </div>
    @endif

    {{-- ─── 4. Floating Sticky Bulk Action Bar ──────────────────────── --}}
    @if (count($selected) > 0)
        <div class="fixed bottom-6 left-1/2 -translate-x-1/2 z-40 animate-slide-up">
            <div class="rounded-2xl bg-white/95 backdrop-blur-xl border border-slate-200/80 p-3.5 shadow-2xl flex items-center gap-4">
                <div class="flex items-center gap-2 border-r border-slate-200 pr-4">
                    <span class="flex items-center justify-center h-6 w-6 rounded-full bg-indigo-600 text-white text-xs font-extrabold">
                        {{ count($selected) }}
                    </span>
                    <span class="text-xs font-bold text-slate-700">Submissions Selected</span>
                </div>

                <div class="flex items-center gap-2">
                    @can('approve-requirements')
                        <button type="button" wire:click="bulkApprove"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-sm transition-all active:scale-95">
                            Approve Selected
                        </button>
                        <button type="button" wire:click="bulkReject"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-sm transition-all active:scale-95">
                            Reject Selected
                        </button>
                    @endcan
                    <button type="button" wire:click="clearSelection"
                        class="ml-2 text-xs font-bold text-slate-500 hover:text-slate-800">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ─── 5. In-App Split Document Preview & Decision Slide-Over Panel ── --}}
    <template x-teleport="body">
        <div x-show="decisionPanelOpen" class="relative z-[100]" x-cloak>
            {{-- Backdrop --}}
            <div x-show="decisionPanelOpen" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm"
                @click="decisionPanelOpen = false"></div>

            <div class="fixed inset-0 overflow-hidden">
                <div class="absolute inset-0 overflow-hidden">
                    <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-6 sm:pl-10">
                        {{-- Slide-over Panel (Wide Split Screen View) --}}
                        <div x-show="decisionPanelOpen"
                            x-transition:enter="transform transition ease-out duration-300"
                            x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
                            x-transition:leave="transform transition ease-in duration-200"
                            x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
                            class="pointer-events-auto w-screen max-w-5xl">

                            <div class="flex h-full flex-col bg-white shadow-2xl">
                                {{-- Slide-over Header --}}
                                <div class="px-6 py-4 border-b border-slate-200/80 bg-slate-50/80 flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-600 text-white font-bold">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                                        </div>
                                        <div>
                                            <h2 class="text-base font-extrabold text-slate-900">Document Verification Workbench</h2>
                                            <p class="text-xs text-slate-500">Inspect file submission & apply decision</p>
                                        </div>
                                    </div>

                                    <button type="button" @click="decisionPanelOpen = false"
                                        class="p-2 rounded-xl text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition-colors">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>

                                {{-- Slide-over Body: Split View (Left Document Preview / Right Decision Form) --}}
                                @if($active)
                                    <div class="flex-1 grid grid-cols-1 lg:grid-cols-12 overflow-hidden divide-y lg:divide-y-0 lg:divide-x divide-slate-200">

                                        {{-- LEFT: In-App Document Preview (7 Cols) --}}
                                        <div class="lg:col-span-7 bg-slate-900/95 flex flex-col h-full overflow-hidden relative">
                                            <div class="p-3 bg-slate-800 text-slate-300 flex items-center justify-between text-xs border-b border-slate-700">
                                                <span class="font-bold truncate max-w-xs text-white" title="{{ $active['original_name'] }}">
                                                    {{ $active['original_name'] }}
                                                </span>
                                                <a href="{{ $active['download_url'] }}" target="_blank"
                                                    class="inline-flex items-center gap-1 text-[11px] font-bold text-indigo-400 hover:text-indigo-300">
                                                    Download Original ↗
                                                </a>
                                            </div>

                                            <div class="flex-1 flex items-center justify-center p-4 overflow-auto">
                                                @if (Str::contains($active['mime_type'], 'pdf'))
                                                    <iframe src="{{ $active['preview_url'] }}" class="w-full h-full rounded-xl border border-slate-700 shadow-2xl bg-white"></iframe>
                                                @elseif (Str::startsWith($active['mime_type'], 'image/'))
                                                    <img src="{{ $active['preview_url'] }}" class="max-h-full max-w-full object-contain rounded-xl shadow-2xl border border-slate-700" alt="document preview">
                                                @else
                                                    <div class="text-center p-8 bg-slate-800/80 rounded-2xl border border-slate-700 max-w-sm">
                                                        <svg class="mx-auto h-12 w-12 text-slate-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                                                        <p class="text-xs font-bold text-white">{{ $active['original_name'] }}</p>
                                                        <p class="text-[11px] text-slate-400 mt-1">Inline preview is not supported for {{ $active['mime_type'] }}.</p>
                                                        <a href="{{ $active['download_url'] }}" target="_blank"
                                                            class="mt-4 inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-indigo-600 text-white text-xs font-bold hover:bg-indigo-700 transition-colors">
                                                            Download & View File
                                                        </a>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                        {{-- RIGHT: Verification & Decision Workbench (5 Cols) --}}
                                        <div class="lg:col-span-5 p-6 overflow-y-auto space-y-5 bg-white flex flex-col justify-between">
                                            <div class="space-y-5">
                                                {{-- Submission Metadata --}}
                                                <div class="p-4 rounded-2xl bg-indigo-50/60 border border-indigo-100 space-y-3">
                                                    <div>
                                                        <span class="text-[10px] font-bold text-indigo-600 uppercase tracking-wider">Requirement</span>
                                                        <p class="text-sm font-extrabold text-slate-900">{{ $active['req_name'] }}</p>
                                                        <span class="font-mono text-[11px] font-bold text-slate-500">{{ $active['req_code'] }}</span>
                                                    </div>

                                                    <div class="pt-2 border-t border-indigo-100/80 flex items-center justify-between">
                                                        <div>
                                                            <span class="text-[10px] font-bold text-indigo-600 uppercase tracking-wider">Department</span>
                                                            <p class="text-xs font-bold text-slate-800">{{ $active['dept_name'] }}</p>
                                                        </div>
                                                        <div class="text-right">
                                                            <span class="text-[10px] font-bold text-indigo-600 uppercase tracking-wider">Validity Claim</span>
                                                            <p class="text-xs font-bold text-slate-800">{{ $active['valid_from'] ?? '—' }} → {{ $active['valid_until'] ?? '—' }}</p>
                                                        </div>
                                                    </div>
                                                </div>

                                                {{-- Preset Feedback Quick Templates --}}
                                                <div>
                                                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Quick Rejection Reason</label>
                                                    <div class="flex flex-wrap gap-1.5">
                                                        <button type="button" wire:click="applyPresetReason('Scan is blurry or illegible')"
                                                            class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-slate-100 text-slate-700 hover:bg-rose-50 hover:text-rose-700 transition-colors border">
                                                            Blurry / Illegible
                                                        </button>
                                                        <button type="button" wire:click="applyPresetReason('Expired document certificate')"
                                                            class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-slate-100 text-slate-700 hover:bg-rose-50 hover:text-rose-700 transition-colors border">
                                                            Expired Certificate
                                                        </button>
                                                        <button type="button" wire:click="applyPresetReason('Missing required signature or seal')"
                                                            class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-slate-100 text-slate-700 hover:bg-rose-50 hover:text-rose-700 transition-colors border">
                                                            Missing Signature
                                                        </button>
                                                        <button type="button" wire:click="applyPresetReason('Incorrect document submitted for requirement')"
                                                            class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-slate-100 text-slate-700 hover:bg-rose-50 hover:text-rose-700 transition-colors border">
                                                            Wrong File Type
                                                        </button>
                                                    </div>
                                                </div>

                                                {{-- Remarks Text Area --}}
                                                <div>
                                                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Review Remarks / Feedback</label>
                                                    <textarea wire:model="review_notes" rows="3"
                                                        placeholder="Notes or feedback for department..."
                                                        class="w-full p-3 rounded-xl border border-slate-200 text-xs font-medium text-slate-800 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all"></textarea>
                                                </div>
                                            </div>

                                            {{-- Footer Action Buttons --}}
                                            <div class="pt-4 border-t border-slate-100 flex items-center justify-between gap-3">
                                                <button type="button" @click="decisionPanelOpen = false"
                                                    class="text-xs font-bold text-slate-500 hover:text-slate-800">
                                                    Cancel
                                                </button>

                                                @if($uploadId)
                                                    <div class="flex items-center gap-2">
                                                        <button type="button"
                                                            wire:click="reject({{ $uploadId }}); decisionPanelOpen = false"
                                                            class="px-4 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-xs transition-all active:scale-95">
                                                            Reject Submission
                                                        </button>
                                                        <button type="button"
                                                            wire:click="approve({{ $uploadId }}); decisionPanelOpen = false"
                                                            class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-xs shadow-emerald-200 transition-all active:scale-95">
                                                            Approve Document
                                                        </button>
                                                    </div>
                                                @endif
                                            </div>

                                        </div>

                                    </div>
                                @endif

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>

</div>
