<section class="reservation-create__panel" data-create-step="3" data-reservation-step="3" hidden>
    {{-- Échéancier du dossier --}}
    <div class="reservation-fast-card">
        <div class="reservation-fast-card__head">
            <div class="reservation-fast-card__heading">
                <h2 class="reservation-fast-card__title">Échéancier du dossier</h2>
                <p class="reservation-fast-card__subtitle" id="fast-pay-subtitle">Total — · — en saisie</p>
            </div>
            <span class="reservation-fast-pay-state" id="fast-pay-state">AUCUN RÈGLEMENT</span>
        </div>

        <div class="reservation-fast-pay-bar" role="img" aria-labelledby="fast-pay-subtitle">
            <span class="reservation-fast-pay-bar__paid" id="fast-pay-bar-paid"></span>
            <span class="reservation-fast-pay-bar__pending" id="fast-pay-bar-pending"></span>
        </div>
        <div class="reservation-fast-pay-legend">
            <span><b class="is-paid" aria-hidden="true"></b>Déjà encaissé <strong id="fast-pay-paid">0 DH</strong></span>
            <span><b class="is-pending" aria-hidden="true"></b>En saisie <strong id="fast-pay-pending">0 DH</strong></span>
            <span><b class="is-due" aria-hidden="true"></b>Reste <strong id="fast-pay-due">0 DH</strong></span>
        </div>
        <p class="reservation-create__helper mt-2">
            Un seul règlement est enregistré à la création du dossier. Les suivants s'ajoutent
            depuis la fiche de réservation, une fois celle-ci confirmée.
        </p>

        <div class="reservation-fast-pay-presets">
            <div class="reservation-create__label">Montant à encaisser maintenant</div>
            <div class="reservation-fast-pay-presets__grid" id="fast-pay-presets">
                <button type="button" class="reservation-fast-pay-preset" data-pay-preset="0.3">
                    <span class="reservation-fast-pay-preset__label">Acompte 30 %</span>
                    <span class="reservation-fast-pay-preset__amount">—</span>
                </button>
                <button type="button" class="reservation-fast-pay-preset" data-pay-preset="0.5">
                    <span class="reservation-fast-pay-preset__label">Acompte 50 %</span>
                    <span class="reservation-fast-pay-preset__amount">—</span>
                </button>
                <button type="button" class="reservation-fast-pay-preset" data-pay-preset="1">
                    <span class="reservation-fast-pay-preset__label">Solde complet</span>
                    <span class="reservation-fast-pay-preset__amount">—</span>
                </button>
                <button type="button" class="reservation-fast-pay-preset" data-pay-preset="free">
                    <span class="reservation-fast-pay-preset__label">Montant libre</span>
                    <span class="reservation-fast-pay-preset__amount">à saisir</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Enregistrer un règlement --}}
    <div class="reservation-fast-card">
        <div class="reservation-fast-card__head">
            <div class="reservation-fast-card__heading">
                <h2 class="reservation-fast-card__title">Règlement à la création</h2>
                <p class="reservation-fast-card__subtitle" id="fast-pay-cap-hint">Le montant ne peut pas dépasser le total du dossier.</p>
            </div>
        </div>

        <input type="hidden" name="discount_scope" id="reservation-discount-scope" value="total">

        <div class="reservation-fast-grid">
            <label class="reservation-create__field">
                <span class="reservation-create__label">Montant <span class="required-star">*</span></span>
                <span class="reservation-fast-amount">
                    <input type="number" name="payment_amount" id="payment_amount" class="reservation-create__input reservation-create__input--mono" value="{{ old('payment_amount', 0) }}" min="0" step="0.01" placeholder="0.00">
                    <span class="reservation-fast-amount__suffix">DH</span>
                </span>
            </label>
            <label class="reservation-create__field">
                <span class="reservation-create__label">Mode</span>
                <select name="payment_type" id="payment_type" class="reservation-create__input">
                    <option value="">Sélectionner…</option>
                    <option value="Espèces" {{ old('payment_type') === 'Espèces' ? 'selected' : '' }}>Espèces</option>
                    <option value="Virement bancaire" {{ old('payment_type') === 'Virement bancaire' ? 'selected' : '' }}>Virement bancaire</option>
                    <option value="Carte bancaire" {{ old('payment_type') === 'Carte bancaire' ? 'selected' : '' }}>Carte bancaire</option>
                    <option value="Chèque" {{ old('payment_type') === 'Chèque' ? 'selected' : '' }}>Chèque</option>
                    <option value="TPE" {{ old('payment_type') === 'TPE' ? 'selected' : '' }}>TPE</option>
                    <option value="Autre" {{ old('payment_type') === 'Autre' ? 'selected' : '' }}>Autre</option>
                </select>
            </label>
            <label class="reservation-create__field reservation-create__field--compact">
                <span class="reservation-create__label">Date</span>
                <input type="date" name="payment_date" id="payment_date" class="reservation-create__input" value="{{ old('payment_date', now()->toDateString()) }}">
            </label>
        </div>

        <div class="reservation-fast-grid">
            <label class="reservation-create__field">
                <span class="reservation-create__label">Référence</span>
                <input type="text" name="payment_reference" id="payment_reference" class="reservation-create__input" value="{{ old('payment_reference') }}" placeholder="N° reçu ou transaction">
            </label>
            <label class="reservation-create__field">
                <span class="reservation-create__label">Note interne</span>
                <input type="text" name="payment_note" id="payment_note" class="reservation-create__input" value="{{ old('payment_note') }}" placeholder="Détail du règlement…">
            </label>
        </div>

        <div class="reservation-fast-grid">
            <div class="reservation-create__field">
                <span class="reservation-create__label">Justificatif</span>
                <label class="reservation-fast-dropzone" for="payment_receipt">
                    <span class="reservation-fast-dropzone__icon" aria-hidden="true">+</span>
                    <span class="reservation-fast-dropzone__body">
                        <span class="reservation-fast-dropzone__title">Reçu, bordereau ou capture de virement</span>
                        <span class="reservation-fast-dropzone__hint" data-dropzone-hint>PDF ou image, 5 Mo maximum</span>
                    </span>
                    <input type="file" name="payment_receipt" id="payment_receipt" class="reservation-fast-dropzone__input" accept="image/*,.pdf">
                </label>
            </div>
            <div class="reservation-create__field">
                <span class="reservation-create__label">Remise sur le total</span>
                <div class="reservation-fast-discount__control">
                    <input type="number" name="discount_value" id="reservation-discount-value" class="reservation-create__input reservation-create__input--mono" value="{{ old('discount_value') }}" min="0" step="0.01" placeholder="0">
                    <select name="discount_type" id="reservation-discount-type" class="reservation-create__input reservation-fast-discount__type">
                        <option value="percentage" @selected(old('discount_type', 'percentage') === 'percentage')>%</option>
                        <option value="fixed" @selected(old('discount_type') === 'fixed')>DH</option>
                    </select>
                </div>
                <p class="reservation-create__helper">Appliquée une seule fois sur le total du dossier.</p>
            </div>
        </div>

        <div class="reservation-fast-pay-foot">
            <span>Après ce règlement : reste <strong id="fast-pay-after">0 DH</strong></span>
            <span class="reservation-create__helper" id="create-payment-help">Le montant payé ne peut pas dépasser le total du dossier.</span>
        </div>
    </div>

    {{-- Récapitulatif financier --}}
    <div class="reservation-fast-card">
        <div class="reservation-fast-card__head">
            <div class="reservation-fast-card__heading">
                <h2 class="reservation-fast-card__title">Récapitulatif financier</h2>
            </div>
        </div>
        <div class="reservation-fast-figures">
            <div class="reservation-fast-figure">
                <span>Total dossier</span>
                <strong id="create-financial-total-amount">0 DH</strong>
            </div>
            <div class="reservation-fast-figure reservation-fast-figure--paid">
                <span>Total payé</span>
                <strong id="create-financial-paid-amount">0 DH</strong>
            </div>
            <div class="reservation-fast-figure reservation-fast-figure--due">
                <span>Reste à payer</span>
                <strong id="create-financial-remaining-amount">0 DH</strong>
            </div>
            <div class="reservation-fast-figure">
                <span>Prix unitaire après remise</span>
                <strong id="reservation-price-after-discount">—</strong>
            </div>
        </div>
    </div>

    <div class="reservation-create__step-errors" id="step-3-errors" hidden></div>
    <div class="reservation-create__actions">
        <button type="button" class="reservation-create__button reservation-create__button--secondary" data-create-prev data-step-back="2">
            <i class="bx bx-left-arrow-alt" aria-hidden="true"></i><span>Retour</span>
        </button>
        <button type="button" class="reservation-create__button reservation-create__button--primary" data-create-next data-step-next="4">
            <span>Continuer</span><i class="bx bx-right-arrow-alt" aria-hidden="true"></i>
        </button>
    </div>
</section>
