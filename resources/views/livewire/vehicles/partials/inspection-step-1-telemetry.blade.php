{{-- ========================================================================= --}}
{{-- P2H WIZARD: STEP 1 - TELEMETRI, DRIVER & BAHAN BAKAR                      --}}
{{-- ========================================================================= --}}
<div class="space-y-5">
    <script>
        if (typeof window.p2hDriverSelect !== 'function') {
            window.p2hDriverSelect = function(initialVal) {
                return {
                    tomSelect: null,
                    init() {
                        if (typeof window.TomSelect === 'undefined') {
                            return;
                        }
                        const selectEl = this.$refs.driverSelect;

                        this.tomSelect = new window.TomSelect(selectEl, {
                            create: true,
                            createOnBlur: true,
                            persist: false,
                            maxItems: 1,
                            placeholder: @js(__('fleet.inspection.driver_name_placeholder')),
                            allowEmptyOption: true,
                            searchField: ['text', 'value'],
                            render: {
                                option_create: function(data, escape) {
                                    return '<div class="create cursor-pointer py-1.5 px-3 text-xs text-indigo-600 font-semibold bg-indigo-50/60 hover:bg-indigo-100/70 border-t border-slate-100 flex items-center gap-1.5"><i class="bi bi-plus-circle"></i> Tambahkan supir baru: <strong>' + escape(data.input) + '</strong></div>';
                                },
                                no_results: function(data, escape) {
                                    return '<div class="no-results py-2 px-3 text-xs text-slate-400 italic">Tekan Enter untuk menambahkan "' + escape(data.input) + '"</div>';
                                }
                            },
                            onChange: (value) => {
                                this.$wire.set('driver_name', value || '');
                            }
                        });

                        if (initialVal) {
                            if (!this.tomSelect.options[initialVal]) {
                                this.tomSelect.addOption({ value: initialVal, text: initialVal });
                            }
                            this.tomSelect.setValue(initialVal, true);
                        }

                        this.$watch('$wire.driver_name', (newVal) => {
                            if (!this.tomSelect) return;
                            const current = this.tomSelect.getValue();
                            if (current !== (newVal || '')) {
                                if (newVal && !this.tomSelect.options[newVal]) {
                                    this.tomSelect.addOption({ value: newVal, text: newVal });
                                }
                                this.tomSelect.setValue(newVal || '', true);
                            }
                        });

                        if (typeof this.$cleanup === 'function') {
                            this.$cleanup(() => {
                                if (this.tomSelect) {
                                    this.tomSelect.destroy();
                                    this.tomSelect = null;
                                }
                            });
                        }
                    }
                };
            };
        }
    </script>
    <div class="rounded-3xl border border-slate-200/80 bg-white p-5 sm:p-6 shadow-xs space-y-5">
        <div>
            <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                <i class="bi bi-speedometer2 text-indigo-600"></i>
                Driver &amp; Telemetri
            </h2>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            {{-- Driver Name (TomSelect with Custom Creation & Presets) --}}
            <div class="ts-fleet-driver">
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                    {{ __('fleet.inspection.driver_name') }} <span class="text-rose-500">*</span>
                    @if ($vehicle->driver_name)
                        <span class="text-[10px] text-slate-400 font-normal ml-1">({{ __('fleet.inspection.driver_default_hint', ['driver' => $vehicle->driver_name]) }})</span>
                    @endif
                </label>
                <div wire:ignore x-data="window.p2hDriverSelect(@js($driver_name))" class="relative">
                    <i class="bi bi-person absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-base pointer-events-none z-10"></i>
                    <select x-ref="driverSelect" class="w-full">
                        <option value="">{{ __('fleet.inspection.driver_name_placeholder') }}</option>
                        @foreach ($drivers as $d)
                            <option value="{{ $d }}" @selected($driver_name === $d)>{{ $d }}</option>
                        @endforeach
                        @if ($driver_name && !in_array($driver_name, $drivers, true))
                            <option value="{{ $driver_name }}" selected>{{ $driver_name }}</option>
                        @endif
                    </select>
                </div>
                @error('driver_name')
                    <p class="mt-1 text-[11px] text-rose-600 font-semibold">{{ $message }}</p>
                @enderror
            </div>

            {{-- Created By (Inspector / PIC) --}}
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                    {{ __('fleet.inspection.created_by') }} <span class="text-rose-500">*</span>
                </label>
                <div class="flex items-center rounded-2xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-xs focus-within:bg-white focus-within:border-slate-400 focus-within:ring-2 focus-within:ring-slate-100 transition">
                    <i class="bi bi-person-check text-slate-400 mr-2.5 text-base"></i>
                    <input type="text" list="inspector-suggestions" wire:model.defer="created_by" placeholder="{{ __('fleet.inspection.created_by_placeholder') }}" autocomplete="off"
                        class="w-full border-0 p-0 text-xs font-medium text-slate-900 bg-transparent focus:outline-none">
                    <datalist id="inspector-suggestions">
                        @foreach ($inspectors as $insp)
                            <option value="{{ $insp }}"></option>
                        @endforeach
                    </datalist>
                </div>
                @error('created_by')
                    <p class="mt-1 text-[11px] text-rose-600 font-semibold">{{ $message }}</p>
                @enderror
            </div>

            {{-- Odometer Input --}}
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                    {{ $type === 'check_in' ? __('fleet.inspection.return_odometer') : __('fleet.inspection.start_odometer') }} <span class="text-rose-500">*</span>
                </label>
                <div class="flex items-center rounded-2xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-xs focus-within:bg-white focus-within:border-slate-400 focus-within:ring-2 focus-within:ring-slate-100 transition">
                    <i class="bi bi-speedometer text-slate-400 mr-2.5 text-base"></i>
                    <input type="number" wire:model.live.debounce.300ms="odometer" placeholder="{{ $vehicle->odometer }}"
                        class="w-full border-0 p-0 text-xs font-mono font-black text-slate-900 bg-transparent focus:outline-none">
                    <span class="text-xs font-bold text-slate-400 font-mono">KM</span>
                </div>
                @error('odometer')
                    <p class="mt-1 text-[11px] text-rose-600 font-semibold">{{ $message }}</p>
                @enderror
            </div>

            {{-- Timestamp Input (Check-out Departure / Check-in Return) --}}
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                    {{ $type === 'check_in' ? __('fleet.inspection.return_time') : __('fleet.inspection.departure_time') }} <span class="text-rose-500">*</span>
                </label>
                <div class="flex items-center rounded-2xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-xs focus-within:bg-white focus-within:border-slate-400 focus-within:ring-2 focus-within:ring-slate-100 transition">
                    <i class="bi bi-clock-history text-slate-400 mr-2.5 text-base"></i>
                    <input type="datetime-local" wire:model.defer="checked_at"
                        class="w-full border-0 p-0 text-xs font-medium text-slate-900 bg-transparent focus:outline-none">
                </div>
                @error('checked_at')
                    <p class="mt-1 text-[11px] text-rose-600 font-semibold">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Fuel Percentage Quick Picker & Visual Bar --}}
        {{-- Fuel Percentage: Custom Input, Range Slider & Quick Presets --}}
        <div class="rounded-2xl border border-slate-100 bg-slate-50/60 p-4 space-y-3.5">
            <div class="flex items-center justify-between gap-2">
                <div>
                    <label class="text-xs font-semibold text-slate-700 flex items-center gap-1.5">
                        <i class="bi bi-fuel-pump text-slate-500"></i>
                        {{ __('fleet.inspection.fuel_level') }}
                    </label>
                </div>

                {{-- Direct Numeric Custom Input --}}
                <div class="flex items-center gap-1 rounded-xl border border-slate-300 bg-white px-2.5 py-1 text-xs shadow-2xs focus-within:border-slate-500 focus-within:ring-2 focus-within:ring-slate-100 transition">
                    <input type="number" min="0" max="100" wire:model.live.debounce.300ms="fuel_percentage"
                        class="w-12 text-right font-mono font-black text-slate-900 border-0 p-0 text-xs bg-transparent focus:outline-none"
                        placeholder="100">
                    <span class="text-xs font-bold text-slate-400">%</span>
                </div>
            </div>

            {{-- Interactive Range Slider --}}
            <div class="space-y-1">
                <input type="range" min="0" max="100" step="1"
                    wire:model.live="fuel_percentage"
                    class="w-full h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-slate-900 transition">
                <div class="flex justify-between text-[10px] text-slate-400 font-mono px-0.5 select-none">
                    <span>E (0%)</span>
                    <span>1/4 (25%)</span>
                    <span>1/2 (50%)</span>
                    <span>3/4 (75%)</span>
                    <span>F (100%)</span>
                </div>
            </div>

            @error('fuel_percentage')
                <p class="text-[11px] text-rose-600 font-semibold">{{ $message }}</p>
            @enderror
        </div>

        {{-- Trip Purpose / Destination --}}
        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                {{ __('fleet.inspection.trip_purpose') }} <span class="text-rose-500">*</span>
            </label>
            <div class="flex items-center rounded-2xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-xs focus-within:bg-white focus-within:border-slate-400 focus-within:ring-2 focus-within:ring-slate-100 transition">
                <i class="bi bi-geo-alt text-slate-400 mr-2.5 text-base"></i>
                <input type="text" wire:model.defer="trip_purpose" placeholder="{{ __('fleet.inspection.trip_purpose_placeholder') }}"
                    class="w-full border-0 p-0 text-xs text-slate-900 bg-transparent focus:outline-none">
            </div>
            @error('trip_purpose')
                <p class="mt-1 text-[11px] text-rose-600 font-semibold">{{ $message }}</p>
            @enderror
        </div>
    </div>

    {{-- Bottom Navigation Bar for Step 1 --}}
    <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs flex items-center justify-between gap-3">
        <a href="{{ route('vehicles.show', $vehicle) }}"
            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50 transition cursor-pointer">
            <i class="bi bi-x-lg"></i> {{ __('fleet.common.cancel') }}
        </a>

        <button type="button" wire:click="nextStep"
            class="inline-flex items-center gap-1.5 rounded-xl bg-slate-900 px-5 py-2.5 text-xs font-bold text-white hover:bg-slate-800 transition cursor-pointer shadow-xs active:scale-[0.98]">
            <span>Lanjut</span>
            <i class="bi bi-arrow-right"></i>
        </button>
    </div>
</div>
