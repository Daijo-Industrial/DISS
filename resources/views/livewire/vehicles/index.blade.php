<div class="max-w-7xl mx-auto px-3 sm:px-6 py-4 sm:py-6 space-y-4 sm:space-y-6"
    x-data="{
        userSelected: false,
        viewMode: (function() {
            try {
                const urlParams = new URLSearchParams(window.location.search);
                const urlView = urlParams.get('view');
                if (urlView === 'grid' || urlView === 'table') {
                    return urlView;
                }
                const saved = localStorage.getItem('diss_vehicle_view_mode');
                if (saved === 'grid' || saved === 'table') {
                    return saved;
                }
            } catch (e) {}
            // Default: 'grid' for tablets & mobile (< 1280px), 'table' for laptop screens (>= 1280px)
            return window.innerWidth < 1280 ? 'grid' : 'table';
        })(),
        setView(mode) {
            this.userSelected = true;
            this.viewMode = mode;
            try {
                localStorage.setItem('diss_vehicle_view_mode', mode);
                const url = new URL(window.location);
                url.searchParams.set('view', mode);
                window.history.replaceState({}, '', url);
            } catch (e) {}
        },
        handleResize() {
            if (!this.userSelected) {
                this.viewMode = window.innerWidth < 1280 ? 'grid' : 'table';
            }
        }
    }"
    @resize.window.debounce.150ms="handleResize()">
    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-indigo-50 text-indigo-700 text-[11px] font-semibold ring-1 ring-inset ring-indigo-200/60 mb-1">
                <i class="bi bi-shield-check"></i>
                <span>{{ __('fleet.common.fleet_management') }}</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900">
                {{ __('fleet.index.title') }}
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">
                {{ __('fleet.index.subtitle') }}
            </p>
        </div>

        <div class="flex items-center gap-2 self-start sm:self-auto">
            <a href="{{ route('vehicles.scan') }}" wire:navigate
                class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-3.5 py-2.5 text-xs font-bold text-white shadow-xs hover:bg-slate-800 transition active:scale-[0.98]">
                <i class="bi bi-qr-code-scan mr-1.5 text-sm text-cyan-400"></i>
                <span>{{ __('fleet.index.scan_qr') }}</span>
            </a>
            @if ($canManage)
                <a href="{{ route('vehicles.create') }}"
                    class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-bold text-white shadow-xs hover:bg-indigo-700 transition active:scale-[0.98]">
                    <i class="bi bi-plus-lg mr-1.5 text-sm"></i>
                    {{ __('fleet.index.add_vehicle') }}
                </a>
            @endif
        </div>
    </div>

    {{-- Top KPI Metrics (Mobile-Optimized Compact Grid) --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-4">
        {{-- Card 1: Total Armada --}}
        <div wire:click="setOperationalTab('all')"
            class="cursor-pointer rounded-2xl border bg-white p-3.5 sm:p-4 shadow-xs hover:border-indigo-300 transition {{ $operationalTab === 'all' ? 'border-indigo-500 ring-2 ring-indigo-100' : 'border-slate-200/80' }}">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">{{ __('fleet.index.metrics_total') }}</span>
                <span class="h-8 w-8 sm:h-9 sm:w-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <i class="bi bi-truck text-sm sm:text-base"></i>
                </span>
            </div>
            <div class="mt-2 flex items-baseline gap-1.5">
                <span class="text-xl sm:text-2xl font-extrabold text-slate-900">{{ $metrics['total'] }}</span>
                <span class="text-[11px] text-slate-400 font-medium">unit</span>
            </div>
        </div>

        {{-- Card 2: On-Trip --}}
        <div wire:click="setOperationalTab('on_trip')"
            class="cursor-pointer rounded-2xl border p-3.5 sm:p-4 shadow-xs hover:border-amber-400 transition {{ $operationalTab === 'on_trip' ? 'border-amber-500 ring-2 ring-amber-200 bg-amber-50/50' : 'border-amber-200/80 bg-gradient-to-br from-white to-amber-50/30' }}">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-semibold text-amber-900 uppercase tracking-wider">{{ __('fleet.index.metrics_on_trip') }}</span>
                <span class="h-8 w-8 sm:h-9 sm:w-9 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center">
                    <i class="bi bi-signpost-2 text-sm sm:text-base"></i>
                </span>
            </div>
            <div class="mt-2 flex items-baseline gap-1.5">
                <span class="text-xl sm:text-2xl font-extrabold text-amber-700">{{ $metrics['on_trip'] }}</span>
                <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-amber-800">
                    <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-ping"></span>
                    Aktif
                </span>
            </div>
        </div>

        {{-- Card 3: Standby di Pool --}}
        <div wire:click="setOperationalTab('in_pool')"
            class="cursor-pointer rounded-2xl border p-3.5 sm:p-4 shadow-xs hover:border-emerald-400 transition {{ $operationalTab === 'in_pool' ? 'border-emerald-500 ring-2 ring-emerald-200 bg-emerald-50/50' : 'border-emerald-200/80 bg-gradient-to-br from-white to-emerald-50/30' }}">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-semibold text-emerald-900 uppercase tracking-wider">{{ __('fleet.index.metrics_in_pool') }}</span>
                <span class="h-8 w-8 sm:h-9 sm:w-9 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center">
                    <i class="bi bi-house-check text-sm sm:text-base"></i>
                </span>
            </div>
            <div class="mt-2 flex items-baseline gap-1.5">
                <span class="text-xl sm:text-2xl font-extrabold text-emerald-700">{{ $metrics['in_pool'] }}</span>
                <span class="text-[11px] text-emerald-600 font-medium">ready</span>
            </div>
        </div>

        {{-- Card 4: Dokumen & Servis Alert --}}
        <div class="rounded-2xl border p-3.5 sm:p-4 shadow-xs transition {{ $metrics['alerts'] > 0 ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-white' }}">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-semibold {{ $metrics['alerts'] > 0 ? 'text-rose-900' : 'text-slate-500' }} uppercase tracking-wider">
                    {{ __('fleet.tabs.documents') }}
                </span>
                <span class="h-8 w-8 sm:h-9 sm:w-9 rounded-xl {{ $metrics['alerts'] > 0 ? 'bg-rose-100 text-rose-700' : 'bg-slate-100 text-slate-500' }} flex items-center justify-center">
                    <i class="bi bi-shield-exclamation text-sm sm:text-base"></i>
                </span>
            </div>
            <div class="mt-2 flex items-baseline gap-1.5">
                <span class="text-xl sm:text-2xl font-extrabold {{ $metrics['alerts'] > 0 ? 'text-rose-700' : 'text-slate-700' }}">{{ $metrics['alerts'] }}</span>
                <span class="text-[11px] {{ $metrics['alerts'] > 0 ? 'text-rose-600 font-semibold' : 'text-slate-400' }}">
                    {{ $metrics['alerts'] > 0 ? 'alerts' : 'safe' }}
                </span>
            </div>
        </div>
    </div>

    {{-- Compliance Expiry Alert Banner --}}
    @if ($canManage && $complianceAlerts->isNotEmpty())
        <div class="rounded-2xl border border-amber-300 bg-gradient-to-r from-amber-50 via-orange-50/50 to-amber-50 p-3.5 sm:p-4 shadow-xs">
            <div class="flex items-start gap-3">
                <div class="h-8 w-8 rounded-xl bg-amber-500 text-white flex items-center justify-center shrink-0 shadow-xs">
                    <i class="bi bi-bell-fill text-sm"></i>
                </div>
                <div class="flex-1">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-amber-950">
                            Peringatan KIR &amp; Pajak STNK
                        </h3>
                        <span class="text-[10px] font-bold text-amber-800 bg-amber-200/70 px-2 py-0.5 rounded-full">
                            {{ $complianceAlerts->count() }} Alert
                        </span>
                    </div>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach ($complianceAlerts as $alertDoc)
                            <a href="{{ route('vehicles.show', ['vehicle' => $alertDoc->vehicle_id, 'tab' => 'documents']) }}"
                                class="inline-flex items-center gap-1.5 rounded-xl border bg-white px-2.5 py-1 text-xs shadow-xs hover:bg-slate-50 transition {{ $alertDoc->status === 'expired' ? 'border-rose-300 text-rose-900' : 'border-amber-300 text-amber-900' }}">
                                <span class="font-mono font-bold">{{ $alertDoc->vehicle?->plate_number }}</span>
                                <span class="text-slate-300">|</span>
                                <span class="text-[11px] font-medium">{{ $alertDoc->type_label }}</span>
                                <span class="rounded-md px-1 py-0.2 text-[9px] font-bold {{ $alertDoc->status_badge_classes }}">
                                    {{ $alertDoc->status_label }}
                                </span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- SEGMENTED OPERATIONAL STATUS TABS (Driver & Satpam Gate Workflow) --}}
    <div class="flex items-center gap-1.5 overflow-x-auto p-1 bg-slate-100 rounded-2xl border border-slate-200/80">
        <button type="button" wire:click="setOperationalTab('all')"
            class="rounded-xl px-3.5 py-2 text-xs font-bold transition whitespace-nowrap {{ $operationalTab === 'all' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-800' }}">
            {{ __('fleet.operational_statuses.all') }}
            <span class="ml-1 rounded-full px-1.5 py-0.2 text-[10px] {{ $operationalTab === 'all' ? 'bg-slate-100 text-slate-700' : 'bg-slate-200 text-slate-600' }}">{{ $metrics['total'] }}</span>
        </button>

        <button type="button" wire:click="setOperationalTab('in_pool')"
            class="rounded-xl px-3.5 py-2 text-xs font-bold transition whitespace-nowrap {{ $operationalTab === 'in_pool' ? 'bg-white text-emerald-700 shadow-xs' : 'text-slate-500 hover:text-slate-800' }}">
            <span class="inline-block h-2 w-2 rounded-full bg-emerald-500 mr-1.5"></span>
            {{ __('fleet.operational_statuses.in_pool') }}
            <span class="ml-1 rounded-full px-1.5 py-0.2 text-[10px] {{ $operationalTab === 'in_pool' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600' }}">{{ $metrics['in_pool'] }}</span>
        </button>

        <button type="button" wire:click="setOperationalTab('on_trip')"
            class="rounded-xl px-3.5 py-2 text-xs font-bold transition whitespace-nowrap {{ $operationalTab === 'on_trip' ? 'bg-white text-amber-700 shadow-xs' : 'text-slate-500 hover:text-slate-800' }}">
            <span class="inline-block h-2 w-2 rounded-full bg-amber-500 mr-1.5"></span>
            {{ __('fleet.operational_statuses.on_trip') }}
            <span class="ml-1 rounded-full px-1.5 py-0.2 text-[10px] {{ $operationalTab === 'on_trip' ? 'bg-amber-100 text-amber-800' : 'bg-slate-200 text-slate-600' }}">{{ $metrics['on_trip'] }}</span>
        </button>

        <button type="button" wire:click="setOperationalTab('maintenance')"
            class="rounded-xl px-3.5 py-2 text-xs font-bold transition whitespace-nowrap {{ $operationalTab === 'maintenance' ? 'bg-white text-rose-700 shadow-xs' : 'text-slate-500 hover:text-slate-800' }}">
            <i class="bi bi-wrench mr-1"></i>
            {{ __('fleet.operational_statuses.maintenance') }}
        </button>
    </div>

    {{-- Filter & Control Bar --}}
    <div class="rounded-2xl border border-slate-200/80 bg-white p-3 sm:p-3.5 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-2.5">
        {{-- Search Input (44px touch target on mobile) --}}
        <div class="flex items-center gap-2 flex-1">
            <div class="relative w-full">
                <i class="bi bi-search absolute left-3 top-3 text-slate-400 text-xs"></i>
                <input type="text" placeholder="{{ __('fleet.index.search_placeholder') }}"
                    wire:model.live.debounce.300ms="q"
                    class="w-full rounded-xl border border-slate-200 bg-slate-50/70 py-2 sm:py-1.5 pl-8 pr-8 text-xs text-slate-900 placeholder:text-slate-400 focus:bg-white focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition">
                @if ($q !== '')
                    <button type="button" wire:click="$set('q','')" class="absolute right-2.5 top-2.5 text-slate-400 hover:text-slate-600">
                        <i class="bi bi-x-circle text-xs"></i>
                    </button>
                @endif
            </div>

            {{-- Category Filter --}}
            <div class="w-36 sm:w-44 shrink-0">
                <select wire:model.live="category"
                    class="w-full rounded-xl border border-slate-200 bg-slate-50/70 py-2 sm:py-1.5 px-2.5 text-xs text-slate-700 focus:bg-white focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition">
                    <option value="all">Semua Kategori</option>
                    <option value="passenger">Mobil Penumpang</option>
                    <option value="commercial_truck">Truk / Mobil Gede</option>
                    <option value="pickup">Pick-up / Bak</option>
                    <option value="other">Lainnya</option>
                </select>
            </div>
        </div>

        {{-- View Mode Switcher (Grid vs Table) & PerPage (Available on Tab & Laptop) --}}
        <div class="flex items-center justify-between md:justify-end gap-3 shrink-0 pt-1 md:pt-0 border-t md:border-t-0 border-slate-100">
            <div class="flex items-center gap-1 text-xs text-slate-500">
                <span>Per hal:</span>
                <select wire:model.live="perPage"
                    class="rounded-lg border border-slate-200 bg-slate-50 py-1 px-1.5 text-xs text-slate-700 focus:outline-none">
                    <option value="10">10</option>
                    <option value="20">20</option>
                    <option value="50">50</option>
                </select>
            </div>

            <div class="inline-flex rounded-xl bg-slate-100 p-0.5 border border-slate-200">
                <button type="button" @click="setView('grid')"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 sm:py-1 rounded-lg text-xs font-bold transition"
                    :class="viewMode === 'grid' ? 'bg-white text-indigo-700 shadow-xs' : 'text-slate-500 hover:text-slate-800'">
                    <i class="bi bi-grid-fill text-xs"></i>
                    <span>Galeri</span>
                </button>
                <button type="button" @click="setView('table')"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 sm:py-1 rounded-lg text-xs font-bold transition"
                    :class="viewMode === 'table' ? 'bg-white text-indigo-700 shadow-xs' : 'text-slate-500 hover:text-slate-800'">
                    <i class="bi bi-table text-xs"></i>
                    <span>Tabel</span>
                </button>
            </div>
        </div>
    </div>

    {{-- RESPONSIVE VIEW ENGINE --}}
    {{-- On Mobile & Tab (< 1280px): DEFAULT TO GRID CARDS --}}
    {{-- On Laptop (>= 1280px): DEFAULT TO DATA TABLE --}}

    {{-- CARDS VIEW (Mobile & Tab Default, or when toggled on Laptop) --}}
    <div x-show="viewMode === 'grid'" x-cloak>
        <div class="grid gap-3 sm:gap-4 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4">
            @forelse ($vehicles as $v)
                @php
                    $isOut = $v->is_out_on_trip;
                    $kirDoc = $v->documents->where('document_type', 'kir')->sortByDesc('expired_date')->first();
                    $stnkDoc = $v->documents->where('document_type', 'stnk_annual')->sortByDesc('expired_date')->first();
                @endphp

                <div wire:key="veh-card-{{ $v->id }}"
                    class="group relative flex flex-col justify-between rounded-2xl border border-slate-200/90 bg-white p-4 sm:p-5 shadow-xs hover:border-indigo-300 hover:shadow-md transition">
                    
                    {{-- Card Header: Plate, Model, Status --}}
                    <div>
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-center gap-2.5">
                                @if ($v->image_path)
                                    <button type="button" @click.stop="$dispatch('open-lightbox', { src: '{{ asset('storage/' . $v->image_path) }}', title: 'Foto Profil {{ $v->plate_number }}', subtitle: '{{ trim($v->brand . ' ' . $v->model) }}' })"
                                        title="Perbesar foto" class="group/img cursor-pointer shrink-0">
                                        <img src="{{ asset('storage/' . $v->image_path) }}" alt="{{ $v->plate_number }}"
                                            class="h-11 w-11 rounded-xl object-cover border border-slate-200/90 shadow-2xs group-hover/img:scale-105 transition">
                                    </button>
                                @else
                                    <span class="h-11 w-11 rounded-xl flex items-center justify-center text-xl shrink-0 {{ $v->category === 'commercial_truck' ? 'bg-amber-100 text-amber-700' : 'bg-indigo-100 text-indigo-700' }}">
                                        @if ($v->category === 'commercial_truck')
                                            <i class="bi bi-truck"></i>
                                        @else
                                            <i class="bi bi-car-front"></i>
                                        @endif
                                    </span>
                                @endif
                                <div>
                                    <div class="inline-block rounded-lg bg-slate-950 px-2.5 py-0.5 text-white">
                                        <span class="font-mono text-xs sm:text-sm font-black tracking-wider text-slate-50">
                                            {{ $v->plate_number }}
                                        </span>
                                    </div>
                                    <div class="text-xs text-slate-600 font-medium mt-0.5">
                                        {{ trim($v->brand . ' ' . $v->model) ?: 'Kendaraan' }} {{ $v->year ? "({$v->year})" : '' }}
                                    </div>
                                </div>
                            </div>

                            {{-- Operational Status Badge --}}
                            @if ($isOut)
                                <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-bold text-amber-700 ring-1 ring-inset ring-amber-200 shrink-0">
                                    <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                    On-Trip
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700 ring-1 ring-inset ring-emerald-200 shrink-0">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                    Di Pool
                                </span>
                            @endif
                        </div>

                        {{-- Active Trip Banner if Out --}}
                        @if ($isOut && $v->activeCheckOut)
                            <div class="mt-3 rounded-xl bg-amber-50/80 border border-amber-200/80 p-2.5 text-xs text-amber-950">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-amber-900 truncate">Driver: {{ $v->activeCheckOut->driver_name }}</span>
                                    <span class="text-[10px] text-amber-700 shrink-0">{{ $v->activeCheckOut->created_at->format('H:i') }} WIB</span>
                                </div>
                                @if ($v->activeCheckOut->trip_purpose)
                                    <p class="text-[11px] text-amber-800 truncate mt-0.5">"{{ $v->activeCheckOut->trip_purpose }}"</p>
                                @endif
                            </div>
                        @else
                            {{-- Info Row: Driver & Odometer --}}
                            <div class="mt-3 grid grid-cols-2 gap-2 text-xs border-y border-slate-100 py-2">
                                <div>
                                    <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider block">Driver Operasional</span>
                                    <span class="font-medium text-slate-700 truncate block mt-0.5">{{ $v->driver_name ?: '—' }}</span>
                                </div>
                                <div>
                                    <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider block">Odometer</span>
                                    <span class="font-bold text-slate-900 block mt-0.5">{{ number_format($v->odometer) }} km</span>
                                </div>
                            </div>
                        @endif

                        {{-- Compliance Badges & Category --}}
                        <div class="mt-2.5 flex items-center justify-between text-xs">
                            <div class="flex items-center gap-2">
                                <span class="rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600">
                                    {{ $v->category_label }}
                                </span>
                                <span class="rounded-md bg-blue-50 px-2 py-0.5 text-[10px] font-semibold text-blue-700">
                                    {{ $v->fuel_type_label }}
                                </span>
                            </div>

                            <div class="flex items-center gap-2 text-[10px] text-slate-400">
                                {{-- STNK Indicator --}}
                                <span class="inline-flex items-center gap-1 font-medium" title="Status Pajak STNK 1th">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $stnkDoc ? ($stnkDoc->status === 'expired' ? 'bg-rose-500' : ($stnkDoc->status === 'warning' ? 'bg-amber-500' : 'bg-emerald-500')) : 'bg-slate-300' }}"></span>
                                    STNK
                                </span>

                                {{-- KIR Indicator --}}
                                @if ($v->requires_kir)
                                    <span class="inline-flex items-center gap-1 font-medium" title="Status Uji Berkala KIR">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $kirDoc ? ($kirDoc->status === 'expired' ? 'bg-rose-500' : ($kirDoc->status === 'warning' ? 'bg-amber-500' : 'bg-emerald-500')) : 'bg-rose-500' }}"></span>
                                        KIR
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Card Footer: Primary Thumb Action Button + Cockpit Link --}}
                    <div class="mt-3.5 pt-3 border-t border-slate-100 space-y-2">
                        {{-- Large Thumb-Friendly Operational Action Button (iPhone XR 44pt touch standard) --}}
                        @if (!$v->is_sold)
                            @if ($isOut)
                                <a href="{{ route('vehicles.inspect', ['vehicle' => $v, 'type' => 'check_in']) }}"
                                    class="w-full flex items-center justify-center gap-1.5 rounded-xl bg-amber-500 py-2.5 text-xs font-black text-white shadow-xs hover:bg-amber-600 transition active:scale-[0.98]">
                                    <i class="bi bi-box-arrow-in-down text-sm"></i>
                                    <span>Check-in (Pulang ke Pool) →</span>
                                </a>
                            @else
                                <a href="{{ route('vehicles.inspect', ['vehicle' => $v, 'type' => 'check_out']) }}"
                                    class="w-full flex items-center justify-center gap-1.5 rounded-xl bg-emerald-600 py-2.5 text-xs font-black text-white shadow-xs hover:bg-emerald-700 transition active:scale-[0.98]">
                                    <i class="bi bi-box-arrow-up-right text-sm"></i>
                                    <span>P2H Check-out (Pra-Jalan) →</span>
                                </a>
                            @endif
                        @endif

                        {{-- Secondary Actions: Cockpit Detail & Servis --}}
                        <div class="flex items-center justify-between gap-2 pt-0.5">
                            <a href="{{ route('vehicles.show', $v) }}"
                                class="flex-1 inline-flex items-center justify-center gap-1 rounded-xl border border-slate-200 bg-white py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-50 transition">
                                <i class="bi bi-speedometer2"></i>
                                <span>Detail Cockpit</span>
                            </a>

                            <a href="{{ route('services.create', $v) }}"
                                class="inline-flex items-center justify-center rounded-xl bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-indigo-50 hover:text-indigo-600 transition"
                                title="Catat Servis">
                                <i class="bi bi-wrench mr-1"></i> Servis
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full rounded-2xl border border-dashed border-slate-300 p-8 sm:p-12 text-center">
                    <i class="bi bi-truck text-3xl sm:text-4xl text-slate-300"></i>
                    <h3 class="mt-2 text-xs sm:text-sm font-bold text-slate-700">Tidak ada armada pada kategori/status ini</h3>
                    <p class="text-[11px] text-slate-400 mt-0.5">Gunakan tab filter lain atau ubah kata kunci pencarian.</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- DATA TABLE VIEW (Default for Laptop Screens >= 1280px, or when toggled on Tab) --}}
    <div x-show="viewMode === 'table'" x-cloak class="rounded-2xl border border-slate-200/80 bg-white shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                    <thead class="bg-slate-50 font-semibold text-slate-600 uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-3">Armada / Plat</th>
                            <th class="px-4 py-3">Kategori</th>
                            <th class="px-4 py-3">Driver</th>
                            <th class="px-4 py-3">Odometer</th>
                            <th class="px-4 py-3">Status Operasional</th>
                            <th class="px-4 py-3">Servis Terakhir</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white text-slate-700">
                        @forelse ($vehicles as $v)
                            @php
                                $last = $v->latestService;
                                $isOut = $v->is_out_on_trip;
                            @endphp
                            <tr wire:key="veh-table-{{ $v->id }}" class="hover:bg-slate-50/70 transition">
                                <td class="px-4 py-3.5">
                                    <div class="flex items-center gap-3">
                                        @if ($v->image_path)
                                            <button type="button" @click.stop="$dispatch('open-lightbox', { src: '{{ asset('storage/' . $v->image_path) }}', title: 'Foto Profil {{ $v->plate_number }}', subtitle: '{{ trim($v->brand . ' ' . $v->model) }}' })"
                                                title="Perbesar foto" class="group/img cursor-pointer shrink-0">
                                                <img src="{{ asset('storage/' . $v->image_path) }}" alt="{{ $v->plate_number }}"
                                                    class="h-9 w-9 rounded-xl object-cover border border-slate-200/90 shadow-2xs group-hover/img:scale-105 transition">
                                            </button>
                                        @else
                                            <span class="h-9 w-9 rounded-xl flex items-center justify-center text-base shrink-0 {{ $v->category === 'commercial_truck' ? 'bg-amber-100 text-amber-700' : 'bg-indigo-100 text-indigo-700' }}">
                                                @if ($v->category === 'commercial_truck')
                                                    <i class="bi bi-truck"></i>
                                                @else
                                                    <i class="bi bi-car-front"></i>
                                                @endif
                                            </span>
                                        @endif
                                        <div>
                                            <div class="font-mono font-bold text-slate-900">{{ $v->plate_number }}</div>
                                            <div class="text-[11px] text-slate-500">{{ trim($v->brand . ' ' . $v->model) }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    <span class="inline-flex items-center rounded-lg bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-700">
                                        {{ $v->category_label }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    <span class="text-slate-800 font-medium">{{ $v->driver_name ?: '—' }}</span>
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap font-semibold">
                                    {{ number_format($v->odometer) }} km
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    @if ($isOut)
                                        <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-0.5 text-[10px] font-bold text-amber-700 ring-1 ring-inset ring-amber-200">
                                            On-Trip
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-[10px] font-bold text-emerald-700 ring-1 ring-inset ring-emerald-200">
                                            In Pool
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    @if ($last)
                                        <div class="font-medium text-slate-800">{{ $last->service_date->isoFormat('DD/MM/YY') }}</div>
                                        <div class="text-[10px] text-slate-400">{{ $last->workshop ?? 'Internal' }}</div>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5">
                                        @if (!$v->is_sold)
                                            @if ($isOut)
                                                <a href="{{ route('vehicles.inspect', ['vehicle' => $v, 'type' => 'check_in']) }}"
                                                    class="rounded-lg bg-amber-500 px-2.5 py-1 text-xs font-bold text-white hover:bg-amber-600 shadow-xs">
                                                    Check-in
                                                </a>
                                            @else
                                                <a href="{{ route('vehicles.inspect', ['vehicle' => $v, 'type' => 'check_out']) }}"
                                                    class="rounded-lg bg-emerald-600 px-2.5 py-1 text-xs font-bold text-white hover:bg-emerald-700 shadow-xs">
                                                    P2H
                                                </a>
                                            @endif
                                        @endif
                                        <a href="{{ route('vehicles.show', $v) }}"
                                            class="rounded-lg border border-slate-200 bg-white px-2 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                            Cockpit
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-xs text-slate-400">
                                    Tidak ada data armada.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    {{-- Pagination --}}
    <div class="pt-2">
        {{ $vehicles->links() }}
    </div>

    {{-- Universal Photo Lightbox --}}
    <x-universal-lightbox />
</div>
