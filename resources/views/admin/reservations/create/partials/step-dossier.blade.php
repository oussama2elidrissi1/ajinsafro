<section class="reservation-create__panel" data-create-step="6" data-reservation-step="6" hidden>
    <div class="reservation-create__card">
        <div class="reservation-create__section-head">
            <div>
                <p class="reservation-create__eyebrow">Étape 6</p>
                <h3 class="reservation-create__section-title">Dossier de réservation</h3>
                <p class="reservation-create__section-subtitle">Relisez le dossier, chargez les documents utiles et confirmez l'ouverture du dossier commercial.</p>
            </div>
            <span class="reservation-create__pill">Dossier</span>
        </div>

        <div class="reservation-create__final-grid">
            <div class="reservation-create__final-summary">
                <p class="reservation-create__mini-title">Aperçu du dossier</p>
                <div class="reservation-create__final-line">
                    <span>Numéro dossier</span>
                    <strong id="create-dossier-number-preview">Généré après confirmation</strong>
                </div>
                <div class="reservation-create__final-line">
                    <span>Statut dossier</span>
                    <strong id="create-dossier-status-preview">En attente</strong>
                </div>
                <div class="reservation-create__final-line reservation-create__final-line--total">
                    <span>Total dossier</span>
                    <strong id="create-final-total">0 DH</strong>
                </div>
                <div class="reservation-create__final-line reservation-create__final-line--warning">
                    <span>Reste à payer</span>
                    <strong id="create-final-remaining">0 DH</strong>
                </div>
            </div>

            <div class="reservation-create__payment-card">
                <p class="reservation-create__mini-title">Documents et conformité</p>
                <div class="reservation-create__grid reservation-create__grid--two">
                    <div class="reservation-create__field reservation-create__field--full">
                        <label class="reservation-create__label" for="dossier_documents">Documents du dossier</label>
                        <input type="file" name="dossier_documents[]" id="dossier_documents" class="reservation-create__input reservation-create__input--file" accept="image/*,.pdf" multiple>
                    </div>
                </div>

                <div class="reservation-create__visa-card">
                    <input type="hidden" name="visa_ok" value="0">
                    <label class="reservation-create__toggle">
                        <input class="form-check-input" type="checkbox" name="visa_ok" id="visa_ok" value="1" {{ old('visa_ok', true) ? 'checked' : '' }}>
                        <span>Visa OK, pas d'assistance nécessaire</span>
                    </label>

                    <div id="assistant-visa-block" class="{{ old('visa_ok', true) ? 'd-none' : '' }}">
                        <div class="reservation-create__grid reservation-create__grid--two">
                            <div class="reservation-create__field">
                                <label class="reservation-create__label" for="visa_status">Statut visa</label>
                                <select name="visa_status" id="visa_status" class="reservation-create__input">
                                    <option value="">Sélectionner...</option>
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
                                <textarea name="visa_notes" id="visa_notes" class="reservation-create__input reservation-create__input--textarea" rows="4" placeholder="Suivi visa, pièces manquantes, remarques internes...">{{ old('visa_notes') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="reservation-create__placeholder">
                    <strong>Documents attendus</strong>
                    <p>Contrat de voyage, facture proforma, reçus, justificatifs de paiement, passeports ou CIN peuvent être ajoutés maintenant ou après création du dossier.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="reservation-create__actions reservation-create__actions--final">
        <button type="button" class="reservation-create__button reservation-create__button--secondary" data-create-prev data-step-back="5">
            <i class="bx bx-left-arrow-alt" aria-hidden="true"></i><span>Retour</span>
        </button>
        <div class="reservation-create__submit-group">
            <a href="{{ route('admin.reservations.workspace') }}" class="reservation-create__button reservation-create__button--ghost">Annuler</a>
            <button type="submit" class="reservation-create__button reservation-create__button--primary">
                <span>Confirmer la réservation</span><i class="bx bx-right-arrow-alt" aria-hidden="true"></i>
            </button>
        </div>
    </div>
</section>

