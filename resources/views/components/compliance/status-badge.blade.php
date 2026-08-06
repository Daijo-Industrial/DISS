@props([
    'status' => 'ok', // 'ok'|'compliant', 'warning', 'critical'|'missing', 'pending', 'expired'
    'label' => null,
    'size' => 'sm', // 'xs', 'sm', 'md'
    'dot' => true,
])

@php
    $key = strtolower((string)$status);

    $configs = [
        'ok' => [
            'label' => 'Compliant',
            'bg' => 'bg-emerald-500/10 dark:bg-emerald-950/40',
            'text' => 'text-emerald-700 dark:text-emerald-400',
            'border' => 'border-emerald-500/20 dark:border-emerald-800/40',
            'dot' => 'bg-emerald-500',
        ],
        'compliant' => [
            'label' => 'Compliant',
            'bg' => 'bg-emerald-500/10 dark:bg-emerald-950/40',
            'text' => 'text-emerald-700 dark:text-emerald-400',
            'border' => 'border-emerald-500/20 dark:border-emerald-800/40',
            'dot' => 'bg-emerald-500',
        ],
        'warning' => [
            'label' => 'Warning',
            'bg' => 'bg-amber-500/10 dark:bg-amber-950/40',
            'text' => 'text-amber-700 dark:text-amber-400',
            'border' => 'border-amber-500/20 dark:border-amber-800/40',
            'dot' => 'bg-amber-500',
        ],
        'critical' => [
            'label' => 'Critical Risk',
            'bg' => 'bg-rose-500/10 dark:bg-rose-950/40',
            'text' => 'text-rose-700 dark:text-rose-400',
            'border' => 'border-rose-500/20 dark:border-rose-800/40',
            'dot' => 'bg-rose-500',
        ],
        'missing' => [
            'label' => 'Missing',
            'bg' => 'bg-rose-500/10 dark:bg-rose-950/40',
            'text' => 'text-rose-700 dark:text-rose-400',
            'border' => 'border-rose-500/20 dark:border-rose-800/40',
            'dot' => 'bg-rose-500',
        ],
        'pending' => [
            'label' => 'Pending Review',
            'bg' => 'bg-blue-500/10 dark:bg-blue-950/40',
            'text' => 'text-blue-700 dark:text-blue-400',
            'border' => 'border-blue-500/20 dark:border-blue-800/40',
            'dot' => 'bg-blue-500 animate-pulse',
        ],
        'expired' => [
            'label' => 'Expired',
            'bg' => 'bg-slate-100 dark:bg-slate-800',
            'text' => 'text-slate-700 dark:text-slate-300',
            'border' => 'border-slate-200 dark:border-slate-700',
            'dot' => 'bg-slate-400',
        ],
    ];

    $cfg = $configs[$key] ?? [
        'label' => ucfirst($key),
        'bg' => 'bg-slate-100 dark:bg-slate-800',
        'text' => 'text-slate-700 dark:text-slate-300',
        'border' => 'border-slate-200 dark:border-slate-700',
        'dot' => 'bg-slate-400',
    ];

    $sizeClasses = [
        'xs' => 'px-2 py-0.5 text-[11px] gap-1',
        'sm' => 'px-2.5 py-0.5 text-xs gap-1.5',
        'md' => 'px-3 py-1 text-sm gap-2',
    ][$size] ?? 'px-2.5 py-0.5 text-xs gap-1.5';

    $displayLabel = $label ?? $cfg['label'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center font-bold rounded-full border backdrop-blur-sm shadow-2xs {$cfg['bg']} {$cfg['text']} {$cfg['border']} {$sizeClasses} shrink-0 transition-colors"]) }}>
    @if($dot)
        <span class="h-1.5 w-1.5 rounded-full {{ $cfg['dot'] }}"></span>
    @endif
    <span>{{ $displayLabel }}</span>
</span>
