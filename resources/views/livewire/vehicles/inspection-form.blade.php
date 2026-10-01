{{-- ========================================================================= --}}
{{-- P2H DAILY VEHICLE INSPECTION FORM (3-STEP RESPONSIVE WIZARD)             --}}
{{-- ========================================================================= --}}
<div class="max-w-5xl mx-auto px-3 sm:px-6 py-5 space-y-6">
    {{-- Stepper Progress & Vehicle Mini-Cockpit --}}
    @include('livewire.vehicles.partials.inspection-stepper')

    {{-- Step 1: Telemetry, Driver & Fuel --}}
    @if ($currentStep === 1)
        @include('livewire.vehicles.partials.inspection-step-1-telemetry')
    @endif

    {{-- Step 2: 4-Zone Physical Checklist --}}
    @if ($currentStep === 2)
        @include('livewire.vehicles.partials.inspection-step-2-checklist')
    @endif

    {{-- Step 3: Defect Evaluation, Summary & Submit --}}
    @if ($currentStep === 3)
        @include('livewire.vehicles.partials.inspection-step-3-summary')
    @endif

    {{-- Universal Photo Lightbox Modal --}}
    <x-universal-lightbox />
</div>
