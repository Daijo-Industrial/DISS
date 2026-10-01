{{-- ========================================================================= --}}
{{-- P2H WIZARD: STEP 3 - RINGKASAN, PENILAIAN KERUSAKAN & KONFIRMASI          --}}
{{-- ========================================================================= --}}
@php
    $defects = $this->defectiveItems;
    $hasDefect = !empty($defects) || $severity !== 'none';
@endphp

<div class="space-y-5">
    {{-- Verdict Banner --}}
    @if (!$hasDefect)
        <div class="rounded-3xl border border-emerald-200 bg-gradient-to-r from-emerald-50 to-teal-50/50 p-5 sm:p-6 shadow-xs">
            <div class="flex items-start gap-4">
                <span class="h-12 w-12 rounded-2xl bg-emerald-500 text-white flex items-center justify-center text-2xl shadow-xs shrink-0">
                    <i class="bi bi-shield-fill-check"></i>
                </span>
                <div>
                    <span class="rounded-lg bg-emerald-100 text-emerald-800 text-[10px] font-extrabold uppercase tracking-wider px-2 py-0.5">
                        Status Kelayakan: Aman
                    </span>
                    <h2 class="text-base sm:text-lg font-black text-emerald-950 mt-1">
                        Armada Siap Beroperasi (Fit to Drive)
                    </h2>
                    <p class="text-xs text-emerald-800/80 mt-0.5">
                        Semua 7 titik checklist fisik dinyatakan dalam kondisi aman dan siap beroperasi. Silakan tinjau ringkasan di bawah sebelum menyelesaikan laporan.
                    </p>
                </div>
            </div>
        </div>
    @else
        <div class="rounded-3xl border border-amber-300 bg-gradient-to-r from-amber-50 to-orange-50/60 p-5 sm:p-6 shadow-xs">
            <div class="flex items-start gap-4">
                <span class="h-12 w-12 rounded-2xl bg-amber-500 text-white flex items-center justify-center text-2xl shadow-xs shrink-0">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                </span>
                <div>
                    <span class="rounded-lg bg-amber-100 text-amber-900 text-[10px] font-extrabold uppercase tracking-wider px-2 py-0.5">
                        Perhatian: Temuan Kerusakan Terdeteksi
                    </span>
                    <h2 class="text-base sm:text-lg font-black text-amber-950 mt-1">
                        Evaluasi Tingkat Keparahan Kendala
                    </h2>
                    <p class="text-xs text-amber-800 mt-0.5">
                        Terdapat {{ count($defects) }} titik checklist yang dilaporkan bermasalah. Tentukan apakah armada masih aman jalan atau wajib masuk bengkel (grounded).
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- Defect Severity Selection & Additional Evidence (Rendered if any issue exists) --}}
    @if ($hasDefect)
        <div class="rounded-3xl border border-amber-200 bg-white p-5 sm:p-6 shadow-xs space-y-4">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center gap-1.5">
                <i class="bi bi-shield-slash text-amber-600"></i>
                Pilih Klasifikasi Tingkat Bahaya (Severity)
            </h3>

            {{-- 2 Severity Radio Cards --}}
            <div class="grid gap-3 sm:grid-cols-2">
                {{-- Minor --}}
                <label class="flex cursor-pointer rounded-2xl border p-4 transition
                    {{ $severity === 'minor' ? 'border-amber-500 bg-amber-50/40 ring-2 ring-amber-300 shadow-2xs' : 'border-slate-200 bg-white hover:border-slate-300 opacity-75' }}">
                    <input type="radio" wire:model.live="severity" value="minor" class="sr-only">
                    <div class="flex items-start gap-3">
                        <span class="h-8 w-8 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center shrink-0">
                            <i class="bi bi-info-circle text-base"></i>
                        </span>
                        <div>
                            <h4 class="text-xs font-bold text-slate-900">Minor (Tetap Boleh Jalan)</h4>
                            <p class="text-[11px] text-slate-500 mt-0.5">
                                Kerusakan ringan / estetika (baret halus, sedikit kotor). Kendaraan tetap aman beroperasi mengantar barang/penumpang.
                            </p>
                        </div>
                    </div>
                </label>

                {{-- Critical Grounded --}}
                <label class="flex cursor-pointer rounded-2xl border p-4 transition
                    {{ $severity === 'critical_grounded' ? 'border-rose-500 bg-rose-50/40 ring-2 ring-rose-300 shadow-2xs' : 'border-slate-200 bg-white hover:border-slate-300 opacity-75' }}">
                    <input type="radio" wire:model.live="severity" value="critical_grounded" class="sr-only">
                    <div class="flex items-start gap-3">
                        <span class="h-8 w-8 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center shrink-0">
                            <i class="bi bi-slash-circle text-base"></i>
                        </span>
                        <div>
                            <h4 class="text-xs font-bold text-rose-950">Critical Grounded (Tidak Boleh Jalan)</h4>
                            <p class="text-[11px] text-rose-700 mt-0.5">
                                Membahayakan keselamatan jiwa atau melanggar hukum. Status armada otomatis dialihkan ke <strong>Maintenance</strong>.
                            </p>
                        </div>
                    </div>
                </label>
            </div>

            {{-- Summary of Flagged Checklist Issues --}}
            @if (!empty($defects))
                <div class="rounded-2xl border border-slate-100 bg-slate-50/70 p-4 space-y-2.5">
                    <span class="text-xs font-bold text-slate-700 block">Rincian Titik yang Dilaporkan Bermasalah:</span>
                    <div class="space-y-2">
                        @foreach ($defects as $dKey => $dItem)
                            <div class="rounded-xl border border-rose-200 bg-white p-3 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
                                <div>
                                    <span class="font-bold text-rose-900">{{ $dItem['label'] ?? ucfirst($dKey) }}:</span>
                                    <span class="text-slate-600 ml-1">{{ $dItem['notes'] ?: 'Tidak ada catatan tertulis' }}</span>
                                </div>

                                {{-- Photos attached to this point --}}
                                @if (!empty($point_photos[$dKey]))
                                    <div class="flex items-center gap-1.5 shrink-0">
                                        @foreach ($point_photos[$dKey] as $p)
                                            @if (method_exists($p, 'temporaryUrl'))
                                                <img src="{{ $p->temporaryUrl() }}"
                                                    @click="$dispatch('open-lightbox', { src: '{{ $p->temporaryUrl() }}', title: 'Temuan: {{ addslashes($dItem['label'] ?? '') }}' })"
                                                    class="h-9 w-9 object-cover rounded-lg border border-slate-200 cursor-pointer shadow-2xs hover:opacity-80 transition">
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Defect Notes & General Defect Photos --}}
            <div class="grid gap-4 sm:grid-cols-2 pt-2">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                        {{ __('fleet.inspection.defect_report') }}
                        <span class="text-slate-400 font-normal">(Rangkuman Utama)</span>
                    </label>
                    <textarea wire:model.defer="defect_notes" rows="3" placeholder="{{ __('fleet.inspection.defect_notes_placeholder') }}"
                        class="w-full rounded-2xl border border-slate-200 bg-white p-3 text-xs text-slate-900 placeholder:text-slate-400 focus:border-slate-400 focus:outline-none transition"></textarea>
                    @error('defect_notes')
                        <p class="mt-1 text-[11px] text-rose-600 font-semibold">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                        {{ __('fleet.inspection.defect_photos') }}
                        <span class="text-slate-400 font-normal">(Foto Bukti Tambahan)</span>
                    </label>
                    <div class="rounded-2xl border-2 border-dashed border-slate-200 bg-white p-4 text-center">
                        <input type="file" wire:model="photos" multiple accept="image/*" class="text-xs text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-slate-100 file:text-slate-700 cursor-pointer">
                        <div wire:loading wire:target="photos" class="text-[11px] text-indigo-600 mt-2 font-medium">
                            <i class="bi bi-arrow-repeat animate-spin mr-1"></i> Mengunggah foto...
                        </div>

                        @if (!empty($photos))
                            <div class="mt-3 flex flex-wrap gap-2 justify-center">
                                @foreach ($photos as $idx => $p)
                                    <div class="relative group">
                                        @if (method_exists($p, 'temporaryUrl'))
                                            <img src="{{ $p->temporaryUrl() }}" @click="$dispatch('open-lightbox', { src: '{{ $p->temporaryUrl() }}', title: 'Foto Bukti Kerusakan Utama' })"
                                                class="h-11 w-11 object-cover rounded-xl border border-slate-200 shadow-2xs cursor-pointer hover:opacity-90 transition">
                                        @endif
                                        <button type="button" wire:click="removePhoto({{ $idx }})" title="Hapus foto"
                                            class="absolute -top-1.5 -right-1.5 bg-rose-600 text-white rounded-full h-4 w-4 flex items-center justify-center text-[10px] hover:bg-rose-700 shadow-2xs cursor-pointer">
                                            ×
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Telemetry Review Strip --}}
    <div class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-xs space-y-3">
        <div class="flex items-center justify-between border-b border-slate-100 pb-2">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center gap-1.5">
                <i class="bi bi-card-checklist text-indigo-600"></i>
                Ringkasan Telemetri Perjalanan
            </h3>
            <button type="button" wire:click="goToStep(1)" class="text-xs text-indigo-600 font-semibold hover:underline cursor-pointer">
                Ubah Data
            </button>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
            <div class="rounded-xl bg-slate-50 p-3">
                <span class="text-[10px] text-slate-400 block font-semibold">Pengemudi / Driver</span>
                <span class="font-bold text-slate-900 mt-0.5 block truncate">{{ $driver_name ?: '-' }}</span>
            </div>

            <div class="rounded-xl bg-slate-50 p-3">
                <span class="text-[10px] text-slate-400 block font-semibold">Odometer Dicatat</span>
                <span class="font-bold text-slate-900 font-mono mt-0.5 block">
                    {{ number_format($odometer) }} km
                    @if ($type === 'check_in' && $parentInspection && $odometer > $parentInspection->odometer)
                        <span class="text-emerald-700 font-sans text-[10px] ml-1">(+{{ number_format($odometer - $parentInspection->odometer) }} km)</span>
                    @endif
                </span>
            </div>

            <div class="rounded-xl bg-slate-50 p-3">
                <span class="text-[10px] text-slate-400 block font-semibold">Level Bahan Bakar</span>
                <span class="font-bold text-slate-900 mt-0.5 block">{{ $fuel_percentage }}%</span>
            </div>

            <div class="rounded-xl bg-slate-50 p-3">
                <span class="text-[10px] text-slate-400 block font-semibold">Checklist Fisik</span>
                <span class="font-bold mt-0.5 block {{ $this->okCount === $this->totalItems ? 'text-emerald-700' : 'text-amber-700' }}">
                    {{ $this->okCount }}/{{ $this->totalItems }} Poin OK
                </span>
            </div>
        </div>

        @if ($trip_purpose)
            <div class="rounded-xl bg-slate-50 p-3 text-xs">
                <span class="text-[10px] text-slate-400 block font-semibold">Keperluan / Catatan Perjalanan</span>
                <span class="text-slate-700 italic mt-0.5 block">"{{ $trip_purpose }}"</span>
            </div>
        @endif
    </div>

    {{-- Sticky Floating Submission Action Bar --}}
    <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <button type="button" wire:click="previousStep"
            class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-50 transition cursor-pointer">
            <i class="bi bi-arrow-left"></i> Kembali ke Checklist
        </button>

        <button type="button" wire:click="save" wire:loading.attr="disabled"
            class="inline-flex items-center justify-center gap-2 rounded-xl px-6 py-2.5 text-xs font-extrabold text-white shadow-xs transition cursor-pointer active:scale-[0.98]
            {{ $type === 'check_in' ? 'bg-amber-600 hover:bg-amber-700' : 'bg-emerald-600 hover:bg-emerald-700' }} disabled:opacity-60">
            <span wire:loading class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
            <i class="bi {{ $type === 'check_in' ? 'bi-check2-circle' : 'bi-send-check' }}" wire:loading.remove></i>
            <span>{{ $type === 'check_in' ? __('fleet.inspection.submit_checkin') : __('fleet.inspection.submit_checkout') }}</span>
        </button>
    </div>
</div>
