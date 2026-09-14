@php
    $fastSteps = [
        1 => 'Client & voyageurs',
        2 => 'Chambres & extras',
        3 => 'Paiement',
        4 => 'Confirmation',
    ];
@endphp

<div class="reservation-create__workflow">
    <div class="reservation-create__steps-card reservation-create__steps-card--workflow">
        <div class="reservation-create__steps reservation-create__steps--chevrons" role="tablist" aria-label="Étapes de création">
            @foreach ($fastSteps as $number => $label)
                <button
                    type="button"
                    class="reservation-create__step {{ $number === 1 ? 'is-active' : '' }}"
                    data-create-step-nav="{{ $number }}"
                    role="tab"
                    aria-selected="{{ $number === 1 ? 'true' : 'false' }}"
                >
                    <span class="reservation-create__step-index" data-step-index>{{ $number }}</span>
                    <span class="reservation-create__step-text">
                        <span class="reservation-create__step-kicker" data-step-kicker>{{ $number === 1 ? 'En cours' : 'Étape ' . $number }}</span>
                        <span class="reservation-create__step-label">{{ $label }}</span>
                    </span>
                </button>
            @endforeach
        </div>
    </div>
</div>
