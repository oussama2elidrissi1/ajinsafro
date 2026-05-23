@php
    $fastDeparture = $selectedDeparture ?? null;
    $fastTour = $preselectedTour ?? null;
    $fastUnitPrice = $selectedUnitPrice ?? null;
@endphp

<div class="reservation-fast-summary">
    <p class="reservation-create__eyebrow">Résumé</p>

    <div class="reservation-fast-summary__item">
        <span>Offre</span>
        <strong>{{ $fastTour?->name ?? '—' }}</strong>
    </div>
    <div class="reservation-fast-summary__item">
        <span>Départ</span>
        <strong>
            @if($fastDeparture?->start_date)
                {{ $fastDeparture->start_date->format('d/m/Y') }}
                @if($fastDeparture?->end_date) → {{ $fastDeparture->end_date->format('d/m/Y') }} @endif
            @else
                —
            @endif
        </strong>
    </div>
    <div class="reservation-fast-summary__item">
        <span>Voyageurs</span>
        <strong id="create-summary-travelers">1</strong>
    </div>
    <div class="reservation-fast-summary__item">
        <span>Prix unitaire</span>
        <strong id="create-summary-unit-price">{{ $fastUnitPrice !== null ? number_format((float) $fastUnitPrice, 2, ',', ' ').' DH' : '—' }}</strong>
    </div>
    <div class="reservation-fast-summary__item">
        <span>Réduction</span>
        <strong id="create-summary-discount">Aucune</strong>
    </div>
    <div class="reservation-fast-summary__item">
        <span>Extras</span>
        <strong id="create-summary-extras">0 DH</strong>
    </div>
    <div class="reservation-fast-summary__item reservation-fast-summary__item--total">
        <span>Total</span>
        <strong id="create-summary-total">—</strong>
    </div>
    <div class="reservation-fast-summary__item">
        <span>Payé</span>
        <strong id="create-summary-paid">0 DH</strong>
    </div>
    <div class="reservation-fast-summary__item reservation-fast-summary__item--remaining">
        <span>Reste à payer</span>
        <strong id="create-summary-remaining">0 DH</strong>
    </div>
</div>
