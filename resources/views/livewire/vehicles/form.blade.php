<div class="max-w-4xl mx-auto px-4 py-6 space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between gap-4 pb-3 border-b border-slate-200">
        <div>
            <nav class="text-xs text-slate-400 mb-1">
                <a href="{{ route('vehicles.index') }}" class="hover:text-slate-700">{{ __('fleet.common.fleet') }}</a>
                <span class="mx-1">/</span>
                <span class="text-slate-600 font-medium">{{ $vehicle?->exists ? __('fleet.common.edit') : __('fleet.index.add_vehicle') }}</span>
            </nav>
            <h1 class="text-lg font-bold text-slate-900">
                {{ $vehicle?->exists ? __('fleet.form.edit_title') . ': ' . $vehicle->plate_number : __('fleet.form.create_title') }}
            </h1>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('vehicles.index') }}"
                class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition">
                {{ __('fleet.common.cancel') }}
            </a>
            @if ($canManage && $vehicle?->exists)
                <button type="button" wire:click="delete"
                    wire:confirm="Hapus data armada ini?"
                    class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-100 transition">
                    {{ __('fleet.common.delete') }}
                </button>
            @endif
            <button type="button" wire:click="save" wire:loading.attr="disabled"
                class="rounded-lg bg-indigo-600 px-4 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700 shadow-sm transition flex items-center gap-1.5">
                <span wire:loading wire:target="save" class="h-3 w-3 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                <i class="bi bi-check2 text-sm" wire:loading.remove wire:target="save"></i>
                <span>{{ __('fleet.common.save') }}</span>
            </button>
        </div>
    </div>

    {{-- Restricted Access Alert (for Non-managers) --}}
    @if (! $canManage)
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800 flex items-center gap-2.5">
            <i class="bi bi-shield-lock text-base"></i>
            <span>Mode Terbatas: Anda hanya dapat memperbarui Plat Nomor dan Driver.</span>
        </div>
    @endif

    {{-- Form --}}
    <form wire:submit.prevent="save" x-data="{
        currentStep: @entangle('currentStep'),
        driver_name: @entangle('driver_name'),
        plate_number: @entangle('plate_number').live,
        @if ($canManage)
            brand: @entangle('brand'),
            model: @entangle('model'),
            category: @entangle('category'),
            fuel_type: @entangle('fuel_type'),
            requires_kir: @entangle('requires_kir'),
            year: @entangle('year'),
            vin: @entangle('vin'),
            odometer: @entangle('odometer'),
            status: @entangle('status'),
            sold_at: @entangle('sold_at'),
        @else
            status: '{{ $vehicle?->status->value ?? 'active' }}',
            category: '{{ $vehicle?->category ?? 'passenger' }}',
            fuel_type: '{{ $vehicle?->fuel_type ?? 'petrol' }}',
            requires_kir: {{ $vehicle?->requires_kir ? 'true' : 'false' }},
            brand: '{{ addslashes($vehicle?->brand ?? '') }}',
            model: '{{ addslashes($vehicle?->model ?? '') }}',
            year: '{{ $vehicle?->year ?? '' }}',
            vin: '{{ addslashes($vehicle?->vin ?? '') }}',
            odometer: {{ $vehicle?->odometer ?? 0 }},
        @endif
        regionMap: @js(config('fleet.plate_regions', [])),
        plateRegex: new RegExp(@js(config('fleet.plate.regex', '^[A-Z]{1,2}\s[1-9][0-9]{0,3}\s[A-Z]{1,4}$'))),
        isPlateValid() {
            return this.plateRegex.test((this.plate_number || '').trim());
        },
        getPlateRegion() {
            if (!this.plate_number) return '';
            let code = (this.plate_number.trim().split(' ')[0] || '').toUpperCase();
            return this.regionMap[code] || '';
        },
        formatPlateInput() {
            if (!this.plate_number) return;
            let val = this.plate_number;
            let endsWithSpace = val.endsWith(' ');
            let raw = val.toUpperCase().replace(/[^A-Z0-9 ]/g, '').replace(/\s+/g, ' ');
            let clean = raw.replace(/\s/g, '');
            let m = clean.match(/^([A-Z]{1,2})([0-9]{0,4})([A-Z]{0,4})/);
            if (!m || !m[1]) {
                this.plate_number = raw;
                return;
            }
            let res = m[1];
            if (m[2]) res += ' ' + m[2];
            if (m[3]) res += ' ' + m[3];
            if (endsWithSpace && !res.endsWith(' ') && (!m[3] || m[3].length < 4)) {
                res += ' ';
            }
            this.plate_number = res;
        },
        normalizePlateOnBlur() {
            if (!this.plate_number) return;
            let clean = this.plate_number.toUpperCase().replace(/[^A-Z0-9]/g, '');
            let match = clean.match(/^([A-Z]{1,2})([1-9][0-9]{0,3})([A-Z]{1,4})$/);
            if (match) {
                this.plate_number = match[1] + ' ' + match[2] + ' ' + match[3];
            } else {
                this.plate_number = this.plate_number.trim();
            }
        },
        selectCategory(cat) {
            @if ($canManage)
                this.category = cat;
                if (cat === 'commercial_truck') {
                    this.requires_kir = true;
                }
            @endif
        }
    }" class="space-y-6">

        {{-- 3-Step Wizard Navigation Stepper (Shown for Managers) --}}
        @if ($canManage)
            <div class="rounded-2xl border border-slate-200/80 bg-white p-2.5 sm:p-3 shadow-xs">
                <div class="grid grid-cols-3 gap-2 sm:gap-3">
                    {{-- Step 1 Button --}}
                    <button type="button" wire:click="goToStep(1)"
                        class="flex items-center gap-2 sm:gap-2.5 p-2 sm:p-2.5 rounded-xl text-left transition cursor-pointer"
                        :class="currentStep === 1 ? 'bg-slate-900 text-white shadow-xs' : (currentStep > 1 ? 'bg-slate-100 text-slate-700 hover:bg-slate-200/80' : 'bg-slate-50 text-slate-400')">
                        <span class="h-6 w-6 rounded-lg flex items-center justify-center text-xs font-bold shrink-0"
                            :class="currentStep === 1 ? 'bg-white text-slate-900' : (currentStep > 1 ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-600')">
                            <template x-if="currentStep > 1">
                                <i class="bi bi-check-lg"></i>
                            </template>
                            <template x-if="currentStep <= 1">
                                <span>1</span>
                            </template>
                        </span>
                        <div class="min-w-0">
                            <span class="block text-xs font-bold truncate">1. Identitas &amp; Status</span>
                            <span class="hidden sm:block text-[10px] truncate"
                                :class="currentStep === 1 ? 'text-slate-300' : 'text-slate-400'">Plat, Driver &amp; Foto</span>
                        </div>
                    </button>

                    {{-- Step 2 Button --}}
                    <button type="button" wire:click="goToStep(2)"
                        class="flex items-center gap-2 sm:gap-2.5 p-2 sm:p-2.5 rounded-xl text-left transition cursor-pointer"
                        :class="currentStep === 2 ? 'bg-slate-900 text-white shadow-xs' : (currentStep > 2 ? 'bg-slate-100 text-slate-700 hover:bg-slate-200/80' : 'bg-slate-50 text-slate-400')">
                        <span class="h-6 w-6 rounded-lg flex items-center justify-center text-xs font-bold shrink-0"
                            :class="currentStep === 2 ? 'bg-white text-slate-900' : (currentStep > 2 ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-600')">
                            <template x-if="currentStep > 2">
                                <i class="bi bi-check-lg"></i>
                            </template>
                            <template x-if="currentStep <= 2">
                                <span>2</span>
                            </template>
                        </span>
                        <div class="min-w-0">
                            <span class="block text-xs font-bold truncate">2. Kategori &amp; Regulasi</span>
                            <span class="hidden sm:block text-[10px] truncate"
                                :class="currentStep === 2 ? 'text-slate-300' : 'text-slate-400'">Tipe Armada &amp; KIR</span>
                        </div>
                    </button>

                    {{-- Step 3 Button --}}
                    <button type="button" wire:click="goToStep(3)"
                        class="flex items-center gap-2 sm:gap-2.5 p-2 sm:p-2.5 rounded-xl text-left transition cursor-pointer"
                        :class="currentStep === 3 ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-50 text-slate-400'">
                        <span class="h-6 w-6 rounded-lg flex items-center justify-center text-xs font-bold shrink-0"
                            :class="currentStep === 3 ? 'bg-white text-slate-900' : 'bg-slate-200 text-slate-600'">
                            3
                        </span>
                        <div class="min-w-0">
                            <span class="block text-xs font-bold truncate">3. Spesifikasi Teknis</span>
                            <span class="hidden sm:block text-[10px] truncate"
                                :class="currentStep === 3 ? 'text-slate-300' : 'text-slate-400'">Merk, VIN &amp; Ringkasan</span>
                        </div>
                    </button>
                </div>
            </div>
        @endif

        {{-- ========================================================================= --}}
        {{-- STEP 1: IDENTITAS & STATUS ARMADA                                         --}}
        {{-- ========================================================================= --}}
        <div x-show="currentStep === 1" x-cloak class="space-y-5">
            <div class="rounded-xl border border-slate-200 bg-white p-5 space-y-4 shadow-xs">
                <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                    <div>
                        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-600">Langkah 1: Identitas &amp; Status Armada</h2>
                        <p class="text-[11px] text-slate-400">Tentukan plat nomor registrasi, driver penanggung jawab, serta status operasional.</p>
                    </div>
                    @if ($canManage)
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-600">Langkah 1 dari 3</span>
                    @endif
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    {{-- Plat Nomor --}}
                    <div class="sm:col-span-2">
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="text-xs font-semibold text-slate-700">
                                Plat Nomor <span class="text-rose-500">*</span>
                            </label>
                            <div class="flex items-center gap-1.5">
                                <span x-show="getPlateRegion()" x-text="getPlateRegion()"
                                    class="text-[11px] font-medium text-slate-600 bg-slate-100 px-2 py-0.5 rounded border border-slate-200"></span>
                                @if (! $errors->has('plate_number'))
                                    <span x-show="isPlateValid()"
                                        class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                                        <i class="bi bi-check-circle-fill text-emerald-500 text-[10px]"></i>
                                        Format Sesuai
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-rose-700 bg-rose-50 px-2 py-0.5 rounded border border-rose-200">
                                        <i class="bi bi-exclamation-triangle-fill text-rose-500 text-[10px]"></i>
                                        Perlu Perhatian
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="relative flex items-center rounded-xl bg-slate-900 border px-3.5 py-2.5 shadow-inner transition {{ $errors->has('plate_number') ? 'border-rose-500 ring-2 ring-rose-500/20' : '' }}"
                            :class="! {{ $errors->has('plate_number') ? 'true' : 'false' }} && isPlateValid() ? 'border-emerald-500/80 ring-1 ring-emerald-500/30' : 'border-slate-700 focus-within:border-indigo-400 focus-within:ring-1 focus-within:ring-indigo-400'">
                            <span class="text-[10px] font-mono font-bold text-slate-400 border-r border-slate-700 pr-2.5 mr-2.5 select-none">RI</span>
                            <input type="text"
                                x-model="plate_number"
                                @input="formatPlateInput()"
                                @blur="normalizePlateOnBlur(); $wire.validateOnly('plate_number')"
                                placeholder="B 1234 XYZ"
                                maxlength="16"
                                autocomplete="off"
                                spellcheck="false"
                                class="w-full bg-transparent font-mono text-xl font-bold uppercase tracking-widest text-white placeholder-slate-500 focus:outline-none">

                            @if ($errors->has('plate_number'))
                                <span class="text-rose-400 text-sm pl-2 shrink-0" title="Plat nomor bermasalah atau duplikat">
                                    <i class="bi bi-exclamation-circle-fill"></i>
                                </span>
                            @else
                                <span x-show="isPlateValid()" class="text-emerald-400 text-sm pl-2 shrink-0" title="Format valid & tersedia">
                                    <i class="bi bi-check-circle-fill"></i>
                                </span>
                            @endif
                        </div>
                        @error('plate_number')
                            <div class="mt-1.5 flex items-center gap-1.5 text-xs text-rose-600 font-semibold">
                                <i class="bi bi-exclamation-circle-fill shrink-0"></i>
                                <span>{{ $message }}</span>
                            </div>
                        @enderror
                    </div>

                    {{-- Driver --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Driver Penanggung Jawab</label>
                        <input type="text" x-model="driver_name" placeholder="Nama driver / penanggung jawab"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 placeholder-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        @error('driver_name')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Status --}}
                    @php use App\Enums\VehicleStatus; @endphp
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Status Operasional <span class="text-rose-500">*</span></label>
                        @if ($canManage)
                            <div class="grid grid-cols-4 gap-1.5">
                                @foreach (VehicleStatus::cases() as $case)
                                    @php $v = $case->value; @endphp
                                    <button type="button" @click="status = '{{ $v }}'"
                                        :class="status === '{{ $v }}'
                                            ? '{{ $case->filterActiveClasses() }} font-semibold shadow-xs'
                                            : '{{ $case->filterInactiveClasses() }}'"
                                        class="rounded-lg border py-2 text-xs transition text-center cursor-pointer">
                                        {{ $case->label() }}
                                    </button>
                                @endforeach
                            </div>
                            @error('status')
                                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        @else
                            <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700 flex items-center justify-between">
                                <span class="capitalize">{{ $vehicle?->status->label() ?? 'Active' }}</span>
                                <span class="text-[11px] text-slate-400">Terkunci</span>
                            </div>
                        @endif
                    </div>

                    {{-- Data Terjual (Sold) Alert --}}
                    @if ($canManage)
                        <div x-show="status === 'sold'" x-transition class="sm:col-span-2 rounded-xl border border-rose-200 bg-rose-50/50 p-4 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-rose-900 uppercase">Pelepasan Armada (Sold)</span>
                                <span class="text-[11px] text-rose-600">Unit akan diarsipkan dan dikecualikan dari operasional</span>
                            </div>
                            <div class="max-w-xs">
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Terjual <span class="text-rose-500">*</span></label>
                                <input type="date" x-model="sold_at" max="{{ now()->toDateString() }}"
                                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none">
                                @error('sold_at')
                                    <p class="mt-1 text-xs text-rose-600 font-semibold">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    @endif

                    {{-- Foto Profil Armada --}}
                    @if ($canManage)
                        <div class="sm:col-span-2 pt-3 border-t border-slate-100">
                            <label class="block text-xs font-semibold text-slate-700 mb-2">Foto Profil Kendaraan (Identifikasi Visual)</label>
                            <div class="flex items-center gap-4">
                                <div class="relative shrink-0">
                                    @if ($photo)
                                        <img src="{{ $photo->temporaryUrl() }}" class="h-20 w-20 rounded-2xl object-cover border border-slate-200 shadow-xs">
                                    @elseif ($current_image_path)
                                        <img src="{{ asset('storage/' . $current_image_path) }}" class="h-20 w-20 rounded-2xl object-cover border border-slate-200 shadow-xs">
                                    @else
                                        <div class="h-20 w-20 rounded-2xl bg-slate-100 flex flex-col items-center justify-center text-slate-400 border border-dashed border-slate-300">
                                            <i class="bi bi-camera text-2xl"></i>
                                            <span class="text-[9px] mt-0.5 font-medium">Belum ada</span>
                                        </div>
                                    @endif

                                    @if ($photo || $current_image_path)
                                        <button type="button" wire:click="removeImage" title="Hapus foto"
                                            class="absolute -top-1.5 -right-1.5 bg-rose-600 text-white rounded-full h-5 w-5 flex items-center justify-center text-xs hover:bg-rose-700 shadow-xs cursor-pointer">
                                            ×
                                        </button>
                                    @endif
                                </div>

                                <div class="space-y-1">
                                    <label class="cursor-pointer inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition shadow-2xs">
                                        <i class="bi bi-upload text-indigo-600"></i>
                                        <span>{{ ($photo || $current_image_path) ? 'Ganti Foto' : 'Unggah Foto Armada' }}</span>
                                        <input type="file" wire:model="photo" accept="image/*" class="sr-only">
                                    </label>
                                    <div wire:loading wire:target="photo" class="text-xs text-indigo-600 flex items-center gap-1 font-medium">
                                        <span class="h-3 w-3 animate-spin rounded-full border-2 border-indigo-600 border-t-transparent"></span>
                                        <span>Mengunggah foto...</span>
                                    </div>
                                    <p class="text-[11px] text-slate-400">Format: JPG, PNG, WEBP maks 5MB. Foto tampak depan / 3/4 kendaraan.</p>
                                    @error('photo')
                                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Step 1 Bottom Actions --}}
            <div class="flex items-center justify-between pt-2">
                <a href="{{ route('vehicles.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800">
                    ← Kembali ke Daftar Armada
                </a>

                @if ($canManage)
                    <button type="button" wire:click="nextStep"
                        class="rounded-xl bg-indigo-600 px-5 py-2 text-xs font-semibold text-white hover:bg-indigo-700 shadow-sm transition flex items-center gap-1.5 cursor-pointer">
                        <span>Lanjut: Kategori &amp; Regulasi</span>
                        <i class="bi bi-arrow-right"></i>
                    </button>
                @else
                    <button type="submit" wire:loading.attr="disabled"
                        class="rounded-xl bg-indigo-600 px-5 py-2 text-xs font-semibold text-white hover:bg-indigo-700 shadow-sm transition">
                        <span wire:loading wire:target="save" class="mr-1 inline-block h-3 w-3 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                        Simpan Perubahan
                    </button>
                @endif
            </div>
        </div>

        @if ($canManage)
            {{-- ========================================================================= --}}
            {{-- STEP 2: KATEGORI & REGULASI                                               --}}
            {{-- ========================================================================= --}}
            <div x-show="currentStep === 2" x-cloak class="space-y-5">
                <div class="rounded-xl border border-slate-200 bg-white p-5 space-y-4 shadow-xs">
                    <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                        <div>
                            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-600">Langkah 2: Kategori &amp; Regulasi</h2>
                            <p class="text-[11px] text-slate-400">Pilih jenis armada, tipe bahan bakar, serta kepatuhan uji berkala (KIR).</p>
                        </div>
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-600">Langkah 2 dari 3</span>
                    </div>

                    {{-- Visual Category Cards --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-2">Kategori Armada <span class="text-rose-500">*</span></label>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                            {{-- Mobil Penumpang --}}
                            <div @click="selectCategory('passenger')"
                                :class="category === 'passenger' ? 'border-indigo-600 bg-indigo-50/60 ring-1 ring-indigo-500 text-indigo-950' : 'border-slate-200 hover:border-slate-300 text-slate-700'"
                                class="cursor-pointer rounded-xl border p-3 transition flex items-center gap-3">
                                <div :class="category === 'passenger' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600'"
                                    class="h-9 w-9 shrink-0 rounded-lg flex items-center justify-center">
                                    <i class="bi bi-car-front-fill text-base"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="font-semibold text-xs text-slate-900 truncate">Mobil Penumpang</div>
                                    <div class="text-[11px] text-slate-400 truncate">Avanza, Innova, MPV</div>
                                </div>
                            </div>

                            {{-- Truk / Mobil Gede --}}
                            <div @click="selectCategory('commercial_truck')"
                                :class="category === 'commercial_truck' ? 'border-indigo-600 bg-indigo-50/60 ring-1 ring-indigo-500 text-indigo-950' : 'border-slate-200 hover:border-slate-300 text-slate-700'"
                                class="cursor-pointer rounded-xl border p-3 transition flex items-center gap-3">
                                <div :class="category === 'commercial_truck' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600'"
                                    class="h-9 w-9 shrink-0 rounded-lg flex items-center justify-center">
                                    <i class="bi bi-truck-flatbed text-base"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="font-semibold text-xs text-slate-900 truncate">Truk / Mobil Gede</div>
                                    <div class="text-[10px] text-amber-700 font-semibold truncate">Wajib KIR</div>
                                </div>
                            </div>

                            {{-- Pick-up / Bak --}}
                            <div @click="selectCategory('pickup')"
                                :class="category === 'pickup' ? 'border-indigo-600 bg-indigo-50/60 ring-1 ring-indigo-500 text-indigo-950' : 'border-slate-200 hover:border-slate-300 text-slate-700'"
                                class="cursor-pointer rounded-xl border p-3 transition flex items-center gap-3">
                                <div :class="category === 'pickup' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600'"
                                    class="h-9 w-9 shrink-0 rounded-lg flex items-center justify-center">
                                    <i class="bi bi-truck text-base"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="font-semibold text-xs text-slate-900 truncate">Pick-up / Bak</div>
                                    <div class="text-[11px] text-slate-400 truncate">Grandmax, Carry, L300</div>
                                </div>
                            </div>

                            {{-- Lainnya --}}
                            <div @click="selectCategory('other')"
                                :class="category === 'other' ? 'border-indigo-600 bg-indigo-50/60 ring-1 ring-indigo-500 text-indigo-950' : 'border-slate-200 hover:border-slate-300 text-slate-700'"
                                class="cursor-pointer rounded-xl border p-3 transition flex items-center gap-3">
                                <div :class="category === 'other' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600'"
                                    class="h-9 w-9 shrink-0 rounded-lg flex items-center justify-center">
                                    <i class="bi bi-boxes text-base"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="font-semibold text-xs text-slate-900 truncate">Lainnya</div>
                                    <div class="text-[11px] text-slate-400 truncate">Forklift, Khusus Pool</div>
                                </div>
                            </div>
                        </div>
                        @error('category')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2 pt-1">
                        {{-- Bahan Bakar --}}
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Bahan Bakar <span class="text-rose-500">*</span></label>
                            <div class="grid grid-cols-3 gap-1.5">
                                <button type="button" @click="fuel_type = 'petrol'"
                                    :class="fuel_type === 'petrol' ? 'border-emerald-500 bg-emerald-50 text-emerald-800 font-semibold ring-1 ring-emerald-400' : 'border-slate-200 text-slate-700 hover:bg-slate-50'"
                                    class="rounded-lg border py-2 text-xs transition text-center cursor-pointer">
                                    Bensin
                                </button>
                                <button type="button" @click="fuel_type = 'diesel'"
                                    :class="fuel_type === 'diesel' ? 'border-amber-500 bg-amber-50 text-amber-800 font-semibold ring-1 ring-amber-400' : 'border-slate-200 text-slate-700 hover:bg-slate-50'"
                                    class="rounded-lg border py-2 text-xs transition text-center cursor-pointer">
                                    Solar
                                </button>
                                <button type="button" @click="fuel_type = 'ev'"
                                    :class="fuel_type === 'ev' ? 'border-cyan-500 bg-cyan-50 text-cyan-800 font-semibold ring-1 ring-cyan-400' : 'border-slate-200 text-slate-700 hover:bg-slate-50'"
                                    class="rounded-lg border py-2 text-xs transition text-center cursor-pointer">
                                    Listrik (EV)
                                </button>
                            </div>
                            @error('fuel_type')
                                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- KIR Toggle --}}
                        <div class="flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2 mt-auto">
                            <div>
                                <span class="text-xs font-semibold text-slate-800">Wajib Uji Berkala KIR</span>
                                <span x-show="category === 'commercial_truck'" class="ml-1 text-[10px] font-bold text-amber-700">(Otomatis untuk Truk)</span>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" x-model="requires_kir" class="sr-only peer">
                                <div class="w-9 h-5 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-indigo-600"></div>
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Step 2 Bottom Actions --}}
                <div class="flex items-center justify-between pt-2">
                    <button type="button" wire:click="previousStep"
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition cursor-pointer">
                        ← Kembali ke Identitas
                    </button>

                    <button type="button" wire:click="nextStep"
                        class="rounded-xl bg-indigo-600 px-5 py-2 text-xs font-semibold text-white hover:bg-indigo-700 shadow-sm transition flex items-center gap-1.5 cursor-pointer">
                        <span>Lanjut: Spesifikasi Teknis</span>
                        <i class="bi bi-arrow-right"></i>
                    </button>
                </div>
            </div>

            {{-- ========================================================================= --}}
            {{-- STEP 3: SPESIFIKASI TEKNIS & RINGKASAN                                    --}}
            {{-- ========================================================================= --}}
            <div x-show="currentStep === 3" x-cloak class="space-y-5">
                <div class="rounded-xl border border-slate-200 bg-white p-5 space-y-4 shadow-xs">
                    <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                        <div>
                            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-600">Langkah 3: Spesifikasi Teknis</h2>
                            <p class="text-[11px] text-slate-400">Lengkapi data teknis kendaraan, merk, tahun perakitan, dan catatan odometer.</p>
                        </div>
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-600">Langkah 3 dari 3</span>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Merk / Brand</label>
                            <input type="text" x-model.trim="brand" placeholder="Toyota, Hino, Isuzu"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 placeholder-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                            @error('brand')
                                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Model / Tipe</label>
                            <input type="text" x-model.trim="model" placeholder="Dutro 130 HD, Avanza"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 placeholder-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                            @error('model')
                                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Tahun</label>
                            <input type="number" min="1900" max="{{ now()->year + 1 }}" x-model.number="year" placeholder="{{ now()->year }}"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                            @error('year')
                                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-3">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor Rangka / VIN</label>
                            <input type="text" x-model.trim="vin" placeholder="Nomor Rangka di STNK / BPKB"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-mono text-slate-800 placeholder-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                            @error('vin')
                                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Odometer</label>
                            <div class="relative flex items-center">
                                <input type="number" min="0" step="1" x-model.number="odometer" placeholder="0"
                                    class="w-full rounded-lg border border-slate-300 pl-3 pr-9 py-2 text-sm font-semibold text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                <span class="absolute right-3 text-xs text-slate-400 font-bold uppercase">KM</span>
                            </div>
                            @error('odometer')
                                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Ringkasan Konfirmasi Data Sebelum Simpan --}}
                <div class="rounded-xl border border-slate-200/90 bg-slate-50/70 p-4 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Ringkasan Konfirmasi</span>
                        <span class="text-[11px] text-slate-400">Pastikan data armada sudah akurat sebelum disimpan</span>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3 bg-white p-3.5 rounded-xl border border-slate-200/80 shadow-2xs">
                        <div class="flex items-center gap-3">
                            <div class="rounded-xl bg-slate-900 px-3.5 py-1.5 text-white font-mono font-bold text-sm tracking-wider shadow-xs">
                                <span x-text="plate_number || 'BELUM DIISI'"></span>
                            </div>
                            <div class="text-xs">
                                <span class="font-bold text-slate-800" x-text="(brand || '') + ' ' + (model || '') || 'Tanpa Merk / Tipe'"></span>
                                <span class="text-slate-400 block text-[11px]" x-text="'Driver: ' + (driver_name || 'Tanpa Driver')"></span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 text-xs">
                            <span class="rounded-lg bg-indigo-50 border border-indigo-200 px-2.5 py-1 text-indigo-700 font-semibold capitalize"
                                x-text="category.replace('_', ' ')"></span>
                            <span class="rounded-lg bg-slate-100 border border-slate-200 px-2.5 py-1 text-slate-700 font-semibold"
                                x-text="Number(odometer || 0).toLocaleString() + ' km'"></span>
                            <span class="rounded-lg bg-slate-100 border border-slate-200 px-2.5 py-1 text-slate-700 font-semibold capitalize"
                                x-text="status"></span>
                        </div>
                    </div>
                </div>

                {{-- Step 3 Bottom Actions --}}
                <div class="flex items-center justify-between pt-2">
                    <button type="button" wire:click="previousStep"
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition cursor-pointer">
                        ← Kembali ke Kategori
                    </button>

                    <button type="submit" wire:loading.attr="disabled"
                        class="rounded-xl bg-indigo-600 px-6 py-2 text-xs font-semibold text-white hover:bg-indigo-700 shadow-sm transition flex items-center gap-1.5 cursor-pointer">
                        <span wire:loading wire:target="save" class="h-3 w-3 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                        <i class="bi bi-check2 text-sm" wire:loading.remove wire:target="save"></i>
                        <span>{{ $vehicle?->exists ? 'Simpan Perubahan' : 'Simpan & Daftarkan Armada' }}</span>
                    </button>
                </div>
            </div>
        @endif

    </form>
</div>
