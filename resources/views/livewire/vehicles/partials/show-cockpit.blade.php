{{-- ========================================================================= --}}
{{-- LEFT COLUMN: Sticky Cockpit & Vehicle Identity (lg:col-span-4)            --}}
{{-- ========================================================================= --}}
<div class="lg:col-span-4 space-y-4 lg:sticky lg:top-6 min-w-0">

    {{-- Vehicle Identity & Primary Cockpit Card --}}
    <div class="rounded-3xl border border-slate-200/80 bg-white p-4 sm:p-5 shadow-xs relative z-20 space-y-4 min-w-0">
        {{-- Header with Photo + Plate & Badges --}}
        <div class="flex items-start gap-3.5">
            {{-- Profile Photo with Sleek Lightbox Trigger & Manage Button --}}
            <div class="relative shrink-0 group">
                @if ($vehicle->image_path)
                    <button type="button" @click.prevent="$dispatch('open-lightbox', { src: '{{ asset('storage/' . $vehicle->image_path) }}', title: '{{ __('fleet.show.lightbox_vehicle_photo', ['plate' => $vehicle->plate_number]) }}', subtitle: '{{ trim($vehicle->brand . ' ' . $vehicle->model) }}' })"
                        title="Klik untuk memperbesar foto armada"
                        class="relative h-16 w-16 sm:h-20 sm:w-20 aspect-square rounded-2xl overflow-hidden border border-slate-200/90 shadow-2xs focus:outline-none focus:ring-2 focus:ring-slate-400 block cursor-pointer shrink-0">
                        <img src="{{ asset('storage/' . $vehicle->image_path) }}" alt="{{ $vehicle->plate_number }}"
                            class="h-full w-full object-cover group-hover:scale-105 transition duration-300">
                        <span class="absolute inset-0 bg-slate-900/30 opacity-0 group-hover:opacity-100 flex items-center justify-center text-white text-base transition">
                            <i class="bi bi-zoom-in"></i>
                        </span>
                    </button>
                @else
                    <div class="h-16 w-16 sm:h-20 sm:w-20 aspect-square rounded-2xl flex items-center justify-center text-2xl sm:text-3xl shadow-2xs shrink-0 {{ $vehicle->category === 'commercial_truck' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-700' }}">
                        @if ($vehicle->category === 'commercial_truck')
                            <i class="bi bi-truck"></i>
                        @else
                            <i class="bi bi-car-front"></i>
                        @endif
                    </div>
                @endif

                @if ($canManage)
                    <button type="button" wire:click="openPhotoModal" title="{{ $vehicle->image_path ? __('fleet.show.photo_delete') : __('fleet.show.photo_modal_title') }}"
                        class="absolute -bottom-1 -right-1 h-6 w-6 sm:h-7 sm:w-7 rounded-xl bg-white border border-slate-200 text-slate-600 hover:text-slate-900 hover:border-slate-300 shadow-2xs flex items-center justify-center text-xs transition active:scale-95 cursor-pointer z-10">
                        <i class="bi bi-camera"></i>
                    </button>
                @endif
            </div>

            {{-- Plate Chassis & Status Badges --}}
            <div class="space-y-1.5 min-w-0 flex-1">
                <div class="inline-flex items-center rounded-xl bg-slate-950 px-3 py-1 text-white shadow-xs">
                    <span class="font-mono text-base sm:text-lg font-black tracking-wider text-slate-50">
                        {{ $vehicle->plate_number }}
                    </span>
                </div>

                <div class="flex flex-wrap items-center gap-1.5">
                    @if ($isOut)
                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-700 border border-amber-200/60 shrink-0">
                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                            {{ __('fleet.operational_statuses.on_trip') }}
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 border border-emerald-200/60 shrink-0">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                            {{ __('fleet.operational_statuses.in_pool') }}
                        </span>
                    @endif

                    <span class="rounded-lg bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-700 border border-slate-200/60 shrink-0">
                        {{ $vehicle->category_label }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Vehicle Specs & Driver Details --}}
        <div class="rounded-2xl bg-slate-50/70 border border-slate-100 p-3 space-y-2 text-xs min-w-0">
            <div class="flex items-center justify-between text-slate-600 gap-2 min-w-0">
                <span class="text-slate-400 shrink-0">{{ __('fleet.common.brand') }} / {{ __('fleet.common.model') }}:</span>
                <span class="font-bold text-slate-900 text-right min-w-0 break-words">{{ trim($vehicle->brand . ' ' . $vehicle->model) }} {{ $vehicle->year ? "({$vehicle->year})" : '' }}</span>
            </div>
            <div class="flex items-center justify-between text-slate-600 gap-2 min-w-0">
                <span class="text-slate-400 shrink-0">{{ __('fleet.common.fuel_type') }}:</span>
                <span class="font-semibold text-slate-800 text-right min-w-0 break-words">{{ $vehicle->fuel_type_label }}</span>
            </div>
            <div class="flex items-center justify-between text-slate-600 gap-2 min-w-0">
                <span class="text-slate-400 shrink-0">{{ __('fleet.show.driver_operational') }}:</span>
                <span class="font-bold text-slate-900 text-right min-w-0 break-words">{{ $vehicle->driver_name ?: __('fleet.show.driver_unassigned') }}</span>
            </div>
            <div class="flex items-center justify-between text-slate-600 border-t border-slate-200/60 pt-2 gap-2 min-w-0">
                <span class="text-slate-400 font-semibold shrink-0">{{ __('fleet.show.gauge_odometer') }}:</span>
                <span class="font-mono text-sm font-black text-slate-900 text-right">{{ number_format($vehicle->odometer) }} KM</span>
            </div>
        </div>

        {{-- Apple Live Activity Banner (When On-Trip) --}}
        @if ($isOut && $vehicle->activeCheckOut)
            <div class="rounded-2xl bg-amber-50/80 border border-amber-200/60 p-3 text-xs text-amber-950 space-y-1 min-w-0">
                <div class="flex items-center gap-2">
                    <span class="h-2 w-2 rounded-full bg-amber-500 shrink-0"></span>
                    <span class="font-bold text-amber-900">{{ __('fleet.show.active_trip_alert') }}</span>
                </div>
                <div class="text-[11px] text-amber-800 pl-4 space-y-0.5 min-w-0">
                    <div>{{ __('fleet.common.driver') }}: <strong>{{ $vehicle->activeCheckOut->driver_name }}</strong></div>
                    <div>{{ __('fleet.show.departed_at') }} <strong>{{ $vehicle->activeCheckOut->created_at->isoFormat('HH:mm') }} WIB</strong> ({{ number_format($vehicle->activeCheckOut->odometer) }} km)</div>
                    @if ($vehicle->activeCheckOut->trip_purpose)
                        <div class="italic break-words">"{{ $vehicle->activeCheckOut->trip_purpose }}"</div>
                    @endif
                </div>
            </div>
        @endif

        {{-- Primary Action Button (Check-out / Check-in) --}}
        @if (!$vehicle->is_sold)
            <div>
                @if ($isOut)
                    <a href="{{ route('vehicles.inspect', ['vehicle' => $vehicle, 'type' => 'check_in']) }}"
                        class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-amber-500 hover:bg-amber-600 px-4 py-2.5 text-xs font-bold text-white shadow-2xs transition active:scale-[0.98]">
                        <i class="bi bi-box-arrow-in-down text-sm"></i>
                        <span>{{ __('fleet.show.btn_checkin') }} →</span>
                    </a>
                @else
                    <a href="{{ route('vehicles.inspect', ['vehicle' => $vehicle, 'type' => 'check_out']) }}"
                        class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 hover:bg-slate-800 px-4 py-2.5 text-xs font-bold text-white shadow-2xs transition active:scale-[0.98]">
                        <i class="bi bi-box-arrow-up-right text-sm"></i>
                        <span>{{ __('fleet.show.btn_checkout') }} →</span>
                    </a>
                @endif
            </div>
        @endif

        {{-- Secondary Action Buttons (Split Print Button + 2-Col Utility Buttons) --}}
        <div class="pt-2 border-t border-slate-100 space-y-2"
            x-data="{ qrMenuOpen: false, copied: false }">

            {{-- Row 1: Direct 1-Click Print Button with Dropdown Options --}}
            <div class="relative flex rounded-xl shadow-2xs w-full">
                <button type="button"
                    @click="printVehicleQrSticker('{{ $qrCodeBase64 }}', '{{ addslashes($vehicle->plate_number) }}', '{{ addslashes(trim($vehicle->brand . ' ' . $vehicle->model)) }}', '{{ $vehicle->year }}', '{{ addslashes($vehicle->id) }}')"
                    title="Cetak Langsung Stiker QR"
                    class="inline-flex items-center justify-center gap-1.5 rounded-l-xl bg-white border border-slate-200/80 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition cursor-pointer flex-1">
                    <i class="bi bi-printer text-xs text-slate-600"></i>
                    <span>{{ __('fleet.show.btn_print_qr') }}</span>
                </button>
                <button type="button" @click="qrMenuOpen = !qrMenuOpen" @click.outside="qrMenuOpen = false"
                    title="Opsi Tambahan Stiker"
                    class="inline-flex items-center px-2.5 rounded-r-xl bg-white border-y border-r border-slate-200/80 text-slate-400 hover:bg-slate-50 hover:text-slate-800 transition cursor-pointer shrink-0">
                    <i class="bi bi-chevron-down text-[10px]"></i>
                </button>

                {{-- Dropdown Menu (Download PNG, Copy UUID, Preview) --}}
                <div x-show="qrMenuOpen" x-cloak
                    class="absolute right-0 top-full mt-1.5 w-52 rounded-2xl bg-white border border-slate-200 shadow-xl p-1 z-50 space-y-0.5 text-xs">
                    @if ($qrCodeBase64)
                        <a href="data:image/png;base64,{{ $qrCodeBase64 }}" download="QR-Armada-{{ str_replace(' ', '-', $vehicle->plate_number) }}.png"
                            @click="qrMenuOpen = false"
                            class="w-full flex items-center gap-2 px-3 py-2 rounded-xl text-slate-700 hover:bg-slate-50 font-medium transition">
                            <i class="bi bi-download text-slate-500"></i>
                            <span>Unduh Gambar PNG</span>
                        </a>
                    @endif
                    <button type="button"
                        @click="navigator.clipboard.writeText('{{ $vehicle->id }}'); copied = true; setTimeout(() => { copied = false; qrMenuOpen = false; }, 1500)"
                        class="w-full flex items-center gap-2 px-3 py-2 rounded-xl text-slate-700 hover:bg-slate-50 font-medium transition cursor-pointer">
                        <i class="bi" :class="copied ? 'bi-check2 text-emerald-600' : 'bi-clipboard text-slate-500'"></i>
                        <span x-text="copied ? 'Tersalin!' : 'Salin UUID Armada'"></span>
                    </button>
                    <button type="button" wire:click="openQrModal" @click="qrMenuOpen = false"
                        class="w-full flex items-center gap-2 px-3 py-2 rounded-xl text-slate-700 hover:bg-slate-50 font-medium transition cursor-pointer">
                        <i class="bi bi-eye text-slate-500"></i>
                        <span>Pratinjau Stiker</span>
                    </button>
                </div>
            </div>

            {{-- Row 2: Secondary Quick Actions Grid --}}
            @php
                $secondaryCount = (!$vehicle->is_sold && $canManage ? 1 : 0) + ($canManage ? 1 : 0);
            @endphp
            @if ($secondaryCount > 0)
                <div class="grid {{ $secondaryCount === 2 ? 'grid-cols-2' : 'grid-cols-1' }} gap-2 min-w-0">
                    @if (!$vehicle->is_sold && $canManage)
                        <a href="{{ route('services.create', $vehicle) }}"
                            class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-white border border-slate-200/80 px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition shadow-2xs truncate min-w-0">
                            <i class="bi bi-wrench text-xs text-slate-500 shrink-0"></i>
                            <span class="truncate">{{ __('fleet.show.btn_add_service') }}</span>
                        </a>
                    @endif

                    @if ($canManage)
                        <a href="{{ route('vehicles.edit', $vehicle) }}"
                            class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-white border border-slate-200/80 px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition shadow-2xs truncate min-w-0">
                            <i class="bi bi-pencil text-xs text-slate-500 shrink-0"></i>
                            <span class="truncate">{{ __('fleet.show.btn_edit_vehicle') }}</span>
                        </a>
                    @endif
                </div>
            @endif
        </div>
    </div>

    {{-- Compact Glance & Health Card --}}
    <div class="rounded-3xl border border-slate-200/80 bg-white p-3.5 sm:p-4 shadow-xs space-y-3 min-w-0">
        {{-- Last Service Record Shortcut --}}
        <div class="cursor-pointer hover:bg-slate-50/80 -mx-1 px-1 py-1 rounded-xl transition"
            wire:click="setTab('services')">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ __('fleet.show.last_service_title') }}</span>
                <span class="text-[10px] text-slate-400">{{ __('fleet.show.doc_compliance_open') }}</span>
            </div>
            @if ($last)
                <div class="mt-1 flex items-baseline justify-between gap-2">
                    <span class="text-xs font-bold text-slate-900">{{ $last->service_date->isoFormat('D MMM YYYY') }}</span>
                    <span class="font-mono text-xs font-semibold text-slate-600 shrink-0">{{ number_format($last->odometer) }} KM</span>
                </div>
                <div class="mt-0.5 flex items-center justify-between text-[11px] text-slate-500">
                    <span class="truncate">{{ $last->workshop ?: __('fleet.show.internal_workshop') }}</span>
                    @if ($last->items && $last->items->count() > 0)
                        <span class="text-[10px] text-slate-400 shrink-0">{{ __('fleet.show.service_items_count', ['count' => $last->items->count()]) }}</span>
                    @endif
                </div>
            @else
                <div class="mt-1 flex items-center gap-2">
                    <span class="h-2 w-2 rounded-full bg-slate-300 shrink-0"></span>
                    <span class="text-xs font-medium text-slate-500">{{ __('fleet.show.no_service_records') }}</span>
                </div>
            @endif
        </div>

        {{-- Document Compliance Shortcut --}}
        <div class="pt-2 border-t border-slate-100 {{ $canViewDocuments ? 'cursor-pointer hover:bg-slate-50/80 -mx-1 px-1 py-1 rounded-xl transition' : '' }}"
            @if ($canViewDocuments) wire:click="setTab('documents')" @endif>
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ __('fleet.show.doc_compliance_title') }}</span>
                @if ($canViewDocuments)
                    <span class="text-[10px] text-slate-400">{{ __('fleet.show.doc_compliance_open') }}</span>
                @endif
            </div>
            <div class="mt-1 flex items-center gap-2">
                @if ($hasExpired > 0)
                    <span class="h-2 w-2 rounded-full bg-rose-500 shrink-0"></span>
                    <span class="text-xs font-bold text-rose-700">{{ __('fleet.show.doc_compliance_expired', ['count' => $hasExpired]) }}</span>
                @elseif ($hasWarning > 0)
                    <span class="h-2 w-2 rounded-full bg-amber-500 shrink-0"></span>
                    <span class="text-xs font-bold text-amber-700">{{ __('fleet.show.doc_compliance_warning', ['count' => $hasWarning]) }}</span>
                @else
                    <span class="h-2 w-2 rounded-full bg-emerald-500 shrink-0"></span>
                    <span class="text-xs font-bold text-emerald-700">{{ __('fleet.show.doc_compliance_valid') }}</span>
                @endif
            </div>
        </div>
    </div>
</div>
