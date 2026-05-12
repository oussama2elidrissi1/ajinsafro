<section class="reservation-create__panel" data-create-step="5" hidden>
    <div class="reservation-create__card">
        <div class="reservation-create__section-head">
            <div>
                <p class="reservation-create__eyebrow">Étape 5</p>
                <h3 class="reservation-create__section-title">Paiement et validation</h3>
                <p class="reservation-create__section-subtitle">Saisissez l'acompte ou le paiement du jour et vérifiez immédiatement le statut financier du dossier.</p>
            </div>
        </div>

        <div class="reservation-create__final-grid">
            <div class="reservation-create__financial-card">
                <p class="reservation-create__mini-title">Récapitulatif financier</p>
                <div class="reservation-create__final-line">
                    <span>Total base</span>
                    <strong id="create-financial-total-base">0 DH</strong>
                </div>
                <div class="reservation-create__final-line">
                    <span>Suppléments chambres</span>
                    <strong id="create-financial-room-supplement">0 DH</strong>
                </div>
                <div class="reservation-create__final-line">
                    <span>Total extras</span>
                    <strong id="create-financial-extras">0 DH</strong>
                </div>
                <div class="reservation-create__final-line reservation-create__final-line--total">
                    <span>Total dossier</span>
                    <strong id="create-financial-total-amount">0 DH</strong>
                </div>
                <div class="reservation-create__final-line reservation-create__final-line--success">
                    <span>Total payé</span>
                    <strong id="create-financial-paid-amount">0 DH</strong>
                </div>
                <div class="reservation-create__final-line reservation-create__final-line--warning">
                    <span>Reste à payer</span>
                    <strong id="create-financial-remaining-amount">0 DH</strong>
                </div>
                <div class="reservation-create__final-line">
                    <span>Statut paiement</span>
                    <strong id="create-financial-payment-status">Non payé</strong>
                </div>
            </div>

            <div class="reservation-create__payment-card">
                <p class="reservation-create__mini-title">Nouveau paiement</p>
                <div class="reservation-create__grid reservation-create__grid--two">
                    <div class="reservation-create__field">
                        <label class="reservation-create__label" for="payment_date">Date paiement</label>
                        <input type="date" name="payment_date" id="payment_date" class="reservation-create__input" value="{{ old('payment_date', now()->toDateString()) }}">
                    </div>
                    <div class="reservation-create__field">
                        <label class="reservation-create__label" for="payment_type">Mode de paiement</label>
                        <select name="payment_type" id="payment_type" class="reservation-create__input">
                            <option value="">Sélectionner…</option>
                            <option value="Espèces" {{ old('payment_type') === 'Espèces' ? 'selected' : '' }}>Espèces</option>
                            <option value="Virement bancaire" {{ old('payment_type') === 'Virement bancaire' ? 'selected' : '' }}>Virement bancaire</option>
                            <option value="Carte bancaire" {{ old('payment_type') === 'Carte bancaire' ? 'selected' : '' }}>Carte bancaire</option>
                            <option value="Chèque" {{ old('payment_type') === 'Chèque' ? 'selected' : '' }}>Chèque</option>
                            <option value="TPE" {{ old('payment_type') === 'TPE' ? 'selected' : '' }}>TPE</option>
                            <option value="Autre" {{ old('payment_type') === 'Autre' ? 'selected' : '' }}>Autre</option>
                        </select>
                    </div>
                    <div class="reservation-create__field">
                        <label class="reservation-create__label" for="payment_amount">Montant payé</label>
                        <input type="number" name="payment_amount" id="payment_amount" class="reservation-create__input" value="{{ old('payment_amount', 0) }}" min="0" step="0.01" placeholder="0.00">
                    </div>
                    <div class="reservation-create__field">
                        <label class="reservation-create__label" for="payment_reference">Référence paiement</label>
                        <input type="text" name="payment_reference" id="payment_reference" class="reservation-create__input" value="{{ old('payment_reference') }}" placeholder="Reçu, transaction, bordereau…">
                    </div>
                    <div class="reservation-create__field">
                        <label class="reservation-create__label" for="payment_receipt">Justificatif</label>
                        <input type="file" name="payment_receipt" id="payment_receipt" class="reservation-create__input reservation-create__input--file" accept="image/*,.pdf">
                    </div>
                    <div class="reservation-create__field reservation-create__field--full">
                        <label class="reservation-create__label" for="payment_note">Note interne</label>
                        <textarea name="payment_note" id="payment_note" class="reservation-create__input reservation-create__input--textarea" rows="4" placeholder="Détail du règlement, échéance, point de suivi…">{{ old('payment_note') }}</textarea>
                    </div>
                </div>
                <p class="reservation-create__helper" id="create-payment-help">Le montant payé ne peut pas dépasser le total du dossier.</p>
            </div>
        </div>
    </div>

    <div class="reservation-create__actions">
        <button type="button" class="reservation-create__button reservation-create__button--secondary" data-create-prev>Retour</button>
        <button type="button" class="reservation-create__button reservation-create__button--primary" data-create-next>Continuer</button>
    </div>
</section>
