{{-- Compliance Dashboard — Livewire component view --}}
@section('title', 'Compliance Dashboard')
@section('page-title', 'Compliance Dashboard')
@section('page-subtitle', 'Document compliance status across all departments')

<div wire:poll.60s class="space-y-6">

    {{-- ─── 1. Header Bar ────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500 via-indigo-600 to-violet-600 shadow-lg shadow-indigo-200 shrink-0">
                <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751A11.959 11.959 0 0112 2.714z" />
                </svg>
            </div>
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Compliance Overview</h1>
                <div class="flex items-center gap-2 mt-0.5">
                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/60">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        Live Dashboard
                    </span>
                    <span class="text-xs text-slate-400">•</span>
                    <span class="text-xs font-medium text-slate-500">
                        @if ($lastUpdated)
                            Updated {{ $lastUpdated->diffForHumans() }}
                        @else
                            No snapshot data yet
                        @endif
                    </span>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button wire:click="exportCsv"
                class="inline-flex items-center gap-2 rounded-xl bg-white border border-slate-200/80 px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-50 hover:border-slate-300 shadow-sm transition-all active:scale-95">
                <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                Export Report (.xlsx)
            </button>
        </div>
    </div>

    {{-- ─── 2. Search & Toolbar ─────────────────────────────────────── --}}
    <div class="rounded-2xl bg-white/90 backdrop-blur-xl border border-slate-200/80 p-4 shadow-sm flex flex-wrap items-center justify-between gap-4">
        {{-- Search Input --}}
        <div class="relative flex-1 min-w-[240px]">
            <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 h-4 w-4 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search department by name or code…"
                class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 bg-white text-xs font-medium text-slate-800 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all">
        </div>

        {{-- Filter Chips --}}
        <x-compliance.filter-chips
            :options="[
                ['value' => '', 'label' => 'All Status', 'count' => $kpi['count']],
                ['value' => '0-49', 'label' => 'Critical (0–49%)', 'count' => $kpi['below50'], 'color' => 'rose'],
                ['value' => '50-99', 'label' => 'Warning (50–99%)', 'count' => $dist['c50_99'], 'color' => 'amber'],
                ['value' => '100', 'label' => '100% Complete', 'count' => $kpi['complete'], 'color' => 'emerald']
            ]"
            :selected="$bucket"
            wireModel="bucket"
        />

        {{-- Hide Complete Toggle Switch --}}
        <label class="flex items-center gap-2.5 cursor-pointer select-none pl-2 border-l border-slate-200">
            <div class="relative">
                <input type="checkbox" wire:model.live="hideComplete" class="sr-only peer">
                <div class="w-9 h-5 rounded-full bg-slate-200 peer-checked:bg-indigo-600 transition-colors"></div>
                <div class="absolute top-0.5 left-0.5 h-4 w-4 rounded-full bg-white shadow-sm transition-transform peer-checked:translate-x-4"></div>
            </div>
            <span class="text-xs font-bold text-slate-600">Hide Complete</span>
        </label>
    </div>

    {{-- ─── 3. Top Executive KPI Cards ──────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <button type="button" wire:click="$set('bucket', ''); $set('hideComplete', false)" class="text-left focus:outline-none">
            <x-compliance.stat-card
                title="Total Departments"
                :value="$kpi['count']"
                icon="building-office-2"
                accent="blue"
                subtitle="Active tracked units"
            />
        </button>

        <button type="button" wire:click="$set('bucket', ''); $set('hideComplete', false)" class="text-left focus:outline-none">
            <x-compliance.stat-card
                title="Avg Compliance"
                :value="$kpi['avg'].'%' "
                icon="chart-bar"
                accent="indigo"
                trend="+2.5%"
                trendType="up"
                subtitle="Organization average"
            />
        </button>

        <button type="button" wire:click="$set('bucket', '100'); $set('hideComplete', false)" class="text-left focus:outline-none">
            <x-compliance.stat-card
                title="100% Compliant"
                :value="$kpi['complete']"
                icon="shield-check"
                accent="emerald"
                subtitle="Fully met requirements"
            />
        </button>

        <button type="button" wire:click="$set('bucket', '0-49'); $set('hideComplete', false)" class="text-left focus:outline-none">
            <x-compliance.stat-card
                title="At Risk (≤49%)"
                :value="$kpi['below50']"
                icon="exclamation-triangle"
                accent="rose"
                subtitle="Requires immediate action"
            />
        </button>
    </div>

    {{-- ─── 4. Analytics Grid (Progress Ring + Trend Chart) ─────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        {{-- Distribution & Score Ring Card --}}
        <div class="lg:col-span-4 rounded-2xl bg-white/90 backdrop-blur-xl border border-slate-200/80 p-5 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Compliance Score</h3>
                    <span class="text-xs font-semibold text-slate-400">{{ $dist['total'] }} departments</span>
                </div>

                {{-- Score Ring --}}
                <div class="flex flex-col items-center justify-center py-2">
                    <x-compliance.progress-ring
                        :percent="$kpi['avg']"
                        size="xl"
                        :strokeWidth="10"
                        labelSub="Overall Rate"
                    />
                </div>
            </div>

            {{-- Breakdown Bar --}}
            <div class="mt-4 pt-4 border-t border-slate-100 space-y-3">
                <div class="flex h-3 rounded-full overflow-hidden gap-0.5 bg-slate-100 p-0.5">
                    @if ($dist['p0_49'] > 0)
                        <div class="bg-rose-500 rounded-l-full transition-all" style="width:{{ $dist['p0_49'] }}%" title="Critical (0–49%): {{ $dist['c0_49'] }}"></div>
                    @endif
                    @if ($dist['p50_99'] > 0)
                        <div class="bg-amber-500 transition-all" style="width:{{ $dist['p50_99'] }}%" title="Warning (50–99%): {{ $dist['c50_99'] }}"></div>
                    @endif
                    @if ($dist['p100'] > 0)
                        <div class="bg-emerald-500 rounded-r-full transition-all" style="width:{{ $dist['p100'] }}%" title="Complete (100%): {{ $dist['c100'] }}"></div>
                    @endif
                </div>

                <div class="grid grid-cols-3 gap-2 text-center text-xs">
                    <div class="p-2 rounded-xl bg-rose-50/50 border border-rose-100">
                        <span class="block font-black text-rose-600 text-sm">{{ $dist['c0_49'] }}</span>
                        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-tight">Critical</span>
                    </div>
                    <div class="p-2 rounded-xl bg-amber-50/50 border border-amber-100">
                        <span class="block font-black text-amber-600 text-sm">{{ $dist['c50_99'] }}</span>
                        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-tight">Warning</span>
                    </div>
                    <div class="p-2 rounded-xl bg-emerald-50/50 border border-emerald-100">
                        <span class="block font-black text-emerald-600 text-sm">{{ $dist['c100'] }}</span>
                        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-tight">Compliant</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Historical Trend Chart Card --}}
        <div class="lg:col-span-8 rounded-2xl bg-white/90 backdrop-blur-xl border border-slate-200/80 p-5 shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Compliance Trend</h3>
                    <p class="text-xs text-slate-400">Historical average compliance performance</p>
                </div>

                {{-- Trend Month Range Selector --}}
                <div class="flex items-center gap-1 p-1 rounded-xl bg-slate-100">
                    <button type="button" wire:click="setTrendMonths(6)"
                        class="px-2.5 py-1 text-xs font-bold rounded-lg transition-colors {{ $trendMonths === 6 ? 'bg-white text-indigo-600 shadow-xs' : 'text-slate-500 hover:text-slate-900' }}">
                        6 Months
                    </button>
                    <button type="button" wire:click="setTrendMonths(12)"
                        class="px-2.5 py-1 text-xs font-bold rounded-lg transition-colors {{ $trendMonths === 12 ? 'bg-white text-indigo-600 shadow-xs' : 'text-slate-500 hover:text-slate-900' }}">
                        12 Months
                    </button>
                </div>
            </div>

            {{-- Chart.js Alpine Component Wrapper --}}
            <x-compliance.chart-wrapper
                type="line"
                :labels="$trendLabels"
                :datasets="$trendDatasets"
                :height="230"
            />
        </div>
    </div>

    {{-- ─── 5. Department Rankings Grid (Bottom 10 vs Top 10) ───────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        @php
            function sparkPath($points, $w = 80, $h = 24)
            {
                $n = max(count($points), 1);
                $dx = $n > 1 ? $w / ($n - 1) : 0;
                $path = [];
                foreach ($points as $i => $p) {
                    $x = $i * $dx;
                    $y = $h - ($p / 100) * $h;
                    $path[] = ($i === 0 ? 'M' : 'L') . round($x, 1) . ' ' . round($y, 1);
                }
                return implode(' ', $path);
            }
        @endphp

        @foreach ([
            ['title' => 'At Risk Departments', 'subtitle' => 'Lowest compliance rates', 'rows' => $bottom, 'accent' => 'rose', 'status' => 'critical'],
            ['title' => 'Top Performing Departments', 'subtitle' => 'Highest compliance rates', 'rows' => $top, 'accent' => 'emerald', 'status' => 'compliant']
        ] as $panel)
            <div class="rounded-2xl bg-white/90 backdrop-blur-xl border border-slate-200/80 overflow-hidden shadow-sm">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">{{ $panel['title'] }}</h3>
                        <p class="text-xs text-slate-400">{{ $panel['subtitle'] }}</p>
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-xs font-extrabold {{ $panel['accent'] === 'rose' ? 'bg-rose-50 text-rose-600' : 'bg-emerald-50 text-emerald-600' }}">
                        Top 10 List
                    </span>
                </div>

                <div class="divide-y divide-slate-100">
                    @forelse($panel['rows'] as $row)
                        @php $pts = $sparklines[$row->department_id] ?? []; @endphp
                        <a href="{{ route('departments.compliance', $row->department) }}"
                            class="flex items-center justify-between px-5 py-3.5 hover:bg-slate-50/80 transition-colors group">
                            <div class="min-w-0 flex-1 pr-4">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-bold text-slate-900 group-hover:text-indigo-600 transition-colors truncate">
                                        {{ $row->department->name }}
                                    </span>
                                    <span class="px-1.5 py-0.2 rounded text-[10px] font-extrabold uppercase bg-slate-100 text-slate-500">
                                        {{ $row->department->code ?? '—' }}
                                    </span>
                                </div>
                            </div>

                            {{-- Mini Sparkline --}}
                            <svg width="70" height="22" viewBox="0 0 80 24" preserveAspectRatio="none" class="shrink-0 mr-4 opacity-80 group-hover:opacity-100 transition-opacity">
                                <path d="{{ sparkPath($pts, 80, 24) }}" fill="none"
                                    stroke="{{ end($pts) >= 80 ? '#10b981' : ($panel['accent'] === 'rose' ? '#f43f5e' : '#10b981') }}"
                                    stroke-width="2.5" />
                            </svg>

                            <x-compliance.status-badge
                                :status="$row->percent >= 100 ? 'compliant' : ($row->percent < 50 ? 'critical' : 'warning')"
                                :label="$row->percent . '%'"
                                size="sm"
                            />
                        </a>
                    @empty
                        <div class="py-12 text-center">
                            <svg class="mx-auto h-8 w-8 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0l-3-3m3 3l3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
                            <p class="text-xs font-semibold text-slate-400 mt-2">No department snapshot records</p>
                        </div>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>

    {{-- ─── 6. Operational Activity Queues ─────────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Pending Approvals Queue Panel --}}
        <div class="rounded-2xl bg-white/90 backdrop-blur-xl border border-slate-200/80 overflow-hidden shadow-sm">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Pending Approvals</h3>
                    @if ($pending->count())
                        <span class="px-2 py-0.5 rounded-full text-xs font-black bg-amber-500 text-white shadow-xs">
                            {{ $pending->count() }}
                        </span>
                    @endif
                </div>
                @if ($pending->count())
                    <a href="{{ route('requirement-uploads.review') }}"
                        class="text-xs font-bold text-indigo-600 hover:underline flex items-center gap-1">
                        Review Queue →
                    </a>
                @endif
            </div>

            <div class="divide-y divide-slate-100">
                @forelse($pending as $u)
                    <div class="flex items-center justify-between px-5 py-3.5 hover:bg-slate-50/60 transition-colors">
                        <div class="min-w-0 flex-1 pr-3">
                            <p class="text-xs font-bold text-slate-900 truncate">{{ $u->dept_name }}</p>
                            <p class="text-xs text-slate-500 truncate mt-0.5">
                                <span class="font-mono font-semibold text-slate-700">{{ $u->req_code }}</span> — {{ $u->req_name }}
                            </p>
                        </div>
                        <div class="text-right shrink-0">
                            <x-compliance.status-badge status="pending" size="xs" />
                            <span class="block text-[10px] text-slate-400 mt-1 font-medium">{{ $u->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                @empty
                    <div class="py-12 text-center">
                        <svg class="mx-auto h-8 w-8 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <p class="text-xs font-semibold text-slate-500 mt-2">All document submissions reviewed</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Expiring Requirements Panel --}}
        <div class="rounded-2xl bg-white/90 backdrop-blur-xl border border-slate-200/80 overflow-hidden shadow-sm">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Expiring Documents</h3>
                    <p class="text-xs text-slate-400">Validations expiring within 30 days</p>
                </div>
                @if($expiring->count())
                    <span class="px-2 py-0.5 rounded-full text-xs font-black bg-rose-500 text-white shadow-xs">
                        {{ $expiring->count() }}
                    </span>
                @endif
            </div>

            <div class="divide-y divide-slate-100">
                @forelse($expiring as $u)
                    @php $daysLeft = (int)now()->diffInDays($u->valid_until, false); @endphp
                    <div class="flex items-center justify-between px-5 py-3.5 hover:bg-slate-50/60 transition-colors">
                        <div class="min-w-0 flex-1 pr-3">
                            <p class="text-xs font-bold text-slate-900 truncate">{{ $u->dept_name }}</p>
                            <p class="text-xs text-slate-500 truncate mt-0.5">
                                <span class="font-mono font-semibold text-slate-700">{{ $u->req_code }}</span> — {{ $u->req_name }}
                            </p>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-extrabold {{ $daysLeft <= 7 ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700' }}">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                {{ $daysLeft <= 0 ? 'Expires today' : "{$daysLeft}d left" }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="py-12 text-center">
                        <svg class="mx-auto h-8 w-8 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5m-9-6h.008v.008H12v-.008zM12 15h.008v.008H12V15z"/></svg>
                        <p class="text-xs font-semibold text-slate-500 mt-2">No documents expiring in the next 30 days</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Loading Overlay --}}
    <div wire:loading.delay class="fixed inset-0 z-50 bg-slate-900/20 backdrop-blur-sm flex items-center justify-center">
        <div class="rounded-2xl bg-white border border-slate-200 px-6 py-4 shadow-2xl flex items-center gap-3">
            <svg class="animate-spin h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
            </svg>
            <span class="text-sm font-bold text-slate-800">Updating Dashboard…</span>
        </div>
    </div>

</div>
