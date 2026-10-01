{{-- ========================================================================= --}}
{{-- MODALS & LIGHTBOX OVERLAYS                                                --}}
{{-- ========================================================================= --}}

{{-- MODAL CETAK STIKER QR CODE (Dashboard Physical Sticker) --}}
@if ($showQrModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-md p-4 overflow-y-auto"
        x-data="{ copied: false }">
        <div class="relative w-full max-w-sm rounded-3xl bg-white shadow-2xl border border-slate-200/90 p-5 sm:p-6 space-y-4">
            {{-- Modal Header --}}
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2">
                    <span class="h-7 w-7 rounded-lg bg-slate-100 flex items-center justify-center text-slate-900">
                        <i class="bi bi-qr-code text-sm"></i>
                    </span>
                    <div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Stiker Fisik Armada</h3>
                        <p class="text-[10px] text-slate-400">Verifikasi unit &amp; inspeksi P2H</p>
                    </div>
                </div>
                <button type="button" wire:click="closeQrModal" class="h-7 w-7 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition cursor-pointer">
                    <i class="bi bi-x-lg text-xs"></i>
                </button>
            </div>

            {{-- Printable Sticker Body (Industrial Vinyl Equipment Tag) --}}
            <div id="printable-qr-sticker" class="rounded-2xl border-2 border-slate-950 p-4 sm:p-5 bg-white text-center space-y-2.5 shadow-xs">
                {{-- Header Brand --}}
                <div class="border-b border-slate-100 pb-2">
                    <div class="text-[11px] font-black tracking-widest text-slate-900 uppercase">
                        PT DAIJO INDUSTRIAL
                    </div>
                    <div class="text-[9px] font-bold text-slate-400 tracking-wider uppercase mt-0.5">
                        FLEET MANAGEMENT &bull; P2H SYSTEM
                    </div>
                </div>

                {{-- Plate Chassis with Rivet Accents --}}
                <div class="inline-flex items-center gap-2 rounded-xl bg-slate-950 px-4 py-1.5 text-white shadow-2xs">
                    <span class="h-1.5 w-1.5 rounded-full bg-slate-400 opacity-60"></span>
                    <span class="font-mono text-xl sm:text-2xl font-black tracking-widest text-slate-50">
                        {{ $vehicle->plate_number }}
                    </span>
                    <span class="h-1.5 w-1.5 rounded-full bg-slate-400 opacity-60"></span>
                </div>

                {{-- Vehicle Model --}}
                <div class="text-xs font-bold text-slate-800">
                    {{ trim($vehicle->brand . ' ' . $vehicle->model) }} {{ $vehicle->year ? "({$vehicle->year})" : '' }}
                </div>

                {{-- High-Resolution QR Code Container --}}
                <div class="flex justify-center p-2 bg-white rounded-xl">
                    @if ($qrCodeBase64)
                        <div class="p-1.5 rounded-xl border border-slate-200/80 bg-white">
                            <img src="data:image/png;base64,{{ $qrCodeBase64 }}" alt="QR Code {{ $vehicle->plate_number }}"
                                class="w-44 h-44 sm:w-48 sm:h-48 object-contain">
                        </div>
                    @else
                        <div class="w-44 h-44 sm:w-48 sm:h-48 flex items-center justify-center text-xs text-rose-500 border border-rose-200 rounded-lg">
                            Gagal menghasilkan QR Code
                        </div>
                    @endif
                </div>

                {{-- Scanning Prompt --}}
                <div class="rounded-lg bg-slate-50 border border-slate-100 py-1.5 px-2">
                    <div class="text-[11px] font-bold text-slate-900 flex items-center justify-center gap-1.5">
                        <i class="bi bi-phone text-slate-600"></i>
                        <span>Pindai untuk P2H Check-out &amp; Check-in</span>
                    </div>
                </div>

                {{-- UUID Monospace Footer --}}
                <div class="text-[9px] font-mono text-slate-400 truncate">
                    UUID: {{ $vehicle->id }}
                </div>
            </div>

            {{-- Action Controls (Print, Download PNG, Copy ID) --}}
            <div class="space-y-2 pt-1">
                {{-- Primary Print Button --}}
                <button type="button"
                    @click="printVehicleQrSticker('{{ $qrCodeBase64 }}', '{{ addslashes($vehicle->plate_number) }}', '{{ addslashes(trim($vehicle->brand . ' ' . $vehicle->model)) }}', '{{ $vehicle->year }}', '{{ addslashes($vehicle->id) }}')"
                    class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-xs font-bold text-white shadow-2xs hover:bg-slate-800 transition active:scale-[0.98] cursor-pointer">
                    <i class="bi bi-printer text-sm"></i>
                    <span>Cetak Stiker Fisik</span>
                </button>

                {{-- Secondary Action Buttons --}}
                <div class="grid grid-cols-2 gap-2">
                    @if ($qrCodeBase64)
                        <a href="data:image/png;base64,{{ $qrCodeBase64 }}" download="QR-Armada-{{ str_replace(' ', '-', $vehicle->plate_number) }}.png"
                            class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition shadow-2xs cursor-pointer">
                            <i class="bi bi-download text-slate-500"></i>
                            <span>Unduh PNG</span>
                        </a>
                    @endif

                    <button type="button"
                        @click="navigator.clipboard.writeText('{{ $vehicle->id }}'); copied = true; setTimeout(() => copied = false, 2000)"
                        class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition shadow-2xs cursor-pointer">
                        <i class="bi" :class="copied ? 'bi-check2 text-emerald-600' : 'bi-clipboard text-slate-500'"></i>
                        <span x-text="copied ? 'Tersalin!' : 'Salin UUID'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif

{{-- MODAL TAMBAH / PERPANJANG DOKUMEN --}}
@if ($showDocModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-xs p-4 overflow-y-auto">
        <div class="relative w-full max-w-lg rounded-3xl bg-white shadow-2xl border border-slate-200 p-4 sm:p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-sm font-bold text-slate-900">Input / Perbarui Dokumen Legalitas</h3>
                <button type="button" wire:click="closeDocModal" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <form wire:submit.prevent="saveDocument" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Jenis Dokumen <span class="text-rose-500">*</span></label>
                    <select wire:model.defer="doc_type"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-800 focus:border-slate-500 focus:outline-none">
                        <option value="kir">Uji Berkala (KIR) — Khusus Mobil Gede / Niaga</option>
                        <option value="stnk_annual">Pajak STNK 1 Tahunan</option>
                        <option value="stnk_five_year">STNK 5 Tahunan &amp; Ganti Plat Kaleng</option>
                        <option value="insurance">Asuransi Kendaraan</option>
                        <option value="other">Dokumen Lainnya</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor Dokumen / Surat</label>
                    <input type="text" wire:model.defer="doc_number" placeholder="Contoh: KIR-JKT-123456"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-800 focus:border-slate-500 focus:outline-none">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Jatuh Tempo <span class="text-rose-500">*</span></label>
                        <input type="date" wire:model.defer="expired_date"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-800 focus:border-slate-500 focus:outline-none">
                        @error('expired_date')
                            <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Diperbarui</label>
                        <input type="date" wire:model.defer="last_renewed_date"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-800 focus:border-slate-500 focus:outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Unggah Scan / Foto Fisik</label>
                    <input type="file" wire:model="attachment" accept="image/*,application/pdf"
                        class="block w-full text-xs text-slate-500 file:mr-3 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-slate-100 file:text-slate-700">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan Tambahan</label>
                    <textarea wire:model.defer="notes" rows="2" placeholder="Catatan instansi / keterangan perpanjangan..."
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-800 focus:border-slate-500 focus:outline-none"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" wire:click="closeDocModal"
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" wire:loading.attr="disabled"
                        class="rounded-xl bg-slate-900 px-5 py-2 text-xs font-bold text-white hover:bg-slate-800 disabled:opacity-60 cursor-pointer">
                        Simpan Dokumen
                    </button>
                </div>
            </form>
        </div>
    </div>
@endif

{{-- UNIVERSAL LIGHTBOX MODULE --}}
<x-universal-lightbox :vehicle="$vehicle" />

{{-- MODAL UBAH / UNGGAH FOTO PROFIL ARMADA --}}
@if ($showPhotoModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-xs p-4 overflow-y-auto">
        <div class="relative w-full max-w-md rounded-3xl bg-white shadow-2xl border border-slate-200 p-4 sm:p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2">
                    <i class="bi bi-camera text-slate-900 text-lg"></i>
                    <h3 class="text-sm font-bold text-slate-900">{{ __('fleet.show.photo_modal_title') }}</h3>
                </div>
                <button type="button" wire:click="closePhotoModal" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <form wire:submit.prevent="saveVehiclePhoto" class="space-y-4">
                {{-- Current or New Preview --}}
                <div class="flex flex-col items-center justify-center text-center p-3 rounded-2xl bg-slate-50 border border-slate-200">
                    @if ($new_photo)
                        <img src="{{ $new_photo->temporaryUrl() }}" class="h-32 w-32 aspect-square object-cover rounded-2xl border border-slate-200 shadow-2xs mb-2">
                        <span class="text-xs font-semibold text-slate-800">Preview Baru</span>
                    @elseif ($vehicle->image_path)
                        <img src="{{ asset('storage/' . $vehicle->image_path) }}" class="h-32 w-32 aspect-square object-cover rounded-2xl border border-slate-200 shadow-2xs mb-2">
                        <span class="text-xs text-slate-500 font-medium">{{ __('fleet.show.photo_modal_title') }}</span>
                    @else
                        <div class="h-24 w-24 aspect-square rounded-2xl bg-slate-100 flex flex-col items-center justify-center text-slate-400 border border-dashed border-slate-300 mb-2">
                            <i class="bi bi-camera text-3xl"></i>
                        </div>
                        <span class="text-xs text-slate-400">—</span>
                    @endif
                </div>

                {{-- Upload input --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">{{ __('fleet.form.profile_photo') }}</label>
                    <input type="file" wire:model="new_photo" accept="image/*"
                        class="block w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200">
                    <div wire:loading wire:target="new_photo" class="text-xs text-slate-600 font-medium mt-1">
                        <i class="bi bi-arrow-repeat animate-spin mr-1"></i> {{ __('fleet.common.loading') }}
                    </div>
                    @error('new_photo')
                        <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-between gap-2 pt-3 border-t border-slate-100">
                    <div>
                        @if ($vehicle->image_path)
                            <button type="button" wire:click="deleteVehiclePhoto" wire:confirm="{{ __('fleet.show.photo_delete_confirm') }}"
                                class="text-xs font-bold text-rose-600 hover:text-rose-800 cursor-pointer">
                                <i class="bi bi-trash mr-1"></i> {{ __('fleet.show.photo_delete') }}
                            </button>
                        @endif
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="button" wire:click="closePhotoModal"
                            class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 cursor-pointer">
                            {{ __('fleet.common.cancel') }}
                        </button>
                        <button type="submit" wire:loading.attr="disabled"
                            class="rounded-xl bg-slate-900 px-5 py-2 text-xs font-bold text-white hover:bg-slate-800 disabled:opacity-60 cursor-pointer">
                            {{ __('fleet.show.photo_save') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endif

{{-- PRINT STYLESHEET (Fallback for Native Browser Print) --}}
<style>
    @media print {
        @page {
            size: 80mm 100mm;
            margin: 0;
        }
        html, body {
            height: 100% !important;
            max-height: 100% !important;
            overflow: hidden !important;
            margin: 0 !important;
            padding: 0 !important;
            background: #ffffff !important;
        }
        body * {
            visibility: hidden !important;
        }
        #printable-qr-sticker, #printable-qr-sticker * {
            visibility: visible !important;
        }
        #printable-qr-sticker {
            position: absolute !important;
            left: 50% !important;
            top: 50% !important;
            transform: translate(-50%, -50%) !important;
            width: 72mm !important;
            border: 2px solid #0f172a !important;
            border-radius: 12px !important;
            padding: 14px 12px !important;
            box-shadow: none !important;
            background: #ffffff !important;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }
    }
</style>

<script>
    function printVehicleQrSticker(qrBase64, plateNumber, model, year, id) {
        if (!qrBase64) {
            alert('Data QR Code belum tersedia.');
            return;
        }

        let frame = document.getElementById('qr-print-frame');
        if (!frame) {
            frame = document.createElement('iframe');
            frame.id = 'qr-print-frame';
            frame.style.position = 'fixed';
            frame.style.right = '0';
            frame.style.bottom = '0';
            frame.style.width = '0';
            frame.style.height = '0';
            frame.style.border = '0';
            document.body.appendChild(frame);
        }

        const modelDisplay = (model ? model : '') + (year ? ' (' + year + ')' : '');
        const frameDoc = frame.contentWindow.document;
        frameDoc.open();
        frameDoc.write([
            '\x3C!DOCTYPE html\x3E',
            '\x3Chtml\x3E',
            '\x3Chead\x3E',
            '  \x3Cmeta charset="utf-8"\x3E',
            '  \x3Ctitle\x3EStiker QR - ' + plateNumber + '\x3C/title\x3E',
            '  \x3Cstyle\x3E',
            '    @page { size: 80mm 100mm; margin: 0; }',
            '    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }',
            '    html, body { width: 80mm; height: 100mm; max-height: 100mm; overflow: hidden; background: #ffffff; font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; -webkit-print-color-adjust: exact; print-color-adjust: exact; }',
            '    .sticker-container { width: 80mm; height: 100mm; display: flex; align-items: center; justify-content: center; padding: 3mm; page-break-inside: avoid; break-inside: avoid; }',
            '    .sticker-box { width: 72mm; border: 2px solid #020617; border-radius: 12px; padding: 8px 6px; text-align: center; background: #ffffff; page-break-inside: avoid; break-inside: avoid; }',
            '    .brand-title { font-size: 11px; font-weight: 900; letter-spacing: 0.1em; color: #020617; text-transform: uppercase; }',
            '    .brand-sub { font-size: 8px; font-weight: 700; color: #64748b; letter-spacing: 0.05em; text-transform: uppercase; margin-top: 1px; padding-bottom: 4px; border-bottom: 1px solid #e2e8f0; }',
            '    .plate-badge { display: inline-flex; align-items: center; justify-content: center; gap: 6px; background: #020617; color: #f8fafc; padding: 4px 12px; border-radius: 7px; margin: 4px 0 3px; }',
            '    .plate-text { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 16px; font-weight: 900; letter-spacing: 0.12em; }',
            '    .rivet { width: 4px; height: 4px; border-radius: 50%; background: #94a3b8; opacity: 0.7; }',
            '    .vehicle-model { font-size: 10px; font-weight: 700; color: #1e293b; margin-bottom: 3px; }',
            '    .qr-wrap { display: flex; justify-content: center; margin: 2px 0; }',
            '    .qr-img { width: 42mm; height: 42mm; object-fit: contain; }',
            '    .prompt-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 3px 5px; font-size: 8.5px; font-weight: 700; color: #0f172a; margin-top: 3px; }',
            '    .uuid-text { font-family: ui-monospace, monospace; font-size: 7.5px; color: #94a3b8; margin-top: 3px; word-break: break-all; }',
            '  \x3C/style\x3E',
            '\x3C/head\x3E',
            '\x3Cbody\x3E',
            '  \x3Cdiv class="sticker-container"\x3E',
            '    \x3Cdiv class="sticker-box"\x3E',
            '      \x3Cdiv class="brand-title"\x3EPT DAIJO INDUSTRIAL\x3C/div\x3E',
            '      \x3Cdiv class="brand-sub"\x3EOPERATIONAL FLEET &bull; P2H SYSTEM\x3C/div\x3E',
            '      \x3Cdiv class="plate-badge"\x3E',
            '        \x3Cspan class="rivet"\x3E\x3C/span\x3E',
            '        \x3Cspan class="plate-text"\x3E' + plateNumber + '\x3C/span\x3E',
            '        \x3Cspan class="rivet"\x3E\x3C/span\x3E',
            '      \x3C/div\x3E',
            '      \x3Cdiv class="vehicle-model"\x3E' + modelDisplay + '\x3C/div\x3E',
            '      \x3Cdiv class="qr-wrap"\x3E',
            '        \x3Cimg src="data:image/png;base64,' + qrBase64 + '" class="qr-img" alt="QR Code"\x3E',
            '      \x3C/div\x3E',
            '      \x3Cdiv class="prompt-box"\x3E📱 Pindai untuk P2H Check-out &amp; Check-in\x3C/div\x3E',
            '      \x3Cdiv class="uuid-text"\x3EUUID: ' + id + '\x3C/div\x3E',
            '    \x3C/div\x3E',
            '  \x3C/div\x3E',
            '\x3C/body\x3E',
            '\x3C/html\x3E'
        ].join('\n'));
        frameDoc.close();

        setTimeout(() => {
            frame.contentWindow.focus();
            frame.contentWindow.print();
        }, 250);
    }

    window.addEventListener('print-qr-sticker', (event) => {
        const qr = event.detail?.qrBase64 || '{{ $qrCodeBase64 }}';
        printVehicleQrSticker(qr, '{{ addslashes($vehicle->plate_number) }}', '{{ addslashes(trim($vehicle->brand . ' ' . $vehicle->model)) }}', '{{ $vehicle->year }}', '{{ addslashes($vehicle->id) }}');
    });
</script>
