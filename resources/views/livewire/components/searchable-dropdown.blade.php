<x-bootstrap-cdn />
<div>
    <div x-data="{ open: false }" @click.outside="open = false" class="position-relative">
        <label class="form-label">{!! $labelHtml ?? $label !!}</label>
        <input type="text"
            class="form-control @if ($hasError) is-invalid @endif @if ($isSaved) is-valid @endif"
            placeholder="{{ $placeholder }}"
            wire:model.live.debounce.300ms="search"
            @focus="open = true; if (!$wire.results.length) $wire.loadMore()"
            @click="open = true"
            @keydown.escape="open = false"
            @keydown.enter.prevent="$wire.selectCustom($el.value); open = false"
            @blur="if ($el.value && $el.value !== @js($value)) { $wire.selectCustom($el.value); }"
            value="{{ $value }}" />

        <ul class="list-group position-absolute w-100 shadow mt-1"
            style="z-index: 1000; max-height: 200px; overflow-y: auto;"
            x-on:scroll.passive="
                if($el.scrollTop + $el.clientHeight >= $el.scrollHeight - 10) {
                    $wire.loadMore();
                }"
            x-show="open"
            x-cloak
            x-transition>
            @foreach ($results as $item)
                <li class="list-group-item list-group-item-action cursor-pointer"
                    role="button"
                    wire:key="dropdown-item-{{ md5($item) }}"
                    wire:click="select({{ json_encode($item) }})"
                    @click="open = false">
                    {{ $item }}
                </li>
            @endforeach

            @if (!$hasMore && count($results) === 0)
                <li class="list-group-item text-muted">No results found.</li>
            @endif
            @if ($hasMore)
                <li class="list-group-item text-center text-muted">Loading more...</li>
            @endif
        </ul>
    </div>
</div>
