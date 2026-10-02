@extends('new.layouts.app')

@section('title', 'Specifications')

@section('content')
    <div class="space-y-6">
        {{-- Header Section --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black tracking-tight text-slate-900">Specification List</h1>
                <nav class="flex items-center gap-2 text-xs font-semibold text-slate-400 mt-1">
                    <a href="{{ route('admin') }}" class="hover:text-blue-600 transition-colors">Home</a>
                    <span>/</span>
                    <span class="text-slate-600">Specifications</span>
                </nav>
            </div>
            <div>
                @include('partials.add-specification-modal')
                <button type="button" @click="$dispatch('open-modal', 'add-specification-modal')"
                    class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-bold text-white shadow-md shadow-blue-200 hover:bg-blue-700 active:scale-95 transition-all">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Specification
                </button>
            </div>
        </div>

        {{-- Alerts --}}
        @if ($message = Session::get('success'))
            <div class="flex items-center justify-between rounded-2xl bg-emerald-50 border border-emerald-200 p-4 text-emerald-800">
                <div class="flex items-center gap-3">
                    <svg class="h-5 w-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span class="text-sm font-semibold">{{ $message }}</span>
                </div>
            </div>
        @elseif ($errors->any())
            <div class="rounded-2xl bg-rose-50 border border-rose-200 p-4 text-rose-800">
                <div class="flex items-start gap-3">
                    <svg class="h-5 w-5 text-rose-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <ul class="text-xs font-semibold space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        {{-- Data Table Card --}}
        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6 overflow-hidden">
            <div class="overflow-x-auto custom-scrollbar">
                {{ $dataTable->table() }}
            </div>
        </div>
    </div>
@endsection

@push('extraJs')
    {{ $dataTable->scripts() }}
@endpush
