@props(['vehicle' => null])

{{-- UNIVERSAL LIGHTBOX MODULE (Fancybox Bridge) --}}
<div x-data
    x-init="
        if (typeof $wire !== 'undefined') {
            $watch('$wire.showLightbox', val => {
                if (val && window.Fancybox) {
                    window.Fancybox.show([{
                        src: '{{ asset('storage/' . ($vehicle?->image_path ?? '')) }}',
                        caption: 'Foto Profil Armada {{ $vehicle?->plate_number ?? '' }}',
                        type: 'image',
                        on: {
                            destroy: () => {
                                $wire.set('showLightbox', false);
                            }
                        }
                    }]);
                }
            });
        }
    ">
</div>
