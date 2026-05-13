<section class="reservation-create__panel" data-create-step="3" data-reservation-step="3" hidden>
    <div class="reservation-create__card">
        <div class="reservation-create__section-head">
            <div>
                <p class="reservation-create__eyebrow">Etape 3</p>
                <h3 class="reservation-create__section-title">Chambres</h3>
                <p class="reservation-create__section-subtitle">Repartissez les voyageurs saisis a l'etape precedente selon le stock disponible et les regles de rooming.</p>
            </div>
            <span class="reservation-create__pill" id="rooming-status-pill">Rooming pending</span>
        </div>

        <div class="reservation-create__rooming-grid">
            <div class="reservation-create__rooming-panel">
                <p class="reservation-create__mini-title">Resume voyageurs</p>
                <div class="reservation-create__traveler-stats reservation-create__traveler-stats--stacked">
                    <span>Total: <strong data-rooming-stat="total">1</strong></span>
                    <span>Adultes: <strong data-rooming-stat="adult">1</strong></span>
                    <span>Enfants: <strong data-rooming-stat="child">0</strong></span>
                    <span>Bebes: <strong data-rooming-stat="infant">0</strong></span>
                    <span>Hommes: <strong data-rooming-stat="male">0</strong></span>
                    <span>Femmes: <strong data-rooming-stat="female">0</strong></span>
                    <span>Sexe non renseigne: <strong data-rooming-stat="gender_unknown">1</strong></span>
                    <span>Lits a couvrir: <strong data-rooming-stat="beds">1</strong></span>
                </div>

                <p class="reservation-create__mini-title mt-3">Voyageurs a affecter</p>
                <div id="rooming-unassigned-travelers" class="reservation-create__traveler-pool"></div>
            </div>

            <div class="reservation-create__rooming-panel">
                <p class="reservation-create__mini-title">Chambres disponibles</p>
                <div id="rooming-available-rooms" class="reservation-create__available-rooms">
                    Selectionnez un depart a l'etape Prestation.
                </div>
            </div>
        </div>

        <div class="reservation-create__rooming-actions">
            <button type="button" class="reservation-create__button reservation-create__button--primary" id="btn-auto-rooming">Repartition automatique</button>
            <button type="button" class="reservation-create__button reservation-create__button--ghost" id="btn-add-room-allocation">Ajouter chambre</button>
            <button type="button" class="reservation-create__button reservation-create__button--secondary" id="btn-reset-rooming">Reinitialiser</button>
        </div>

        <div class="reservation-create__rooming-board" id="rooming-allocation-board"></div>

        <div class="reservation-create__alert reservation-create__alert--warn d-none" id="rooming-alerts"></div>
    </div>

    <div class="reservation-create__actions">
        <button type="button" class="reservation-create__button reservation-create__button--secondary" data-create-prev data-step-back="2">Retour</button>
        <button type="button" class="reservation-create__button reservation-create__button--primary" data-create-next data-step-next="4">Continuer</button>
    </div>
</section>
