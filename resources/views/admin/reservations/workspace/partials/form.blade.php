@php
    $__wsRd = 999999999;
    $wsReservationDataUrlTemplate = str_replace((string) $__wsRd, '__VOYAGE__', route('admin.circuits.voyages.reservation-data', ['voyage' => $__wsRd]));
@endphp
<form id="workspace-reservation-form" method="post" action="{{ route('admin.reservations.workspace.store') }}" class="ws-booking-form">
    @csrf
    <input type="hidden" id="ws-reservation-data-url-template" value="{{ $wsReservationDataUrlTemplate }}">
    <input type="hidden" name="tour_id" id="ws-tour-id" value="">
    <input type="hidden" name="travel_date_id" id="ws-travel-date-id" value="">
    <input type="hidden" name="prestation_type" id="ws-prestation-type" value="package">
    <input type="hidden" name="extras_json" id="ws-extras-json" value="[]">
    <input type="hidden" name="passengers_json" id="ws-passengers-json" value="[]">

    <div class="hidden" aria-hidden="true">
        <input type="hidden" name="vol_rbd" value="Y">
        <input type="hidden" name="vol_tarif_type" value="Public">
        <input type="hidden" name="vol_ff_number" value="">
        <input type="hidden" name="hotel_room_type" value="Standard">
        <input type="hidden" name="hotel_pension" value="RO">
        <input type="hidden" name="hotel_remarks" value="">
    </div>

    <div class="ws-flow-shell">
        <aside class="ws-flow-sidebar">
            <div class="ws-flow-nav-card">
                <p class="ws-flow-nav-card__eyebrow">Nouvelle réservation</p>
                <h3 class="ws-flow-nav-card__title">Workflow</h3>
                <div class="ws-flow-nav" role="tablist" aria-label="?tapes de réservation">
                    <button type="button" class="ws-flow-nav__item is-active" data-ws-step-nav="1">
                        <span class="ws-flow-nav__index">1</span>
                        <span class="ws-flow-nav__text">Prestation</span>
                    </button>
                    <button type="button" class="ws-flow-nav__item" data-ws-step-nav="2">
                        <span class="ws-flow-nav__index">2</span>
                        <span class="ws-flow-nav__text">Client</span>
                    </button>
                    <button type="button" class="ws-flow-nav__item" data-ws-step-nav="3">
                        <span class="ws-flow-nav__index">3</span>
                        <span class="ws-flow-nav__text">Voyageurs</span>
                    </button>
                    <button type="button" class="ws-flow-nav__item" data-ws-step-nav="4">
                        <span class="ws-flow-nav__index">4</span>
                        <span class="ws-flow-nav__text">Extras</span>
                    </button>
                    <button type="button" class="ws-flow-nav__item" data-ws-step-nav="5">
                        <span class="ws-flow-nav__index">5</span>
                        <span class="ws-flow-nav__text">Paiement</span>
                    </button>
                </div>
            </div>

            <div class="ws-sticky-summary" id="ws-sticky-summary">
                <p class="ws-sticky-summary__eyebrow">Résumé rapide</p>
                <div class="ws-sticky-summary__item">
                    <span>Prestation</span>
                    <strong id="ws-sticky-title">Aucune sélection</strong>
                </div>
                <div class="ws-sticky-summary__item">
                    <span>Date de départ</span>
                    <strong id="ws-sticky-date">?</strong>
                </div>
                <div class="ws-sticky-summary__item">
                    <span>Voyageurs</span>
                    <strong id="ws-sticky-pax">1</strong>
                </div>
                <div class="ws-sticky-summary__item">
                    <span>Total provisoire</span>
                    <strong id="ws-sticky-total">0 MAD</strong>
                </div>
            </div>
        </aside>

        <div class="ws-flow-main">
            <section class="ws-flow-step is-active" data-ws-step="1">
                <div id="ws-prefill-panel" class="hidden ws-section ws-section--voyage">
                    <div class="ws-step-head">
                        <div>
                            <p class="ws-step-head__eyebrow">?tape 1</p>
                            <h3 class="ws-step-head__title">Sélection de la prestation</h3>
                            <p class="ws-step-head__desc">Vérifiez le voyage, le départ, la disponibilité et la formule avant de continuer.</p>
                        </div>
                        <span id="ws-prefill-type-badge" class="ws-type-pill" data-ws-prestation="package">Circuit</span>
                    </div>

                    <div class="ws-premium-trip-card">
                        <div class="ws-premium-trip-card__main">
                            <p class="ws-section__kicker">Voyage sélectionné</p>
                            <h3 id="ws-prefill-heading" class="ws-section__heading">?</h3>
                            <p id="ws-prefill-sub" class="ws-section__sub hidden"></p>
                        </div>
                        <div id="ws-prefill-sections"></div>
                    </div>

                    <div id="ws-departure-wrap" class="hidden ws-step-block">
                        <label for="ws-departure-select" class="ws-departure-label">Date de départ</label>
                        <select id="ws-departure-select" class="ws-input-shell"></select>
                        <p class="ws-departure-hint" id="ws-departure-hint"></p>
                    </div>

                    <div id="details-package" class="details-block ws-step-block">
                        <div class="ws-step-block__header">
                            <h4 class="ws-step-block__title">Formule et chambre</h4>
                            <p class="ws-step-block__sub">Choisissez l?Toption de séjour la plus adaptée au dossier.</p>
                        </div>
                        <div class="ws-form-grid ws-form-grid--two">
                            <div class="ws-form-field">
                                <label class="ws-form-label" for="ws-package-room-type">Type de chambre</label>
                                <select name="package_room_type" id="ws-package-room-type" class="ws-input-shell">
                                    <option value="">? Choisir ?</option>
                                    <option>Chambre Double</option>
                                    <option>Chambre Twin</option>
                                    <option>Chambre Triple</option>
                                </select>
                            </div>
                            <div class="ws-form-field ws-form-field--full">
                                <label class="ws-form-label" for="ws-package-remarks">Remarques séjour</label>
                                <textarea name="package_remarks" id="ws-package-remarks" rows="3" placeholder="Préférences chambre, contraintes client, points d’attention…" class="ws-input-shell ws-input-shell--textarea"></textarea>
                            </div>
                        </div>
                    </div>

                    <div id="details-vol" class="details-block hidden ws-step-block">
                        <div class="ws-step-block__header">
                            <h4 class="ws-step-block__title">Détails vol</h4>
                            <p class="ws-step-block__sub">Le tarif et les suppléments seront pris en compte dans le total final.</p>
                        </div>
                    </div>

                    <div id="details-hebergement" class="details-block hidden ws-step-block">
                        <div class="ws-step-block__header">
                            <h4 class="ws-step-block__title">Détails hébergement</h4>
                            <p class="ws-step-block__sub">La prestation hébergement sera reprise dans le récapitulatif en bas de page.</p>
                        </div>
                    </div>
                </div>

                <div class="ws-flow-actions">
                    <span></span>
                    <button type="button" class="ws-flow-btn ws-flow-btn--primary" data-ws-step-next>Continuer</button>
                </div>
            </section>

            <section class="ws-flow-step" data-ws-step="2" hidden>
                <div class="ws-section">
                    <div class="ws-step-head">
                        <div>
                            <p class="ws-step-head__eyebrow">?tape 2</p>
                            <h3 class="ws-step-head__title">Informations client</h3>
                            <p class="ws-step-head__desc">Identifiez le client ou créez un nouveau dossier avec les informations utiles au voyage.</p>
                        </div>
                    </div>

                    <div class="ws-form-grid ws-form-grid--two">
                        <div class="ws-form-field">
                            <label class="ws-form-label" for="ws-client-mode">Client</label>
                            <select name="client_mode" id="ws-client-mode" class="ws-input-shell">
                                <option value="new">Nouveau client</option>
                                <option value="existing">Client existant</option>
                            </select>
                        </div>
                        <div class="ws-form-field ws-form-field--full hidden" id="ws-client-existing-wrap">
                            <label class="ws-form-label" for="ws-client-external-id">Recherche client existant</label>
                            <select name="client_external_id" id="ws-client-external-id" class="ws-input-shell">
                                <option value="">? Sélectionner un client ?</option>
                                @foreach($clients as $c)
                                    <option value="{{ $c->id }}">{{ $c->client_code }} ? {{ $c->full_name ?: $c->email }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="ws-form-field">
                            <label class="ws-form-label" for="titulaire-civilite">Civilité</label>
                            <select name="titulaire_civilite" id="titulaire-civilite" class="ws-input-shell">
                                <option value="MR">Monsieur</option>
                                <option value="MRS">Madame</option>
                                <option value="MS">Mademoiselle</option>
                            </select>
                        </div>
                        <div class="ws-form-field">
                            <label class="ws-form-label" for="titulaire-type">Type voyageur <span>*</span></label>
                            <select name="titulaire_type" id="titulaire-type" class="ws-input-shell" required>
                                <option value="adulte" selected>Adulte</option>
                                <option value="enfant">Enfant</option>
                                <option value="bebe">Bébé</option>
                            </select>
                        </div>
                        <div class="ws-form-field">
                            <label class="ws-form-label" for="titulaire-nom">Nom <span>*</span></label>
                            <input type="text" name="titulaire_nom" id="titulaire-nom" required class="ws-input-shell" autocomplete="family-name">
                        </div>
                        <div class="ws-form-field">
                            <label class="ws-form-label" for="titulaire-prenom">Prénom <span>*</span></label>
                            <input type="text" name="titulaire_prenom" id="titulaire-prenom" required class="ws-input-shell" autocomplete="given-name">
                        </div>
                        <div class="ws-form-field">
                            <label class="ws-form-label" for="titulaire-phone">Téléphone <span>*</span></label>
                            <input type="tel" name="titulaire_phone" id="titulaire-phone" required class="ws-input-shell" autocomplete="tel" inputmode="tel">
                        </div>
                        <div class="ws-form-field">
                            <label class="ws-form-label" for="titulaire-email">Email</label>
                            <input type="email" name="titulaire_email" id="titulaire-email" class="ws-input-shell" autocomplete="email">
                        </div>
                        <div class="ws-form-field ws-form-field--full">
                            <label class="ws-form-label" for="titulaire-document">CIN / Passeport <span>*</span></label>
                            <input type="text" name="titulaire_document" id="titulaire-document" required class="ws-input-shell">
                        </div>
                    </div>

                    <details class="ws-optional-details">
                        <summary>Afficher les informations complémentaires</summary>
                        <div class="ws-form-grid ws-form-grid--three">
                            <div class="ws-form-field">
                                <label class="ws-form-label" for="titulaire-nationalite">Nationalité</label>
                                <input type="text" name="titulaire_nationalite" id="titulaire-nationalite" value="MA" maxlength="10" class="ws-input-shell">
                            </div>
                            <div class="ws-form-field">
                                <label class="ws-form-label" for="titulaire-dob">Date de naissance</label>
                                <input type="date" name="titulaire_dob" id="titulaire-dob" class="ws-input-shell">
                            </div>
                            <div class="ws-form-field">
                                <label class="ws-form-label" for="titulaire-doc-expires">Expiration document</label>
                                <input type="date" name="titulaire_doc_expires" id="titulaire-doc-expires" class="ws-input-shell">
                            </div>
                        </div>
                    </details>
                </div>

                <div class="ws-flow-actions">
                    <button type="button" class="ws-flow-btn ws-flow-btn--secondary" data-ws-step-prev>Retour</button>
                    <button type="button" class="ws-flow-btn ws-flow-btn--primary" data-ws-step-next>Continuer</button>
                </div>
            </section>

            <section class="ws-flow-step" data-ws-step="3" hidden>
                <div class="ws-section">
                    <div class="ws-step-head">
                        <div>
                            <p class="ws-step-head__eyebrow">?tape 3</p>
                            <h3 class="ws-step-head__title">Participants</h3>
                            <p class="ws-step-head__desc">Ajoutez les accompagnants et répartissez clairement adulte, enfant et bébé.</p>
                        </div>
                        <div class="ws-step-counter">
                            <span>Total voyageurs</span>
                            <strong id="ws-pax-total-display">1</strong>
                        </div>
                    </div>

                    <div class="ws-travelers-toolbar">
                        <p class="ws-travelers-toolbar__hint">Le titulaire est inclus automatiquement. Ajoutez ensuite les accompagnants nécessaires.</p>
                        <button type="button" id="btn-add-companion" class="ws-flow-btn ws-flow-btn--ghost">
                            <i class="fas fa-plus"></i> Ajouter un accompagnant
                        </button>
                    </div>

                    <div id="companions-container" class="ws-travelers-list">
                        <p id="empty-companion-msg" class="ws-travelers-empty">Aucun accompagnant pour le moment.</p>
                    </div>
                </div>

                <div class="ws-flow-actions">
                    <button type="button" class="ws-flow-btn ws-flow-btn--secondary" data-ws-step-prev>Retour</button>
                    <button type="button" class="ws-flow-btn ws-flow-btn--primary" data-ws-step-next>Continuer</button>
                </div>
            </section>

            <section class="ws-flow-step" data-ws-step="4" hidden>
                <div id="section-extras" class="ws-section">
                    <div class="ws-step-head">
                        <div>
                            <p class="ws-step-head__eyebrow">?tape 4</p>
                            <h3 class="ws-step-head__title">Extras et activités</h3>
                            <p class="ws-step-head__desc">Ajoutez les options de réservation pertinentes et visualisez leur impact sur le total.</p>
                        </div>
                        <span id="badge-extras-type" class="ws-type-pill" data-ws-prestation="package">Circuit</span>
                    </div>
                    <div id="extras-container" class="grid grid-cols-1 sm:grid-cols-2 gap-4"></div>
                </div>

                <div class="ws-flow-actions">
                    <button type="button" class="ws-flow-btn ws-flow-btn--secondary" data-ws-step-prev>Retour</button>
                    <button type="button" class="ws-flow-btn ws-flow-btn--primary" data-ws-step-next>Continuer</button>
                </div>
            </section>

            <section class="ws-flow-step" data-ws-step="5" hidden>
                <div class="ws-section ws-section--final">
                    <div class="ws-step-head">
                        <div>
                            <p class="ws-step-head__eyebrow">?tape 5</p>
                            <h3 class="ws-step-head__title">Paiement et validation</h3>
                            <p class="ws-step-head__desc">Vérifiez le récapitulatif final, enregistrez le paiement et confirmez la réservation.</p>
                        </div>
                    </div>

                    <div class="ws-final-summary">
                        <div class="ws-recap">
                            <h4 class="ws-section__title border-0 pb-2 mb-3">Récapitulatif final</h4>
                            <div id="ws-capacity-live" class="ws-capacity-banner hidden mb-4" role="status" aria-live="polite"></div>
                            <div class="ws-recap__lines">
                                <div class="ws-recap__line">
                                    <span>Base (<span id="summary-pax-count">1</span> voyageur(s))</span>
                                    <span id="summary-base-price" class="font-bold text-[#0e3a5a]">0 MAD</span>
                                </div>
                                <div class="ws-recap__line border-0 pb-0">
                                    <span>Extras</span>
                                    <span id="summary-extras-price" class="font-bold text-[#f37a1f]">+ 0 MAD</span>
                                </div>
                            </div>
                            <div class="ws-recap__total">
                                <span class="block text-[10px] text-slate-500 font-bold uppercase tracking-wide mb-1">Total réservation</span>
                                <span id="summary-grand-total" class="text-2xl font-bold text-[#0e3a5a]">0 <span class="text-sm text-slate-500 font-medium">MAD</span></span>
                            </div>
                        </div>

                        <div class="ws-pay">
                            <p class="ws-pay__label m-0 mb-3">Paiement</p>
                            <div class="space-y-3">
                                <div>
                                    <label class="ws-form-label" for="input-montant-total">Montant total <span>*</span></label>
                                    <input type="number" step="0.01" name="montant_total" id="input-montant-total" required class="ws-input-shell ws-input-shell--strong">
                                </div>
                                <div>
                                    <label class="ws-form-label" for="ws-montant-paye">Montant payé <span>*</span></label>
                                    <input type="number" step="0.01" name="montant_paye" id="ws-montant-paye" required class="ws-input-shell ws-input-shell--paid" value="0">
                                </div>
                                <div class="ws-pay__rest">
                                    <span>Reste à payer</span>
                                    <strong id="summary-montant-reste">0 MAD</strong>
                                </div>
                                <div>
                                    <label class="ws-form-label" for="ws-payment-mode">Mode de paiement</label>
                                    <select name="payment_mode" id="ws-payment-mode" class="ws-input-shell">
                                        <option>Espèces</option>
                                        <option>Virement Bancaire</option>
                                        <option>Chèque</option>
                                        <option>Carte Bancaire</option>
                                        <option>CashPlus</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="ws-form-label" for="ws-workspace-notes">Notes internes</label>
                                    <textarea name="workspace_notes" id="ws-workspace-notes" rows="4" placeholder="Instructions internes, suivi commercial, contraintes client…" class="ws-input-shell ws-input-shell--textarea"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ws-flow-actions ws-flow-actions--final">
                    <button type="button" class="ws-flow-btn ws-flow-btn--secondary" data-ws-step-prev>Retour</button>
                    <div class="ws-flow-actions__submit">
                        <button type="button" id="btn-cancel-add-reservation" class="ws-flow-btn ws-flow-btn--ghost">Annuler</button>
                        @can('reservations.view')
                            <button type="submit" id="ws-booking-submit" class="ws-flow-btn ws-flow-btn--confirm">
                                <i class="fas fa-save"></i> Confirmer la réservation
                            </button>
                        @endcan
                    </div>
                </div>
            </section>
        </div>
    </div>
</form>
