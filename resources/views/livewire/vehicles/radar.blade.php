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
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200/80">
                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse shrink-0"></span>
                            <span>{{ __('fleet.operations.active_trips_count', ['count' => $metrics['on_trip']]) }}</span>
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200/80">
                            {{ __('fleet.operations.all_standby') }}
                        </span>
                    @endif
                </div>
                <div class="text-xs text-slate-500 mt-0.5">
                    {{ __('fleet.operations.live_radar_subtitle') }}
                </div>
            </div>
        </div>

        {{-- Right Controls: Live Telemetry Indicator & Manual Refresh --}}
        <div class="flex items-center gap-2 sm:gap-2.5 self-start sm:self-auto text-xs text-slate-500">
            <div class="inline-flex items-center gap-1.5 sm:gap-2 px-2.5 sm:px-3 py-1.5 rounded-xl bg-white border border-slate-200/80 shadow-2xs"
                title="Live • 30s">
                <span class="relative flex h-2 w-2 shrink-0">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                </span>
                <span class="text-[10px] sm:text-[11px] font-bold tracking-wider text-emerald-700 uppercase">LIVE</span>
                <span class="text-slate-300">•</span>
                <span class="font-mono text-[11px] sm:text-xs text-slate-500 flex items-center gap-1">
                    <span class="text-slate-400">{{ __('fleet.operations.last_updated') }}:</span>
                    <strong class="text-slate-700 font-semibold">{{ $lastUpdatedAt }} WIB</strong>
                </span>
            </div>

            <button type="button" wire:click="$refresh"
                class="inline-flex items-center gap-1 px-2.5 py-1.5 sm:py-2 rounded-xl bg-white border border-slate-200/80 hover:bg-slate-50 text-slate-700 text-xs font-semibold transition active:scale-95 cursor-pointer shadow-2xs">
                <i class="bi bi-arrow-clockwise text-xs" wire:loading.class="animate-spin" wire:target="$refresh"></i>
                <span class="hidden sm:inline">{{ __('fleet.operations.refresh') }}</span>
            </button>
        </div>
    </div>

    {{-- Subtle Orphan Checkouts Alert Banner (Collapsible - Only shown to users who can inspect) --}}
    @if ($canInspect && $orphanCheckouts->isNotEmpty())
        <div x-data="{ open: false }" class="rounded-2xl border border-slate-200/80 bg-slate-50/70 p-3 sm:p-3.5 text-xs shadow-2xs">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-2.5">
                    <span class="h-7 w-7 rounded-lg bg-amber-50 border border-amber-200/60 text-amber-700 flex items-center justify-center text-xs shrink-0">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </span>
                    <div>
                        <span class="font-bold text-slate-900">
                            {{ __('fleet.operations.orphan_alert_title', ['count' => $orphanCheckouts->count()]) }}
                        </span>
                        <div class="text-[11px] text-slate-500 mt-0.5">
                            {{ __('fleet.operations.orphan_alert_desc') }}
                        </div>
                    </div>
                </div>

                <button type="button" @click="open = !open"
                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-white border border-slate-200/80 hover:bg-slate-100/70 text-slate-700 text-xs font-semibold transition cursor-pointer shrink-0">
                    <span x-text="open ? '{{ __('fleet.operations.hide_telemetry') }}' : '{{ __('fleet.operations.view_details') }}'"></span>
                    <i class="bi text-xs" :class="open ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                </button>
            </div>

            <div x-show="open" x-collapse x-cloak class="mt-3 pt-3 border-t border-slate-200/60 space-y-2">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2">
                    @foreach ($orphanCheckouts as $orphan)
                        <div class="rounded-xl bg-white border border-slate-200/70 p-2.5 flex items-center justify-between gap-2 shadow-2xs">
                            <div class="min-w-0">
                                <span class="font-mono font-bold text-slate-900 block whitespace-nowrap">
                                    {{ $orphan->vehicle?->plate_number ?: '—' }}
                                </span>
                                <span class="text-[10px] text-slate-500 block truncate">
                                    {{ $orphan->driver_name }} • {{ $orphan->checked_at?->format('d M H:i') }}
                                </span>
                            </div>
                            @if ($orphan->vehicle)
                                <a href="{{ route('vehicles.show', ['vehicle' => $orphan->vehicle, 'tab' => 'inspections', 'from' => 'radar']) }}" wire:navigate
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

    {{-- Content: Ultra-Lean Cards per Active Vehicle on Trip (or Standby Banner) --}}
    @if ($liveActiveTrips->isNotEmpty())
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-4">
            @foreach ($liveActiveTrips as $trip)
                @php
                    $v = $trip->vehicle;
                    $depTime = $trip->checked_at ?? $trip->created_at;
                @endphp
                <div wire:key="live-radar-{{ $v?->id }}-{{ $trip->id }}"
                    class="group rounded-2xl border border-slate-200/90 bg-white p-3.5 sm:p-4 shadow-xs hover:border-slate-300 transition space-y-3 flex flex-col justify-between">
                    
                    <div class="space-y-2.5">
                        {{-- Top: Vehicle Plate, Model & Elapsed Badge --}}
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-center gap-2.5 min-w-0">
                                @if ($v?->image_path)
                                    <button type="button" @click.stop="$dispatch('open-lightbox', { src: '{{ asset('storage/' . $v->image_path) }}', title: '{{ __('fleet.show.lightbox_vehicle_photo', ['plate' => $v->plate_number]) }}', subtitle: '{{ trim($v->brand . ' ' . $v->model) }}' })"
                                        class="shrink-0 cursor-pointer">
                                        <img src="{{ asset('storage/' . $v->image_path) }}" alt="{{ $v->plate_number }}"
                                            class="h-9 w-9 rounded-xl object-cover border border-slate-200/80 shadow-2xs">
                                    </button>
                                @else
                                    <span class="h-9 w-9 rounded-xl flex items-center justify-center text-sm bg-slate-100 text-slate-600 shrink-0">
                                        <i class="bi bi-car-front-fill"></i>
                                    </span>
                                @endif
                                <div class="min-w-0">
                                    <a href="{{ $v ? route('vehicles.show', ['vehicle' => $v, 'from' => 'radar']) : '#' }}" wire:navigate
                                        class="font-mono text-sm font-bold text-slate-900 hover:text-indigo-600 transition whitespace-nowrap block">
                                        {{ $v?->plate_number ?: '—' }}
                                    </a>
                                    <div class="text-[11px] text-slate-500 truncate">
                                        {{ trim(($v?->brand ?? '') . ' ' . ($v?->model ?? '')) ?: __('fleet.common.vehicle') }}
                                    </div>
                                </div>
                            </div>

                            {{-- Lean Elapsed Badge --}}
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 border border-amber-200/70 px-2.5 py-1 text-[11px] font-semibold text-amber-900 shrink-0">
                                <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse shrink-0"></span>
                                <span>{{ __('fleet.operations.elapsed') }} {{ $trip->elapsed_text }}</span>
                            </span>
                        </div>

                        {{-- Details: Driver & Purpose --}}
                        <div class="space-y-1 text-xs">
                            <div class="flex items-center gap-1.5 text-slate-700 font-medium truncate">
                                <i class="bi bi-person text-slate-400 text-xs shrink-0"></i>
                                <span class="truncate">{{ $trip->driver_name ?: '—' }}</span>
                            </div>

                            @if ($trip->trip_purpose)
                                <div class="flex items-start gap-1.5 text-slate-500 text-[11px]">
                                    <i class="bi bi-geo-alt text-slate-400 text-xs shrink-0 mt-0.5"></i>
                                    <span class="italic leading-snug line-clamp-2">"{{ $trip->trip_purpose }}"</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Footer: Departure Time, Inspector PIC & Vehicle Link --}}
                    <div class="pt-2.5 border-t border-slate-100 flex items-center justify-between text-xs text-slate-400">
                        <div class="flex items-center gap-2 min-w-0 text-[11px]">
                            <span class="font-mono text-slate-600 shrink-0 font-medium">
                                {{ $depTime ? $depTime->format('H:i') . ' WIB' : '—' }}
                            </span>
                            <span class="text-slate-200">•</span>
                            <span class="truncate text-slate-500" title="{{ __('fleet.operations.inspector') }}: {{ $trip->created_by ?: ($trip->inspector?->name ?? '—') }}">
                                {{ $trip->created_by ?: ($trip->inspector?->name ?? '—') }}
                            </span>
                        </div>
                        @if ($v)
                            <a href="{{ route('vehicles.show', ['vehicle' => $v, 'from' => 'radar']) }}" wire:navigate
                                class="shrink-0 inline-flex items-center gap-1 text-[11px] font-semibold text-indigo-600 hover:text-indigo-800 transition">
                                <span>{{ __('fleet.operations.view_vehicle') }}</span>
                                <i class="bi bi-arrow-right text-[10px]"></i>
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @else
        {{-- Standby Pool Banner --}}
        <div class="rounded-2xl bg-white border border-slate-200/80 p-6 sm:p-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4 text-slate-900 shadow-xs">
            <div class="flex items-center gap-3.5">
                <span class="h-10 w-10 rounded-xl bg-emerald-50 border border-emerald-200/60 flex items-center justify-center text-emerald-600 text-lg shrink-0">
                    <i class="bi bi-check-circle-fill"></i>
                </span>
                <div>
                    <h3 class="text-sm font-bold text-slate-900">
                        {{ __('fleet.operations.all_in_pool') }}
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">
                        {{ __('fleet.operations.all_in_pool_desc') }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2 self-start sm:self-auto">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-50 border border-slate-200/80 text-xs font-semibold text-slate-700 shadow-2xs">
                    <span class="h-2 w-2 rounded-full bg-emerald-500 shrink-0"></span>
                    <span>{{ __('fleet.operations.ready_units_count', ['count' => $metrics['in_pool']]) }}</span>
                </span>
            </div>
        </div>
    @endif

    {{-- Universal Photo Lightbox --}}
    <x-universal-lightbox />
</div>
