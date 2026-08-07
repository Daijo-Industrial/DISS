{{-- Requirements Departments — Livewire component view --}}
@section('title', $req->code . ' — Departments')
@section('page-title', $req->code . ' — Departments')
@section('page-subtitle', 'Assigned departments compliance breakdown')

<div class="space-y-6">

    {{-- Header Bar --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500 via-indigo-600 to-blue-600 shadow-lg shadow-indigo-200 shrink-0">
                <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-18v18M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h18M3.75 3v18m16.5-18v18"/></svg>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">{{ $req->name }}</h1>
                    <span class="font-mono text-xs font-bold text-slate-600 bg-slate-100 px-2 py-0.5 rounded">{{ $req->code }}</span>
                </div>
                <p class="text-xs font-medium text-slate-500 mt-0.5">Assigned department compliance status breakdown</p>
            </div>
        </div>

        <a href="{{ route('requirements.index') }}"
            class="inline-flex items-center gap-2 rounded-xl bg-white border border-slate-200/80 px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-50 shadow-sm transition-all active:scale-95">
            ← Requirements Catalogue
        </a>
    </div>

    {{-- Top KPI Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <x-compliance.stat-card
            title="Total Assigned"
            :value="$summary['total']"
            icon="building-office"
            accent="blue"
            subtitle="Departments tracking rule"
        />

        <x-compliance.stat-card
            title="Compliant (OK)"
            :value="$summary['ok']"
            icon="shield-check"
            accent="emerald"
            subtitle="Requirement met"
        />

        <x-compliance.stat-card
            title="Pending Review"
            :value="$summary['pending']"
            icon="clock"
            accent="amber"
            subtitle="Awaiting admin approval"
        />

        <x-compliance.stat-card
            title="Missing Uploads"
            :value="$summary['missing']"
            icon="exclamation-triangle"
            accent="rose"
            subtitle="Action required"
        />
    </div>

    {{-- Toolbar --}}
    <div class="rounded-2xl bg-white/90 backdrop-blur-xl border border-slate-200/80 p-4 shadow-sm flex flex-wrap items-center justify-between gap-4">
        <div class="relative flex-1 min-w-[240px]">
            <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 h-4 w-4 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search department by name or code…"
                class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 bg-white text-xs font-medium text-slate-800 placeholder-slate-400 outline-none focus:ring-2 focus:ring-indigo-500/20">
        </div>

        <x-compliance.filter-chips
            :options="[
                ['value' => 'all', 'label' => 'All Statuses', 'count' => $summary['total']],
                ['value' => 'ok', 'label' => 'OK', 'count' => $summary['ok'], 'color' => 'emerald'],
                ['value' => 'pending', 'label' => 'Pending', 'count' => $summary['pending'], 'color' => 'amber'],
                ['value' => 'missing', 'label' => 'Missing', 'count' => $summary['missing'], 'color' => 'rose']
            ]"
            :selected="$status"
            wireModel="status"
        />
    </div>

    {{-- Department List Table --}}
    <div class="rounded-2xl bg-white/90 backdrop-blur-xl border border-slate-200/80 overflow-hidden shadow-sm">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50/80 border-b border-slate-200/80 text-xs font-bold text-slate-500 uppercase tracking-wider">
                    <th class="px-5 py-3.5">Department</th>
                    <th class="px-5 py-3.5">Code</th>
                    <th class="px-5 py-3.5">Progress Ratio</th>
                    <th class="px-5 py-3.5">Status</th>
                    <th class="px-5 py-3.5 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($items as $row)
                    @php
                        $p = min(100, (int) round(($row['valid'] / max(1, $row['min'])) * 100));
                        $barColor = $p >= 100 ? 'bg-emerald-500' : ($p > 0 ? 'bg-amber-500' : 'bg-rose-500');
                    @endphp
                    <tr wire:key="dept-row-{{ $row['dept']->id }}" class="hover:bg-slate-50/80 transition-colors group">
                        <td class="px-5 py-4">
                            <a href="{{ route('departments.compliance', $row['dept']) }}" class="text-sm font-bold text-slate-900 group-hover:text-indigo-600 transition-colors">
                                {{ $row['dept']->name }}
                            </a>
                        </td>
                        <td class="px-5 py-4">
                            <span class="font-mono text-xs font-bold text-slate-600 bg-slate-100 px-2 py-0.5 rounded">
                                {{ $row['dept']->code ?? '—' }}
                            </span>
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3 w-48">
                                <div class="flex-1 h-2 bg-slate-100 rounded-full overflow-hidden">
                                    <div class="{{ $barColor }} h-full rounded-full transition-all" style="width: {{ $p }}%"></div>
                                </div>
                                <span class="text-xs font-bold text-slate-700 w-12 text-right">{{ $row['valid'] }}/{{ $row['min'] }}</span>
                            </div>
                        </td>
                        <td class="px-5 py-4">
                            <x-compliance.status-badge
                                :status="$row['status'] === 'OK' ? 'compliant' : ($row['status'] === 'Pending' ? 'pending' : 'critical')"
                                size="sm"
                            />
                        </td>
                        <td class="px-5 py-4 text-right">
                            <a href="{{ route('departments.compliance', $row['dept']) }}"
                                class="inline-flex items-center gap-1 text-xs font-bold text-indigo-600 hover:text-indigo-800 transition-colors">
                                Manage →
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-12 text-center text-xs text-slate-400">
                            No assigned departments match your filter.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $items->links() }}
    </div>

</div>
