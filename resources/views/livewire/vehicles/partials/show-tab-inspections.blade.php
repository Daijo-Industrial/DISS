{{-- ========================================================================= --}}
{{-- TAB 1: LOGBOOK PEMERIKSAAN HARIAN (P2H) - TIMELINE ACTIVITY VIEW          --}}
{{-- ========================================================================= --}}
<div class="rounded-3xl border border-slate-200/80 bg-white p-4 sm:p-5 shadow-xs space-y-4 min-w-0">
    {{-- Header with Title & Dual Filters --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3 min-w-0">
        <div>
            <h2 class="text-sm font-bold text-slate-900">{{ __('fleet.show.inspections_title') }}</h2>
            <p class="text-xs text-slate-400">{{ __('fleet.show.inspections_desc') }}</p>
        </div>

        {{-- Dual Filter Controls: Type + Severity --}}
        <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto [&>select]:flex-1 sm:[&>select]:flex-none">
            <select wire:model.live="inspectionType"
                class="rounded-xl border border-slate-200 bg-slate-50/70 py-1.5 px-3 text-xs text-slate-700 focus:outline-none cursor-pointer">
                <option value="all">{{ __('fleet.show.filter_type_all') }}</option>
                <option value="check_out">{{ __('fleet.show.filter_type_checkout') }}</option>
                <option value="check_in">{{ __('fleet.show.filter_type_checkin') }}</option>
            </select>

            <select wire:model.live="inspectionSeverity"
                class="rounded-xl border border-slate-200 bg-slate-50/70 py-1.5 px-3 text-xs text-slate-700 focus:outline-none cursor-pointer">
                <option value="all">{{ __('fleet.show.filter_severity_all') }}</option>
                <option value="defects_only">{{ __('fleet.show.filter_severity_defects') }}</option>
                <option value="critical_only">{{ __('fleet.show.filter_severity_critical') }}</option>
            </select>
        </div>
    </div>

    @if ($inspections->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-200 p-8 text-center text-xs text-slate-400">
            <i class="bi bi-clipboard-x text-2xl text-slate-300 block mb-1"></i>
            {{ __('fleet.show.empty_inspections') }}
        </div>
    @else
        {{-- CONTINUOUS TIMELINE FEED CONTAINER --}}
        <div class="relative pl-6 sm:pl-8 space-y-3 min-w-0 before:absolute before:left-3 sm:before:left-3.5 before:top-3 before:bottom-3 before:w-0.5 before:bg-slate-200/80">
            @foreach ($inspections as $ins)
                @php
                    $isOutTrip = $ins->inspection_type === 'check_out';
                    $hasDefect = (bool) $ins->defect_notes || in_array($ins->severity, ['minor', 'critical_grounded']);
                    $isCritical = $ins->severity === 'critical_grounded';

                    $chkResults = is_array($ins->checklist_results) ? $ins->checklist_results : json_decode($ins->checklist_results, true);
                    $totalPhotos = 0;
                    if (!empty($chkResults)) {
                        foreach ($chkResults as $r) {
                            if (!empty($r['photos'])) {
                                $totalPhotos += count($r['photos']);
                            }
                        }
                    }
                    if (!empty($ins->defect_photos)) {
                        $totalPhotos += count($ins->defect_photos);
                    }
                @endphp

                <div x-data="{ showDetails: false }" class="relative group min-w-0">
                    {{-- Timeline Bullet Node on the Vertical Thread --}}
                    <div @click="showDetails = !showDetails"
                        class="absolute -left-6 sm:-left-8 top-1.5 flex items-center justify-center cursor-pointer"
                        title="{{ __('fleet.show.view_checklist_details') }}">
                        <span class="h-6 w-6 sm:h-7 sm:w-7 rounded-full flex items-center justify-center text-xs ring-4 ring-white shadow-2xs transition
                            {{ $isCritical ? 'bg-rose-500 text-white' : ($hasDefect ? 'bg-amber-500 text-white' : ($isOutTrip ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700 border border-slate-200')) }}">
                            <i class="bi {{ $isOutTrip ? 'bi-box-arrow-up-right text-[10px]' : 'bi-box-arrow-in-down text-[10px]' }}"></i>
                        </span>
                    </div>

                    {{-- Timeline Item Body: Compact Row (Whole Card Clickable) --}}
                    <div @click="showDetails = !showDetails"
                        role="button"
                        tabindex="0"
                        :aria-expanded="showDetails.toString()"
                        @keydown.enter="showDetails = !showDetails"
                        @keydown.space.prevent="showDetails = !showDetails"
                        class="rounded-2xl border transition p-3 sm:p-3.5 cursor-pointer outline-none focus:outline-none focus:ring-0 min-w-0
                        {{ $isCritical ? 'border-rose-200 bg-rose-50/25 hover:border-rose-300 hover:bg-rose-50/40' : ($hasDefect ? 'border-amber-200 bg-amber-50/20 hover:border-amber-300 hover:bg-amber-50/35' : 'border-slate-200/80 bg-white hover:border-slate-300 hover:bg-slate-50/50 shadow-2xs') }}">

                        {{-- Line 1: Type, Badges, Driver, Time, and Metrics --}}
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 min-w-0">
                            {{-- Left: Event Identification --}}
                            <div class="flex flex-wrap items-center gap-1.5 sm:gap-2 min-w-0">
                                <span class="text-xs font-bold text-slate-900 shrink-0">
                                    {{ $isOutTrip ? __('fleet.inspection.type_checkout') : __('fleet.inspection.type_checkin') }}
                                </span>

                                <span class="rounded-md px-2 py-0.5 text-[10px] font-bold shrink-0 {{ $ins->severity_badge_classes }}">
                                    {{ $ins->severity_label }}
                                </span>

                                <span class="text-xs text-slate-600 truncate max-w-[200px] sm:max-w-none">
                                    Driver: <strong>{{ $ins->driver_name }}</strong>
                                    @if ($ins->created_by)
                                        <span class="text-slate-400 font-normal">• Dibuat: <strong>{{ $ins->created_by }}</strong></span>
                                    @endif
                                </span>

                                <span class="text-[11px] text-slate-400 shrink-0">
                                    • {{ $ins->created_at->isoFormat('DD MMM YYYY, HH:mm') }}
                                </span>
                            </div>

                            {{-- Right: Key Telemetry (Odometer & Fuel & Delta) --}}
                            <div class="flex flex-wrap items-center justify-between sm:justify-end gap-2 text-xs text-slate-600 w-full sm:w-auto min-w-0 pt-1.5 sm:pt-0 border-t border-slate-100 sm:border-0">
                                <div class="flex items-center gap-2 font-mono">
                                    <span class="font-bold text-slate-900">{{ number_format($ins->odometer) }} km</span>
                                    <span class="text-slate-300">|</span>
                                    <span class="text-[11px] text-slate-500 font-sans">{{ __('fleet.common.fuel') }}: {{ $ins->fuel_percentage }}%</span>

                                    @if ($ins->trip_distance)
                                        <span class="rounded-lg bg-slate-100 border border-slate-200/70 px-1.5 py-0.2 text-[10px] font-bold text-slate-800">
                                            +{{ number_format($ins->trip_distance) }} km
                                        </span>
                                    @endif
                                </div>

                                {{-- Compact Toggle Indicator --}}
                                <div title="{{ __('fleet.show.view_checklist_details') }}"
                                    class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-[11px] font-semibold text-slate-600 bg-slate-50 border border-slate-200/60 group-hover:bg-slate-100 group-hover:border-slate-300 transition shrink-0 ml-auto sm:ml-0">
                                    <i class="bi bi-list-check"></i>
                                    @if ($totalPhotos > 0)
                                        <span class="rounded-full bg-slate-200/80 text-slate-800 px-1.5 py-0.2 text-[9px] font-bold">
                                            <i class="bi bi-camera"></i> {{ $totalPhotos }}
                                        </span>
                                    @endif
                                    <i class="bi bi-chevron-down text-[10px] transition-transform duration-200" :class="showDetails ? 'rotate-180' : ''"></i>
                                </div>
                            </div>
                        </div>

                        {{-- Line 2: Purpose or Defect Alerts (Shown only if present) --}}
                        @if ($ins->trip_purpose || $ins->defect_notes)
                            <div class="mt-2 pt-2 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs min-w-0">
                                @if ($ins->trip_purpose)
                                    <div class="text-[11px] text-slate-500 italic truncate max-w-md">
                                        <i class="bi bi-geo-alt text-slate-400 mr-1 not-italic"></i>"{{ $ins->trip_purpose }}"
                                    </div>
                                @endif

                                @if ($ins->defect_notes)
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 {{ $isCritical ? 'text-rose-700' : 'text-amber-800' }} font-semibold text-xs bg-white/60 px-2.5 py-1.5 rounded-xl border {{ $isCritical ? 'border-rose-200' : 'border-amber-200' }} min-w-0 w-full sm:w-auto">
                                        <div class="flex items-center gap-1.5 min-w-0">
                                            <i class="bi bi-exclamation-triangle-fill shrink-0"></i>
                                            <span class="truncate">{{ $ins->defect_notes }}</span>
                                        </div>

                                        <a href="{{ route('services.create', ['vehicle' => $vehicle, 'odometer' => $ins->odometer, 'notes' => 'Temuan P2H: ' . $ins->defect_notes]) }}"
                                            @click.stop
                                            class="inline-flex items-center justify-center gap-1 rounded-lg bg-slate-900 px-2 py-1 text-[10px] font-bold text-white hover:bg-slate-800 transition shrink-0 shadow-2xs self-end sm:self-auto">
                                            <i class="bi bi-wrench"></i> {{ __('fleet.show.escalate_service_btn') }}
                                        </a>
                                    </div>
                                @endif
                            </div>
                        @endif

                        {{-- Expanded Checklist & Photos Details Drawer --}}
                        <div x-show="showDetails" x-cloak @click.stop class="mt-3 pt-3 border-t border-dashed border-slate-200 space-y-3 cursor-default">
                            @if (!empty($chkResults))
                                <div class="grid gap-2 sm:grid-cols-2">
                                    @foreach ($chkResults as $itemKey => $item)
                                        @php
                                            $statusOk = ($item['status'] ?? 'ok') === 'ok';
                                            $pointPhotos = $item['photos'] ?? [];
                                        @endphp
                                        <div class="rounded-xl border p-2 text-xs min-w-0 {{ $statusOk ? 'border-slate-100 bg-slate-50/70' : 'border-rose-200 bg-rose-50/50' }}">
                                            <div class="flex items-center justify-between gap-1.5 min-w-0">
                                                <span class="font-bold min-w-0 break-words {{ $statusOk ? 'text-slate-800' : 'text-rose-900' }}">
                                                    {{ $item['label'] ?? ucfirst($itemKey) }}
                                                </span>
                                                <span class="rounded-md px-1.5 py-0.2 text-[10px] font-bold shrink-0 {{ $statusOk ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                                                    {{ $statusOk ? __('fleet.show.checklist_ok') : __('fleet.show.checklist_issue') }}
                                                </span>
                                            </div>

                                            @if (!empty($item['notes']))
                                                <p class="mt-1 text-[11px] text-slate-600">
                                                    <span class="font-semibold text-slate-500">{{ __('fleet.show.checklist_remark') }}</span> {{ $item['notes'] }}
                                                </p>
                                            @endif

                                            @if (!empty($pointPhotos))
                                                <div class="mt-2 flex flex-wrap gap-1.5">
                                                    @foreach ($pointPhotos as $pPath)
                                                        <button type="button" @click.prevent="$dispatch('open-lightbox', { src: '{{ asset('storage/' . $pPath) }}', title: 'Foto Temuan P2H: {{ addslashes($item['label'] ?? ucfirst($itemKey)) }}', subtitle: '{{ $vehicle->plate_number }} • {{ $ins->created_at->format('d M Y H:i') }}' })"
                                                            class="group relative block cursor-pointer" title="Perbesar foto">
                                                            <img src="{{ asset('storage/' . $pPath) }}" class="h-10 w-10 object-cover rounded-md border border-slate-200 group-hover:opacity-80 group-hover:ring-2 group-hover:ring-slate-400 transition shadow-2xs">
                                                        </button>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            @if (!empty($ins->defect_photos))
                                <div class="pt-2 border-t border-slate-100">
                                    <span class="text-[11px] font-bold text-slate-700 block mb-1">{{ __('fleet.show.additional_defect_photos') }}</span>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($ins->defect_photos as $dPhoto)
                                            <button type="button" @click.prevent="$dispatch('open-lightbox', { src: '{{ asset('storage/' . $dPhoto) }}', title: 'Foto Bukti Kerusakan Utama P2H', subtitle: '{{ $vehicle->plate_number }} • {{ $ins->created_at->format('d M Y H:i') }}' })"
                                                class="group relative block cursor-pointer" title="Perbesar foto">
                                                <img src="{{ asset('storage/' . $dPhoto) }}" class="h-12 w-12 object-cover rounded-lg border border-slate-200 group-hover:opacity-80 group-hover:ring-2 group-hover:ring-slate-400 transition shadow-xs">
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="pt-2">{{ $inspections->links() }}</div>
    @endif
</div>
