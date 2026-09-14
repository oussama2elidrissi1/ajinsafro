@php
    $fastDeparture = $selectedDeparture ?? null;
    $fastTour = $preselectedTour ?? null;
    $fastUnitPrice = $selectedUnitPrice ?? null;
    $agentReservationMode = (bool) request()->attributes->get('agent_reservation_mode', false);
    $cancelHref = $agentReservationMode ? route('agent.catalogue') : route('admin.reservations.workspace');

    $fastSummaryDates = null;
    if ($fastDeparture?->start_date) {
        $fastSummaryDates = $fastDeparture->start_date->format('d/m');
        $fastSummaryDates .= $fastDeparture->end_date
            ? ' → ' . $fastDeparture->end_date->format('d/m/Y')
            : '/' . $fastDeparture->start_date->format('Y');
    }
@endphp

<div class="reservation-fast-summary">
    <div class="reservation-fast-summary__head">
        <p class="reservation-create__eyebrow">Résumé</p>
        <div class="reservation-fast-summary__offer">{{ $fastTour?->name ?? '—' }}</div>
        @if ($fastSummaryDates)
            <div class="reservation-fast-summary__dates">{{ $fastSummaryDates }}</div>
        @endif
    </div>

    <div class="reservation-fast-summary__lines">
        <div class="reservation-fast-summary__item">
            <span>Prix unitaire</span>
            <strong id="create-summary-unit-price">{{ $fastUnitPrice !== null ? number_format((float) $fastUnitPrice, 0, ',', ' ').' DH' : '—' }}</strong>
        </div>
        <div class="reservation-fast-summary__item" data-summary-line="adult">
            <span>Adultes ×<span data-summary-count="adult">1</span></span>
            <strong data-summary-amount="adult">—</strong>
        </div>
        <div class="reservation-fast-summary__item" data-summary-line="child" hidden>
            <span>Enfants ×<span data-summary-count="child">0</span></span>
            <strong data-summary-amount="child">—</strong>
        </div>
        <div class="reservation-fast-summary__item" data-summary-line="infant" hidden>
            <span>Bébés ×<span data-summary-count="infant">0</span></span>
            <strong data-summary-amount="infant">—</strong>
        </div>
        <div class="reservation-fast-summary__item" data-summary-line="pending" hidden>
            <span><span data-summary-count="pending">0</span> fiche(s) à compléter</span>
            <strong class="reservation-fast-summary__note">non facturée(s)</strong>
        </div>
        <div class="reservation-fast-summary__item" id="create-summary-discount-line" hidden>
            <span>Remise</span>
            <strong id="create-summary-discount">Aucune</strong>
        </div>
        <div class="reservation-fast-summary__item">
            <span>Extras ×<span id="fast-extras-count-summary">0</span></span>
            <strong id="create-summary-extras">0 DH</strong>
        </div>
    </div>

    <div class="reservation-fast-summary__totals">
        <div class="reservation-fast-summary__total-row">
            <span class="reservation-fast-summary__total-label">Total</span>
            <span class="reservation-fast-summary__total-value" id="create-summary-total">—</span>
        </div>
        <div class="reservation-fast-summary__total-row reservation-fast-summary__total-row--paid">
            <span>Payé</span>
            <strong id="create-summary-paid">0 DH</strong>
        </div>
        <div class="reservation-fast-summary__total-row reservation-fast-summary__total-row--remaining">
            <span>Reste à payer</span>
            <strong id="create-summary-remaining">0 DH</strong>
        </div>
    </div>

    <div class="reservation-fast-summary__cta">
        <button type="button" class="reservation-fast-summary__primary" data-fast-cta-primary>
            <span data-fast-cta-label>Continuer</span>
            <span aria-hidden="true">→</span>
        </button>
        <button type="button" class="reservation-fast-summary__secondary" data-fast-cta-secondary hidden>Retour</button>
        <a href="{{ $cancelHref }}" class="reservation-fast-summary__secondary" data-fast-cta-cancel>Annuler</a>
        <p class="reservation-fast-summary__req" data-fast-cta-note>Champs obligatoires : prénom, nom, téléphone, sexe.</p>
    </div>
</div>
