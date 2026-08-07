{{-- Requirements Assign — Livewire component view --}}
@section('title', 'Assign Requirement — Compliance')
@section('page-title', 'Assign Requirement')
@section('page-subtitle', 'Batch assign compliance requirements to departments')

<div class="space-y-6">

    {{-- Header Bar --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500 via-indigo-600 to-blue-600 shadow-lg shadow-indigo-200 shrink-0">
                <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244"/></svg>
            </div>
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Requirement Assignment Matrix</h1>
                <p class="text-xs font-medium text-slate-500 mt-0.5">Attach document compliance rules to organizational departments</p>
            </div>
        </div>

        <a href="{{ route('requirements.index') }}"
            class="inline-flex items-center gap-2 rounded-xl bg-white border border-slate-200/80 px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-50 shadow-sm transition-all active:scale-95">
            Requirements Catalogue
        </a>
    </div>

    <div class="flex flex-col lg:flex-row gap-6">

        {{-- Left Column: Selection Matrix --}}
        <div class="flex-1 w-full lg:w-2/3 space-y-5">
            <div class="rounded-2xl bg-white/90 backdrop-blur-xl border border-slate-200/80 p-6 shadow-sm space-y-5">

                {{-- Top controls --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 border-b border-slate-100 pb-5">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Target Requirement <span class="text-rose-500">*</span></label>
                        <select wire:model.live="requirement_id" class="w-full rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-800 py-2.5 px-3 outline-none focus:ring-2 focus:ring-indigo-500/20">
                            <option value="">— Select Requirement Definition —</option>
                            @foreach ($requirements as $r)
                                <option value="{{ $r->id }}">{{ $r->name }} ({{ $r->code }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Rule Mandatory</label>
                        <label class="flex items-center gap-2.5 cursor-pointer pt-1">
                            <input type="checkbox" wire:model.live="is_mandatory" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 w-4 h-4">
                            <span class="text-xs font-bold text-slate-800">Mandatory Rule</span>
                        </label>
                    </div>
                </div>

                {{-- Department Selection Toolbar --}}
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="relative flex-1 min-w-[200px]">
                        <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 h-4 w-4 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                        <input type="text" wire:model.live.debounce.250ms="deptSearch" placeholder="Search department by code or name…"
                            class="w-full pl-10 pr-4 py-2 rounded-xl border border-slate-200 bg-white text-xs font-medium text-slate-800 outline-none focus:ring-2 focus:ring-indigo-500/20">
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="button" wire:click="selectAll" @disabled(!$requirement_id) class="px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700 text-xs font-bold hover:bg-slate-200 disabled:opacity-50">Select All</button>
                        <button type="button" wire:click="selectUnassigned" @disabled(!$requirement_id) class="px-2.5 py-1 rounded-lg bg-indigo-50 text-indigo-700 text-xs font-bold hover:bg-indigo-100 disabled:opacity-50">Unassigned</button>
                        <button type="button" wire:click="selectNone" class="px-2.5 py-1 rounded-lg bg-slate-100 text-slate-500 text-xs font-bold hover:bg-slate-200">Clear</button>

                        <span class="px-2.5 py-1 rounded-lg bg-indigo-600 text-white text-xs font-extrabold">
                            {{ count($department_ids) }} selected
                        </span>
                    </div>
                </div>

                {{-- Department Checklist --}}
                <div class="rounded-xl border border-slate-200 overflow-y-auto max-h-[360px] divide-y divide-slate-100 bg-white">
                    @forelse($departments as $d)
                        <label class="flex items-center gap-3 px-4 py-3 hover:bg-slate-50/80 cursor-pointer transition-colors">
                            <input type="checkbox" wire:model.live="department_ids" value="{{ $d->id }}"
                                class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 w-4 h-4">
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-bold text-slate-900">{{ $d->name }}</p>
                                <span class="font-mono text-[10px] font-bold text-slate-400">{{ $d->code ?? '—' }}</span>
                            </div>
                            @if (in_array($d->id, $assignedDeptIds))
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-600">
                                    Assigned
                                </span>
                            @endif
                        </label>
                    @empty
                        <div class="py-12 text-center text-xs text-slate-400">
                            No departments found
                        </div>
                    @endforelse
                </div>

                {{-- Action CTA Buttons --}}
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                    @if ($requirement_id && count($department_ids) > 0)
                        <button type="button" wire:click="unassign"
                            wire:confirm="Unassign selected departments from this requirement?"
                            class="px-4 py-2 rounded-xl bg-rose-50 text-rose-700 hover:bg-rose-100 text-xs font-bold border border-rose-200 transition-all">
                            Unassign Selected
                        </button>
                    @endif

                    <button type="button" wire:click="save" @disabled(!$requirement_id || count($department_ids) === 0)
                        class="px-6 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-xs shadow-indigo-200 transition-all active:scale-95 disabled:opacity-50">
                        Save Assignments
                    </button>
                </div>

            </div>
        </div>

        {{-- Right Column: Live Impact Preview --}}
        <div class="w-full lg:w-1/3 space-y-5">
            <div class="rounded-2xl bg-white/90 backdrop-blur-xl border border-slate-200/80 p-5 shadow-sm space-y-4">
                <h3 class="text-xs font-extrabold text-slate-400 uppercase tracking-wider border-b border-slate-100 pb-3">Assignment Impact</h3>

                <div class="space-y-2 text-xs">
                    <div class="flex justify-between py-1 border-b border-slate-100">
                        <span class="text-slate-600 font-bold">New Assignments</span>
                        <span class="font-extrabold text-emerald-600">{{ $willCreate }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-100">
                        <span class="text-slate-600 font-bold">Existing Updates</span>
                        <span class="font-extrabold text-slate-700">{{ $willUpdate }}</span>
                    </div>
                </div>

                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-600 leading-relaxed">
                    Rule will be marked as <strong class="text-indigo-600 font-extrabold">{{ $is_mandatory ? 'Mandatory' : 'Optional' }}</strong>.
                    {{ $is_mandatory ? 'Will be included in compliance score calculations.' : 'Will serve as an optional document reference.' }}
                </div>
            </div>
        </div>

    </div>

</div>
