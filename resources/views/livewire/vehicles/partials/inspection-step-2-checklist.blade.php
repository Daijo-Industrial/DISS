{{-- ========================================================================= --}}
{{-- P2H WIZARD: STEP 2 - CHECKLIST FISIK 4 ZONA (DIRECT & ALWAYS VISIBLE)     --}}
{{-- ========================================================================= --}}
@php
    $pointIcons = [
        'headlights' => 'bi-brightness-high',
        'body' => 'bi-car-front',
        'brake_lights' => 'bi-shield-exclamation',
        'turn_signals' => 'bi-arrow-left-right',
        'tires' => 'bi-disc',
        'battery_fuel' => 'bi-fuel-pump',
        'interior' => 'bi-box-seam',
    ];
@endphp

<div class="space-y-4">
    {{-- Header with Quick-Pass Action & Readiness Score --}}
    <div class="rounded-3xl border border-slate-200/80 bg-white p-4 shadow-xs flex items-center justify-between gap-3">
        <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
            <i class="bi bi-shield-check text-indigo-600"></i>
            Pemeriksaan Fisik
        </h2>

        {{-- Quick Pass Shortcut --}}
        <button type="button" wire:click="passAllChecklist"
            class="inline-flex items-center gap-1.5 rounded-xl border border-emerald-300 bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-800 hover:bg-emerald-100 transition cursor-pointer shadow-2xs shrink-0 active:scale-95">
            <i class="bi bi-check-all text-base"></i>
            <span>Semua OK</span>
        </button>
    </div>

    {{-- 4 Zones Checklist Single Column Feed --}}
    <div class="space-y-3.5">
        @foreach ($this->zones as $zKey => $zone)
            <div class="rounded-3xl border border-slate-200/80 bg-slate-50/50 p-4 shadow-xs space-y-3">
                {{-- Zone Header --}}
                <div class="flex items-center gap-2 border-b border-slate-200/70 pb-2">
                    <span class="h-6 w-6 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold shrink-0">
                        <i class="bi {{ $zone['icon'] }} text-[11px]"></i>
                    </span>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700">
                        {{ $zone['title'] }}
                    </h3>
                </div>

                {{-- Checklist Items within Zone --}}
                <div class="space-y-2.5">
                    @foreach ($zone['keys'] as $itemKey)
                        @php
                            $item = $checklist[$itemKey] ?? [];
                            $isOk = ($item['status'] ?? 'ok') === 'ok';
                            $hasPhotos = !empty($point_photos[$itemKey]);
                            $hasNotes = !empty($item['notes']);
                            $icon = $pointIcons[$itemKey] ?? 'bi-check2-circle';
                        @endphp

                        <div wire:key="checklist-item-{{ $itemKey }}"
                            class="rounded-2xl border transition p-3.5
                            {{ !$isOk ? 'border-rose-300 bg-rose-50/20 ring-1 ring-rose-200' : 'border-slate-200/90 bg-white hover:border-slate-300 shadow-2xs' }}">

                            {{-- Line 1: Item Identification & Status Segmented Switch --}}
                            <div class="flex items-center justify-between gap-2.5">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <span class="h-7 w-7 rounded-lg flex items-center justify-center text-xs shrink-0
                                        {{ $isOk ? 'bg-slate-100 text-slate-600' : 'bg-rose-100 text-rose-700' }}">
                                        <i class="bi {{ $icon }}"></i>
                                    </span>
                                    <h4 class="text-xs font-bold truncate {{ $isOk ? 'text-slate-900' : 'text-rose-950 font-black' }}">
                                        {{ $item['label'] ?? ucfirst($itemKey) }}
                                    </h4>
                                </div>

                                {{-- Status Switch Pill --}}
                                <div class="inline-flex rounded-xl p-0.5 bg-slate-100 border border-slate-200 shrink-0">
                                    <button type="button" wire:click="setChecklistStatus('{{ $itemKey }}', 'ok')"
                                        class="px-2.5 py-1 rounded-lg text-xs font-bold transition cursor-pointer
                                        {{ $isOk ? 'bg-emerald-600 text-white shadow-2xs' : 'text-slate-500 hover:text-slate-800' }}">
                                        OK
                                    </button>
                                    <button type="button" wire:click="setChecklistStatus('{{ $itemKey }}', 'issue')"
                                        class="px-2.5 py-1 rounded-lg text-xs font-bold transition cursor-pointer
                                        {{ !$isOk ? 'bg-rose-600 text-white shadow-2xs' : 'text-slate-500 hover:text-rose-600' }}">
                                        Ada Isu
                                    </button>
                                </div>
                            </div>

                            {{-- Notes & Photo Attachment: Always Visible Directly (No toggle) --}}
                            <div class="mt-2.5 pt-2.5 border-t {{ $isOk ? 'border-slate-100' : 'border-rose-100' }} space-y-2">
                                <input type="text" wire:model.defer="checklist.{{ $itemKey }}.notes"
                                    placeholder="{{ $isOk ? 'Catatan kondisi (opsional)...' : 'Rincian kendala / kerusakan...' }}"
                                    class="w-full rounded-xl border {{ $isOk ? 'border-slate-200 bg-slate-50/70 text-slate-800 placeholder:text-slate-400 focus:bg-white focus:border-slate-400' : 'border-rose-300 bg-rose-50/50 text-rose-950 placeholder:text-rose-400 focus:bg-white focus:border-rose-500' }} px-3 py-1.5 text-xs focus:outline-none transition">

                                {{-- Photo Attachment Controls --}}
                                <div class="flex items-center justify-between gap-2 pt-0.5">
                                    <label class="cursor-pointer inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 px-2 py-1 text-[11px] font-semibold text-slate-600 shadow-2xs transition">
                                        <i class="bi bi-camera text-indigo-600"></i>
                                        <span>+ Foto</span>
                                        <input type="file" wire:model="point_photos.{{ $itemKey }}" multiple accept="image/*" class="sr-only">
                                    </label>

                                    <div wire:loading wire:target="point_photos.{{ $itemKey }}" class="text-[11px] text-indigo-600 font-medium">
                                        <i class="bi bi-arrow-repeat animate-spin mr-1"></i> Unggah...
                                    </div>

                                    @if (!empty($point_photos[$itemKey]))
                                        <span class="text-[10px] font-bold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded-md">
                                            {{ count($point_photos[$itemKey]) }} foto
                                        </span>
                                    @endif
                                </div>

                                {{-- Photo Previews with Lightbox Trigger & Remove Button --}}
                                @if (!empty($point_photos[$itemKey]))
                                    <div class="flex flex-wrap items-center gap-2 pt-1">
                                        @foreach ($point_photos[$itemKey] as $idx => $p)
                                            <div class="relative group">
                                                @if (method_exists($p, 'temporaryUrl'))
                                                    <img src="{{ $p->temporaryUrl() }}"
                                                        @click="$dispatch('open-lightbox', { src: '{{ $p->temporaryUrl() }}', title: 'Foto Temuan: {{ addslashes($item['label'] ?? '') }}' })"
                                                        class="h-11 w-11 object-cover rounded-xl border border-slate-200 shadow-2xs cursor-pointer hover:opacity-90 transition">
                                                @else
                                                    <div class="h-11 w-11 bg-slate-100 rounded-xl flex items-center justify-center text-[9px] text-slate-500 font-semibold border border-slate-200">#{{ $idx+1 }}</div>
                                                @endif
                                                <button type="button" wire:click="removePointPhoto('{{ $itemKey }}', {{ $idx }})" title="Hapus foto"
                                                    class="absolute -top-1.5 -right-1.5 bg-rose-600 text-white rounded-full h-4 w-4 flex items-center justify-center text-[10px] hover:bg-rose-700 shadow-xs cursor-pointer">
                                                    ×
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    {{-- Bottom Navigation Bar for Step 2 --}}
    <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs flex items-center justify-between gap-3">
        <button type="button" wire:click="previousStep"
            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 transition cursor-pointer">
            <i class="bi bi-arrow-left"></i> Kembali
        </button>

        <button type="button" wire:click="nextStep"
            class="inline-flex items-center gap-1.5 rounded-xl bg-slate-900 px-5 py-2.5 text-xs font-bold text-white hover:bg-slate-800 transition cursor-pointer shadow-xs active:scale-[0.98]">
            <span>Lanjut</span>
            <i class="bi bi-arrow-right"></i>
        </button>
    </div>
</div>
