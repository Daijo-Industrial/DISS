<div class="max-w-7xl mx-auto px-3 sm:px-6 py-4 sm:py-6 space-y-4 sm:space-y-5"
    x-data="{
        showFilters: false,
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
        <span class="text-slate-700 font-semibold truncate" aria-current="page">{{ __('fleet.index.title') }}</span>
    </nav>

    {{-- Page Header (Apple Clean Typography & Sleek Action Pills) --}}
    <div class="flex items-center justify-between gap-3">
        <div class="flex items-center gap-2.5 sm:gap-3">
            <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900">
                {{ __('fleet.index.title') }}
            </h1>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 border border-slate-200/60">
                {{ $metrics['total'] }} unit
            </span>
        </div>

        <div class="flex items-center gap-2">
            @if ($canInspect)
                <a href="{{ route('vehicles.scan') }}" wire:navigate
                    class="inline-flex items-center justify-center rounded-xl {{ ! $canManage ? 'bg-slate-900 text-white hover:bg-slate-800' : 'bg-white border border-slate-200/80 text-slate-700 hover:bg-slate-50' }} px-3.5 py-2 text-xs font-semibold transition active:scale-[0.98] shadow-2xs">
                    <i class="bi bi-qr-code-scan mr-1.5 text-xs {{ ! $canManage ? 'text-white' : 'text-slate-600' }}"></i>
                    <span>{{ __('fleet.index.scan_qr') }}</span>
                </a>
            @endif
            @if ($canManage)
                <a href="{{ route('vehicles.create') }}"
                    class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-3.5 py-2 text-xs font-semibold text-white hover:bg-slate-800 transition active:scale-[0.98] shadow-2xs">
                    <i class="bi bi-plus-lg mr-1.5 text-xs"></i>
                    <span>{{ __('fleet.index.add_vehicle') }}</span>
                </a>
            @endif
        </div>
    </div>

    {{-- Flash Notifications & Standby Re-Scan CTA --}}
    @if (session()->has('success'))
        <div class="rounded-2xl bg-emerald-50 border border-emerald-200/80 p-3 sm:p-4 text-xs sm:text-sm text-emerald-800 flex items-center justify-between gap-3 shadow-2xs">
            <div class="flex items-center gap-2">
                <i class="bi bi-check-circle-fill text-emerald-600 text-base"></i>
                <span class="font-medium">{{ session('success') }}</span>
            </div>
            @if ($canInspect)
                <a href="{{ route('vehicles.scan') }}" wire:navigate
                    class="shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-600 text-white font-bold text-xs hover:bg-emerald-700 transition shadow-2xs">
                    <i class="bi bi-qr-code-scan"></i>
                    <span>{{ __('fleet.scanner.scan_next') }}</span>
                </a>
            @endif
        </div>
    @endif

    {{-- Compact Compliance Expiry Notification Strip --}}
    @if ($canManage && $complianceAlerts->isNotEmpty())
        <div class="flex items-center justify-between gap-3 px-3.5 py-2.5 rounded-2xl bg-amber-50/70 border border-amber-200/60 text-xs text-amber-900">
            <div class="flex items-center gap-2 min-w-0">
                <i class="bi bi-exclamation-triangle-fill text-amber-500 text-xs shrink-0"></i>
                <span class="truncate font-medium">
                    <strong class="font-bold">{{ $complianceAlerts->count() }}</strong> dokumen KIR &amp; Pajak STNK membutuhkan perhatian segera
                </span>
            </div>
            @php
                $firstAlert = $complianceAlerts->first();
            @endphp
            @if ($firstAlert)
                <a href="{{ route('vehicles.show', ['vehicle' => $firstAlert->vehicle_id, 'tab' => 'documents']) }}"
                    class="shrink-0 font-semibold text-amber-800 hover:text-amber-950 inline-flex items-center gap-1 transition">
                    <span>Lihat Dokumen</span>
                    <i class="bi bi-arrow-right text-[11px]"></i>
                </a>
            @endif
        </div>
    @endif

    {{-- Search & Control Bar --}}
    <div class="rounded-2xl border border-slate-200/80 bg-white p-2.5 sm:p-3 shadow-xs space-y-2.5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
            {{-- Left: Search Input & Filter Toggle Button --}}
            <div class="flex items-center gap-2 flex-1">
                {{-- Search Input (Stays Visible) --}}
                <div class="relative w-full sm:max-w-xs">
                    <i class="bi bi-search absolute left-3 top-2.5 text-slate-400 text-xs"></i>
                    <input type="text" placeholder="{{ __('fleet.index.search_placeholder') }}"
                        wire:model.live.debounce.300ms="q"
                        class="w-full rounded-xl border border-slate-200/80 bg-slate-50/60 py-2 sm:py-1.5 pl-8 pr-8 text-xs text-slate-900 placeholder:text-slate-400 focus:bg-white focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200 transition">
                    @if ($q !== '')
                        <button type="button" wire:click="$set('q','')" class="absolute right-2.5 top-2.5 text-slate-400 hover:text-slate-600">
                            <i class="bi bi-x-circle text-xs"></i>
                        </button>
                    @endif
                </div>

                {{-- Filter Toggle Button (Shown in Grid view, since Table view has interactive toolbar filters) --}}
                <button type="button" x-show="viewMode === 'grid'" @click="showFilters = !showFilters"
                    :class="showFilters ? 'bg-indigo-50 border-indigo-200 text-indigo-700 font-bold shadow-2xs' : 'bg-slate-50/80 border-slate-200/80 text-slate-600 hover:bg-slate-100 font-semibold'"
                    class="inline-flex items-center gap-1.5 rounded-xl border py-2 sm:py-1.5 px-3 text-xs transition shrink-0 cursor-pointer active:scale-95">
                    <i class="bi bi-sliders text-xs"></i>
                    <span>Filter</span>
                    @if ($hasActiveFilters)
                        <span class="h-1.5 w-1.5 rounded-full bg-indigo-600 ring-2 ring-indigo-200"></span>
                    @endif
                </button>
            </div>

            {{-- Right: View Mode Switcher (Grid vs Table) & PerPage --}}
            <div class="flex items-center justify-between sm:justify-end gap-2.5 shrink-0 pt-1 sm:pt-0 border-t sm:border-t-0 border-slate-100">
                <select wire:model.live="perPage"
                    class="rounded-xl border border-slate-200/80 bg-slate-50/60 py-2 sm:py-1.5 px-2.5 text-xs font-semibold text-slate-700 hover:border-slate-300 focus:bg-white focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200 transition cursor-pointer">
                    <option value="10">10 / hal</option>
                    <option value="20">20 / hal</option>
                    <option value="50">50 / hal</option>
                </select>

                <div class="inline-flex rounded-xl bg-slate-100/90 p-0.5 border border-slate-200/60">
                    <button type="button" @click="setView('grid')"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 sm:py-1 rounded-lg text-xs font-semibold transition cursor-pointer"
                        :class="viewMode === 'grid' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-800'">
                        <i class="bi bi-grid-fill text-xs"></i>
                        <span>Galeri</span>
                    </button>
                    <button type="button" @click="setView('table')"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 sm:py-1 rounded-lg text-xs font-semibold transition cursor-pointer"
                        :class="viewMode === 'table' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-800'">
                        <i class="bi bi-table text-xs"></i>
                        <span>Tabel</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Collapsible Available Filters Panel (Shown only in Grid view, since Table view has interactive toolbar filters) --}}
        <div x-show="showFilters && viewMode === 'grid'" x-collapse x-cloak style="display: none;"
            class="pt-3 border-t border-slate-100 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Filter Tambahan</span>
                @if ($hasActiveFilters)
                    <button type="button" wire:click="resetFilters"
                        class="text-[11px] font-semibold text-rose-600 hover:text-rose-700 inline-flex items-center gap-1 cursor-pointer">
                        <i class="bi bi-arrow-counterclockwise"></i>
                        <span>Reset Filter</span>
                    </button>
                @endif
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                {{-- Operational Status Dropdown Filter (Grid view only) --}}
                <div x-show="viewMode === 'grid'">
                    <label for="fleet-status-filter" class="block text-[11px] font-semibold text-slate-600 mb-1">
                        Status Operasional
                    </label>
                    <div class="relative">
                        <select id="fleet-status-filter" wire:model.live="operationalTab"
                            class="w-full appearance-none rounded-xl border border-slate-200/80 bg-slate-50/60 py-2 pl-3 pr-8 text-xs font-semibold text-slate-800 hover:border-slate-300 focus:bg-white focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200 transition cursor-pointer">
                            @if ($operationalTab === 'custom')
                                <option value="custom" disabled selected>Multi-Pilihan ({{ count($selectedStatuses) }} status)</option>
                            @endif
                            <option value="all">Semua Status ({{ $metrics['total'] }})</option>
                            <option value="in_pool">Di Pool ({{ $metrics['in_pool'] }})</option>
                            <option value="on_trip">On-Trip ({{ $metrics['on_trip'] }})</option>
                            <option value="maintenance">Perawatan ({{ $metrics['maintenance'] ?? 0 }})</option>
                            @if ($canManage)
                                <option value="sold">Terjual ({{ $metrics['sold'] ?? 0 }})</option>
                            @endif
                        </select>
                        <i class="bi bi-chevron-down absolute right-2.5 top-1/2 -translate-y-1/2 text-[10px] text-slate-400 pointer-events-none"></i>
                    </div>
                </div>

                {{-- Category Filter (Grid view only) --}}
                <div x-show="viewMode === 'grid'">
                    <label for="fleet-category-filter" class="block text-[11px] font-semibold text-slate-600 mb-1">
                        {{ __('fleet.index.filter_category') }}
                    </label>
                    <div class="relative">
                        <select id="fleet-category-filter" wire:model.live="category"
                            class="w-full appearance-none rounded-xl border border-slate-200/80 bg-slate-50/60 py-2 pl-3 pr-8 text-xs font-semibold text-slate-800 hover:border-slate-300 focus:bg-white focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200 transition cursor-pointer">
                            @if ($category === 'custom' || (!empty($selectedCategories) && count($selectedCategories) > 1 && count($selectedCategories) < 4))
                                <option value="custom" disabled selected>Multi-Pilihan ({{ count($selectedCategories) }} tipe)</option>
                            @endif
                            <option value="passenger">{{ __('fleet.categories.passenger') }}</option>
                            <option value="commercial_truck">{{ __('fleet.categories.commercial_truck') }}</option>
                            <option value="pickup">{{ __('fleet.categories.pickup') }}</option>
                            <option value="other">{{ __('fleet.categories.other') }}</option>
                            <option value="all">{{ __('fleet.index.all_categories') }}</option>
                        </select>
                        <i class="bi bi-chevron-down absolute right-2.5 top-1/2 -translate-y-1/2 text-[10px] text-slate-400 pointer-events-none"></i>
                    </div>
                </div>

                {{-- Sort Field (Shown only when viewMode is grid) --}}
                <div x-show="viewMode === 'grid'">
                    <label for="fleet-sort-filter" class="block text-[11px] font-semibold text-slate-600 mb-1">
                        Urutkan Berdasarkan
                    </label>
                    <div class="relative">
                        <select id="fleet-sort-filter" wire:model.live="sort"
                            class="w-full appearance-none rounded-xl border border-slate-200/80 bg-slate-50/60 py-2 pl-3 pr-8 text-xs font-semibold text-slate-800 hover:border-slate-300 focus:bg-white focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200 transition cursor-pointer">
                            <option value="plate_number">Plat Nomor</option>
                            <option value="driver_name">Driver Penanggung Jawab</option>
                            @if ($canManage)
                                <option value="odometer">Odometer (KM)</option>
                                <option value="status">Status Unit</option>
                                <option value="last_service_date">Servis Terakhir</option>
                            @endif
                        </select>
                        <i class="bi bi-chevron-down absolute right-2.5 top-1/2 -translate-y-1/2 text-[10px] text-slate-400 pointer-events-none"></i>
                    </div>
                </div>

                {{-- Sort Direction (Shown only when viewMode is grid) --}}
                <div x-show="viewMode === 'grid'">
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">
                        Arah Urutan
                    </label>
                    <div class="relative">
                        <select wire:model.live="dir"
                            class="w-full appearance-none rounded-xl border border-slate-200/80 bg-slate-50/60 py-2 pl-3 pr-8 text-xs font-semibold text-slate-800 hover:border-slate-300 focus:bg-white focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200 transition cursor-pointer">
                            <option value="asc">A - Z (Naik / Terendah)</option>
                            <option value="desc">Z - A (Turun / Tertinggi)</option>
                        </select>
                        <i class="bi bi-chevron-down absolute right-2.5 top-1/2 -translate-y-1/2 text-[10px] text-slate-400 pointer-events-none"></i>
                    </div>
                </div>
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
                    $stnk1 = $v->documents->where('document_type', 'stnk_annual')->sortByDesc('expired_date')->first();
                    $stnk5 = $v->documents->where('document_type', 'stnk_five_year')->sortByDesc('expired_date')->first();

                    $stnkStatus = 'none';
                    if ($stnk1 || $stnk5) {
                        if (($stnk1 && $stnk1->status === 'expired') || ($stnk5 && $stnk5->status === 'expired')) {
                            $stnkStatus = 'expired';
                        } elseif (($stnk1 && in_array($stnk1->status, ['critical', 'warning'])) || ($stnk5 && in_array($stnk5->status, ['critical', 'warning']))) {
                            $stnkStatus = 'warning';
                        } else {
                            $stnkStatus = 'valid';
                        }
                    }
                @endphp

                <div wire:key="veh-card-{{ $v->id }}"
                    @click="window.location.href = '{{ route('vehicles.show', $v) }}'"
                    class="group relative flex flex-col justify-between rounded-2xl border border-slate-200/80 p-4 sm:p-4.5 shadow-xs hover:border-slate-300 hover:shadow-md transition cursor-pointer {{ ($v->is_sold || $v->status === \App\Enums\VehicleStatus::RETIRED || $v->status === 'retired') ? 'bg-slate-50/70 opacity-85 hover:opacity-100' : 'bg-white' }}">
                    
                    <div>
                        {{-- Top Row: Photo/Icon, Plate Number, Model & Operational Badge --}}
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-center gap-3">
                                @if ($v->image_path)
                                    <button type="button" @click.stop="$dispatch('open-lightbox', { src: '{{ asset('storage/' . $v->image_path) }}', title: 'Foto Profil {{ $v->plate_number }}', subtitle: '{{ trim($v->brand . ' ' . $v->model) }}' })"
                                        title="Perbesar foto" class="group/img cursor-pointer shrink-0">
                                        <img src="{{ asset('storage/' . $v->image_path) }}" alt="{{ $v->plate_number }}"
                                            class="h-10 w-10 rounded-xl object-cover border border-slate-200/90 shadow-2xs group-hover/img:scale-105 transition">
                                    </button>
                                @else
                                    <span class="h-10 w-10 rounded-xl flex items-center justify-center text-lg shrink-0 {{ $v->category === 'commercial_truck' ? 'bg-amber-50 text-amber-600' : 'bg-slate-100 text-slate-600' }}">
                                        @if ($v->category === 'commercial_truck')
                                            <i class="bi bi-truck"></i>
                                        @else
                                            <i class="bi bi-car-front"></i>
                                        @endif
                                    </span>
                                @endif
                                <div>
                                    <div class="font-mono text-sm font-bold tracking-tight text-slate-900 group-hover:text-indigo-600 transition-colors">
                                        {{ $v->plate_number }}
                                    </div>
                                    <div class="text-xs text-slate-500 font-medium">
                                        {{ trim($v->brand . ' ' . $v->model) ?: 'Kendaraan' }} {{ $v->year ? "({$v->year})" : '' }}
                                    </div>
                                </div>
                            </div>

                            {{-- Operational Status Badge --}}
                            @if ($v->is_sold)
                                <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2 py-0.5 text-[10px] font-bold text-rose-700 border border-rose-200/60 shrink-0" title="{{ $v->sold_at ? 'Terjual: ' . $v->sold_at->format('d/m/Y') : 'Unit Terjual' }}">
                                    <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                                    <span>Terjual{{ $v->sold_at ? ' ' . $v->sold_at->format('d/m/y') : '' }}</span>
                                </span>
                            @elseif ($v->status === \App\Enums\VehicleStatus::RETIRED || $v->status === 'retired')
                                <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-700 border border-slate-200/60 shrink-0">
                                    <span class="h-1.5 w-1.5 rounded-full bg-slate-500"></span>
                                    <span>Purnatugas</span>
                                </span>
                            @elseif ($v->status === \App\Enums\VehicleStatus::MAINTENANCE || $v->status === 'maintenance')
                                <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2 py-0.5 text-[10px] font-semibold text-rose-700 border border-rose-200/60 shrink-0">
                                    <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                                    <span>Perawatan</span>
                                </span>
                            @elseif ($isOut)
                                <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-700 border border-amber-200/60 shrink-0">
                                    <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                    <span>On-Trip</span>
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 border border-emerald-200/60 shrink-0">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                    <span>Di Pool</span>
                                </span>
                            @endif
                        </div>

                        {{-- Active Trip Banner if Out --}}
                        @if ($isOut && $v->activeCheckOut)
                            <div class="mt-3 rounded-xl bg-amber-50/70 border border-amber-200/50 p-2 text-xs text-amber-950">
                                <div class="flex items-center justify-between">
                                    <span class="font-semibold text-amber-900 truncate">Driver: {{ $v->activeCheckOut->driver_name }}</span>
                                    <span class="text-[10px] text-amber-700 shrink-0">{{ ($v->activeCheckOut->checked_at ?? $v->activeCheckOut->created_at)->format('H:i') }} WIB</span>
                                </div>
                                @if ($v->activeCheckOut->trip_purpose)
                                    <p class="text-[11px] text-amber-800 truncate mt-0.5 font-normal">"{{ $v->activeCheckOut->trip_purpose }}"</p>
                                @endif
                            </div>
                        @else
                            {{-- Condensed Single Line Metadata: Driver, Odometer & Legal Indicators --}}
                            <div class="mt-3 flex items-center justify-between text-xs pt-2 border-t border-slate-100">
                                <div class="flex items-center gap-1.5 min-w-0">
                                    <i class="bi bi-person text-slate-400 text-xs"></i>
                                    <span class="font-medium text-slate-700 truncate max-w-[120px]">{{ $v->driver_name ?: 'Tanpa Driver' }}</span>
                                    <span class="text-slate-300">•</span>
                                    <span class="font-semibold text-slate-800 shrink-0">{{ number_format($v->odometer) }} km</span>
                                </div>

                                <div class="flex items-center gap-2 shrink-0 text-[10px] text-slate-400">
                                    <span class="inline-flex items-center gap-1 font-medium" title="STNK">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $stnkStatus === 'expired' ? 'bg-rose-500' : ($stnkStatus === 'warning' ? 'bg-amber-500' : ($stnkStatus === 'valid' ? 'bg-emerald-500' : 'bg-slate-300')) }}"></span>
                                        STNK
                                    </span>
                                    @if ($v->requires_kir)
                                        <span class="inline-flex items-center gap-1 font-medium" title="KIR">
                                            <span class="h-1.5 w-1.5 rounded-full {{ $kirDoc ? ($kirDoc->status === 'expired' ? 'bg-rose-500' : ($kirDoc->status === 'warning' ? 'bg-amber-500' : 'bg-emerald-500')) : 'bg-rose-500' }}"></span>
                                            KIR
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Card Footer: Single Primary Action Button --}}
                    @if (!$v->is_sold && $v->category === 'passenger')
                        <div class="mt-3 pt-3 border-t border-slate-100" @click.stop>
                            @if ($isOut)
                                <a href="{{ route('vehicles.inspect', ['vehicle' => $v, 'type' => 'check_in']) }}"
                                    class="w-full flex items-center justify-center gap-1.5 rounded-xl bg-amber-500 py-2 px-3 text-xs font-bold text-white shadow-2xs hover:bg-amber-600 transition active:scale-[0.98]">
                                    <i class="bi bi-box-arrow-in-down text-sm"></i>
                                    <span>Check-in (Pulang ke Pool) →</span>
                                </a>
                            @else
                                <a href="{{ route('vehicles.inspect', ['vehicle' => $v, 'type' => 'check_out']) }}"
                                    class="w-full flex items-center justify-center gap-1.5 rounded-xl bg-slate-900 py-2 px-3 text-xs font-bold text-white shadow-2xs hover:bg-slate-800 transition active:scale-[0.98]">
                                    <i class="bi bi-box-arrow-up-right text-sm"></i>
                                    <span>P2H Check-out →</span>
                                </a>
                            @endif
                        </div>
                    @endif
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
    <div x-show="viewMode === 'table'" x-cloak class="rounded-2xl border border-slate-200/80 bg-white shadow-xs relative"
        x-data="{
            catFilterOpen: false,
            statusFilterOpen: false
        }">

        {{-- TABLE TOOLBAR (ON TOP OF TABLE: Prevents dropdowns from getting clipped by overflow-x-auto or table body) --}}
        <div class="px-4 py-2.5 sm:py-3 border-b border-slate-100 bg-slate-50/70 rounded-t-2xl flex flex-wrap items-center justify-between gap-3 relative z-30">
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mr-1 flex items-center gap-1.5">
                    <i class="bi bi-funnel text-xs text-slate-500"></i>
                    <span>Filter:</span>
                </span>

                {{-- Kategori Filter Dropdown Popover --}}
                <div class="relative" @click.outside="catFilterOpen = false">
                    <button type="button" @click="catFilterOpen = !catFilterOpen; statusFilterOpen = false"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border text-xs font-semibold transition cursor-pointer active:scale-95 shadow-2xs {{ $hasCustomCategory ? 'bg-indigo-50 border-indigo-200 text-indigo-700 font-bold' : 'bg-white border-slate-200/80 text-slate-700 hover:bg-slate-50' }}">
                        <i class="bi bi-car-front text-xs {{ $hasCustomCategory ? 'text-indigo-600' : 'text-slate-400' }}"></i>
                        <span>Kategori</span>
                        <span class="rounded-full px-1.5 py-0.2 text-[10px] font-bold {{ $hasCustomCategory ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-100 text-slate-600' }}">
                            {{ count($selectedCategories) }}
                        </span>
                        <i class="bi bi-chevron-down text-[10px] text-slate-400"></i>
                    </button>

                    {{-- Category Popover Menu --}}
                    <div x-show="catFilterOpen" x-cloak
                        class="absolute left-0 top-full mt-1.5 w-64 rounded-2xl bg-white border border-slate-200 shadow-xl p-3 z-50 normal-case tracking-normal space-y-2.5">
                        <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                            <span class="text-[11px] font-bold text-slate-800">Filter Kategori</span>
                            <div class="flex items-center gap-2 text-[10px]">
                                <button type="button" wire:click="selectAllCategories"
                                    class="font-semibold text-indigo-600 hover:text-indigo-800 cursor-pointer">
                                    Semua
                                </button>
                                @if ($hasCustomCategory)
                                    <span class="text-slate-300">•</span>
                                    <button type="button" wire:click="resetCategoryFilter"
                                        class="font-semibold text-rose-600 hover:text-rose-800 cursor-pointer">
                                        Reset
                                    </button>
                                @endif
                            </div>
                        </div>

                        <div class="space-y-1">
                            {{-- Passenger --}}
                            <label class="flex items-center gap-2.5 px-2 py-1.5 rounded-xl hover:bg-slate-50 cursor-pointer transition">
                                <input type="checkbox" value="passenger" wire:model.live="selectedCategories"
                                    class="rounded-md border-slate-300 text-indigo-600 focus:ring-indigo-500/20 cursor-pointer">
                                <div class="flex items-center gap-2 flex-1 min-w-0">
                                    <span class="h-5 w-5 rounded-md bg-slate-100 flex items-center justify-center text-slate-600 shrink-0 text-xs">
                                        <i class="bi bi-car-front"></i>
                                    </span>
                                    <span class="text-xs text-slate-700 font-medium truncate">{{ __('fleet.categories.passenger') }}</span>
                                </div>
                            </label>

                            {{-- Commercial Truck --}}
                            <label class="flex items-center gap-2.5 px-2 py-1.5 rounded-xl hover:bg-slate-50 cursor-pointer transition">
                                <input type="checkbox" value="commercial_truck" wire:model.live="selectedCategories"
                                    class="rounded-md border-slate-300 text-indigo-600 focus:ring-indigo-500/20 cursor-pointer">
                                <div class="flex items-center gap-2 flex-1 min-w-0">
                                    <span class="h-5 w-5 rounded-md bg-amber-50 flex items-center justify-center text-amber-600 shrink-0 text-xs">
                                        <i class="bi bi-truck"></i>
                                    </span>
                                    <span class="text-xs text-slate-700 font-medium truncate">{{ __('fleet.categories.commercial_truck') }}</span>
                                </div>
                            </label>

                            {{-- Pickup --}}
                            <label class="flex items-center gap-2.5 px-2 py-1.5 rounded-xl hover:bg-slate-50 cursor-pointer transition">
                                <input type="checkbox" value="pickup" wire:model.live="selectedCategories"
                                    class="rounded-md border-slate-300 text-indigo-600 focus:ring-indigo-500/20 cursor-pointer">
                                <div class="flex items-center gap-2 flex-1 min-w-0">
                                    <span class="h-5 w-5 rounded-md bg-emerald-50 flex items-center justify-center text-emerald-600 shrink-0 text-xs">
                                        <i class="bi bi-truck-front"></i>
                                    </span>
                                    <span class="text-xs text-slate-700 font-medium truncate">{{ __('fleet.categories.pickup') }}</span>
                                </div>
                            </label>

                            {{-- Other --}}
                            <label class="flex items-center gap-2.5 px-2 py-1.5 rounded-xl hover:bg-slate-50 cursor-pointer transition">
                                <input type="checkbox" value="other" wire:model.live="selectedCategories"
                                    class="rounded-md border-slate-300 text-indigo-600 focus:ring-indigo-500/20 cursor-pointer">
                                <div class="flex items-center gap-2 flex-1 min-w-0">
                                    <span class="h-5 w-5 rounded-md bg-slate-100 flex items-center justify-center text-slate-600 shrink-0 text-xs">
                                        <i class="bi bi-gear"></i>
                                    </span>
                                    <span class="text-xs text-slate-700 font-medium truncate">{{ __('fleet.categories.other') }}</span>
                                </div>
                            </label>
                        </div>

                        <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
                            <span>{{ ! $hasCustomCategory ? 'Penumpang (Default)' : (count($selectedCategories) === 4 ? 'Semua kategori' : count($selectedCategories) . ' terpilih') }}</span>
                            <button type="button" @click="catFilterOpen = false"
                                class="px-2.5 py-1 rounded-lg bg-slate-900 text-white font-semibold hover:bg-slate-800 transition cursor-pointer">
                                Tutup
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Status Operasional Filter Dropdown Popover --}}
                <div class="relative" @click.outside="statusFilterOpen = false">
                    <button type="button" @click="statusFilterOpen = !statusFilterOpen; catFilterOpen = false"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border text-xs font-semibold transition cursor-pointer active:scale-95 shadow-2xs {{ $hasCustomStatus ? 'bg-indigo-50 border-indigo-200 text-indigo-700 font-bold' : 'bg-white border-slate-200/80 text-slate-700 hover:bg-slate-50' }}">
                        <i class="bi bi-activity text-xs {{ $hasCustomStatus ? 'text-indigo-600' : 'text-slate-400' }}"></i>
                        <span>Status Operasional</span>
                        <span class="rounded-full px-1.5 py-0.2 text-[10px] font-bold {{ $hasCustomStatus ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-100 text-slate-600' }}">
                            {{ count($selectedStatuses) }}
                        </span>
                        <i class="bi bi-chevron-down text-[10px] text-slate-400"></i>
                    </button>

                    {{-- Status Popover Menu --}}
                    <div x-show="statusFilterOpen" x-cloak
                        class="absolute left-0 top-full mt-1.5 w-64 rounded-2xl bg-white border border-slate-200 shadow-xl p-3 z-50 normal-case tracking-normal space-y-2.5">
                        <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                            <span class="text-[11px] font-bold text-slate-800">Filter Status Armada</span>
                            <div class="flex items-center gap-2 text-[10px]">
                                <button type="button" wire:click="selectAllStatuses"
                                    class="font-semibold text-indigo-600 hover:text-indigo-800 cursor-pointer">
                                    Semua
                                </button>
                                @if ($hasCustomStatus)
                                    <span class="text-slate-300">•</span>
                                    <button type="button" wire:click="resetStatusFilter"
                                        class="font-semibold text-rose-600 hover:text-rose-800 cursor-pointer">
                                        Reset
                                    </button>
                                @endif
                            </div>
                        </div>

                        <div class="space-y-1">
                            {{-- In Pool --}}
                            <label class="flex items-center gap-2.5 px-2 py-1.5 rounded-xl hover:bg-slate-50 cursor-pointer transition">
                                <input type="checkbox" value="in_pool" wire:model.live="selectedStatuses"
                                    class="rounded-md border-slate-300 text-indigo-600 focus:ring-indigo-500/20 cursor-pointer">
                                <div class="flex items-center gap-2 flex-1 min-w-0">
                                    <span class="h-2 w-2 rounded-full bg-emerald-500 shrink-0"></span>
                                    <span class="text-xs text-slate-700 font-medium truncate">Di Pool</span>
                                    <span class="ml-auto text-[10px] text-slate-400 font-semibold">{{ $metrics['in_pool'] }}</span>
                                </div>
                            </label>

                            {{-- On Trip --}}
                            <label class="flex items-center gap-2.5 px-2 py-1.5 rounded-xl hover:bg-slate-50 cursor-pointer transition">
                                <input type="checkbox" value="on_trip" wire:model.live="selectedStatuses"
                                    class="rounded-md border-slate-300 text-indigo-600 focus:ring-indigo-500/20 cursor-pointer">
                                <div class="flex items-center gap-2 flex-1 min-w-0">
                                    <span class="h-2 w-2 rounded-full bg-amber-500 shrink-0"></span>
                                    <span class="text-xs text-slate-700 font-medium truncate">On-Trip</span>
                                    <span class="ml-auto text-[10px] text-slate-400 font-semibold">{{ $metrics['on_trip'] }}</span>
                                </div>
                            </label>

                            {{-- Maintenance --}}
                            <label class="flex items-center gap-2.5 px-2 py-1.5 rounded-xl hover:bg-slate-50 cursor-pointer transition">
                                <input type="checkbox" value="maintenance" wire:model.live="selectedStatuses"
                                    class="rounded-md border-slate-300 text-indigo-600 focus:ring-indigo-500/20 cursor-pointer">
                                <div class="flex items-center gap-2 flex-1 min-w-0">
                                    <span class="h-2 w-2 rounded-full bg-rose-500 shrink-0"></span>
                                    <span class="text-xs text-slate-700 font-medium truncate">Perawatan</span>
                                    <span class="ml-auto text-[10px] text-slate-400 font-semibold">{{ $metrics['maintenance'] ?? 0 }}</span>
                                </div>
                            </label>

                            {{-- Sold (Visible for managers only) --}}
                            @if ($canManage)
                                <label class="flex items-center gap-2.5 px-2 py-1.5 rounded-xl hover:bg-slate-50 cursor-pointer transition">
                                    <input type="checkbox" value="sold" wire:model.live="selectedStatuses"
                                        class="rounded-md border-slate-300 text-indigo-600 focus:ring-indigo-500/20 cursor-pointer">
                                    <div class="flex items-center gap-2 flex-1 min-w-0">
                                        <span class="h-2 w-2 rounded-full bg-slate-500 shrink-0"></span>
                                        <span class="text-xs text-slate-700 font-medium truncate">Terjual / Purnatugas</span>
                                        <span class="ml-auto text-[10px] text-slate-400 font-semibold">{{ $metrics['sold'] ?? 0 }}</span>
                                    </div>
                                </label>
                            @endif
                        </div>

                        <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
                            <span>{{ ! $hasCustomStatus ? 'Semua status aktif' : (count($selectedStatuses) === ($canManage ? 4 : 3) ? 'Semua status' : count($selectedStatuses) . ' terpilih') }}</span>
                            <button type="button" @click="statusFilterOpen = false"
                                class="px-2.5 py-1 rounded-lg bg-slate-900 text-white font-semibold hover:bg-slate-800 transition cursor-pointer">
                                Tutup
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Quick Clear / Reset if any active filter is applied --}}
                @if ($hasActiveFilters)
                    <button type="button" wire:click="resetFilters"
                        class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold text-rose-600 hover:text-rose-700 hover:bg-rose-50 rounded-xl transition cursor-pointer">
                        <i class="bi bi-arrow-counterclockwise text-xs"></i>
                        <span>Reset</span>
                    </button>
                @endif
            </div>

            {{-- Summary count on the right --}}
            <div class="text-[11px] text-slate-400 font-medium">
                Total: <span class="font-bold text-slate-700">{{ $vehicles->total() }}</span> unit
            </div>
        </div>

        {{-- Scrollable Table Area --}}
        <div class="overflow-x-auto rounded-b-2xl">
            <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                <thead class="bg-slate-50 font-semibold text-slate-600 uppercase tracking-wider text-[11px]">
                    <tr>
                        <th class="px-4 py-3 cursor-pointer select-none hover:bg-slate-100/80 transition group"
                            wire:click="sortBy('plate_number')">
                            <div class="flex items-center gap-1.5">
                                <span>Armada / Plat</span>
                                <i class="bi {{ $sort === 'plate_number' ? ($dir === 'asc' ? 'bi-sort-up text-indigo-600 font-bold' : 'bi-sort-down text-indigo-600 font-bold') : 'bi-arrow-down-up text-slate-300 opacity-0 group-hover:opacity-100' }} text-xs transition"></i>
                            </div>
                        </th>

                        {{-- Kategori Column Header --}}
                        <th class="px-4 py-3">Kategori</th>

                        <th class="px-4 py-3 cursor-pointer select-none hover:bg-slate-100/80 transition group"
                            wire:click="sortBy('driver_name')">
                            <div class="flex items-center gap-1.5">
                                <span>Driver</span>
                                <i class="bi {{ $sort === 'driver_name' ? ($dir === 'asc' ? 'bi-sort-up text-indigo-600 font-bold' : 'bi-sort-down text-indigo-600 font-bold') : 'bi-arrow-down-up text-slate-300 opacity-0 group-hover:opacity-100' }} text-xs transition"></i>
                            </div>
                        </th>

                        @if ($canManage)
                            <th class="px-4 py-3 cursor-pointer select-none hover:bg-slate-100/80 transition group"
                                wire:click="sortBy('odometer')">
                                <div class="flex items-center gap-1.5">
                                    <span>Odometer</span>
                                    <i class="bi {{ $sort === 'odometer' ? ($dir === 'asc' ? 'bi-sort-up text-indigo-600 font-bold' : 'bi-sort-down text-indigo-600 font-bold') : 'bi-arrow-down-up text-slate-300 opacity-0 group-hover:opacity-100' }} text-xs transition"></i>
                                </div>
                            </th>
                        @else
                            <th class="px-4 py-3">Odometer</th>
                        @endif

                        {{-- Status Operasional Column Header --}}
                        @if ($canManage)
                            <th class="px-4 py-3 cursor-pointer select-none hover:bg-slate-100/80 transition group"
                                wire:click="sortBy('status')">
                                <div class="flex items-center gap-1.5">
                                    <span>Status Operasional</span>
                                    <i class="bi {{ $sort === 'status' ? ($dir === 'asc' ? 'bi-sort-up text-indigo-600 font-bold' : 'bi-sort-down text-indigo-600 font-bold') : 'bi-arrow-down-up text-slate-300 opacity-0 group-hover:opacity-100' }} text-xs transition"></i>
                                </div>
                            </th>
                        @else
                            <th class="px-4 py-3">Status Operasional</th>
                        @endif

                        @if ($canManage)
                            <th class="px-4 py-3 cursor-pointer select-none hover:bg-slate-100/80 transition group"
                                wire:click="sortBy('last_service_date')">
                                <div class="flex items-center gap-1.5">
                                    <span>Servis Terakhir</span>
                                    <i class="bi {{ $sort === 'last_service_date' ? ($dir === 'asc' ? 'bi-sort-up text-indigo-600 font-bold' : 'bi-sort-down text-indigo-600 font-bold') : 'bi-arrow-down-up text-slate-300 opacity-0 group-hover:opacity-100' }} text-xs transition"></i>
                                </div>
                            </th>
                        @else
                            <th class="px-4 py-3">Servis Terakhir</th>
                        @endif
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white text-slate-700">
                    @forelse ($vehicles as $v)
                        @php
                            $last = $v->latestService;
                            $isOut = $v->is_out_on_trip;
                        @endphp
                        <tr wire:key="veh-table-{{ $v->id }}"
                            onclick="window.location.href = '{{ route('vehicles.show', $v) }}'"
                            class="hover:bg-slate-50/80 cursor-pointer transition {{ ($v->is_sold || $v->status === \App\Enums\VehicleStatus::RETIRED || $v->status === 'retired') ? 'bg-slate-50/60 opacity-85 hover:opacity-100' : '' }}">
                            <td class="px-4 py-3.5">
                                <div class="flex items-center gap-3">
                                    @if ($v->image_path)
                                        <button type="button" @click.stop="$dispatch('open-lightbox', { src: '{{ asset('storage/' . $v->image_path) }}', title: 'Foto Profil {{ $v->plate_number }}', subtitle: '{{ trim($v->brand . ' ' . $v->model) }}' })"
                                            title="Perbesar foto" class="group/img cursor-pointer shrink-0">
                                            <img src="{{ asset('storage/' . $v->image_path) }}" alt="{{ $v->plate_number }}"
                                                class="h-9 w-9 rounded-xl object-cover border border-slate-200/90 shadow-2xs group-hover/img:scale-105 transition">
                                        </button>
                                    @else
                                        <span class="h-9 w-9 rounded-xl flex items-center justify-center text-base shrink-0 {{ $v->category === 'commercial_truck' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600' }}">
                                            @if ($v->category === 'commercial_truck')
                                                <i class="bi bi-truck"></i>
                                            @else
                                                <i class="bi bi-car-front"></i>
                                            @endif
                                        </span>
                                    @endif
                                    <div>
                                        <div class="font-mono font-bold text-slate-900 group-hover:text-indigo-600">{{ $v->plate_number }}</div>
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
                            <td class="px-4 py-3.5 whitespace-nowrap font-semibold text-slate-800">
                                {{ number_format($v->odometer) }} km
                            </td>
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                @if ($v->is_sold)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2.5 py-0.5 text-[10px] font-bold text-rose-700 ring-1 ring-inset ring-rose-200" title="{{ $v->sold_at ? 'Terjual: ' . $v->sold_at->format('d/m/Y') : 'Unit Terjual' }}">
                                        <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                                        <span>Terjual{{ $v->sold_at ? ' ' . $v->sold_at->format('d/m/y') : '' }}</span>
                                    </span>
                                @elseif ($v->status === \App\Enums\VehicleStatus::RETIRED || $v->status === 'retired')
                                    <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-0.5 text-[10px] font-bold text-slate-700 ring-1 ring-inset ring-slate-200">
                                        <span class="h-1.5 w-1.5 rounded-full bg-slate-500"></span>
                                        <span>Purnatugas</span>
                                    </span>
                                @elseif ($v->status === \App\Enums\VehicleStatus::MAINTENANCE || $v->status === 'maintenance')
                                    <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2.5 py-0.5 text-[10px] font-bold text-rose-700 ring-1 ring-inset ring-rose-200">
                                        <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                                        <span>Perawatan</span>
                                    </span>
                                @elseif ($isOut)
                                    <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-0.5 text-[10px] font-bold text-amber-700 ring-1 ring-inset ring-amber-200">
                                        On-Trip
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-[10px] font-bold text-emerald-700 ring-1 ring-inset ring-emerald-200">
                                        Di Pool
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
                            <td class="px-4 py-3.5 text-right whitespace-nowrap" onclick="event.stopPropagation()">
                                <div class="inline-flex items-center gap-1.5">
                                    @if (!$v->is_sold && $v->category === 'passenger')
                                        @if ($isOut)
                                            <a href="{{ route('vehicles.inspect', ['vehicle' => $v, 'type' => 'check_in']) }}"
                                                class="rounded-xl bg-amber-500 px-3 py-1.5 text-xs font-bold text-white hover:bg-amber-600 shadow-2xs transition active:scale-[0.98]">
                                                Check-in
                                            </a>
                                        @else
                                            <a href="{{ route('vehicles.inspect', ['vehicle' => $v, 'type' => 'check_out']) }}"
                                                class="rounded-xl bg-slate-900 px-3 py-1.5 text-xs font-bold text-white hover:bg-slate-800 shadow-2xs transition active:scale-[0.98]">
                                                P2H
                                            </a>
                                        @endif
                                    @endif
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
