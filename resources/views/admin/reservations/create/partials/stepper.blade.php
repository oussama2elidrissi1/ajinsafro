<aside class="reservation-create__sidebar">
    <div class="reservation-create__steps-card">
        <p class="reservation-create__eyebrow">Workflow</p>
        <h2 class="reservation-create__sidebar-title">Nouvelle réservation</h2>
        <div class="reservation-create__steps" role="tablist" aria-label="Étapes de création">
            <button type="button" class="reservation-create__step is-active" data-create-step-nav="1">
                <span class="reservation-create__step-index">1</span>
                <span class="reservation-create__step-label">Prestation</span>
            </button>
            <button type="button" class="reservation-create__step" data-create-step-nav="2">
                <span class="reservation-create__step-index">2</span>
                <span class="reservation-create__step-label">Voyageurs</span>
            </button>
            <button type="button" class="reservation-create__step" data-create-step-nav="3">
                <span class="reservation-create__step-index">3</span>
                <span class="reservation-create__step-label">Chambres</span>
            </button>
            <button type="button" class="reservation-create__step" data-create-step-nav="4">
                <span class="reservation-create__step-index">4</span>
                <span class="reservation-create__step-label">Extras</span>
            </button>
            <button type="button" class="reservation-create__step" data-create-step-nav="5">
                <span class="reservation-create__step-index">5</span>
                <span class="reservation-create__step-label">Paiement</span>
            </button>
            <button type="button" class="reservation-create__step" data-create-step-nav="6">
                <span class="reservation-create__step-index">6</span>
                <span class="reservation-create__step-label">Dossier</span>
            </button>
        </div>
    </div>

    <div class="reservation-create__summary-card">
        <p class="reservation-create__eyebrow">Résumé rapide</p>
        <div class="reservation-create__summary-item">
            <span>Prestation</span>
            <strong id="create-summary-trip">Aucune sélection</strong>
        </div>
        <div class="reservation-create__summary-item">
            <span>Départ</span>
            <strong id="create-summary-departure">—</strong>
        </div>
        <div class="reservation-create__summary-item">
            <span>Voyageurs</span>
            <strong id="create-summary-travelers">1</strong>
        </div>
        <div class="reservation-create__summary-item">
            <span>Prix unitaire</span>
            <strong id="create-summary-unit-price">—</strong>
        </div>
        <div class="reservation-create__summary-item">
            <span>Réduction</span>
            <strong id="create-summary-discount">Aucune</strong>
        </div>
        <div class="reservation-create__summary-item">
            <span>Prix après réduction</span>
            <strong id="create-summary-price-after-discount">—</strong>
        </div>
        <div class="reservation-create__summary-item">
            <span>Total provisoire</span>
            <strong id="create-summary-total">—</strong>
        </div>
        <div class="reservation-create__summary-item">
            <span>Total payé</span>
            <strong id="create-summary-paid">0 DH</strong>
        </div>
        <div class="reservation-create__summary-item">
            <span>Reste à payer</span>
            <strong id="create-summary-remaining">0 DH</strong>
        </div>
    </div>
</aside>
