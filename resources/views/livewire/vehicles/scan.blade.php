<div class="max-w-xl mx-auto px-4 py-6 sm:px-6 space-y-6"
    x-data="{
        html5QrCode: null,
        isScanning: false,
        cameraError: null,
        torchSupported: false,
        torchOn: false,
        hasMultipleCameras: false,
        currentCameraIndex: 0,
        cameras: [],
        scanSuccess: false,
        scanResultText: '',

        async initScanner() {
            if (typeof Html5Qrcode === 'undefined') {
                this.cameraError = 'Komponen pemindai sedang dimuat, silakan tunggu sebentar...';
                return;
            }

            try {
                this.cameras = await Html5Qrcode.getCameras();
                if (!this.cameras || this.cameras.length === 0) {
                    this.cameraError = 'Kamera tidak terdeteksi pada perangkat ini. Gunakan pencarian manual di bawah.';
                    return;
                }
                this.hasMultipleCameras = this.cameras.length > 1;

                // Prefer back camera / environment
                let selectedCameraId = this.cameras[0].id;
                const backCam = this.cameras.find(c => c.label.toLowerCase().includes('back') || c.label.toLowerCase().includes('rear') || c.label.toLowerCase().includes('belakang') || c.label.toLowerCase().includes('environment'));
                if (backCam) {
                    selectedCameraId = backCam.id;
                    this.currentCameraIndex = this.cameras.findIndex(c => c.id === backCam.id);
                }

                await this.startCamera(selectedCameraId);
            } catch (err) {
                console.error(err);
                this.cameraError = 'Izin akses kamera ditolak atau tidak didukung di peramban ini. Pastikan Anda mengizinkan akses kamera.';
            }
        },

        async startCamera(cameraId) {
            this.cameraError = null;
            if (this.html5QrCode) {
                try {
                    await this.html5QrCode.stop();
                } catch(e) {}
            }

            this.html5QrCode = new Html5Qrcode('qr-reader');
            const config = {
                fps: 15,
                qrbox: { width: 250, height: 250 },
                aspectRatio: 1.0,
            };

            try {
                await this.html5QrCode.start(
                    cameraId,
                    config,
                    (decodedText) => this.onScanSuccess(decodedText),
                    (errorMessage) => {}
                );
                this.isScanning = true;
                this.checkTorchSupport();
            } catch (err) {
                this.isScanning = false;
                this.cameraError = 'Gagal menyalakan stream kamera: ' + (err.message || err);
            }
        },

        async switchCamera() {
            if (!this.hasMultipleCameras) return;
            this.currentCameraIndex = (this.currentCameraIndex + 1) % this.cameras.length;
            await this.startCamera(this.cameras[this.currentCameraIndex].id);
        },

        checkTorchSupport() {
            try {
                const track = this.html5QrCode.getRunningTrackCapabilities();
                this.torchSupported = !!(track && track.torch);
            } catch(e) {
                this.torchSupported = false;
            }
        },

        async toggleTorch() {
            if (!this.torchSupported || !this.html5QrCode) return;
            try {
                this.torchOn = !this.torchOn;
                await this.html5QrCode.applyVideoConstraints({
                    advanced: [{ torch: this.torchOn }]
                });
            } catch (e) {
                console.error('Torch error:', e);
            }
        },

        onScanSuccess(decodedText) {
            if (this.scanSuccess) return;
            this.scanSuccess = true;
            this.scanResultText = decodedText;

            // Audio & Vibration feedback
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.setValueAtTime(880, ctx.currentTime); // A5 note
                gain.gain.setValueAtTime(0.2, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.15);
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start();
                osc.stop(ctx.currentTime + 0.15);
            } catch(e) {}

            if (navigator.vibrate) {
                navigator.vibrate([80, 50, 80]);
            }

            // Stop scanner to prevent multiple triggers
            try {
                this.html5QrCode.stop();
                this.isScanning = false;
            } catch(e) {}

            // Send to Livewire
            $wire.resolve(decodedText);
        },

        destroy() {
            if (this.html5QrCode) {
                try {
                    this.html5QrCode.stop();
                } catch(e) {}
            }
        }
    }"
    x-init="initScanner()"
    @page-unloaded.window="destroy()"
    class="min-h-[80vh] flex flex-col justify-start">

    {{-- Top Bar & Back --}}
    <div class="flex items-center justify-between">
        <a href="{{ route('vehicles.index') }}" wire:navigate
            class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-slate-900 bg-white border border-slate-200 px-3 py-1.5 rounded-xl shadow-2xs hover:bg-slate-50 transition">
            <i class="bi bi-arrow-left"></i>
            <span>{{ __('fleet.scanner.back_to_index') }}</span>
        </a>

        <div class="text-right">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200/60">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                <span>{{ __('fleet.scanner.badge') }}</span>
            </span>
        </div>
    </div>

    {{-- Header Card --}}
    <div class="text-center space-y-1">
        <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">{{ __('fleet.scanner.title') }}</h1>
        <p class="text-xs sm:text-sm text-slate-500">
            {{ __('fleet.scanner.subtitle') }}
        </p>
    </div>

    {{-- Camera Viewfinder Container --}}
    <div class="relative bg-slate-950 rounded-3xl overflow-hidden shadow-xl border-4 border-slate-900 aspect-square max-w-sm mx-auto w-full flex flex-col items-center justify-center">
        
        {{-- Video Element for Html5Qrcode --}}
        <div id="qr-reader" class="w-full h-full [&>video]:w-full [&>video]:h-full [&>video]:object-cover"
            :class="{ 'opacity-20': scanSuccess }">
        </div>

        {{-- Camera Error Overlay --}}
        <template x-if="cameraError">
            <div class="absolute inset-0 bg-slate-900/95 flex flex-col items-center justify-center p-6 text-center text-white space-y-3 z-20">
                <div class="w-12 h-12 rounded-2xl bg-rose-500/20 text-rose-400 flex items-center justify-center text-2xl border border-rose-500/30">
                    <i class="bi bi-camera-video-off"></i>
                </div>
                <div class="space-y-1">
                    <p class="text-xs font-bold text-rose-300">Kamera Tidak Aktif</p>
                    <p class="text-[11px] text-slate-300" x-text="cameraError"></p>
                </div>
                <button type="button" @click="initScanner()"
                    class="rounded-xl bg-white text-slate-900 px-3.5 py-1.5 text-xs font-bold shadow-xs hover:bg-slate-100 transition active:scale-95">
                    <i class="bi bi-arrow-clockwise mr-1"></i> Coba Lagi
                </button>
            </div>
        </template>

        {{-- Scanning Reticle & Aiming Frame HUD --}}
        <div x-show="isScanning && !cameraError && !scanSuccess" class="pointer-events-none absolute inset-0 flex items-center justify-center z-10">
            <div class="relative w-56 h-56 rounded-2xl border-2 border-indigo-400/60 shadow-[0_0_0_9999px_rgba(15,23,42,0.45)]">
                {{-- Corner Accents --}}
                <div class="absolute -top-1 -left-1 w-6 h-6 border-t-4 border-l-4 border-indigo-400 rounded-tl-lg"></div>
                <div class="absolute -top-1 -right-1 w-6 h-6 border-t-4 border-r-4 border-indigo-400 rounded-tr-lg"></div>
                <div class="absolute -bottom-1 -left-1 w-6 h-6 border-b-4 border-l-4 border-indigo-400 rounded-bl-lg"></div>
                <div class="absolute -bottom-1 -right-1 w-6 h-6 border-b-4 border-r-4 border-indigo-400 rounded-br-lg"></div>

                {{-- Animated Laser Scan Line --}}
                <div class="absolute inset-x-2 h-0.5 bg-gradient-to-r from-transparent via-cyan-400 to-transparent shadow-[0_0_8px_#22d3ee] animate-scanner-laser"></div>
            </div>
        </div>

        {{-- Success Flash Overlay --}}
        <div x-show="scanSuccess" x-cloak
            class="absolute inset-0 bg-emerald-600/90 flex flex-col items-center justify-center p-6 text-center text-white space-y-2 z-30 animate-fade-in">
            <div class="w-14 h-14 rounded-full bg-white text-emerald-600 flex items-center justify-center text-3xl shadow-lg animate-bounce">
                <i class="bi bi-check2"></i>
            </div>
            <p class="text-sm font-black tracking-wide">QR Terdeteksi!</p>
            <p class="text-xs text-emerald-100">Membuka formulir inspeksi P2H...</p>
        </div>

        {{-- Top HUD Controls (Torch & Flip Camera) --}}
        <div class="absolute top-3 inset-x-3 flex items-center justify-between z-20" x-show="isScanning && !cameraError && !scanSuccess">
            <template x-if="torchSupported">
                <button type="button" @click="toggleTorch()"
                    :class="torchOn ? 'bg-amber-400 text-slate-950' : 'bg-slate-900/70 text-white backdrop-blur-md'"
                    class="h-9 w-9 rounded-full flex items-center justify-center text-sm shadow-md transition active:scale-90 border border-white/10"
                    title="Flashlight">
                    <i class="bi" :class="torchOn ? 'bi-lightbulb-fill' : 'bi-lightbulb'"></i>
                </button>
            </template>
            <div x-show="!torchSupported"></div>

            <template x-if="hasMultipleCameras">
                <button type="button" @click="switchCamera()"
                    class="h-9 w-9 rounded-full bg-slate-900/70 text-white backdrop-blur-md flex items-center justify-center text-sm shadow-md transition active:scale-90 border border-white/10"
                    title="Ganti Kamera">
                    <i class="bi bi-camera-reverse"></i>
                </button>
            </template>
        </div>
    </div>

    {{-- Livewire Error Message --}}
    @if ($errorMessage)
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-xs text-rose-800 space-y-1 shadow-2xs">
            <div class="flex items-center gap-2 font-bold text-rose-900">
                <i class="bi bi-exclamation-triangle-fill text-rose-600"></i>
                <span>Gagal Memproses QR</span>
            </div>
            <p>{{ $errorMessage }}</p>
            <div class="pt-2">
                <button type="button" @click="scanSuccess = false; initScanner()"
                    class="inline-flex items-center gap-1 font-semibold text-rose-700 hover:text-rose-900 underline">
                    <i class="bi bi-arrow-repeat"></i> Coba Pindai Ulang
                </button>
            </div>
        </div>
    @endif

    {{-- Alternative: Manual Input / Quick Plate Picker --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-5 space-y-4 shadow-xs" x-data="{ expanded: false }">
        <div class="flex items-center justify-between cursor-pointer" @click="expanded = !expanded">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-xl bg-slate-100 flex items-center justify-center text-slate-700 text-sm">
                    <i class="bi bi-search"></i>
                </div>
                <div>
                    <h3 class="text-xs font-bold text-slate-900">{{ __('fleet.scanner.manual_heading') }}</h3>
                    <p class="text-[11px] text-slate-500">{{ __('fleet.scanner.manual_desc') }}</p>
                </div>
            </div>
            <button type="button" class="text-slate-400 hover:text-slate-600">
                <i class="bi" :class="expanded ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
            </button>
        </div>

        <div x-show="expanded" x-collapse class="space-y-4 pt-2 border-t border-slate-100">
            <form wire:submit="searchManual" class="flex gap-2">
                <div class="relative flex-1">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                        <i class="bi bi-card-text"></i>
                    </div>
                    <input type="text" wire:model="manualInput" placeholder="{{ __('fleet.scanner.manual_placeholder') }}"
                        class="w-full rounded-xl border border-slate-300 py-2 pl-9 pr-3 text-xs uppercase font-mono text-slate-800 placeholder-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                </div>
                <button type="submit"
                    class="rounded-xl bg-indigo-600 px-4 py-2 text-xs font-bold text-white shadow-xs hover:bg-indigo-700 transition active:scale-95 flex items-center gap-1.5">
                    <i class="bi bi-arrow-right"></i>
                    <span>{{ __('fleet.scanner.manual_submit') }}</span>
                </button>
            </form>

            @if ($recentVehicles->isNotEmpty())
                <div class="space-y-2">
                    <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider">Armada Operasional Aktif</label>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach ($recentVehicles as $v)
                            <button type="button" wire:click="resolve('{{ $v->id }}')"
                                class="flex items-center gap-2 p-2 rounded-xl border border-slate-200 hover:border-indigo-500 hover:bg-indigo-50/50 text-left transition group">
                                <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center shrink-0 border border-slate-200 overflow-hidden">
                                    @if ($v->image_url)
                                        <img src="{{ $v->image_url }}" alt="{{ $v->plate_number }}" class="w-full h-full object-cover">
                                    @else
                                        <i class="bi bi-truck text-slate-400 group-hover:text-indigo-600 text-xs"></i>
                                    @endif
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="font-mono text-xs font-bold text-slate-900 group-hover:text-indigo-600 truncate">{{ $v->plate_number }}</p>
                                    <p class="text-[10px] text-slate-400 truncate">{{ $v->brand }} {{ $v->model }}</p>
                                </div>
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    <style>
    @keyframes scannerLaser {
        0% { top: 0%; opacity: 0.8; }
        50% { top: 98%; opacity: 1; }
        100% { top: 0%; opacity: 0.8; }
    }
    .animate-scanner-laser {
        animation: scannerLaser 2.2s ease-in-out infinite;
    }
    </style>
</div>
