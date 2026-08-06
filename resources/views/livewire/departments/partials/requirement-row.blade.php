@php
    $p = (int)$r['percent'];
    $expires = $r['last_valid_until']?->format('d M Y');
    $due = $r['next_due']?->format('d M Y');
    $barColor = $p >= 100 ? 'bg-emerald-500' : ($p < 50 ? 'bg-rose-500' : 'bg-amber-500');
@endphp

<div class="px-5 py-4 hover:bg-slate-50/70 transition-colors" wire:key="req-{{ $r['id'] }}">
    <div class="flex flex-wrap items-center justify-between gap-4">

        {{-- Requirement Info --}}
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2 mb-1">
                <span class="font-mono text-xs font-bold text-slate-700 bg-slate-100 px-2 py-0.5 rounded">
                    {{ $r['code'] }}
                </span>

                <x-compliance.status-badge
                    :status="$r['status'] === 'OK' ? 'compliant' : ($r['status'] === 'Pending' ? 'pending' : 'critical')"
                    size="xs"
                />

                @if ($r['pending'] > 0)
                    <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                        {{ $r['pending'] }} pending review
                    </span>
                @endif

                @if ($r['requires_approval'])
                    <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751A11.959 11.959 0 0112 2.714z"/></svg>
                        Approval required
                    </span>
                @endif
            </div>

            <p class="text-sm font-bold text-slate-900" title="{{ $r['allowed_summary'] }}">
                {{ $r['name'] }}
            </p>

            <div class="flex items-center gap-3 mt-1 text-xs text-slate-500">
                <span>Requires min <strong>{{ $r['min'] }}</strong> file(s)</span>
                <span>•</span>
                <span class="truncate max-w-xs">Allowed: {{ $r['allowed_summary'] }}</span>
            </div>
        </div>

        {{-- Progress + Dates + Action Buttons --}}
        <div class="flex items-center gap-5 shrink-0 flex-wrap sm:flex-nowrap">
            {{-- Progress Ratio & Bar --}}
            <div class="flex flex-col items-end gap-1 min-w-[140px]">
                <div class="flex items-center justify-between w-full text-xs">
                    <span class="text-slate-400 text-[10px] font-bold uppercase tracking-wider">Met Ratio</span>
                    <span class="font-extrabold text-slate-800">{{ $r['valid_count'] }}/{{ $r['min'] }}</span>
                </div>
                <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                    <div class="{{ $barColor }} h-full rounded-full transition-all" style="width: {{ $p }}%"></div>
                </div>
            </div>

            {{-- Expiry / Due Date Badge --}}
            <div class="min-w-[110px] text-right">
                @if ($expires)
                    <span class="block text-[10px] text-slate-400 font-bold uppercase tracking-wider">Valid Until</span>
                    <span class="text-xs font-bold text-slate-700">{{ $expires }}</span>
                @elseif($due)
                    <span class="block text-[10px] text-rose-500 font-bold uppercase tracking-wider">Deadline</span>
                    <span class="text-xs font-extrabold text-rose-600">Due {{ $due }}</span>
                @else
                    <span class="text-xs text-slate-400">—</span>
                @endif
            </div>

            {{-- Action Buttons --}}
            <div class="flex items-center gap-2" x-data>
                <button type="button"
                    @click="$dispatch('trigger-upload-modal', { reqId: {{ $r['id'] }}, deptId: {{ $department->id }} })"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white px-3.5 py-2 text-xs font-bold shadow-xs shadow-indigo-200 transition-all active:scale-95">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                    Upload
                </button>

                <button type="button"
                    @click="$dispatch('trigger-history-modal', { reqId: {{ $r['id'] }}, deptId: {{ $department->id }} })"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 px-3 py-2 text-xs font-bold transition-all shadow-2xs">
                    <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    History
                    @if ($r['pending'] > 0)
                        <span class="inline-flex items-center justify-center h-4 w-4 rounded-full bg-amber-500 text-white text-[10px] font-extrabold">{{ $r['pending'] }}</span>
                    @endif
                </button>
            </div>

        </div>
    </div>
</div>
