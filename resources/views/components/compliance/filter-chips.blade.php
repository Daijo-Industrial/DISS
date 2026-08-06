@props([
    'options' => [], // [ ['value' => '', 'label' => 'All', 'count' => 12], ... ]
    'selected' => '',
    'wireModel' => null,
])

<div {{ $attributes->merge(['class' => "flex items-center gap-1.5 p-1 rounded-2xl bg-slate-100/90 border border-slate-200/70 backdrop-blur-md overflow-x-auto custom-scrollbar"]) }}>
    @foreach($options as $opt)
        @php
            $val = (string)($opt['value'] ?? '');
            $lbl = $opt['label'] ?? '';
            $cnt = $opt['count'] ?? null;
            $clr = $opt['color'] ?? 'blue';
            $isActive = (string)$selected === $val;

            $activeClasses = match ($clr) {
                'emerald' => 'bg-emerald-600 text-white shadow-sm shadow-emerald-500/20',
                'amber' => 'bg-amber-500 text-white shadow-sm shadow-amber-500/20',
                'rose' => 'bg-rose-600 text-white shadow-sm shadow-rose-500/20',
                'violet' => 'bg-violet-600 text-white shadow-sm shadow-violet-500/20',
                default => 'bg-blue-600 text-white shadow-sm shadow-blue-500/20',
            };
        @endphp

        <button
            type="button"
            @if($wireModel)
                wire:click="$set('{{ $wireModel }}', '{{ $val }}')"
            @endif
            class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition-all duration-200 shrink-0 select-none {{ $isActive ? $activeClasses : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/70' }}"
        >
            <span>{{ $lbl }}</span>

            @if($cnt !== null)
                <span class="px-1.5 py-0.2 text-[10px] font-extrabold rounded-full transition-colors {{ $isActive ? 'bg-white/25 text-white' : 'bg-slate-200 text-slate-600' }}">
                    {{ $cnt }}
                </span>
            @endif
        </button>
    @endforeach
</div>
