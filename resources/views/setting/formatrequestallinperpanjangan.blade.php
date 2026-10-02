@extends('new.layouts.app')

@section('title', 'Evaluation Format Request - Perpanjangan')

@section('content')
    <div class="max-w-xl mx-auto py-6">
        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden p-6 sm:p-8">
            <div class="mb-6">
                <h1 class="text-xl font-black text-slate-900 tracking-tight">Evaluation Format Request (Perpanjangan)</h1>
                <p class="text-xs font-medium text-slate-500 mt-1">Select department and year to generate evaluation export</p>
            </div>

            <form action="{{ route('get.format.allinperpanjangan') }}" method="POST" class="space-y-5">
                @csrf

                <div>
                    <label for="dept" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                        Department
                    </label>
                    <div class="relative">
                        <select id="dept" name="dept" required
                            class="w-full appearance-none rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-2.5 text-sm font-semibold text-slate-800 focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/10 outline-none transition cursor-pointer">
                            @foreach ($departments as $department)
                                <option value="{{ $department->dept_no }}">{{ $department->name }}</option>
                            @endforeach
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div>
                    <label for="year" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                        Year
                    </label>
                    <div class="relative">
                        <select id="year" name="year" required
                            class="w-full appearance-none rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-2.5 text-sm font-semibold text-slate-800 focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/10 outline-none transition cursor-pointer">
                            @foreach (range(date('Y') - 5, date('Y')) as $y)
                                <option value="{{ $y }}" {{ $y == date('Y') ? 'selected' : '' }}>{{ $y }}</option>
                            @endforeach
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="pt-3">
                    <button type="submit"
                        class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-3 text-sm font-bold text-white shadow-md shadow-blue-200 hover:bg-blue-700 active:scale-95 transition-all">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Generate Format
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
