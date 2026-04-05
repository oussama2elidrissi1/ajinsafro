<section class="reservation-create__panel" data-create-step="5" hidden>
    <div class="reservation-create__card">
        <div class="reservation-create__section-head">
            <div>
                <p class="reservation-create__eyebrow">Étape 5</p>
                <h3 class="reservation-create__section-title">Paiement et validation</h3>
                <p class="reservation-create__section-subtitle">Finalisez la réservation avec un bloc clair pour le paiement, le visa et les justificatifs.</p>
            </div>
        </div>

        <div class="reservation-create__final-grid">
            <div class="reservation-create__final-summary">
                <p class="reservation-create__mini-title">Récapitulatif final</p>
                <div class="reservation-create__final-line">
                    <span>Prestation</span>
                    <strong id="create-final-trip">Aucune sélection</strong>
                </div>
                <div class="reservation-create__final-line">
                    <span>Départ</span>
                    <strong id="create-final-departure">—</strong>
                </div>
                <div class="reservation-create__final-line">
                    <span>Voyageurs</span>
                    <strong id="create-final-travelers">1</strong>
                </div>
                <div class="reservation-create__final-line">
                    <span>Extras</span>
                    <strong id="create-final-extras">0 DH</strong>
                </div>
                <div class="reservation-create__final-line reservation-create__final-line--total">
                    <span>Total dossier</span>
                    <strong id="create-final-total">—</strong>
                </div>
            </div>

            <div class="reservation-create__payment-card">
                <div class="reservation-create__grid reservation-create__grid--two">
                    <div class="reservation-create__field">
                        <label class="reservation-create__label" for="payment_type">Mode de paiement</label>
                        <select name="payment_type" id="payment_type" class="reservation-create__input">
                            <option value="">Sélectionner…</option>
                            <option value="CASHPLUS" {{ old('payment_type') === 'CASHPLUS' ? 'selected' : '' }}>CashPlus</option>
                            <option value="VIREMENT" {{ old('payment_type') === 'VIREMENT' ? 'selected' : '' }}>Virement</option>
                            <option value="ESPECE" {{ old('payment_type') === 'ESPECE' ? 'selected' : '' }}>Espèce</option>
                        </select>
                    </div>
                    <div class="reservation-create__field">
                        <label class="reservation-create__label" for="payment_receipt">Justificatif</label>
                        <input type="file" name="payment_receipt" id="payment_receipt" class="reservation-create__input reservation-create__input--file" accept="image/*,.pdf">
                    </div>
                </div>

                <div class="reservation-create__visa-card">
                    <input type="hidden" name="visa_ok" value="0">
                    <label class="reservation-create__toggle">
                        <input class="form-check-input" type="checkbox" name="visa_ok" id="visa_ok" value="1" {{ old('visa_ok', true) ? 'checked' : '' }}>
                        <span>Visa OK, pas d’assistance nécessaire</span>
                    </label>

                    <div id="assistant-visa-block" class="{{ old('visa_ok', true) ? 'd-none' : '' }}">
                        <div class="reservation-create__grid reservation-create__grid--two">
                            <div class="reservation-create__field">
                                <label class="reservation-create__label" for="visa_status">Statut visa</label>
                                <select name="visa_status" id="visa_status" class="reservation-create__input">
                                    <option value="">—</option>
                                    <option value="not_required" {{ old('visa_status') === 'not_required' ? 'selected' : '' }}>Non requis</option>
                                    <option value="pending" {{ old('visa_status') === 'pending' ? 'selected' : '' }}>En attente</option>
                                    <option value="approved" {{ old('visa_status') === 'approved' ? 'selected' : '' }}>Approuvé</option>
                                    <option value="rejected" {{ old('visa_status') === 'rejected' ? 'selected' : '' }}>Refusé</option>
                                </select>
                            </div>
                            <div class="reservation-create__field">
                                <label class="reservation-create__label" for="visa_document">Document visa</label>
                                <input type="file" name="visa_document" id="visa_document" class="reservation-create__input reservation-create__input--file" accept="image/*,.pdf">
                            </div>
                            <div class="reservation-create__field reservation-create__field--full">
                                <label class="reservation-create__label" for="visa_notes">Notes visa</label>
                                <textarea name="visa_notes" id="visa_notes" class="reservation-create__input reservation-create__input--textarea" rows="4" placeholder="Suivi visa, remarques internes, pièces manquantes…">{{ old('visa_notes') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="reservation-create__actions reservation-create__actions--final">
        <button type="button" class="reservation-create__button reservation-create__button--secondary" data-create-prev>Retour</button>
        <div class="reservation-create__submit-group">
            <a href="{{ route('admin.reservations.workspace') }}" class="reservation-create__button reservation-create__button--ghost">Annuler</a>
            <button type="submit" class="reservation-create__button reservation-create__button--primary">Confirmer la réservation</button>
        </div>
    </div>
</section>
