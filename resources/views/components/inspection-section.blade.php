<x-bootstrap-cdn />
{{-- resources/views/components/inspection-section.blade.php --}}
@props(['parent' => null, 'id', 'title'])
<div class="accordion-item">
    <h2 class="accordion-header" id="hd-{{ $id }}">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
            data-bs-target="#cl-{{ $id }}" aria-expanded="false" aria-controls="cl-{{ $id }}">
            {{ $title }}
        </button>
    </h2>
    <div id="cl-{{ $id }}" class="accordion-collapse collapse" @if(!empty($parent)) data-bs-parent="#{{ $parent }}" @endif
        style="visibility: visible;">
        <div class="accordion-body p-0">
            {{ $slot }}
        </div>
    </div>
</div>
