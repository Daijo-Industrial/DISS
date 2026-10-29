<div class="max-w-7xl mx-auto px-3 sm:px-6 py-4 sm:py-6 space-y-4 sm:space-y-5">
    {{-- Breadcrumb Navigation --}}
    <nav class="flex items-center gap-1.5 text-xs font-medium text-slate-500 -mb-1" aria-label="Breadcrumb">
        <a href="{{ route('home') }}" class="inline-flex items-center gap-1 text-slate-400 hover:text-blue-600 transition-colors">
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
            </svg>
            <span>Home</span>
        </a>
        <svg class="h-3.5 w-3.5 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
        </svg>
        <a href="{{ route('vehicles.index') }}" wire:navigate class="text-slate-400 hover:text-blue-600 transition-colors">
            {{ __('fleet.index.title') }}
        </a>
        <svg class="h-3.5 w-3.5 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
        </svg>
        <span class="text-slate-700 font-semibold truncate" aria-current="page">{{ __('fleet.operations.tab_operations') }}</span>
    </nav>

    {{-- Dedicated Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="flex items-center gap-2.5 sm:gap-3">
            <div class="h-10 w-10 rounded-xl bg-indigo-50 border border-indigo-200/80 flex items-center justify-center text-indigo-600 text-lg shadow-2xs shrink-0">
                <i class="bi bi-broadcast"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900">
                        {{ __('fleet.operations.tab_operations') }}
                    </h1>
                    @if ($metrics['on_trip'] > 0)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 ring-1 ring-inset ring-amber-300">
                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse mr-1.5"></span>
                            {{ __('fleet.operations.active_trips_count', ['count' => $metrics['on_trip']]) }}
                        </span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                            {{ __('fleet.operations.all_standby') }}
                        </span>
                    @endif
                </div>
                <p class="text-xs text-slate-500 mt-0.5">
                    {{ __('fleet.operations.operations_subtitle') }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2 self-start sm:self-auto">
            {{-- Link to Catalog --}}
            <a href="{{ route('vehicles.index') }}" wire:navigate
                class="inline-flex items-center justify-center rounded-xl bg-white border border-slate-200/80 hover:bg-slate-50 text-slate-700 px-3.5 py-2 text-xs font-semibold transition active:scale-[0.98] shadow-2xs">
                <i class="bi bi-grid-3x3-gap mr-1.5 text-xs text-slate-500"></i>
                <span>{{ __('fleet.operations.tab_catalog') }}</span>
                <span class="ml-1.5 text-[10px] font-semibold text-slate-400 font-mono">{{ $metrics['total'] }}</span>
            </a>
        </div>
    </div>

    {{-- 1. Live Operational Radar --}}
    @include('livewire.vehicles.partials.operations-live-radar')

    {{-- 2. Daily Timeline Feed --}}
    @include('livewire.vehicles.partials.operations-timeline')

    {{-- Universal Photo Lightbox --}}
    <x-universal-lightbox />
</div>
