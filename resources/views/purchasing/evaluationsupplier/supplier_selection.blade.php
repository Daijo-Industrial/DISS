@extends('new.layouts.app')

@push('head')
<style>
    /* TomSelect Tailwind integration */
    .ts-control {
        border-radius: 0.75rem !important; /* rounded-xl */
        padding: 0.55rem 0.875rem !important;
        border: 1px solid #e2e8f0 !important; /* border-slate-200 */
        font-size: 0.875rem !important;
        line-height: 1.25rem !important;
        color: #1e293b !important;
        background-color: #ffffff !important;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
        transition: all 0.15s ease-in-out !important;
    }
    .ts-control:focus, .ts-control.focus {
        border-color: #6366f1 !important;
        box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.2) !important;
    }
    .ts-dropdown {
        border-radius: 0.75rem !important;
        border: 1px solid #e2e8f0 !important;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1) !important;
        font-size: 0.875rem !important;
        overflow: hidden !important;
        z-index: 50 !important;
    }
    .ts-dropdown .active {
        background-color: #eef2ff !important; /* bg-indigo-50 */
        color: #4338ca !important; /* text-indigo-700 */
        font-weight: 600 !important;
    }
</style>
@endpush

@section('content')
    <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

        {{-- PAGE HEADER --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 mb-1.5">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200/60">
                        <i class="bx bx-check-shield text-sm"></i> Purchasing Module
                    </span>
                    <span class="text-xs text-slate-400 font-medium">/ Supplier Management</span>
                </div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Supplier Evaluation</h1>
                <p class="text-sm text-slate-500 mt-1">
                    Hitung evaluasi kinerja supplier berdasarkan 6 kriteria SAP, monitor penilaian, dan tinjau riwayat laporan.
                </p>
            </div>

            @if(auth()->check() && auth()->user()->hasRole(['super-admin', 'admin']))
                <div>
                    <button type="button" onclick="openImportModal()"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold shadow-sm shadow-indigo-600/20 hover:shadow-md transition-all cursor-pointer">
                        <i class="bx bx-upload text-lg"></i>
                        <span>Update / Import SAP Data</span>
                    </button>
                </div>
            @endif
        </div>

        {{-- FLASH MESSAGES --}}
        @if(session('success'))
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900 flex items-center justify-between text-sm shadow-sm" role="alert">
                <div class="flex items-center gap-2.5">
                    <i class="bx bx-check-circle text-xl text-emerald-600 flex-shrink-0"></i>
                    <span>{{ session('success') }}</span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 transition-colors">
                    <i class="bx bx-x text-xl"></i>
                </button>
            </div>
        @endif
        @if(session('error'))
            <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-900 flex items-center justify-between text-sm shadow-sm" role="alert">
                <div class="flex items-center gap-2.5">
                    <i class="bx bx-error-circle text-xl text-rose-600 flex-shrink-0"></i>
                    <span>{{ session('error') }}</span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700 transition-colors">
                    <i class="bx bx-x text-xl"></i>
                </button>
            </div>
        @endif

        {{-- FORM CALCULATE EVALUATION --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-3 bg-slate-50/50">
                <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold">
                    <i class="bx bx-calculator text-xl"></i>
                </div>
                <div>
                    <h2 class="text-base font-bold text-slate-800">Hitung Evaluasi Supplier Baru</h2>
                    <p class="text-xs text-slate-500 font-medium">Pilih supplier dan rentang bulan untuk melakukan kalkulasi penilaian</p>
                </div>
            </div>

            <div class="p-6">
                <form action="{{ route('purchasing.evaluationsupplier.calculate') }}" method="POST" id="evaluation-form">
                    @csrf

                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                        {{-- SUPPLIER SELECT --}}
                        <div class="lg:col-span-5 space-y-1.5">
                            <label for="supplier" class="block text-xs font-bold uppercase tracking-wider text-slate-500">
                                Supplier <span class="text-rose-500">*</span>
                            </label>
                            <select name="supplier" id="supplier" required class="w-full">
                                <option value="">-- Search or Select Supplier --</option>
                                @foreach ($supplierData as $supplier => $years)
                                    <option value="{{ $supplier }}">{{ $supplier }}</option>
                                @endforeach
                            </select>
                            <p class="text-[11px] text-slate-400">
                                Ketik nama atau kode BP untuk pencarian cepat (didukung TomSelect).
                            </p>
                        </div>

                        {{-- PERIOD SETUP WITH QUICK PRESETS --}}
                        <div class="lg:col-span-7 space-y-2">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500">
                                    Periode Evaluasi <span class="text-rose-500">*</span>
                                </label>
                                {{-- Quick Presets Toolbar --}}
                                <div class="flex flex-wrap items-center gap-1" id="preset-container">
                                    <span class="text-[11px] font-semibold text-slate-400 mr-1">Presets:</span>
                                    <button type="button" class="preset-btn px-2.5 py-1 text-xs font-semibold rounded-lg border border-indigo-600 bg-indigo-600 text-white transition-all cursor-pointer active" data-preset="full_year">Full Year</button>
                                    <button type="button" class="preset-btn px-2.5 py-1 text-xs font-semibold rounded-lg border border-slate-200 text-slate-600 hover:border-indigo-400 hover:bg-indigo-50 transition-all cursor-pointer" data-preset="s1">Semester 1</button>
                                    <button type="button" class="preset-btn px-2.5 py-1 text-xs font-semibold rounded-lg border border-slate-200 text-slate-600 hover:border-indigo-400 hover:bg-indigo-50 transition-all cursor-pointer" data-preset="s2">Semester 2</button>
                                    <button type="button" class="preset-btn px-2.5 py-1 text-xs font-semibold rounded-lg border border-slate-200 text-slate-600 hover:border-indigo-400 hover:bg-indigo-50 transition-all cursor-pointer" data-preset="q1">Q1</button>
                                    <button type="button" class="preset-btn px-2.5 py-1 text-xs font-semibold rounded-lg border border-slate-200 text-slate-600 hover:border-indigo-400 hover:bg-indigo-50 transition-all cursor-pointer" data-preset="q2">Q2</button>
                                    <button type="button" class="preset-btn px-2.5 py-1 text-xs font-semibold rounded-lg border border-slate-200 text-slate-600 hover:border-indigo-400 hover:bg-indigo-50 transition-all cursor-pointer" data-preset="q3">Q3</button>
                                    <button type="button" class="preset-btn px-2.5 py-1 text-xs font-semibold rounded-lg border border-slate-200 text-slate-600 hover:border-indigo-400 hover:bg-indigo-50 transition-all cursor-pointer" data-preset="q4">Q4</button>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                {{-- FROM (Month & Year) --}}
                                <div class="flex items-center rounded-xl border border-slate-200 overflow-hidden bg-white shadow-xs focus-within:ring-2 focus-within:ring-indigo-500 focus-within:border-indigo-500 transition-all">
                                    <span class="px-3 py-2.5 bg-slate-50 border-r border-slate-200 text-xs font-bold text-slate-500 uppercase tracking-wider">
                                        From
                                    </span>
                                    <select name="start_month" id="start_month" class="w-full px-3 py-2 text-xs font-medium text-slate-700 bg-transparent focus:outline-none cursor-pointer" required>
                                        <option value="January" selected>January</option>
                                        <option value="February">February</option>
                                        <option value="March">March</option>
                                        <option value="April">April</option>
                                        <option value="May">May</option>
                                        <option value="June">June</option>
                                        <option value="July">July</option>
                                        <option value="August">August</option>
                                        <option value="September">September</option>
                                        <option value="October">October</option>
                                        <option value="November">November</option>
                                        <option value="December">December</option>
                                    </select>
                                    <select name="start_year" id="start_year" class="w-28 px-3 py-2 text-xs font-bold text-slate-800 bg-slate-50/50 border-l border-slate-200 focus:outline-none cursor-pointer" required>
                                        <option value="">Year</option>
                                    </select>
                                </div>

                                {{-- TO (Month & Year) --}}
                                <div class="flex items-center rounded-xl border border-slate-200 overflow-hidden bg-white shadow-xs focus-within:ring-2 focus-within:ring-indigo-500 focus-within:border-indigo-500 transition-all">
                                    <span class="px-3 py-2.5 bg-slate-50 border-r border-slate-200 text-xs font-bold text-slate-500 uppercase tracking-wider">
                                        To
                                    </span>
                                    <select name="end_month" id="end_month" class="w-full px-3 py-2 text-xs font-medium text-slate-700 bg-transparent focus:outline-none cursor-pointer" required>
                                        <option value="January">January</option>
                                        <option value="February">February</option>
                                        <option value="March">March</option>
                                        <option value="April">April</option>
                                        <option value="May">May</option>
                                        <option value="June">June</option>
                                        <option value="July">July</option>
                                        <option value="August">August</option>
                                        <option value="September">September</option>
                                        <option value="October">October</option>
                                        <option value="November">November</option>
                                        <option value="December" selected>December</option>
                                    </select>
                                    <select name="end_year" id="end_year" class="w-28 px-3 py-2 text-xs font-bold text-slate-800 bg-slate-50/50 border-l border-slate-200 focus:outline-none cursor-pointer" required>
                                        <option value="">Year</option>
                                    </select>
                                </div>
                            </div>
                            <p class="text-[11px] text-slate-400">
                                Tahun otomatis diselaraskan dengan data GRPO aktif supplier yang dipilih.
                            </p>
                        </div>

                        {{-- SUBMIT BUTTON --}}
                        <div class="lg:col-span-12 flex items-center justify-end pt-4 border-t border-slate-100">
                            <button type="submit" id="btn-calculate"
                                class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold shadow-sm shadow-emerald-600/20 hover:shadow-md transition-all cursor-pointer">
                                <i class="bx bx-play-circle text-lg"></i>
                                <span>Calculate Evaluation</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- VISUAL CRITERIA DIRECTORY --}}
        <div class="space-y-3">
            <div>
                <h2 class="text-base font-bold text-slate-800">Kriteria Penilaian SAP</h2>
                <p class="text-xs text-slate-500 font-medium">Pustaka 6 parameter kriteria evaluasi vendor terintegrasi data SAP</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                {{-- Kriteria 1 --}}
                <a href="{{ route('kriteria1') }}" class="group bg-white rounded-2xl border border-slate-200 p-5 shadow-sm hover:shadow-md hover:border-indigo-400 hover:-translate-y-0.5 transition-all flex flex-col justify-between">
                    <div>
                        <div class="flex items-start justify-between mb-3">
                            <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                                <i class="bx bx-package text-xl"></i>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200/60">
                                Max 20 Pts
                            </span>
                        </div>
                        <h3 class="text-sm font-bold text-slate-800 mb-1 group-hover:text-indigo-600 transition-colors">
                            Kualitas Barang & Kemasan
                        </h3>
                        <p class="text-xs text-slate-500 leading-relaxed">
                            Klaim vendor, rejection internal, can use status, dan insiden customer stopline.
                        </p>
                    </div>
                    <div class="flex items-center justify-between text-xs font-semibold text-slate-400 group-hover:text-indigo-600 pt-3 mt-4 border-t border-slate-100 transition-colors">
                        <span>Lihat Detail Data</span>
                        <i class="bx bx-right-arrow-alt text-base group-hover:translate-x-1 transition-transform"></i>
                    </div>
                </a>

                {{-- Kriteria 2 --}}
                <a href="{{ route('kriteria2') }}" class="group bg-white rounded-2xl border border-slate-200 p-5 shadow-sm hover:shadow-md hover:border-emerald-400 hover:-translate-y-0.5 transition-all flex flex-col justify-between">
                    <div>
                        <div class="flex items-start justify-between mb-3">
                            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center group-hover:bg-emerald-600 group-hover:text-white transition-colors">
                                <i class="bx bx-check-double text-xl"></i>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/60">
                                Max 20 Pts
                            </span>
                        </div>
                        <h3 class="text-sm font-bold text-slate-800 mb-1 group-hover:text-emerald-600 transition-colors">
                            Ketepatan Kuantitas Barang
                        </h3>
                        <p class="text-xs text-slate-500 leading-relaxed">
                            Kesesuaian qty delivery vs received, shortage, over shipment, dan closing status.
                        </p>
                    </div>
                    <div class="flex items-center justify-between text-xs font-semibold text-slate-400 group-hover:text-emerald-600 pt-3 mt-4 border-t border-slate-100 transition-colors">
                        <span>Lihat Detail Data</span>
                        <i class="bx bx-right-arrow-alt text-base group-hover:translate-x-1 transition-transform"></i>
                    </div>
                </a>

                {{-- Kriteria 3 --}}
                <a href="{{ route('kriteria3') }}" class="group bg-white rounded-2xl border border-slate-200 p-5 shadow-sm hover:shadow-md hover:border-sky-400 hover:-translate-y-0.5 transition-all flex flex-col justify-between">
                    <div>
                        <div class="flex items-start justify-between mb-3">
                            <div class="w-10 h-10 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center group-hover:bg-sky-600 group-hover:text-white transition-colors">
                                <i class="bx bx-time-five text-xl"></i>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-sky-50 text-sky-700 border border-sky-200/60">
                                Max 20 Pts
                            </span>
                        </div>
                        <h3 class="text-sm font-bold text-slate-800 mb-1 group-hover:text-sky-600 transition-colors">
                            Ketepatan Waktu Pengiriman
                        </h3>
                        <p class="text-xs text-slate-500 leading-relaxed">
                            Ketepatan tanggal kedatangan actual incoming vs purchase request date.
                        </p>
                    </div>
                    <div class="flex items-center justify-between text-xs font-semibold text-slate-400 group-hover:text-sky-600 pt-3 mt-4 border-t border-slate-100 transition-colors">
                        <span>Lihat Detail Data</span>
                        <i class="bx bx-right-arrow-alt text-base group-hover:translate-x-1 transition-transform"></i>
                    </div>
                </a>

                {{-- Kriteria 4 --}}
                <a href="{{ route('kriteria4') }}" class="group bg-white rounded-2xl border border-slate-200 p-5 shadow-sm hover:shadow-md hover:border-amber-400 hover:-translate-y-0.5 transition-all flex flex-col justify-between">
                    <div>
                        <div class="flex items-start justify-between mb-3">
                            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center group-hover:bg-amber-600 group-hover:text-white transition-colors">
                                <i class="bx bx-bolt-circle text-xl"></i>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200/60">
                                Max 10 Pts
                            </span>
                        </div>
                        <h3 class="text-sm font-bold text-slate-800 mb-1 group-hover:text-amber-600 transition-colors">
                            Permintaan Mendadak
                        </h3>
                        <p class="text-xs text-slate-500 leading-relaxed">
                            Respon urgent order request, percepatan lead time, dan penerapan special price.
                        </p>
                    </div>
                    <div class="flex items-center justify-between text-xs font-semibold text-slate-400 group-hover:text-amber-600 pt-3 mt-4 border-t border-slate-100 transition-colors">
                        <span>Lihat Detail Data</span>
                        <i class="bx bx-right-arrow-alt text-base group-hover:translate-x-1 transition-transform"></i>
                    </div>
                </a>

                {{-- Kriteria 5 --}}
                <a href="{{ route('kriteria5') }}" class="group bg-white rounded-2xl border border-slate-200 p-5 shadow-sm hover:shadow-md hover:border-rose-400 hover:-translate-y-0.5 transition-all flex flex-col justify-between">
                    <div>
                        <div class="flex items-start justify-between mb-3">
                            <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center group-hover:bg-rose-600 group-hover:text-white transition-colors">
                                <i class="bx bx-message-square-detail text-xl"></i>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200/60">
                                Max 10 Pts
                            </span>
                        </div>
                        <h3 class="text-sm font-bold text-slate-800 mb-1 group-hover:text-rose-600 transition-colors">
                            Respon Klaim (CPAR)
                        </h3>
                        <p class="text-xs text-slate-500 leading-relaxed">
                            Kecepatan respon dokumen perbaikan CPAR (1-3 hari) dan status penutupan klaim.
                        </p>
                    </div>
                    <div class="flex items-center justify-between text-xs font-semibold text-slate-400 group-hover:text-rose-600 pt-3 mt-4 border-t border-slate-100 transition-colors">
                        <span>Lihat Detail Data</span>
                        <i class="bx bx-right-arrow-alt text-base group-hover:translate-x-1 transition-transform"></i>
                    </div>
                </a>

                {{-- Kriteria 6 --}}
                <a href="{{ route('kriteria6') }}" class="group bg-white rounded-2xl border border-slate-200 p-5 shadow-sm hover:shadow-md hover:border-purple-400 hover:-translate-y-0.5 transition-all flex flex-col justify-between">
                    <div>
                        <div class="flex items-start justify-between mb-3">
                            <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center group-hover:bg-purple-600 group-hover:text-white transition-colors">
                                <i class="bx bx-certification text-xl"></i>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-purple-50 text-purple-700 border border-purple-200/60">
                                Max 10 Pts
                            </span>
                        </div>
                        <h3 class="text-sm font-bold text-slate-800 mb-1 group-hover:text-purple-600 transition-colors">
                            Sertifikasi Standar Mutu
                        </h3>
                        <p class="text-xs text-slate-500 leading-relaxed">
                            Kepemilikan dan masa aktif sertifikat ISO 9001:2015, ISO 14001, dan IATF 16949.
                        </p>
                    </div>
                    <div class="flex items-center justify-between text-xs font-semibold text-slate-400 group-hover:text-purple-600 pt-3 mt-4 border-t border-slate-100 transition-colors">
                        <span>Lihat Detail Data</span>
                        <i class="bx bx-right-arrow-alt text-base group-hover:translate-x-1 transition-transform"></i>
                    </div>
                </a>
            </div>
        </div>

        {{-- SUPPLIER EVALUATIONS HISTORY TABLE --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            {{-- Card Header --}}
            <div class="px-6 py-4 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-3 bg-slate-50/50">
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-base font-bold text-slate-800">Riwayat Evaluasi Tersimpan</h2>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-200/70 text-slate-700">
                            {{ $header->count() }} Total
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 font-medium mt-0.5">Daftar evaluasi supplier yang telah dikalkulasi di sistem</p>
                </div>

                {{-- Search & Grade Filter Pills --}}
                <div class="flex flex-col sm:flex-row sm:items-center gap-2.5">
                    {{-- Search Input --}}
                    <div class="relative w-full sm:w-64">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                            <i class="bx bx-search text-base"></i>
                        </span>
                        <input type="text" id="evalSearchInput"
                            class="w-full pl-9 pr-3 py-1.5 text-xs rounded-xl border border-slate-200 text-slate-700 bg-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all"
                            placeholder="Search vendor / code / period...">
                    </div>

                    {{-- Grade Filter Tabs --}}
                    <div class="inline-flex rounded-xl p-1 bg-slate-100 border border-slate-200/80 text-xs font-semibold gap-1" id="gradeFilterGroup">
                        <button type="button" class="px-3 py-1 rounded-lg transition-all text-white bg-indigo-600 shadow-xs cursor-pointer active" data-grade="ALL">All</button>
                        <button type="button" class="px-3 py-1 rounded-lg transition-all text-slate-600 hover:text-slate-900 cursor-pointer" data-grade="A">A</button>
                        <button type="button" class="px-3 py-1 rounded-lg transition-all text-slate-600 hover:text-slate-900 cursor-pointer" data-grade="B">B</button>
                        <button type="button" class="px-3 py-1 rounded-lg transition-all text-slate-600 hover:text-slate-900 cursor-pointer" data-grade="C">C</button>
                        <button type="button" class="px-3 py-1 rounded-lg transition-all text-slate-600 hover:text-slate-900 cursor-pointer" data-grade="PENDING">Pending</button>
                    </div>
                </div>
            </div>

            {{-- Table Body --}}
            <div>
                @if ($header->isEmpty())
                    <div class="p-12 text-center">
                        <div class="w-14 h-14 rounded-2xl bg-slate-50 text-slate-400 flex items-center justify-center mx-auto mb-3">
                            <i class="bx bx-folder-open text-2xl"></i>
                        </div>
                        <h3 class="text-sm font-bold text-slate-700 mb-1">Belum ada riwayat data evaluasi</h3>
                        <p class="text-xs text-slate-400 max-w-sm mx-auto">
                            Gunakan formulir kalkulasi di atas untuk melakukan evaluasi supplier pertama.
                        </p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-sm" id="evaluationsTable">
                            <thead class="bg-slate-50/75 border-b border-slate-100 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                                <tr>
                                    <th class="px-6 py-3" style="width: 70px;">ID</th>
                                    <th class="px-4 py-3" style="width: 140px;">Vendor Code</th>
                                    <th class="px-4 py-3">Vendor Name</th>
                                    <th class="px-4 py-3" style="width: 190px;">Period</th>
                                    <th class="px-4 py-3 text-center" style="width: 130px;">Grade</th>
                                    <th class="px-4 py-3 text-center" style="width: 160px;">Status</th>
                                    <th class="px-6 py-3 text-end" style="width: 140px;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="evalTableBody" class="divide-y divide-slate-100">
                                @foreach ($header as $head)
                                    @php
                                        $grade = strtoupper(trim((string)$head->grade));
                                        $filterGrade = empty($grade) ? 'PENDING' : $grade;
                                        $periodText = $head->period ?: ($head->start_month . ' - ' . $head->end_month . ' ' . $head->year);
                                    @endphp
                                    <tr class="eval-row hover:bg-slate-50/60 transition-colors" data-grade="{{ $filterGrade }}" data-search="{{ strtolower($head->vendor_code . ' ' . $head->vendor_name . ' ' . $periodText . ' ' . $head->grade . ' ' . $head->status) }}">
                                        <td class="px-6 py-3.5 font-bold text-slate-400 text-xs">#{{ $head->id }}</td>
                                        <td class="px-4 py-3.5">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md font-mono text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200/80">
                                                {{ $head->vendor_code }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3.5 font-semibold text-slate-800">
                                            {{ $head->vendor_name }}
                                        </td>
                                        <td class="px-4 py-3.5 text-xs text-slate-500">
                                            <span class="inline-flex items-center gap-1.5">
                                                <i class="bx bx-calendar text-slate-400"></i>
                                                <span>{{ $periodText }}</span>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3.5 text-center">
                                            @if ($grade === 'A')
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                    <i class="bx bx-check-circle"></i> Grade A
                                                </span>
                                            @elseif ($grade === 'B')
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                                    <i class="bx bx-info-circle"></i> Grade B
                                                </span>
                                            @elseif ($grade === 'C')
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                                    <i class="bx bx-x-circle"></i> Grade C
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-600">
                                                    Pending
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3.5 text-center">
                                            @if ($head->status === 'Diteruskan')
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                                                    Diteruskan
                                                </span>
                                            @elseif ($head->status === 'Dipertahankan')
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-800">
                                                    Dipertahankan
                                                </span>
                                            @elseif (str_contains((string)$head->status, 'Monitoring'))
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-100 text-rose-800">
                                                    Monitoring 3 Bulan
                                                </span>
                                            @elseif ($head->status)
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                                    {{ $head->status }}
                                                </span>
                                            @else
                                                <span class="text-xs text-slate-400">-</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-3.5 text-end">
                                            <a href="{{ route('purchasing.evaluationsupplier.details', ['id' => $head->id]) }}"
                                                target="_blank"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 hover:border-indigo-300 hover:bg-indigo-50/50 text-xs font-semibold text-slate-700 hover:text-indigo-600 transition-all shadow-xs">
                                                <i class="bx bx-file-blank text-slate-400"></i>
                                                <span>View Report</span>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Client-Side Pagination & Showing Info --}}
                    <div class="px-6 py-4 bg-slate-50/60 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500">
                        <div id="paginationSummary">
                            Showing <span id="showingStart" class="font-bold text-slate-700">1</span> to <span id="showingEnd" class="font-bold text-slate-700">10</span> of <span id="showingTotal" class="font-bold text-slate-700">{{ $header->count() }}</span> entries
                        </div>
                        <div class="inline-flex items-center gap-1.5" id="tablePagination">
                            <button type="button" id="btnPrevPage"
                                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-semibold disabled:opacity-40 disabled:cursor-not-allowed transition-all cursor-pointer shadow-xs">
                                <i class="bx bx-chevron-left text-base"></i> Prev
                            </button>
                            <span class="px-3 py-1.5 rounded-xl border border-slate-200 bg-white text-slate-700 font-semibold shadow-xs" id="pageIndicator">
                                Page 1
                            </span>
                            <button type="button" id="btnNextPage"
                                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-semibold disabled:opacity-40 disabled:cursor-not-allowed transition-all cursor-pointer shadow-xs">
                                Next <i class="bx bx-chevron-right text-base"></i>
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        @include('purchasing.evaluationsupplier.partials.import_modal')
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const supplierData = @json($supplierData);
            const supplierSelect = document.getElementById('supplier');
            const startYearSelect = document.getElementById('start_year');
            const endYearSelect = document.getElementById('end_year');
            const startMonthSelect = document.getElementById('start_month');
            const endMonthSelect = document.getElementById('end_month');

            // 1. Initialize TomSelect on Supplier Dropdown
            let tomSupplierInstance = null;
            if (typeof window.TomSelect !== 'undefined' && supplierSelect) {
                tomSupplierInstance = new window.TomSelect('#supplier', {
                    create: false,
                    placeholder: '-- Search or Select Supplier --',
                    maxOptions: 500,
                    allowEmptyOption: true,
                    onChange: function(value) {
                        handleSupplierChange(value);
                    }
                });
            } else if (supplierSelect) {
                supplierSelect.addEventListener('change', function() {
                    handleSupplierChange(this.value);
                });
            }

            function handleSupplierChange(supplier) {
                startYearSelect.innerHTML = '<option value="">Year</option>';
                endYearSelect.innerHTML = '<option value="">Year</option>';

                if (supplier && supplierData[supplier] && supplierData[supplier].length > 0) {
                    const years = supplierData[supplier];
                    const latestYear = years[0]; // ordered desc

                    years.forEach(function(year) {
                        startYearSelect.insertAdjacentHTML('beforeend', `<option value="${year}">${year}</option>`);
                        endYearSelect.insertAdjacentHTML('beforeend', `<option value="${year}">${year}</option>`);
                    });

                    // Auto-fill latest active year
                    startYearSelect.value = latestYear;
                    endYearSelect.value = latestYear;
                }
            }

            // 2. Period Quick Presets
            const presetButtons = document.querySelectorAll('.preset-btn');
            function setActivePreset(activeBtn) {
                presetButtons.forEach(b => {
                    b.classList.remove('bg-indigo-600', 'border-indigo-600', 'text-white');
                    b.classList.add('border-slate-200', 'text-slate-600');
                });
                if (activeBtn) {
                    activeBtn.classList.remove('border-slate-200', 'text-slate-600');
                    activeBtn.classList.add('bg-indigo-600', 'border-indigo-600', 'text-white');
                }
            }

            presetButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    setActivePreset(this);

                    const preset = this.dataset.preset;
                    switch(preset) {
                        case 'full_year':
                            startMonthSelect.value = 'January';
                            endMonthSelect.value = 'December';
                            break;
                        case 's1':
                            startMonthSelect.value = 'January';
                            endMonthSelect.value = 'June';
                            break;
                        case 's2':
                            startMonthSelect.value = 'July';
                            endMonthSelect.value = 'December';
                            break;
                        case 'q1':
                            startMonthSelect.value = 'January';
                            endMonthSelect.value = 'March';
                            break;
                        case 'q2':
                            startMonthSelect.value = 'April';
                            endMonthSelect.value = 'June';
                            break;
                        case 'q3':
                            startMonthSelect.value = 'July';
                            endMonthSelect.value = 'September';
                            break;
                        case 'q4':
                            startMonthSelect.value = 'October';
                            endMonthSelect.value = 'December';
                            break;
                    }
                });
            });

            // If user manually changes months, sync preset button active state
            [startMonthSelect, endMonthSelect].forEach(sel => {
                sel.addEventListener('change', () => {
                    const sm = startMonthSelect.value;
                    const em = endMonthSelect.value;

                    if (sm === 'January' && em === 'December') {
                        setActivePreset(document.querySelector('[data-preset="full_year"]'));
                    } else if (sm === 'January' && em === 'June') {
                        setActivePreset(document.querySelector('[data-preset="s1"]'));
                    } else if (sm === 'July' && em === 'December') {
                        setActivePreset(document.querySelector('[data-preset="s2"]'));
                    } else {
                        setActivePreset(null);
                    }
                });
            });

            // 3. Date Range Validation
            const monthMap = {
                'January': 1, 'February': 2, 'March': 3, 'April': 4,
                'May': 5, 'June': 6, 'July': 7, 'August': 8,
                'September': 9, 'October': 10, 'November': 11, 'December': 12
            };

            function validateDateRange() {
                const startMonthName = startMonthSelect.value;
                const endMonthName = endMonthSelect.value;
                const startYear = parseInt(startYearSelect.value);
                const endYear = parseInt(endYearSelect.value);

                if (!supplierSelect.value) {
                    alert('Silakan pilih supplier terlebih dahulu.');
                    return false;
                }

                if (!startYear || !endYear) {
                    alert('Silakan pilih tahun awal dan tahun akhir evaluasi.');
                    return false;
                }

                if (startMonthName && endMonthName && startYear && endYear) {
                    const startMonth = monthMap[startMonthName];
                    const endMonth = monthMap[endMonthName];

                    if (startMonth && endMonth) {
                        const startDate = new Date(startYear, startMonth - 1, 1);
                        const endDate = new Date(endYear, endMonth - 1, 1);

                        if (startDate > endDate) {
                            alert('Start period tidak boleh lebih besar dari end period.');
                            return false;
                        }
                    }
                }
                return true;
            }

            const evalForm = document.getElementById('evaluation-form');
            if (evalForm) {
                evalForm.addEventListener('submit', function(e) {
                    if (!validateDateRange()) {
                        e.preventDefault();
                    } else {
                        const btnCalc = document.getElementById('btn-calculate');
                        if (btnCalc) {
                            btnCalc.disabled = true;
                            btnCalc.innerHTML = '<i class="bx bx-loader-alt animate-spin text-lg mr-1.5"></i> Calculating...';
                        }
                    }
                });
            }

            // 4. Client-side Table Search, Grade Filter & Pagination
            const evalRows = Array.from(document.querySelectorAll('.eval-row'));
            const searchInput = document.getElementById('evalSearchInput');
            const gradeFilterBtns = document.querySelectorAll('#gradeFilterGroup button');
            const btnPrev = document.getElementById('btnPrevPage');
            const btnNext = document.getElementById('btnNextPage');
            const pageIndicator = document.getElementById('pageIndicator');
            const showingStart = document.getElementById('showingStart');
            const showingEnd = document.getElementById('showingEnd');
            const showingTotal = document.getElementById('showingTotal');

            if (evalRows.length > 0) {
                let currentSearch = '';
                let currentGrade = 'ALL';
                let currentPage = 1;
                const pageSize = 10;

                function getFilteredRows() {
                    return evalRows.filter(row => {
                        const matchGrade = currentGrade === 'ALL' || row.dataset.grade === currentGrade;
                        const matchSearch = !currentSearch || row.dataset.search.includes(currentSearch);
                        return matchGrade && matchSearch;
                    });
                }

                function renderTable() {
                    const filtered = getFilteredRows();
                    const totalItems = filtered.length;
                    const totalPages = Math.ceil(totalItems / pageSize) || 1;

                    if (currentPage > totalPages) currentPage = totalPages;
                    if (currentPage < 1) currentPage = 1;

                    const startIndex = (currentPage - 1) * pageSize;
                    const endIndex = startIndex + pageSize;

                    // Hide all, then show slice
                    evalRows.forEach(row => row.style.display = 'none');
                    filtered.slice(startIndex, endIndex).forEach(row => row.style.display = '');

                    // Pagination controls
                    if (pageIndicator) pageIndicator.textContent = `Page ${currentPage} of ${totalPages}`;
                    if (btnPrev) btnPrev.disabled = currentPage <= 1;
                    if (btnNext) btnNext.disabled = currentPage >= totalPages;

                    // Summary counts
                    if (showingStart) showingStart.textContent = totalItems === 0 ? '0' : startIndex + 1;
                    if (showingEnd) showingEnd.textContent = Math.min(endIndex, totalItems);
                    if (showingTotal) showingTotal.textContent = totalItems;
                }

                if (searchInput) {
                    searchInput.addEventListener('input', function() {
                        currentSearch = this.value.trim().toLowerCase();
                        currentPage = 1;
                        renderTable();
                    });
                }

                gradeFilterBtns.forEach(btn => {
                    btn.addEventListener('click', function() {
                        gradeFilterBtns.forEach(b => {
                            b.classList.remove('bg-indigo-600', 'text-white', 'shadow-xs');
                            b.classList.add('text-slate-600', 'hover:text-slate-900');
                        });
                        this.classList.remove('text-slate-600', 'hover:text-slate-900');
                        this.classList.add('bg-indigo-600', 'text-white', 'shadow-xs');

                        currentGrade = this.dataset.grade;
                        currentPage = 1;
                        renderTable();
                    });
                });

                if (btnPrev) {
                    btnPrev.addEventListener('click', function() {
                        if (currentPage > 1) {
                            currentPage--;
                            renderTable();
                        }
                    });
                }

                if (btnNext) {
                    btnNext.addEventListener('click', function() {
                        const totalPages = Math.ceil(getFilteredRows().length / pageSize) || 1;
                        if (currentPage < totalPages) {
                            currentPage++;
                            renderTable();
                        }
                    });
                }

                // Initial render
                renderTable();
            }
        });
    </script>
@endpush
