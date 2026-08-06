@props([
    'percent' => 0,
    'size' => 'md', // 'sm', 'md', 'lg', 'xl'
    'strokeWidth' => 8,
    'showLabel' => true,
    'labelSub' => null,
])

@php
    $clampedPercent = max(0, min(100, (float) $percent));

    $dimensions = match ($size) {
        'sm' => ['px' => 48, 'radius' => 18, 'text' => 'text-xs font-bold', 'subText' => 'text-[9px]'],
        'md' => ['px' => 72, 'radius' => 28, 'text' => 'text-sm font-extrabold', 'subText' => 'text-[10px]'],
        'lg' => ['px' => 110, 'radius' => 45, 'text' => 'text-xl font-black', 'subText' => 'text-xs'],
        'xl' => ['px' => 150, 'radius' => 62, 'text' => 'text-3xl font-black', 'subText' => 'text-xs'],
        default => ['px' => 72, 'radius' => 28, 'text' => 'text-sm font-extrabold', 'subText' => 'text-[10px]'],
    };

    $r = $dimensions['radius'];
    $circumference = 2 * M_PI * $r;
    $offset = $circumference - ($clampedPercent / 100) * $circumference;

    // Color thresholds
    $colorClass = match (true) {
        $clampedPercent >= 80 => 'text-emerald-500 stroke-emerald-500',
        $clampedPercent >= 60 => 'text-amber-500 stroke-amber-500',
        default => 'text-rose-500 stroke-rose-500',
    };

    $gradientId = 'progress-ring-gradient-' . Str::random(8);
@endphp

<div {{ $attributes->merge(['class' => "relative inline-flex items-center justify-center shrink-0"]) }} style="width: {{ $dimensions['px'] }}px; height: {{ $dimensions['px'] }}px;">
    <svg class="transform -rotate-90 w-full h-full" viewBox="0 0 {{ $dimensions['px'] }} {{ $dimensions['px'] }}">
        <defs>
            @if($clampedPercent >= 80)
                <linearGradient id="{{ $gradientId }}" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="#10b981" />
                    <stop offset="100%" stop-color="#14b8a6" />
                </linearGradient>
            @elseif($clampedPercent >= 60)
                <linearGradient id="{{ $gradientId }}" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="#f59e0b" />
                    <stop offset="100%" stop-color="#f97316" />
                </linearGradient>
            @else
                <linearGradient id="{{ $gradientId }}" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="#f43f5e" />
                    <stop offset="100%" stop-color="#e11d48" />
                </linearGradient>
            @endif
        </defs>

        {{-- Background ring --}}
        <circle
            cx="{{ $dimensions['px'] / 2 }}"
            cy="{{ $dimensions['px'] / 2 }}"
            r="{{ $r }}"
            stroke-width="{{ $strokeWidth }}"
            class="stroke-slate-100 dark:stroke-slate-800"
            fill="transparent"
        />

        {{-- Animated progress ring --}}
        <circle
            cx="{{ $dimensions['px'] / 2 }}"
            cy="{{ $dimensions['px'] / 2 }}"
            r="{{ $r }}"
            stroke-width="{{ $strokeWidth }}"
            stroke="url(#{{ $gradientId }})"
            stroke-dasharray="{{ $circumference }}"
            stroke-dashoffset="{{ $offset }}"
            stroke-linecap="round"
            fill="transparent"
            class="transition-all duration-1000 ease-out"
        />
    </svg>

    @if($showLabel)
        <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
            <span class="{{ $dimensions['text'] }} {{ $colorClass }} tracking-tight leading-none">
                {{ (int)$clampedPercent }}%
            </span>
            @if($labelSub)
                <span class="{{ $dimensions['subText'] }} text-slate-400 font-bold uppercase tracking-wider mt-0.5">
                    {{ $labelSub }}
                </span>
            @endif
        </div>
    @endif
</div>
