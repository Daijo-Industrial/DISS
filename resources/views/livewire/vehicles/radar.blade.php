<div wire:poll.30s="$refresh" class="max-w-7xl mx-auto px-3 sm:px-6 py-4 sm:py-6 space-y-4 sm:space-y-5">
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
        <span class="text-slate-700 font-semibold truncate" aria-current="page">{{ __('fleet.operations.live_radar_title') }}</span>
    </nav>

    {{-- Dedicated Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="flex items-center gap-2.5 sm:gap-3">
            <div class="h-10 w-10 rounded-xl bg-amber-50 border border-amber-200/80 flex items-center justify-center text-amber-600 text-lg shadow-2xs shrink-0">
                <i class="bi bi-broadcast"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900">
                        {{ __('fleet.operations.live_radar_title') }}
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
                    {{ __('fleet.operations.live_radar_subtitle') }}
                </p>
            </div>
        </div>

        {{-- Right Controls: Auto-Refresh Status & Refresh Button --}}
        <div class="flex items-center gap-2 self-start sm:self-auto text-xs text-slate-500">
            <span class="inline-flex items-center gap-1.5 text-[11px] text-slate-400 mr-1">
                <span class="h-1.5 w-1.5 rounded-full bg-indigo-500 animate-pulse"></span>
                <span>{{ __('fleet.operations.auto_refresh') }} (30s)</span>
            </span>

            <button type="button" wire:click="$refresh"
                class="inline-flex items-center gap-1 px-2.5 py-2 rounded-xl bg-white border border-slate-200/80 hover:bg-slate-50 text-slate-700 text-xs font-semibold transition active:scale-95 cursor-pointer shadow-2xs">
                <i class="bi bi-arrow-clockwise text-xs"></i>
                <span class="hidden sm:inline">{{ __('fleet.operations.refresh') }}</span>
            </button>
        </div>
    </div>

    {{-- Subtle Orphan Checkouts Alert Banner (Collapsible) --}}
    @if ($orphanCheckouts->isNotEmpty())
        <div x-data="{ open: false }" class="rounded-2xl border border-amber-200/80 bg-amber-50/60 p-3.5 sm:p-4 text-xs shadow-2xs">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-2.5">
                    <span class="h-7 w-7 rounded-lg bg-amber-100 text-amber-800 flex items-center justify-center text-sm shrink-0">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </span>
                    <div>
                        <span class="font-bold text-amber-950">
                            {{ __('fleet.operations.orphan_alert_title', ['count' => $orphanCheckouts->count()]) }}
                        </span>
                        <p class="text-[11px] text-amber-800/90 mt-0.5">
                            {{ __('fleet.operations.orphan_alert_desc') }}
                        </p>
                    </div>
                </div>

                <button type="button" @click="open = !open"
                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-white border border-amber-300/80 hover:bg-amber-100/60 text-amber-950 text-xs font-semibold transition cursor-pointer shrink-0">
                    <span x-text="open ? '{{ __('fleet.operations.hide_telemetry') }}' : '{{ __('fleet.operations.view_details') }}'"></span>
                    <i class="bi text-xs" :class="open ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                </button>
            </div>

            <div x-show="open" x-collapse x-cloak class="mt-3 pt-3 border-t border-amber-200/60 space-y-2">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2">
                    @foreach ($orphanCheckouts as $orphan)
                        <div class="rounded-xl bg-white border border-amber-200/70 p-2.5 flex items-center justify-between gap-2 shadow-2xs">
                            <div class="min-w-0">
                                <span class="font-mono font-bold text-slate-900 block truncate">
                                    {{ $orphan->vehicle?->plate_number ?: '—' }}
                                </span>
                                <span class="text-[10px] text-slate-500 block truncate">
                                    {{ $orphan->driver_name }} • {{ $orphan->checked_at?->format('d M H:i') }}
                                </span>
                            </div>
                            @if ($orphan->vehicle)
                                <a href="{{ route('vehicles.show', [$orphan->vehicle, 'tab' => 'inspections']) }}" wire:navigate
                                    class="shrink-0 text-[11px] font-semibold text-indigo-600 hover:text-indigo-800 transition">
                                    {{ __('fleet.operations.orphan_view_logs') }} →
                                </a>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- Content: Strictly 1 Card per Active Vehicle on Trip (or Standby Banner) --}}
    @if ($liveActiveTrips->isNotEmpty())
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-4">
            @foreach ($liveActiveTrips as $trip)
                @php
                    $v = $trip->vehicle;
                    $depTime = $trip->checked_at ?? $trip->created_at;
                @endphp
                <div wire:key="live-radar-{{ $v?->id }}-{{ $trip->id }}"
                    class="group relative flex flex-col justify-between rounded-2xl border-2 border-amber-300/80 bg-linear-to-b from-amber-50/50 to-white p-3.5 sm:p-4 shadow-xs hover:shadow-md transition">
                    
                    <div class="space-y-3">
                        {{-- Top: Vehicle Plate & Model & Category --}}
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-center gap-2.5 min-w-0">
                                @if ($v?->image_path)
                                    <button type="button" @click.stop="$dispatch('open-lightbox', { src: '{{ asset('storage/' . $v->image_path) }}', title: '{{ __('fleet.show.lightbox_vehicle_photo', ['plate' => $v->plate_number]) }}', subtitle: '{{ trim($v->brand . ' ' . $v->model) }}' })"
                                        class="shrink-0 cursor-pointer">
                                        <img src="{{ asset('storage/' . $v->image_path) }}" alt="{{ $v->plate_number }}"
                                            class="h-9 w-9 rounded-xl object-cover border border-amber-200/80 shadow-2xs">
                                    </button>
                                @else
                                    <span class="h-9 w-9 rounded-xl flex items-center justify-center text-base bg-amber-100 text-amber-800 shrink-0">
                                        <i class="bi bi-car-front-fill"></i>
                                    </span>
                                @endif
                                <div class="min-w-0">
                                    <a href="{{ $v ? route('vehicles.show', $v) : '#' }}" wire:navigate
                                        class="font-mono text-sm font-bold text-slate-900 hover:text-indigo-600 transition truncate block">
                                        {{ $v?->plate_number ?: '—' }}
                                    </a>
                                    <div class="text-[11px] text-slate-500 truncate">
                                        {{ trim(($v?->brand ?? '') . ' ' . ($v?->model ?? '')) ?: __('fleet.common.vehicle') }}
                                    </div>
                                </div>
                            </div>

                            <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-800 shrink-0">
                                <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                <span>{{ __('fleet.index.badge_on_trip') }}</span>
                            </span>
                        </div>

                        {{-- Elapsed Duration Badge --}}
                        <div class="rounded-xl bg-amber-100/70 border border-amber-200/60 px-3 py-2 text-xs flex items-center justify-between gap-2">
                            <div class="flex items-center gap-1.5 text-amber-950 font-semibold">
                                <i class="bi bi-stopwatch text-amber-700"></i>
                                <span>{{ __('fleet.operations.elapsed') }} {{ $trip->elapsed_text }}</span>
                            </div>
                            <span class="text-[11px] text-amber-800 font-mono font-medium">
                                {{ $depTime ? $depTime->format('H:i') . ' WIB' : '—' }}
                            </span>
                        </div>

                        {{-- Trip Details: Driver & Purpose --}}
                        <div class="space-y-1.5 text-xs">
                            <div class="flex items-center gap-2 text-slate-700">
                                <i class="bi bi-person-fill text-slate-400 text-xs shrink-0"></i>
                                <span class="font-medium truncate">{{ $trip->driver_name ?: '—' }}</span>
                            </div>

                            @if ($trip->trip_purpose)
                                <div class="flex items-start gap-2 text-slate-600">
                                    <i class="bi bi-geo-alt-fill text-slate-400 text-xs shrink-0 mt-0.5"></i>
                                    <span class="italic text-[11px] leading-snug line-clamp-2">"{{ $trip->trip_purpose }}"</span>
                                </div>
                            @endif

                            {{-- Initial Telemetry: Odometer & Fuel --}}
                            <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
                                <span class="font-mono font-medium">
                                    <i class="bi bi-speedometer2 text-slate-400 mr-1"></i>
                                    {{ number_format($trip->odometer) }} km
                                </span>
                                <span class="font-semibold text-slate-700">
                                    <i class="bi bi-fuel-pump text-slate-400 mr-1"></i>
                                    {{ $trip->fuel_percentage }}%
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Footer: Inspector Info & Cockpit Link --}}
                    <div class="mt-3.5 pt-2.5 border-t border-amber-200/50 flex items-center justify-between text-xs">
                        <span class="text-[11px] text-slate-400 font-medium truncate" title="{{ __('fleet.operations.inspector') }}: {{ $trip->created_by ?: ($trip->inspector?->name ?? '—') }}">
                            {{ $trip->created_by ?: ($trip->inspector?->name ?? '—') }}
                        </span>
                        @if ($v)
                            <a href="{{ route('vehicles.show', $v) }}" wire:navigate
                                class="shrink-0 inline-flex items-center gap-1 text-xs font-semibold text-amber-900 hover:text-amber-950 transition">
                                <span>{{ __('fleet.operations.view_vehicle') }}</span>
                                <i class="bi bi-arrow-right text-[11px]"></i>
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @else
        {{-- Standby Pool Banner --}}
        <div class="rounded-2xl bg-emerald-50/60 border border-emerald-200/60 p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-emerald-950 shadow-xs">
            <div class="flex items-center gap-3">
                <span class="h-10 w-10 rounded-xl bg-emerald-100 flex items-center justify-center text-emerald-700 text-lg shrink-0">
                    <i class="bi bi-check-circle-fill"></i>
                </span>
                <div>
                    <h3 class="text-sm font-bold text-emerald-950">
                        {{ __('fleet.operations.all_in_pool') }}
                    </h3>
                    <p class="text-xs text-emerald-800/80 mt-0.5">
                        {{ __('fleet.operations.all_in_pool_desc') }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2 self-start sm:self-auto">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white border border-emerald-200/80 text-xs font-semibold text-emerald-900 shadow-2xs">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                    <span>{{ __('fleet.operations.ready_units_count', ['count' => $metrics['in_pool']]) }}</span>
                </span>
            </div>
        </div>
    @endif

    {{-- Universal Photo Lightbox --}}
    <x-universal-lightbox />
</div>
