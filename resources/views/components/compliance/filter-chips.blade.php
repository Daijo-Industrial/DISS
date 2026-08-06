@props([
    'options' => [], // [ ['value' => '', 'label' => 'All', 'count' => 12], ... ]
    'selected' => '',
    'wireModel' => null,
])

<div {{ $attributes->merge(['class' => "flex items-center gap-1.5 p-1 rounded-2xl bg-slate-100/80 dark:bg-slate-950/60 border border-slate-200/60 dark:border-slate-800/60 backdrop-blur-md overflow-x-auto custom-scrollbar"]) }}>
    @foreach($options as $opt)
        @php
            $val = (string)($opt['value'] ?? '');
            $lbl = $opt['label'] ?? '';
            $cnt = $opt['count'] ?? null;
            $clr = $opt['color'] ?? 'blue';
            $isActive = (string)$selected === $val;

            $activeClasses = match ($clr) {
                'emerald' => 'bg-emerald-600 text-white shadow-md shadow-emerald-500/25',
                'amber' => 'bg-amber-500 text-white shadow-md shadow-amber-500/25',
                'rose' => 'bg-rose-600 text-white shadow-md shadow-rose-500/25',
                'violet' => 'bg-violet-600 text-white shadow-md shadow-violet-500/25',
                default => 'bg-blue-600 text-white shadow-md shadow-blue-500/25',
            };
        @endphp

        <button
            type="button"
            @if($wireModel)
                wire:click="$set('{{ $wireModel }}', '{{ $val }}')"
            @endif
            class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition-all duration-200 shrink-0 select-none {{ $isActive ? $activeClasses : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-200/60 dark:hover:bg-slate-800/60' }}"
        >
            <span>{{ $lbl }}</span>

            @if($cnt !== null)
                <span class="px-1.5 py-0.2 text-[10px] font-extrabold rounded-full transition-colors {{ $isActive ? 'bg-white/20 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400' }}">
                    {{ $cnt }}
                </span>
            @endif
        </button>
    @endforeach
</div>
