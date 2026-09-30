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
        driver_name: @entangle('driver_name'),
        plate_number: @entangle('plate_number'),
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
            this.plate_number = this.plate_number.toUpperCase().replace(/[^A-Z0-9 ]/g, '').replace(/\s+/g, ' ');
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
    }" class="space-y-5">

        {{-- Section 1: Identitas Armada --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 space-y-4">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500">Identitas Armada</h2>

            <div class="grid gap-4 sm:grid-cols-2">
                {{-- Plat Nomor --}}
                <div class="sm:col-span-2">
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="text-xs font-semibold text-slate-700">
                            Plat Nomor <span class="text-rose-500">*</span>
                        </label>
                        <span x-show="isPlateValid() && getPlateRegion()" x-text="getPlateRegion()"
                            class="text-[11px] font-medium text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200"></span>
                    </div>

                    <div class="relative flex items-center rounded-xl bg-slate-900 border border-slate-700 px-3.5 py-2.5 shadow-inner focus-within:border-indigo-400 focus-within:ring-1 focus-within:ring-indigo-400">
                        <span class="text-[10px] font-mono font-bold text-slate-400 border-r border-slate-700 pr-2.5 mr-2.5 select-none">RI</span>
                        <input type="text"
                            x-model="plate_number"
                            @input="formatPlateInput()"
                            @blur="normalizePlateOnBlur()"
                            placeholder="B 1234 XYZ"
                            class="w-full bg-transparent font-mono text-xl font-bold uppercase tracking-widest text-white placeholder-slate-500 focus:outline-none">
                        <span x-show="isPlateValid()" class="text-emerald-400 text-sm pl-2">
                            <i class="bi bi-check-circle-fill"></i>
                        </span>
                    </div>
                    @error('plate_number')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
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
                                    class="rounded-lg border py-2 text-xs transition text-center">
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
                                        class="absolute -top-1.5 -right-1.5 bg-rose-600 text-white rounded-full h-5 w-5 flex items-center justify-center text-xs hover:bg-rose-700 shadow-xs">
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

        @if ($canManage)
            {{-- Section 2: Kategori & Regulasi --}}
            <div class="rounded-xl border border-slate-200 bg-white p-5 space-y-4">
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500">Kategori &amp; Regulasi</h2>

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
                </div>

                <div class="grid gap-4 sm:grid-cols-2 pt-1">
                    {{-- Bahan Bakar --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Bahan Bakar <span class="text-rose-500">*</span></label>
                        <div class="grid grid-cols-3 gap-1.5">
                            <button type="button" @click="fuel_type = 'petrol'"
                                :class="fuel_type === 'petrol' ? 'border-emerald-500 bg-emerald-50 text-emerald-800 font-semibold ring-1 ring-emerald-400' : 'border-slate-200 text-slate-700 hover:bg-slate-50'"
                                class="rounded-lg border py-2 text-xs transition text-center">
                                Bensin
                            </button>
                            <button type="button" @click="fuel_type = 'diesel'"
                                :class="fuel_type === 'diesel' ? 'border-amber-500 bg-amber-50 text-amber-800 font-semibold ring-1 ring-amber-400' : 'border-slate-200 text-slate-700 hover:bg-slate-50'"
                                class="rounded-lg border py-2 text-xs transition text-center">
                                Solar
                            </button>
                            <button type="button" @click="fuel_type = 'ev'"
                                :class="fuel_type === 'ev' ? 'border-cyan-500 bg-cyan-50 text-cyan-800 font-semibold ring-1 ring-cyan-400' : 'border-slate-200 text-slate-700 hover:bg-slate-50'"
                                class="rounded-lg border py-2 text-xs transition text-center">
                                Listrik (EV)
                            </button>
                        </div>
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

            {{-- Section 3: Spesifikasi Teknis --}}
            <div class="rounded-xl border border-slate-200 bg-white p-5 space-y-4">
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500">Spesifikasi Teknis</h2>

                <div class="grid gap-3 sm:grid-cols-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Merk / Brand</label>
                        <input type="text" x-model.trim="brand" placeholder="Toyota, Hino, Isuzu"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 placeholder-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Model / Tipe</label>
                        <input type="text" x-model.trim="model" placeholder="Dutro 130 HD, Avanza"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 placeholder-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Tahun</label>
                        <input type="number" min="1900" max="{{ now()->year + 1 }}" x-model.number="year" placeholder="{{ now()->year }}"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-3">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor Rangka / VIN</label>
                        <input type="text" x-model.trim="vin" placeholder="Nomor Rangka di STNK / BPKB"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-mono text-slate-800 placeholder-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Odometer</label>
                        <div class="relative flex items-center">
                            <input type="number" min="0" step="1" x-model.number="odometer" placeholder="0"
                                class="w-full rounded-lg border border-slate-300 pl-3 pr-9 py-2 text-sm font-semibold text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                            <span class="absolute right-3 text-xs text-slate-400 font-bold uppercase">KM</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Section 4: Data Terjual (Sold) --}}
            <div x-show="status === 'sold'" x-transition class="rounded-xl border border-rose-200 bg-rose-50/50 p-4 space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-rose-900 uppercase">Pelepasan Armada (Sold)</span>
                    <span class="text-[11px] text-rose-600">Unit akan diarsipkan</span>
                </div>
                <div class="max-w-xs">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Terjual <span class="text-rose-500">*</span></label>
                    <input type="date" x-model="sold_at" max="{{ now()->toDateString() }}"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none">
                    @error('sold_at')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        @endif

        {{-- Bottom Actions --}}
        <div class="flex items-center justify-between pt-2">
            <a href="{{ route('vehicles.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800">
                ← Kembali ke Daftar Armada
            </a>
            <button type="submit" wire:loading.attr="disabled"
                class="rounded-lg bg-indigo-600 px-5 py-2 text-xs font-semibold text-white hover:bg-indigo-700 shadow-sm transition">
                <span wire:loading wire:target="save" class="mr-1 inline-block h-3 w-3 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                Simpan Perubahan
            </button>
        </div>
    </form>
</div>
