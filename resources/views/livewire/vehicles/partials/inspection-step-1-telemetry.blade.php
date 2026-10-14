{{-- ========================================================================= --}}
{{-- P2H WIZARD: STEP 1 - TELEMETRI, DRIVER & BAHAN BAKAR                      --}}
{{-- ========================================================================= --}}
<div class="space-y-5">
    <script>
        if (typeof window.p2hSelectComponent !== 'function') {
            window.p2hSelectComponent = function(config) {
                return {
                    tomSelect: null,
                    init() {
                        if (typeof window.TomSelect === 'undefined') {
                            return;
                        }
                        const selectEl = this.$refs[config.refName];
                        if (!selectEl) return;

                        this.tomSelect = new window.TomSelect(selectEl, {
                            create: true,
                            createOnBlur: true,
                            persist: false,
                            maxItems: 1,
                            placeholder: config.placeholder,
                            allowEmptyOption: true,
                            searchField: ['text', 'value'],
                            render: {
                                option_create: function(data, escape) {
                                    return '<div class="create cursor-pointer py-1.5 px-3 text-xs text-indigo-600 font-semibold bg-indigo-50/60 hover:bg-indigo-100/70 border-t border-slate-100 flex items-center gap-1.5"><i class="bi bi-plus-circle"></i> ' + config.createLabel + ': <strong>' + escape(data.input) + '</strong></div>';
                                },
                                no_results: function(data, escape) {
                                    return '<div class="no-results py-2 px-3 text-xs text-slate-400 italic">' + @js(__('fleet.inspection.press_enter_to_add')) + ' "' + escape(data.input) + '"</div>';
                                }
                            },
                            onChange: (value) => {
                                this.$wire.set(config.wireProperty, value || '');
                            }
                        });

                        if (config.initialVal) {
                            if (!this.tomSelect.options[config.initialVal]) {
                                this.tomSelect.addOption({ value: config.initialVal, text: config.initialVal });
                            }
                            this.tomSelect.setValue(config.initialVal, true);
                        }

                        this.$watch('$wire.' + config.wireProperty, (newVal) => {
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

        if (typeof window.p2hDriverSelect !== 'function') {
            window.p2hDriverSelect = function(initialVal) {
                return window.p2hSelectComponent({
                    refName: 'driverSelect',
                    wireProperty: 'driver_name',
                    initialVal: initialVal,
                    placeholder: @js(__('fleet.inspection.driver_name_placeholder')),
                    createLabel: @js(__('fleet.inspection.add_new_driver'))
                });
            };
        }

        if (typeof window.p2hInspectorSelect !== 'function') {
            window.p2hInspectorSelect = function(initialVal) {
                return window.p2hSelectComponent({
                    refName: 'inspectorSelect',
                    wireProperty: 'created_by',
                    initialVal: initialVal,
                    placeholder: @js(__('fleet.inspection.created_by_placeholder')),
                    createLabel: @js(__('fleet.inspection.add_new_inspector'))
                });
            };
        }
    </script>
    <div class="rounded-3xl border border-slate-200/80 bg-white p-5 sm:p-7 shadow-xs space-y-6">
        <div>
            <h2 class="text-base font-black text-slate-900 flex items-center gap-2">
                <i class="bi bi-speedometer2 text-indigo-600 text-lg"></i>
                {{ __('fleet.inspection.driver_telemetry_title') }}
            </h2>
            <p class="text-xs sm:text-sm text-slate-500 mt-1 font-medium">
                {{ __('fleet.inspection.driver_telemetry_desc') }}
            </p>
        </div>

        {{-- Uniform Linear Top-to-Bottom Stack (No Z-Flow) --}}
        <div class="space-y-5">
            {{-- 1. Driver Name (TomSelect with Custom Creation & Presets) --}}
            <div class="ts-fleet-driver">
                <label class="block text-sm font-bold text-slate-800 mb-2">
                    {{ __('fleet.inspection.driver_name') }} <span class="text-rose-500">*</span>
                    @if ($vehicle->driver_name)
                        <span class="text-xs text-slate-400 font-normal ml-1">({{ __('fleet.inspection.driver_default_hint', ['driver' => $vehicle->driver_name]) }})</span>
                    @endif
                </label>
                <div wire:ignore x-data="window.p2hDriverSelect(@js($driver_name))" class="relative">
                    <i class="bi bi-person absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-lg pointer-events-none z-10"></i>
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
                    <p class="mt-1.5 text-xs text-rose-600 font-bold flex items-center gap-1">
                        <i class="bi bi-exclamation-circle"></i> {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- 2. Created By (Inspector / PIC) (TomSelect with Custom Creation & Presets) --}}
            <div class="ts-fleet-driver ts-fleet-inspector">
                <label class="block text-sm font-bold text-slate-800 mb-2">
                    {{ __('fleet.inspection.created_by') }} <span class="text-rose-500">*</span>
                </label>
                <div wire:ignore x-data="window.p2hInspectorSelect(@js($created_by))" class="relative">
                    <i class="bi bi-person-check absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-lg pointer-events-none z-10"></i>
                    <select x-ref="inspectorSelect" class="w-full">
                        <option value="">{{ __('fleet.inspection.created_by_placeholder') }}</option>
                        @foreach ($inspectors as $insp)
                            <option value="{{ $insp }}" @selected($created_by === $insp)>{{ $insp }}</option>
                        @endforeach
                        @if ($created_by && !in_array($created_by, $inspectors, true))
                            <option value="{{ $created_by }}" selected>{{ $created_by }}</option>
                        @endif
                    </select>
                </div>
                @error('created_by')
                    <p class="mt-1.5 text-xs text-rose-600 font-bold flex items-center gap-1">
                        <i class="bi bi-exclamation-circle"></i> {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- 3. Timestamp Input (Check-out Departure / Check-in Return) --}}
            <div>
                <label class="block text-sm font-bold text-slate-800 mb-2">
                    {{ $type === 'check_in' ? __('fleet.inspection.return_time') : __('fleet.inspection.departure_time') }} <span class="text-rose-500">*</span>
                </label>
                <div class="min-h-[48px] flex items-center rounded-2xl border border-slate-200 bg-slate-50/60 px-4 py-2.5 text-sm focus-within:bg-white focus-within:border-slate-400 focus-within:ring-2 focus-within:ring-slate-100 transition shadow-2xs">
                    <i class="bi bi-clock-history text-slate-400 mr-3 text-lg"></i>
                    <input type="datetime-local" wire:model.defer="checked_at"
                        class="w-full border-0 p-0 text-sm font-semibold text-slate-900 bg-transparent focus:outline-none">
                </div>
                @error('checked_at')
                    <p class="mt-1.5 text-xs text-rose-600 font-bold flex items-center gap-1">
                        <i class="bi bi-exclamation-circle"></i> {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- 4. Odometer Input with Real-Time Thousand Separator (UI Only) --}}
            <div x-data="{
                rawOdo: @entangle('odometer'),
                displayOdo: '',
                formatOdo(v) {
                    if (v === null || v === undefined || v === '') return '';
                    let digits = String(v).replace(/\D/g, '');
                    return digits ? Number(digits).toLocaleString('id-ID') : '';
                },
                updateOdo(el) {
                    let cursor = el.selectionStart;
                    let prevLen = el.value.length;
                    let digits = el.value.replace(/\D/g, '');
                    let formatted = digits ? Number(digits).toLocaleString('id-ID') : '';
                    el.value = formatted;
                    this.displayOdo = formatted;
                    let intVal = digits !== '' ? parseInt(digits, 10) : null;
                    this.rawOdo = intVal;
                    $wire.set('odometer', intVal);
                    let newLen = formatted.length;
                    let newPos = Math.max(0, cursor + (newLen - prevLen));
                    el.setSelectionRange(newPos, newPos);
                },
                init() {
                    this.displayOdo = this.formatOdo(this.rawOdo);
                    this.$watch('rawOdo', (newVal) => {
                        let currentDigits = this.displayOdo.replace(/\D/g, '');
                        if (String(newVal ?? '') !== currentDigits) {
                            this.displayOdo = this.formatOdo(newVal);
                        }
                    });
                }
            }">
                <label class="block text-sm font-bold text-slate-800 mb-2">
                    {{ $type === 'check_in' ? __('fleet.inspection.return_odometer') : __('fleet.inspection.start_odometer') }} <span class="text-rose-500">*</span>
                </label>
                <div class="min-h-[48px] flex items-center rounded-2xl border border-slate-200 bg-slate-50/60 px-4 py-2.5 focus-within:bg-white focus-within:border-slate-400 focus-within:ring-2 focus-within:ring-slate-100 transition shadow-2xs">
                    <i class="bi bi-speedometer text-slate-400 mr-3 text-lg"></i>
                    <input type="text"
                        inputmode="numeric"
                        x-model="displayOdo"
                        @input="updateOdo($event.target)"
                        placeholder="{{ $vehicle->odometer > 0 ? number_format($vehicle->odometer, 0, ',', '.') : __('fleet.inspection.odometer_example', ['val' => '45.000']) }}"
                        class="w-full border-0 p-0 text-base font-mono font-black text-slate-900 bg-transparent focus:outline-none placeholder:text-slate-400">
                    <span class="text-xs font-black text-slate-400 font-mono tracking-wider ml-2">KM</span>
                </div>
                @error('odometer')
                    <p class="mt-1.5 text-xs text-rose-600 font-bold flex items-center gap-1">
                        <i class="bi bi-exclamation-circle"></i> {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- 5. Fuel Level: Range Slider + Non-Digital / Bar Dashboard Assistant --}}
            <div x-data="{
                showBarHelper: {{ in_array(strtolower((string) $vehicle->fuel_type), ['petrol', 'bensin'], true) ? 'true' : 'false' }},
                totalBars: 8,
                barOptions: [6, 8],
                currentBar() {
                    let total = Math.max(1, Number(this.totalBars) || 8);
                    let pct = Number($wire.fuel_percentage) || 0;
                    return Math.min(total, Math.max(0, Math.round((pct / 100) * total)));
                },
                setBar(barIndex) {
                    let total = Math.max(1, Number(this.totalBars) || 8);
                    let pct = Math.round((barIndex / total) * 100);
                    $wire.set('fuel_percentage', pct);
                    if (navigator.vibrate) navigator.vibrate(25);
                }
            }" class="rounded-2xl border border-slate-200/90 bg-slate-50/70 p-4 sm:p-5 space-y-4 shadow-2xs">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <label class="text-sm font-bold text-slate-800 flex items-center gap-2">
                            <i class="bi bi-fuel-pump text-slate-500 text-base"></i>
                            <span>{{ __('fleet.inspection.fuel_level') }}</span>
                        </label>
                        <p class="text-xs text-slate-500 mt-0.5">
                            {{ __('fleet.inspection.fuel_level_desc') }}
                        </p>
                    </div>

                    {{-- Direct Numeric Custom Input --}}
                    <div class="min-h-[44px] flex items-center gap-1 rounded-xl border border-slate-300 bg-white px-3 py-1.5 shadow-2xs focus-within:border-slate-500 focus-within:ring-2 focus-within:ring-slate-100 transition">
                        <input type="number" min="0" max="100" wire:model.live.debounce.300ms="fuel_percentage"
                            class="w-12 text-right font-mono font-black text-slate-900 border-0 p-0 text-sm bg-transparent focus:outline-none"
                            placeholder="100">
                        <span class="text-xs font-black text-slate-400">%</span>
                    </div>
                </div>

                {{-- Interactive Range Slider with Analog Needle Scale --}}
                <div class="space-y-2 pt-1">
                    <input type="range" min="0" max="100" step="1"
                        wire:model.live="fuel_percentage"
                        class="w-full h-3 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-slate-900 transition">
                    <div class="flex justify-between text-xs text-slate-500 font-bold font-mono px-0.5 select-none">
                        <span class="text-rose-600">E (0%)</span>
                        <span>1/4 (25%)</span>
                        <span>1/2 (50%)</span>
                        <span>3/4 (75%)</span>
                        <span class="text-emerald-700">F (100%)</span>
                    </div>
                </div>

                {{-- Non-Digital Dashboard Bar Helper Trigger --}}
                <div class="pt-2 border-t border-slate-200/70 flex flex-wrap items-center justify-between gap-2">
                    <button type="button" @click="showBarHelper = !showBarHelper"
                        class="min-h-[38px] inline-flex items-center gap-1.5 text-xs font-bold text-indigo-700 bg-indigo-50/80 hover:bg-indigo-100 border border-indigo-200/80 rounded-xl px-3.5 py-1.5 transition cursor-pointer">
                        <i class="bi bi-segmented-nav text-sm"></i>
                        <span x-text="showBarHelper ? @js(__('fleet.inspection.close_bar_helper')) : @js(__('fleet.inspection.open_bar_helper'))">{{ in_array(strtolower((string) $vehicle->fuel_type), ['petrol', 'bensin'], true) ? __('fleet.inspection.close_bar_helper') : __('fleet.inspection.open_bar_helper') }}</span>
                    </button>
                    <span class="text-[11px] text-slate-500 font-medium hidden sm:inline">
                        {{ __('fleet.inspection.bar_helper_hint') }}
                    </span>
                </div>

                {{-- Collapsible Non-Digital Bar Calculator (Zero Mental Math) --}}
                <div x-show="showBarHelper" x-cloak x-collapse class="space-y-3 pt-2">
                    <div class="rounded-xl bg-white border border-slate-200 p-3.5 space-y-3">
                        <div class="flex flex-wrap items-center justify-between gap-2.5">
                            <span class="text-xs font-bold text-slate-700">{{ __('fleet.inspection.max_total_bars') }}</span>
                            <div class="flex items-center gap-1.5">
                                <template x-for="opt in barOptions" :key="opt">
                                    <button type="button" @click="totalBars = opt"
                                        :class="totalBars === opt ? 'bg-slate-900 text-white font-black shadow-2xs' : 'bg-slate-100 text-slate-600 font-bold hover:bg-slate-200'"
                                        class="px-2.5 py-1 rounded-xl text-xs transition cursor-pointer"
                                        x-text="opt + ' ' + @js(__('fleet.inspection.bar_unit'))"></button>
                                </template>

                                {{-- Custom Max Bar Input --}}
                                <div class="flex items-center gap-1 rounded-xl border border-slate-300 bg-white px-2 py-1 shadow-2xs focus-within:border-slate-500 focus-within:ring-2 focus-within:ring-slate-100 transition">
                                    <span class="text-[11px] font-bold text-slate-400">{{ __('fleet.inspection.custom_bars') }}</span>
                                    <input type="number" min="2" max="24"
                                        x-model.number="totalBars"
                                        @input="if(totalBars && totalBars < 2) totalBars = 2; if(totalBars > 24) totalBars = 24;"
                                        placeholder="Max"
                                        class="w-10 text-center font-mono font-black text-xs text-slate-900 border-0 p-0 bg-transparent focus:outline-none">
                                    <span class="text-[11px] font-bold text-slate-400">{{ __('fleet.inspection.bar_unit') }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Tactile LCD-style Bar Grid Buttons --}}
                        <div class="space-y-1.5">
                            <p class="text-[11px] text-slate-500 font-medium">
                                {{ __('fleet.inspection.touch_last_lit_bar') }}
                            </p>
                            <div class="grid gap-1.5" :style="`grid-template-columns: repeat(${totalBars || 8}, minmax(0, 1fr))`">
                                <template x-for="i in (totalBars || 8)" :key="i">
                                    <button type="button" @click="setBar(i)"
                                        :class="i <= currentBar() ? (currentBar() <= Math.round((totalBars || 8) * 0.25) ? 'bg-rose-500 text-white border-rose-600' : (currentBar() <= Math.round((totalBars || 8) * 0.5) ? 'bg-amber-500 text-white border-amber-600' : 'bg-emerald-600 text-white border-emerald-700')) : 'bg-slate-100 text-slate-400 border-slate-200 hover:bg-slate-200'"
                                        class="min-h-[44px] rounded-xl border flex flex-col items-center justify-center font-mono font-black text-xs transition shadow-2xs cursor-pointer active:scale-95">
                                        <span x-text="i"></span>
                                    </button>
                                </template>
                            </div>
                            <div class="flex justify-between items-center text-xs font-bold text-slate-600 pt-1">
                                <span x-text="@js(__('fleet.inspection.lit_bars_summary', ['lit' => '__LIT__', 'total' => '__TOTAL__'])).replace('__LIT__', currentBar()).replace('__TOTAL__', totalBars || 8)"></span>
                                <span class="text-indigo-700 font-mono" x-text="`≈ ${Math.round((currentBar() / (totalBars || 8)) * 100)}%`"></span>
                            </div>
                        </div>
                    </div>
                </div>

                @error('fuel_percentage')
                    <p class="text-xs text-rose-600 font-bold flex items-center gap-1">
                        <i class="bi bi-exclamation-circle"></i> {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- 6. Trip Purpose / Destination (With Quick Configurable Presets) --}}
            <div x-data="{
                selectPurpose(val) {
                    $wire.set('trip_purpose', val);
                    if (navigator.vibrate) navigator.vibrate(20);
                }
            }">
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-sm font-bold text-slate-800">
                        {{ __('fleet.inspection.trip_purpose') }} <span class="text-rose-500">*</span>
                    </label>
                    <span class="text-[11px] text-slate-400 font-medium hidden sm:inline">{{ __('fleet.inspection.trip_purpose_hint') }}</span>
                </div>
                <div class="min-h-[48px] flex items-center rounded-2xl border border-slate-200 bg-slate-50/60 px-4 py-2.5 focus-within:bg-white focus-within:border-slate-400 focus-within:ring-2 focus-within:ring-slate-100 transition shadow-2xs">
                    <i class="bi bi-geo-alt text-slate-400 mr-3 text-lg"></i>
                    <input type="text"
                        list="trip-purposes-datalist"
                        wire:model="trip_purpose"
                        placeholder="{{ __('fleet.inspection.trip_purpose_placeholder') }}"
                        class="w-full border-0 p-0 text-sm font-medium text-slate-900 bg-transparent focus:outline-none placeholder:text-slate-400">
                </div>

                {{-- Autocomplete Datalist --}}
                @if (!empty($trip_purposes))
                    <datalist id="trip-purposes-datalist">
                        @foreach ($trip_purposes as $tp)
                            @php
                                $displayPurpose = __("fleet.trip_purposes.{$tp}") !== "fleet.trip_purposes.{$tp}" ? __("fleet.trip_purposes.{$tp}") : $tp;
                            @endphp
                            <option value="{{ $displayPurpose }}"></option>
                        @endforeach
                    </datalist>

                    {{-- 1-Tap Quick Suggestion Chips (Fat-Finger Friendly) --}}
                    <div class="mt-2.5 flex flex-wrap items-center gap-1.5">
                        <span class="text-[11px] font-bold text-slate-400 mr-1 inline-flex items-center gap-1">
                            <i class="bi bi-lightning-charge-fill text-amber-500"></i> {{ __('fleet.inspection.quick_chips') }}
                        </span>
                        @foreach ($trip_purposes as $tp)
                            @php
                                $displayPurpose = __("fleet.trip_purposes.{$tp}") !== "fleet.trip_purposes.{$tp}" ? __("fleet.trip_purposes.{$tp}") : $tp;
                            @endphp
                            <button type="button"
                                @click="selectPurpose(@js($displayPurpose))"
                                :class="$wire.trip_purpose === @js($displayPurpose) ? 'bg-indigo-600 text-white border-indigo-600 shadow-2xs' : 'bg-slate-100 hover:bg-slate-200 text-slate-700 border-slate-200'"
                                class="inline-flex items-center min-h-[34px] px-3 py-1 rounded-xl border text-xs font-bold transition cursor-pointer active:scale-95">
                                {{ $displayPurpose }}
                            </button>
                        @endforeach
                    </div>
                @endif

                @error('trip_purpose')
                    <p class="mt-1.5 text-xs text-rose-600 font-bold flex items-center gap-1">
                        <i class="bi bi-exclamation-circle"></i> {{ $message }}
                    </p>
                @enderror
            </div>
        </div>
    </div>

    {{-- Bottom Navigation Bar for Step 1 (Touch Target 48px Friendly) --}}
    <div class="rounded-2xl border border-slate-200/80 bg-white p-4 sm:p-5 shadow-xs flex items-center justify-between gap-3">
        <a href="{{ route('vehicles.show', $vehicle) }}"
            class="min-h-[48px] inline-flex items-center gap-2 rounded-2xl border border-slate-200 px-5 py-3 text-sm font-bold text-slate-700 hover:bg-slate-50 transition cursor-pointer">
            <i class="bi bi-x-lg text-xs"></i> <span>{{ __('fleet.common.cancel') }}</span>
        </a>

        <button type="button" wire:click="nextStep"
            class="min-h-[48px] inline-flex items-center gap-2 rounded-2xl bg-slate-900 px-7 py-3 text-sm font-black text-white hover:bg-slate-800 transition cursor-pointer shadow-xs active:scale-[0.98]">
            <span>{{ __('fleet.inspection.next_to_checklist') }}</span>
            <i class="bi bi-arrow-right text-base"></i>
        </button>
    </div>
</div>
