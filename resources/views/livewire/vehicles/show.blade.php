<div class="max-w-7xl mx-auto px-3 sm:px-6 py-4 sm:py-6 space-y-4 sm:space-y-5">
    {{-- Breadcrumb Navigation --}}
    <div class="flex flex-wrap items-center justify-between gap-2 min-w-0">
        <a href="{{ route('vehicles.index') }}"
            class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-slate-900 transition shrink-0">
            <i class="bi bi-chevron-left text-[11px]"></i>
            <span>{{ __('fleet.show.back_to_index') }}</span>
        </a>

        @if ($vehicle->vin)
            <span class="font-mono text-[11px] text-slate-500 bg-slate-100 px-2 py-0.5 rounded-md border border-slate-200/60 truncate max-w-full">
                VIN: {{ $vehicle->vin }}
            </span>
        @endif
    </div>

    @php
        $last = $vehicle->latestService;
        $isOut = $vehicle->is_out_on_trip;
        $hasExpired = $documents->where('status', 'expired')->count();
        $hasWarning = $documents->whereIn('status', ['critical', 'warning'])->count();
    @endphp

    {{-- 2-COLUMN SPLIT COCKPIT LAYOUT --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start min-w-0">

        {{-- LEFT COLUMN: Sticky Cockpit & Vehicle Identity (lg:col-span-4) --}}
        @include('livewire.vehicles.partials.show-cockpit')

        {{-- RIGHT COLUMN: Tab Navigation & Rich Content Panels (lg:col-span-8) --}}
        <div class="lg:col-span-8 space-y-4 min-w-0">
            {{-- APPLE SEGMENTED TAB CONTROL --}}
            <div class="overflow-x-auto pb-1 sm:pb-0 -mx-3 px-3 sm:mx-0 sm:px-0 no-scrollbar">
                <div class="inline-flex items-center p-1 bg-slate-100/90 rounded-2xl border border-slate-200/60 min-w-full w-max sm:w-full">
                    <button type="button" wire:click="setTab('inspections')"
                        class="flex items-center justify-center gap-1.5 px-3 sm:px-3.5 py-2 rounded-xl text-xs whitespace-nowrap transition cursor-pointer flex-1 shrink-0 sm:shrink {{ $tab === 'inspections' ? 'bg-white text-slate-900 font-bold shadow-xs' : 'text-slate-500 hover:text-slate-800 font-medium' }}">
                        <i class="bi bi-clipboard-check text-xs"></i>
                        <span>{{ __('fleet.tabs.inspections') }}</span>
                        <span class="rounded-full px-1.5 py-0.2 text-[10px] {{ $tab === 'inspections' ? 'bg-slate-100 text-slate-900 font-bold' : 'text-slate-400 font-medium' }}">
                            {{ $inspections->total() }}
                        </span>
                    </button>

                    <button type="button" wire:click="setTab('documents')"
                        class="flex items-center justify-center gap-1.5 px-3 sm:px-3.5 py-2 rounded-xl text-xs whitespace-nowrap transition cursor-pointer flex-1 shrink-0 sm:shrink {{ $tab === 'documents' ? 'bg-white text-slate-900 font-bold shadow-xs' : 'text-slate-500 hover:text-slate-800 font-medium' }}">
                        <i class="bi bi-file-earmark-text text-xs"></i>
                        <span>{{ __('fleet.tabs.documents') }}</span>
                        @if ($hasExpired + $hasWarning > 0)
                            <span class="rounded-full bg-rose-50 text-rose-700 border border-rose-200/60 px-1.5 py-0.2 text-[10px] font-bold">
                                {{ $hasExpired + $hasWarning }}
                            </span>
                        @else
                            <span class="rounded-full px-1.5 py-0.2 text-[10px] {{ $tab === 'documents' ? 'bg-slate-100 text-slate-900 font-bold' : 'text-slate-400 font-medium' }}">
                                {{ $documents->count() }}
                            </span>
                        @endif
                    </button>

                    <button type="button" wire:click="setTab('services')"
                        class="flex items-center justify-center gap-1.5 px-3 sm:px-3.5 py-2 rounded-xl text-xs whitespace-nowrap transition cursor-pointer flex-1 shrink-0 sm:shrink {{ $tab === 'services' ? 'bg-white text-slate-900 font-bold shadow-xs' : 'text-slate-500 hover:text-slate-800 font-medium' }}">
                        <i class="bi bi-wrench text-xs"></i>
                        <span>{{ __('fleet.tabs.services') }}</span>
                        <span class="rounded-full px-1.5 py-0.2 text-[10px] {{ $tab === 'services' ? 'bg-slate-100 text-slate-900 font-bold' : 'text-slate-400 font-medium' }}">
                            {{ $records->total() }}
                        </span>
                    </button>
                </div>
            </div>

            {{-- TAB 1: LOGBOOK PEMERIKSAAN HARIAN (P2H) --}}
            @if ($tab === 'inspections')
                @include('livewire.vehicles.partials.show-tab-inspections')
            @endif

            {{-- TAB 2: LEGALITAS & DOKUMEN --}}
            @if ($tab === 'documents')
                @include('livewire.vehicles.partials.show-tab-documents')
            @endif

            {{-- TAB 3: RIWAYAT SERVIS BENGKEL & BIAYA --}}
            @if ($tab === 'services')
                @include('livewire.vehicles.partials.show-tab-services')
            @endif
        </div>
    </div>

    {{-- MODALS & LIGHTBOX OVERLAYS --}}
    @include('livewire.vehicles.partials.show-modals')
</div>
