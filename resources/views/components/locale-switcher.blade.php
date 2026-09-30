@props(['mobile' => false])

@php
    $currentLocale = app()->getLocale();
    $locales = config('app.supported_locales', [
        'id' => ['name' => 'Bahasa Indonesia', 'short' => 'ID', 'flag' => '🇮🇩'],
        'en' => ['name' => 'English', 'short' => 'EN', 'flag' => '🇬🇧'],
    ]);
    $current = $locales[$currentLocale] ?? $locales['id'];
@endphp

@if ($mobile)
    {{-- Mobile Version (Inside Drawer) --}}
    <div class="px-1 py-1">
        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">
            Bahasa / Language
        </div>
        <div class="grid grid-cols-2 gap-2">
            @foreach ($locales as $code => $loc)
                <a href="{{ route('locale.switch', $code) }}"
                   class="flex items-center justify-center gap-2 py-2 px-3 rounded-xl text-xs font-bold transition-all border {{ $currentLocale === $code ? 'bg-blue-50 text-blue-700 border-blue-200 shadow-2xs' : 'bg-white text-slate-600 border-slate-200/80 hover:bg-slate-50' }}">
                    <span class="text-sm">{{ $loc['flag'] }}</span>
                    <span>{{ $loc['short'] }}</span>
                    @if ($currentLocale === $code)
                        <i class="bi bi-check2 text-xs"></i>
                    @endif
                </a>
            @endforeach
        </div>
    </div>
@else
    {{-- Desktop Version (In Topbar) --}}
    <div class="relative" x-data="{ langMenuOpen: false }">
        <button type="button" @click="langMenuOpen = !langMenuOpen"
            @click.outside="langMenuOpen = false"
            title="Pilih Bahasa / Select Language"
            class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl border border-slate-200/70 bg-white/80 hover:bg-slate-50 text-slate-700 hover:text-blue-600 text-xs font-bold transition-all shadow-2xs group">
            <span class="text-sm leading-none">{{ $current['flag'] }}</span>
            <span class="tracking-tight">{{ $current['short'] }}</span>
            <svg class="h-3 w-3 text-slate-400 group-hover:text-blue-600 transition-transform duration-200"
                :class="langMenuOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        {{-- Dropdown Menu --}}
        <div x-show="langMenuOpen" x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 scale-95 translate-y-1"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-1"
            class="absolute right-0 top-full mt-1.5 w-44 rounded-2xl bg-white p-1.5 shadow-lg ring-1 ring-slate-900/5 focus:outline-none z-[100]"
            x-cloak>
            <div class="px-2 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                Pilih Bahasa
            </div>
            @foreach ($locales as $code => $loc)
                <a href="{{ route('locale.switch', $code) }}"
                    class="flex items-center justify-between rounded-xl px-2.5 py-2 text-xs font-semibold transition-colors {{ $currentLocale === $code ? 'bg-blue-50 text-blue-700 font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                    <div class="flex items-center gap-2">
                        <span class="text-base">{{ $loc['flag'] }}</span>
                        <span>{{ $loc['name'] }}</span>
                    </div>
                    @if ($currentLocale === $code)
                        <svg class="h-4 w-4 text-blue-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                        </svg>
                    @endif
                </a>
            @endforeach
        </div>
    </div>
@endif
