@php
    $daysLeft = $u->valid_until ? now()->diffInDays($u->valid_until, false) : null;
    $isPdf = Str::contains($u->mime_type, 'pdf');
    $isImage = Str::startsWith($u->mime_type, 'image/');
@endphp

<div wire:key="kanban-card-{{ $u->id }}"
    class="rounded-xl bg-white border border-slate-200/90 p-4 shadow-2xs hover:shadow-md transition-all duration-200 space-y-3 group">

    {{-- Card Top: Requirement info & check --}}
    <div class="flex items-start justify-between gap-2">
        <div>
            <span class="font-mono text-[10px] font-bold text-slate-500 bg-slate-100 px-1.5 py-0.5 rounded uppercase">
                {{ $u->req_code }}
            </span>
            <h4 class="text-xs font-bold text-slate-900 mt-1 line-clamp-1 group-hover:text-indigo-600 transition-colors" title="{{ $u->req_name }}">
                {{ $u->req_name }}
            </h4>
        </div>

        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-600 shrink-0">
            {{ $u->dept_code }}
        </span>
    </div>

    {{-- File details badge --}}
    <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-100 flex items-center gap-2.5">
        <div class="flex h-7 w-7 items-center justify-center rounded-md {{ $isPdf ? 'bg-rose-100 text-rose-600' : ($isImage ? 'bg-indigo-100 text-indigo-600' : 'bg-emerald-100 text-emerald-600') }} font-bold text-[10px] shrink-0">
            {{ $isPdf ? 'PDF' : ($isImage ? 'IMG' : 'DOC') }}
        </div>
        <div class="min-w-0 flex-1">
            <p class="text-xs font-semibold text-slate-800 truncate" title="{{ $u->original_name }}">
                {{ $u->original_name }}
            </p>
            <p class="text-[10px] text-slate-400">
                {{ number_format($u->size / 1024, 1) }} KB
            </p>
        </div>
    </div>

    {{-- Date claim --}}
    <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1">
        <span>Uploaded: {{ $u->created_at->format('d M y') }}</span>
        @if(!is_null($daysLeft))
            <span class="font-bold {{ $daysLeft < 0 ? 'text-rose-600' : 'text-slate-600' }}">
                {{ $daysLeft < 0 ? 'Expired' : "in {$daysLeft}d" }}
            </span>
        @endif
    </div>

    {{-- Card Actions --}}
    <div class="pt-2 border-t border-slate-100 flex items-center justify-between gap-1">
        <a href="{{ URL::signedRoute('uploads.download', ['upload' => $u->id]) }}" target="_blank"
            class="p-1.5 rounded-lg text-slate-400 hover:text-indigo-600 hover:bg-slate-50 transition-colors"
            title="Download file">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
        </a>

        <div class="flex items-center gap-1">
            @if($u->status === 'pending')
                @can('approve-requirements')
                    <button type="button" wire:click="quickReject({{ $u->id }}, 'Illegible or incorrect document')"
                        title="Reject submission"
                        class="p-1.5 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 transition-colors text-xs font-bold">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                    <button type="button" wire:click="approve({{ $u->id }})"
                        title="Approve submission"
                        class="p-1.5 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-100 transition-colors text-xs font-bold">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                    </button>
                @endcan
            @endif

            <button type="button" wire:click="openDecision({{ $u->id }})"
                class="px-2.5 py-1 rounded-lg bg-indigo-50 text-indigo-700 hover:bg-indigo-100 transition-colors text-xs font-bold">
                Inspect
            </button>
        </div>
    </div>
</div>
