@props(['id', 'name', 'show' => false, 'maxWidth' => '2xl', 'focusable' => false])

@php
    $hasWireModel = $attributes->wire('model')->value() !== null;
    $modalName = $name ?? $id ?? ($hasWireModel ? md5($attributes->wire('model')) : 'modal-' . uniqid());

    $maxWidthClass = [
        'sm' => 'sm:max-w-sm',
        'md' => 'sm:max-w-md',
        'lg' => 'sm:max-w-lg',
        'xl' => 'sm:max-w-xl',
        '2xl' => 'sm:max-w-2xl',
        '3xl' => 'sm:max-w-3xl',
        '4xl' => 'sm:max-w-4xl',
        '5xl' => 'sm:max-w-5xl',
        'full' => 'sm:max-w-full sm:mx-6',
    ][$maxWidth ?? '2xl'] ?? 'sm:max-w-2xl';
@endphp

<div
    x-data="{
        show: @if($hasWireModel) @entangle($attributes->wire('model')) @else {{ $show ? 'true' : 'false' }} @endif,
        name: '{{ $modalName }}',
        focusables() {
            let selector = 'a, button, input:not([type=\'hidden\']), textarea, select, details, [tabindex]:not([tabindex=\'-1\'])';
            return [...$el.querySelectorAll(selector)].filter(el => !el.hasAttribute('disabled'));
        },
        firstFocusable() { return this.focusables()[0] },
        lastFocusable() { return this.focusables().slice(-1)[0] },
        nextFocusable() { return this.focusables()[this.focusables().indexOf(document.activeElement) + 1] || this.firstFocusable() },
        prevFocusable() { return this.focusables()[this.focusables().indexOf(document.activeElement) - 1] || this.lastFocusable() }
    }"
    x-init="
        $watch('show', value => {
            if (value) {
                document.body.classList.add('overflow-y-hidden');
                {{ $focusable ? 'setTimeout(() => firstFocusable()?.focus(), 100)' : '' }}
            } else {
                document.body.classList.remove('overflow-y-hidden');
            }
        })
    "
    @open-modal.window="if ($event.detail === name || $event.detail?.name === name) show = true"
    @close-modal.window="if (!$event.detail || $event.detail === name || $event.detail?.name === name) show = false"
    @keydown.escape.window="show = false"
    @keydown.tab.prevent="$event.shiftKey ? prevFocusable()?.focus() : nextFocusable()?.focus()"
    x-show="show"
    id="{{ $modalName }}"
    class="relative z-[110]"
    style="display: none;"
    x-cloak>
    
    <template x-teleport="body">
        <div x-show="show" class="fixed inset-0 z-[120] overflow-y-auto px-4 py-6 sm:px-0 flex items-center justify-center min-h-screen">
            {{-- Backdrop --}}
            <div x-show="show"
                class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm transition-opacity"
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click="show = false">
            </div>

            {{-- Modal Panel --}}
            <div x-show="show"
                class="relative w-full {{ $maxWidthClass }} bg-white rounded-3xl shadow-2xl border border-slate-100 overflow-hidden transform transition-all my-8 z-10"
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 -translate-y-2">
                {{ $slot }}
            </div>
        </div>
    </template>
</div>
