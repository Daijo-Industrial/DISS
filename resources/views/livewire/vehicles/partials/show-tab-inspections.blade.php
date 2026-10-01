{{-- ========================================================================= --}}
{{-- TAB 1: LOGBOOK PEMERIKSAAN HARIAN (P2H)                                   --}}
{{-- ========================================================================= --}}
<div class="rounded-3xl border border-slate-200/80 bg-white p-4 sm:p-5 shadow-xs space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3">
        <div>
            <h2 class="text-sm font-bold text-slate-900">{{ __('fleet.show.inspections_title') }}</h2>
            <p class="text-xs text-slate-400">{{ __('fleet.show.inspections_desc') }}</p>
        </div>

        {{-- Dual Filter Controls: Type + Severity --}}
        <div class="flex flex-wrap items-center gap-2">
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
        <div class="space-y-3">
            @foreach ($inspections as $ins)
                @php $isOutTrip = $ins->inspection_type === 'check_out'; @endphp
                <div x-data="{ showDetails: false }" class="rounded-2xl border p-3.5 sm:p-4 transition
                    {{ $ins->severity === 'critical_grounded' ? 'border-rose-200 bg-rose-50/30' : ($ins->severity === 'minor' ? 'border-amber-200 bg-amber-50/20' : 'border-slate-200/80 bg-white hover:border-slate-300') }}">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        {{-- Left Info --}}
                        <div class="flex items-start gap-3">
                            <span class="h-8 w-8 sm:h-9 sm:w-9 rounded-xl flex items-center justify-center text-base shrink-0
                                {{ $isOutTrip ? 'bg-slate-100 text-slate-700' : 'bg-amber-100 text-amber-700' }}">
                                <i class="bi {{ $isOutTrip ? 'bi-box-arrow-up-right' : 'bi-box-arrow-in-down' }}"></i>
                            </span>
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-bold text-slate-900">
                                        {{ $isOutTrip ? __('fleet.inspection.type_checkout') : __('fleet.inspection.type_checkin') }}
                                    </span>
                                    <span class="rounded-md px-2 py-0.5 text-[10px] font-bold {{ $ins->severity_badge_classes }}">
                                        {{ $ins->severity_label }}
                                    </span>
                                </div>
                                <div class="text-[11px] text-slate-500 mt-0.5">
                                    <span>{{ __('fleet.common.driver') }}: <strong>{{ $ins->driver_name }}</strong></span>
                                    <span class="mx-1">•</span>
                                    <span>{{ $ins->created_at->isoFormat('DD MMM YYYY, HH:mm') }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Right Metrics --}}
                        <div class="flex items-center gap-3">
                            <div class="text-left sm:text-right">
                                <span class="font-mono text-xs font-bold text-slate-900">{{ number_format($ins->odometer) }} km</span>
                                <span class="text-[10px] text-slate-400 block">{{ __('fleet.common.fuel') }}: {{ $ins->fuel_percentage }}%</span>
                            </div>

                            @if ($ins->trip_distance)
                                <span class="rounded-xl bg-slate-100 border border-slate-200/70 px-2.5 py-1 text-xs font-bold text-slate-800">
                                    +{{ number_format($ins->trip_distance) }} km
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Defect note or purpose if any --}}
                    @if ($ins->defect_notes || $ins->trip_purpose)
                        <div class="mt-3 pt-2.5 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
                            <div class="text-slate-600">
                                @if ($ins->trip_purpose)
                                    <span class="text-slate-400 mr-1">{{ __('fleet.show.destination') }}</span> <em>"{{ $ins->trip_purpose }}"</em>
                                @endif
                                @if ($ins->defect_notes)
                                    <div class="text-rose-700 font-semibold mt-0.5">
                                        <i class="bi bi-exclamation-triangle mr-1"></i> {{ __('fleet.show.finding_label') }} {{ $ins->defect_notes }}
                                    </div>
                                @endif
                            </div>

                            @if ($ins->defect_notes)
                                <a href="{{ route('services.create', ['vehicle' => $vehicle, 'odometer' => $ins->odometer, 'notes' => 'Temuan P2H: ' . $ins->defect_notes]) }}"
                                    class="inline-flex items-center gap-1 rounded-xl bg-slate-900 px-2.5 py-1 text-[11px] font-bold text-white hover:bg-slate-800 transition shrink-0 shadow-2xs">
                                    <i class="bi bi-wrench"></i> {{ __('fleet.show.escalate_service_btn') }}
                                </a>
                            @endif
                        </div>
                    @endif

                    {{-- Expandable Checklist Results & Photos --}}
                    @php
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

                    <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between text-xs">
                        <button type="button" @click="showDetails = !showDetails"
                            class="inline-flex items-center gap-1.5 text-[11px] font-bold text-slate-500 hover:text-slate-900 transition cursor-pointer">
                            <i class="bi bi-list-check text-slate-700"></i>
                            <span x-text="showDetails ? '{{ __('fleet.show.close_checklist_details') }}' : '{{ __('fleet.show.view_checklist_details') }}'"></span>
                            @if ($totalPhotos > 0)
                                <span class="rounded-full bg-slate-100 text-slate-700 px-1.5 py-0.2 text-[10px] font-bold border border-slate-200">
                                    <i class="bi bi-camera"></i> {{ $totalPhotos }}
                                </span>
                            @endif
                        </button>

                        <span class="text-[10px] text-slate-400 font-mono">
                            {{ __('fleet.show.p2h_id', ['id' => $ins->id]) }}
                        </span>
                    </div>

                    <div x-show="showDetails" x-cloak class="mt-3 pt-3 border-t border-dashed border-slate-200 space-y-3">
                        @if (!empty($chkResults))
                            <div class="grid gap-2 sm:grid-cols-2">
                                @foreach ($chkResults as $itemKey => $item)
                                    @php
                                        $statusOk = ($item['status'] ?? 'ok') === 'ok';
                                        $pointPhotos = $item['photos'] ?? [];
                                    @endphp
                                    <div class="rounded-xl border p-2.5 text-xs {{ $statusOk ? 'border-slate-100 bg-slate-50/70' : 'border-rose-200 bg-rose-50/50' }}">
                                        <div class="flex items-center justify-between gap-1">
                                            <span class="font-bold {{ $statusOk ? 'text-slate-800' : 'text-rose-900' }}">
                                                {{ $item['label'] ?? ucfirst($itemKey) }}
                                            </span>
                                            <span class="rounded-md px-1.5 py-0.2 text-[10px] font-bold {{ $statusOk ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
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
            @endforeach
        </div>

        <div>{{ $inspections->links() }}</div>
    @endif
</div>
