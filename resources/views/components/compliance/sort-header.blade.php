@props([
    'field' => '',
    'label' => '',
    'currentSort' => '',
    'currentDir' => 'asc',
    'wireAction' => 'sortBy',
])

@php
    $isActive = $currentSort === $field;
@endphp

<th {{ $attributes->merge(['class' => 'px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500 select-none']) }}>
    <button
        type="button"
        wire:click="{{ $wireAction }}('{{ $field }}')"
        class="inline-flex items-center gap-1.5 group hover:text-slate-900 transition-colors focus:outline-none"
    >
        <span class="{{ $isActive ? 'text-blue-600 font-extrabold' : '' }}">
            {{ $label }}
        </span>

        <span class="inline-flex flex-col text-slate-400 transition-colors group-hover:text-slate-600">
            @if($isActive)
                @if($currentDir === 'asc')
                    <svg class="h-3.5 w-3.5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5"/></svg>
                @else
                    <svg class="h-3.5 w-3.5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                @endif
            @else
                <svg class="h-3.5 w-3.5 opacity-40 group-hover:opacity-100" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9"/></svg>
            @endif
        </span>
    </button>
</th>
