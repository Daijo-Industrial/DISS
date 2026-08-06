@props([
    'title' => '',
    'value' => '0',
    'icon' => 'chart-bar',
    'trend' => null,
    'trendType' => 'up', // 'up', 'down', 'neutral'
    'accent' => 'blue',  // 'emerald', 'blue', 'amber', 'rose', 'indigo', 'violet'
    'subtitle' => null,
])

@php
    $accentMap = [
        'emerald' => [
            'bg' => 'bg-emerald-50',
            'text' => 'text-emerald-600',
            'border' => 'border-emerald-200/60',
            'iconBg' => 'bg-gradient-to-br from-emerald-500 to-teal-600 text-white shadow-emerald-200',
            'glow' => 'from-emerald-500/10 to-teal-500/5',
        ],
        'blue' => [
            'bg' => 'bg-blue-50',
            'text' => 'text-blue-600',
            'border' => 'border-blue-200/60',
            'iconBg' => 'bg-gradient-to-br from-blue-500 to-indigo-600 text-white shadow-blue-200',
            'glow' => 'from-blue-500/10 to-indigo-500/5',
        ],
        'amber' => [
            'bg' => 'bg-amber-50',
            'text' => 'text-amber-600',
            'border' => 'border-amber-200/60',
            'iconBg' => 'bg-gradient-to-br from-amber-500 to-orange-600 text-white shadow-amber-200',
            'glow' => 'from-amber-500/10 to-orange-500/5',
        ],
        'rose' => [
            'bg' => 'bg-rose-50',
            'text' => 'text-rose-600',
            'border' => 'border-rose-200/60',
            'iconBg' => 'bg-gradient-to-br from-rose-500 to-red-600 text-white shadow-rose-200',
            'glow' => 'from-rose-500/10 to-red-500/5',
        ],
        'violet' => [
            'bg' => 'bg-violet-50',
            'text' => 'text-violet-600',
            'border' => 'border-violet-200/60',
            'iconBg' => 'bg-gradient-to-br from-violet-500 to-purple-600 text-white shadow-violet-200',
            'glow' => 'from-violet-500/10 to-purple-500/5',
        ],
        'indigo' => [
            'bg' => 'bg-indigo-50',
            'text' => 'text-indigo-600',
            'border' => 'border-indigo-200/60',
            'iconBg' => 'bg-gradient-to-br from-indigo-500 to-blue-600 text-white shadow-indigo-200',
            'glow' => 'from-indigo-500/10 to-blue-500/5',
        ],
    ];

    $style = $accentMap[$accent] ?? $accentMap['blue'];
@endphp

<div {{ $attributes->merge(['class' => "relative overflow-hidden rounded-2xl bg-white/90 backdrop-blur-xl border border-slate-200/80 p-5 shadow-sm hover:shadow-md transition-all duration-300 group"]) }}>
    {{-- Ambient gradient glow background --}}
    <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-gradient-to-br {{ $style['glow'] }} blur-2xl group-hover:scale-150 transition-transform duration-500"></div>

    <div class="relative z-10 flex items-start justify-between gap-4">
        <div class="space-y-1 min-w-0">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-500 truncate">
                {{ $title }}
            </p>
            <div class="flex items-baseline gap-2.5 flex-wrap">
                <span class="text-2xl lg:text-3xl font-extrabold tracking-tight text-slate-900">
                    {{ $value }}
                </span>
                @if($trend !== null)
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold {{ $trendType === 'up' ? 'bg-emerald-100 text-emerald-700' : ($trendType === 'down' ? 'bg-rose-100 text-rose-700' : 'bg-slate-100 text-slate-600') }}">
                        @if($trendType === 'up')
                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19V5m0 0l-7 7m7-7l7 7"/></svg>
                        @elseif($trendType === 'down')
                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m0 0l7-7m-7 7l-7-7"/></svg>
                        @endif
                        {{ $trend }}
                    </span>
                @endif
            </div>
            @if($subtitle)
                <p class="text-xs text-slate-500 font-medium">
                    {{ $subtitle }}
                </p>
            @endif
        </div>

        <div class="flex h-11 w-11 items-center justify-center rounded-xl {{ $style['iconBg'] }} shadow-md shrink-0 transition-transform duration-300 group-hover:scale-110">
            @if($icon === 'chart-bar')
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/></svg>
            @elseif($icon === 'building-office-2' || $icon === 'building-office')
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5s.75 0 .75.75v1.5s0 .75-.75.75H9s-.75 0-.75-.75v-1.5s0-.75.75-.75zm0 5.25h1.5s.75 0 .75.75v1.5s0 .75-.75.75H9s-.75 0-.75-.75v-1.5s0-.75.75-.75zm0 5.25h1.5s.75 0 .75.75v1.5s0 .75-.75.75H9s-.75 0-.75-.75v-1.5s0-.75.75-.75zm4.5-10.5h1.5s.75 0 .75.75v1.5s0 .75-.75.75h-1.5s-.75 0-.75-.75v-1.5s0-.75.75-.75zm0 5.25h1.5s.75 0 .75.75v1.5s0 .75-.75.75h-1.5s-.75 0-.75-.75v-1.5s0-.75.75-.75zm0 5.25h1.5s.75 0 .75.75v1.5s0 .75-.75.75h-1.5s-.75 0-.75-.75v-1.5s0-.75.75-.75z"/></svg>
            @elseif($icon === 'clock')
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            @elseif($icon === 'exclamation-triangle' || $icon === 'alert')
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
            @elseif($icon === 'shield-check')
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751A11.959 11.959 0 0112 2.714z"/></svg>
            @elseif($icon === 'document-text' || $icon === 'document')
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
            @else
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            @endif
        </div>
    </div>
</div>
