<section class="reservation-create__panel" data-create-step="4" data-reservation-step="4" hidden>
    {{-- Vérification du dossier --}}
    <div class="reservation-fast-card">
        <div class="reservation-fast-card__head">
            <div class="reservation-fast-card__heading">
                <h2 class="reservation-fast-card__title">Vérification du dossier</h2>
                <p class="reservation-fast-card__subtitle">Relisez chaque bloc avant de confirmer. Le numéro de dossier est généré à la confirmation.</p>
            </div>
            <span class="reservation-fast-header__tag" id="create-dossier-status-preview">BROUILLON</span>
        </div>

        <div class="reservation-fast-review" id="fast-review">
            <div class="reservation-fast-review__card" data-review="client">
                <div class="reservation-fast-review__head">
                    <span class="reservation-fast-review__kicker">Client &amp; voyageurs</span>
                    <button type="button" class="reservation-fast-review__edit" data-create-step-nav="1">Modifier</button>
                </div>
                <div class="reservation-fast-review__lines">
                    <div class="reservation-fast-review__line"><span>Client principal</span><b data-review-line="client-name">—</b></div>
                    <div class="reservation-fast-review__line"><span>Voyageurs</span><b data-review-line="travelers">—</b></div>
                    <div class="reservation-fast-review__line"><span>Accompagnants nommés</span><b data-review-line="companions">—</b></div>
                </div>
            </div>

            <div class="reservation-fast-review__card" data-review="rooms">
                <div class="reservation-fast-review__head">
                    <span class="reservation-fast-review__kicker">Chambres</span>
                    <button type="button" class="reservation-fast-review__edit" data-create-step-nav="2">Modifier</button>
                </div>
                <div class="reservation-fast-review__lines">
                    <div class="reservation-fast-review__line"><span>Chambres</span><b data-review-line="rooms-count">—</b></div>
                    <div class="reservation-fast-review__line"><span>Lits occupés</span><b data-review-line="beds">—</b></div>
                    <div class="reservation-fast-review__line"><span>Rooming</span><b data-review-line="rooming">—</b></div>
                </div>
            </div>

            <div class="reservation-fast-review__card" data-review="extras">
                <div class="reservation-fast-review__head">
                    <span class="reservation-fast-review__kicker">Extras</span>
                    <button type="button" class="reservation-fast-review__edit" data-create-step-nav="2">Modifier</button>
                </div>
                <div class="reservation-fast-review__lines">
                    <div class="reservation-fast-review__line"><span>Options retenues</span><b data-review-line="extras-count">—</b></div>
                    <div class="reservation-fast-review__line"><span>Montant extras</span><b data-review-line="extras-total">—</b></div>
                    <div class="reservation-fast-review__line"><span>Supplément chambres</span><b data-review-line="room-supplement">—</b></div>
                </div>
            </div>

            <div class="reservation-fast-review__card" data-review="payment">
                <div class="reservation-fast-review__head">
                    <span class="reservation-fast-review__kicker">Paiement</span>
                    <button type="button" class="reservation-fast-review__edit" data-create-step-nav="3">Modifier</button>
                </div>
                <div class="reservation-fast-review__lines">
                    <div class="reservation-fast-review__line"><span>Total dossier</span><b data-review-line="total" id="create-final-total">—</b></div>
                    <div class="reservation-fast-review__line"><span>Règlement saisi</span><b data-review-line="paid">—</b></div>
                    <div class="reservation-fast-review__line"><span>Reste à payer</span><b data-review-line="due" id="create-final-remaining">—</b></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Conformité --}}
    <div class="reservation-fast-card">
        <div class="reservation-fast-card__head">
            <div class="reservation-fast-card__heading">
                <h2 class="reservation-fast-card__title">Conformité avant confirmation</h2>
                <p class="reservation-fast-card__subtitle" id="fast-checks-hint">Contrôles déduits du dossier.</p>
            </div>
        </div>

        <div class="reservation-fast-checks">
            {{-- Contrôle réellement enregistré sur la réservation. --}}
            <label class="reservation-fast-check" data-check="visa">
                <input type="hidden" name="visa_ok" value="0">
                <input class="reservation-fast-check__input" type="checkbox" name="visa_ok" id="visa_ok" value="1" {{ old('visa_ok', true) ? 'checked' : '' }}>
                <span class="reservation-fast-check__box" aria-hidden="true"></span>
                <span class="reservation-fast-check__body">
                    <span class="reservation-fast-check__label">Visa OK, pas d'assistance nécessaire</span>
                    <span class="reservation-fast-check__hint">Le client gère lui-même ses formalités consulaires.</span>
                </span>
                <span class="reservation-fast-check__tag">Enregistré</span>
            </label>

            {{-- Contrôles déduits des données déjà saisies : rien de neuf n'est stocké,
                 ils signalent seulement ce qui manque avant confirmation. --}}
            <div class="reservation-fast-check is-derived" data-derived-check="identity">
                <span class="reservation-fast-check__box" aria-hidden="true"></span>
                <span class="reservation-fast-check__body">
                    <span class="reservation-fast-check__label">Pièce d'identité du titulaire</span>
                    <span class="reservation-fast-check__hint" data-check-hint>Type et numéro de document renseignés à l'étape 1.</span>
                </span>
                <span class="reservation-fast-check__tag" data-check-tag>—</span>
            </div>

            <div class="reservation-fast-check is-derived" data-derived-check="contact">
                <span class="reservation-fast-check__box" aria-hidden="true"></span>
                <span class="reservation-fast-check__body">
                    <span class="reservation-fast-check__label">Coordonnées du titulaire</span>
                    <span class="reservation-fast-check__hint" data-check-hint>Téléphone et email pour l'envoi du dossier.</span>
                </span>
                <span class="reservation-fast-check__tag" data-check-tag>—</span>
            </div>

            <div class="reservation-fast-check is-derived" data-derived-check="travelers">
                <span class="reservation-fast-check__box" aria-hidden="true"></span>
                <span class="reservation-fast-check__body">
                    <span class="reservation-fast-check__label">Voyageurs nommés</span>
                    <span class="reservation-fast-check__hint" data-check-hint>Chaque place annoncée correspond à une fiche voyageur.</span>
                </span>
                <span class="reservation-fast-check__tag" data-check-tag>—</span>
            </div>
        </div>

        <div id="assistant-visa-block" class="reservation-fast-visa {{ old('visa_ok', true) ? 'd-none' : '' }}">
            <div class="reservation-fast-grid">
                <label class="reservation-create__field">
                    <span class="reservation-create__label">Statut visa</span>
                    <select name="visa_status" id="visa_status" class="reservation-create__input">
                        <option value="">Sélectionner…</option>
                        <option value="not_required" {{ old('visa_status') === 'not_required' ? 'selected' : '' }}>Non requis</option>
                        <option value="pending" {{ old('visa_status') === 'pending' ? 'selected' : '' }}>En attente</option>
                        <option value="approved" {{ old('visa_status') === 'approved' ? 'selected' : '' }}>Approuvé</option>
                        <option value="rejected" {{ old('visa_status') === 'rejected' ? 'selected' : '' }}>Refusé</option>
                    </select>
                </label>
                <div class="reservation-create__field">
                    <span class="reservation-create__label">Document visa</span>
                    <label class="reservation-fast-dropzone" for="visa_document">
                        <span class="reservation-fast-dropzone__icon" aria-hidden="true">+</span>
                        <span class="reservation-fast-dropzone__body">
                            <span class="reservation-fast-dropzone__title">Justificatif visa</span>
                            <span class="reservation-fast-dropzone__hint" data-dropzone-hint>PDF ou image, 5 Mo maximum</span>
                        </span>
                        <input type="file" name="visa_document" id="visa_document" class="reservation-fast-dropzone__input" accept="image/*,.pdf">
                    </label>
                </div>
                <label class="reservation-create__field reservation-create__field--full">
                    <span class="reservation-create__label">Notes visa</span>
                    <textarea name="visa_notes" id="visa_notes" class="reservation-create__input reservation-create__input--textarea" rows="3" placeholder="Suivi visa, pièces manquantes…">{{ old('visa_notes') }}</textarea>
                </label>
            </div>
        </div>

        <div class="reservation-create__field mt-3">
            <span class="reservation-create__label">Documents du dossier</span>
            <label class="reservation-fast-dropzone" for="dossier_documents">
                <span class="reservation-fast-dropzone__icon" aria-hidden="true">+</span>
                <span class="reservation-fast-dropzone__body">
                    <span class="reservation-fast-dropzone__title">Passeports, contrats, justificatifs</span>
                    <span class="reservation-fast-dropzone__hint" data-dropzone-hint>Plusieurs fichiers acceptés · PDF ou image, 10 Mo chacun</span>
                </span>
                <input type="file" name="dossier_documents[]" id="dossier_documents" class="reservation-fast-dropzone__input" accept="image/*,.pdf" multiple>
            </label>
        </div>
    </div>

    {{-- Ce qui se passe à la confirmation --}}
    <div class="reservation-fast-outcome">
        <h2 class="reservation-fast-outcome__title">À la confirmation</h2>
        <ul class="reservation-fast-outcome__list">
            <li><b aria-hidden="true">→</b>Un numéro de dossier est généré et les places sont décomptées du départ.</li>
            <li><b aria-hidden="true">→</b>Le dossier est créé en statut « en attente » et apparaît dans le workspace réservations.</li>
            <li><b aria-hidden="true">→</b>Une chambre partielle reste en attente de jumelage jusqu'à son appariement.</li>
        </ul>
    </div>

    <div class="reservation-create__step-errors" id="step-4-errors" hidden></div>
    <div class="reservation-create__actions reservation-create__actions--final">
        <button type="button" class="reservation-create__button reservation-create__button--secondary" data-create-prev data-step-back="3">
            <i class="bx bx-left-arrow-alt" aria-hidden="true"></i><span>Retour</span>
        </button>
        <div class="reservation-create__submit-group">
            <a href="{{ request()->attributes->get('agent_reservation_mode', false) ? route('agent.catalogue') : route('admin.reservations.workspace') }}" class="reservation-create__button reservation-create__button--ghost">Annuler</a>
            <button type="submit" class="reservation-create__button reservation-create__button--primary">
                <span>Confirmer la réservation</span><i class="bx bx-right-arrow-alt" aria-hidden="true"></i>
            </button>
        </div>
    </div>
</section>
