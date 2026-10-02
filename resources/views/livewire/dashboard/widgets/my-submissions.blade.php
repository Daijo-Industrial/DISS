<div class="h-full flex flex-col rounded-3xl bg-white border border-slate-200/90 shadow-sm overflow-hidden group hover:shadow-md transition-all duration-300">
    {{-- Card Header --}}
    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/40">
        <div class="flex items-center gap-3">
            <div class="p-2 rounded-xl bg-violet-50 text-violet-600">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
            <div>
                <h3 class="font-bold text-slate-800 tracking-tight text-sm">My Recent Requests</h3>
                <p class="text-[11px] text-slate-400">Live status of documents you filed</p>
            </div>
        </div>

        @if ($this->submissions->isNotEmpty())
            <span class="px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-600 text-[10px] font-bold uppercase tracking-wider">
                {{ $this->submissions->count() }} Recent
            </span>
        @endif
    </div>

    {{-- Submissions List --}}
    <div class="flex-1 overflow-y-auto custom-scrollbar p-5 space-y-3">
        @forelse ($this->submissions as $request)
            @php
                $approvable = $request->approvable;
                $type = $approvable ? $approvable->getMorphClass() : null;

                $typeLabel = match ($type) {
                    'pr' => 'Purchase Request',
                    'overtime' => 'Overtime',
                    'po' => 'Purchase Order',
                    default => 'Request',
                };

                $typeColor = match ($type) {
                    'pr' => 'bg-blue-50 text-blue-700',
                    'overtime' => 'bg-amber-50 text-amber-700',
                    'po' => 'bg-emerald-50 text-emerald-700',
                    default => 'bg-slate-100 text-slate-700',
                };

                $status = strtoupper($request->status ?? 'DRAFT');
                $statusBadge = match ($status) {
                    'APPROVED' => 'bg-emerald-100/80 text-emerald-800 border-emerald-200',
                    'IN_REVIEW' => 'bg-blue-100/80 text-blue-800 border-blue-200',
                    'REJECTED' => 'bg-rose-100/80 text-rose-800 border-rose-200',
                    'CANCELLED' => 'bg-slate-100 text-slate-500 border-slate-200',
                    default => 'bg-amber-100/80 text-amber-800 border-amber-200',
                };

                $docTitle = $approvable
                    ? ($approvable->doc_number ?? ($approvable->number ?? ($approvable->title ?? "Request #{$approvable->id}")))
                    : "Request #{$request->id}";

                $showUrl = ($approvable && method_exists($approvable, 'getApprovableShowUrl'))
                    ? $approvable->getApprovableShowUrl()
                    : '#';
            @endphp

            <div wire:key="sub-{{ $request->id }}"
                class="flex items-center justify-between p-3.5 rounded-2xl border border-slate-100 hover:border-violet-200 hover:bg-violet-50/20 transition-all group">
                <div class="flex items-center gap-3.5 min-w-0">
                    <div class="flex flex-col min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-[9px] font-black uppercase tracking-wider px-2 py-0.5 rounded-md {{ $typeColor }}">
                                {{ $typeLabel }}
                            </span>
                            <span class="text-[10px] font-semibold text-slate-400">
                                {{ $request->submitted_at?->diffForHumans() ?? 'Recently' }}
                            </span>
                        </div>

                        <p class="font-bold text-slate-900 text-sm truncate">
                            {{ $docTitle }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <span class="px-2.5 py-1 rounded-xl text-[10px] font-black uppercase tracking-wider border {{ $statusBadge }}">
                        @if ($status === 'IN_REVIEW' && $request->current_step)
                            In Review (Step {{ $request->current_step }})
                        @else
                            {{ $status }}
                        @endif
                    </span>

                    @if ($showUrl !== '#')
                        <a href="{{ $showUrl }}"
                            class="p-2 rounded-xl text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition-colors"
                            title="View Details">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    @endif
                </div>
            </div>
        @empty
            <div class="py-8 text-center text-slate-400">
                <div class="h-12 w-12 rounded-2xl bg-slate-50 text-slate-300 mx-auto flex items-center justify-center mb-2">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                </div>
                <p class="text-xs font-semibold text-slate-600">No requests submitted yet</p>
                <p class="text-[11px] text-slate-400 mt-0.5">When you submit requests, their approval progress will appear here.</p>
            </div>
        @endforelse
    </div>
</div>
