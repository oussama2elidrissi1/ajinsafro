@php
    $__wsRd = 999999999;
    $wsReservationDataUrlTemplate = str_replace((string) $__wsRd, '__VOYAGE__', route('admin.circuits.voyages.reservation-data', ['voyage' => $__wsRd]));
@endphp
<form id="workspace-reservation-form" method="post" action="{{ route('admin.reservations.workspace.store') }}" class="ws-booking-form space-y-6">
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

    {{-- 1. Voyage --}}
    <div id="ws-prefill-panel" class="hidden ws-section ws-section--voyage">
        <div class="ws-section__head">
            <div class="min-w-0">
                <p class="ws-section__kicker">Voyage</p>
                <h3 id="ws-prefill-heading" class="ws-section__heading">—</h3>
                <p id="ws-prefill-sub" class="ws-section__sub hidden"></p>
            </div>
            <span id="ws-prefill-type-badge" class="ws-type-pill" data-ws-prestation="package">Circuit</span>
        </div>
        <div id="ws-prefill-sections"></div>
        <div id="ws-departure-wrap" class="hidden mt-5">
            <label for="ws-departure-select" class="ws-departure-label">Date de départ</label>
            <select id="ws-departure-select" class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm font-semibold text-[#0e3a5a] focus:outline-none focus:border-[#0083c4] focus:ring-1 focus:ring-[#0083c4]/30 cursor-pointer"></select>
            <p class="ws-departure-hint" id="ws-departure-hint"></p>
        </div>
        <div id="details-package" class="details-block ws-options-stay">
            <p class="ws-options-stay__title">Options séjour</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-bold text-gray-500 mb-1">Type de chambre</label>
                    <select name="package_room_type" id="ws-package-room-type" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-[#0083c4]">
                        <option value="">— Choisir —</option>
                        <option>Chambre Double</option>
                        <option>Chambre Twin</option>
                        <option>Chambre Triple</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-[11px] font-bold text-gray-500 mb-1">Remarques séjour</label>
                    <textarea name="package_remarks" rows="2" placeholder="Optionnel" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-[#0083c4] resize-none"></textarea>
                </div>
            </div>
        </div>
    </div>

    {{-- 2. Client --}}
    <div class="ws-section">
        <h4 class="ws-section__title">Client (titulaire)</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Client</label>
                <select name="client_mode" id="ws-client-mode" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-[#0083c4] cursor-pointer">
                    <option value="new">Nouveau client</option>
                    <option value="existing">Client existant</option>
                </select>
            </div>
            <div class="lg:col-span-3 hidden" id="ws-client-existing-wrap">
                <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Sélection</label>
                <select name="client_external_id" id="ws-client-external-id" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-[#0083c4] cursor-pointer">
                    <option value="">—</option>
                    @foreach($clients as $c)
                        <option value="{{ $c->id }}">{{ $c->client_code }} — {{ $c->full_name ?: $c->email }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Civilité</label>
                <select name="titulaire_civilite" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-[#0083c4] cursor-pointer">
                    <option value="MR">Monsieur</option>
                    <option value="MRS">Madame</option>
                    <option value="MS">Mademoiselle</option>
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Type *</label>
                <select name="titulaire_type" id="titulaire-type" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-[#0083c4] cursor-pointer">
                    <option value="adulte" selected>Adulte</option>
                    <option value="enfant">Enfant</option>
                    <option value="bebe">Bébé</option>
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Nom *</label>
                <input type="text" name="titulaire_nom" id="titulaire-nom" required class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-[#0083c4] uppercase">
            </div>
            <div>
                <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Prénom *</label>
                <input type="text" name="titulaire_prenom" id="titulaire-prenom" required class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-[#0083c4] uppercase">
            </div>
            <div>
                <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Téléphone *</label>
                <input type="tel" name="titulaire_phone" required class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-[#0083c4]">
            </div>
            <div>
                <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Email</label>
                <input type="email" name="titulaire_email" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-[#0083c4]">
            </div>
            <div class="lg:col-span-2">
                <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">CIN / Passeport *</label>
                <input type="text" name="titulaire_document" required class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-[#0083c4] uppercase">
            </div>
        </div>
        <details class="ws-optional-details">
            <summary>Informations complémentaires (optionnel)</summary>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-1">
                <div>
                    <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Nationalité</label>
                    <input type="text" name="titulaire_nationalite" value="MA" maxlength="10" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-[#0083c4] uppercase">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Date de naissance</label>
                    <input type="date" name="titulaire_dob" id="titulaire-dob" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-[#0083c4]">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Expiration document</label>
                    <input type="date" name="titulaire_doc_expires" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-[#0083c4]">
                </div>
            </div>
        </details>
    </div>

    {{-- 3. Participants --}}
    <div class="ws-section">
        <div class="flex flex-wrap justify-between items-start gap-3 border-b border-gray-100 pb-3 mb-4">
            <div>
                <h4 class="ws-section__title border-0 p-0 m-0 mb-1">Participants</h4>
                <p class="ws-pax-inline m-0">Total voyageurs : <strong id="ws-pax-total-display">1</strong> <span class="text-slate-400 font-normal">(titulaire inclus)</span></p>
            </div>
            <button type="button" id="btn-add-companion" class="text-xs font-bold text-[#0083c4] bg-[#e6f3fa] px-3 py-2 rounded-lg hover:bg-[#0083c4] hover:text-white transition-colors shadow-sm flex items-center gap-1.5 shrink-0">
                <i class="fas fa-plus"></i> Accompagnant
            </button>
        </div>
        <p class="text-xs text-slate-500 mb-3">Indiquez le type de chaque personne (adulte, enfant, bébé).</p>
        <div id="companions-container" class="bg-slate-50/80 p-4 rounded-xl border border-gray-200 space-y-3 min-h-[60px]">
            <p id="empty-companion-msg" class="text-xs text-slate-500 text-center italic m-0">Aucun accompagnant.</p>
        </div>
    </div>

    {{-- 4. Extras --}}
    <div id="section-extras" class="ws-section">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 pb-3 mb-4">
            <h4 class="ws-section__title border-0 p-0 m-0">Extras</h4>
            <span id="badge-extras-type" class="ws-type-pill" data-ws-prestation="package">Circuit</span>
        </div>
        <div id="extras-container" class="grid grid-cols-1 sm:grid-cols-2 gap-4"></div>
    </div>

    {{-- 5. Récap & paiement --}}
    <div class="ws-recap-grid">
        <div class="ws-recap">
            <h4 class="ws-section__title border-0 pb-2 mb-3">Récapitulatif</h4>
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
                <span class="block text-[10px] text-slate-500 font-bold uppercase tracking-wide mb-1">Total</span>
                <span id="summary-grand-total" class="text-2xl font-bold text-[#0e3a5a]">0 <span class="text-sm text-slate-500 font-medium">MAD</span></span>
            </div>
        </div>
        <div class="ws-pay">
            <p class="ws-pay__label m-0 mb-3">Paiement</p>
            <div class="space-y-3">
                <div>
                    <label class="block text-[11px] font-bold text-gray-500 uppercase mb-1.5">Montant total *</label>
                    <input type="number" step="0.01" name="montant_total" id="input-montant-total" required class="w-full px-4 py-2 bg-white border border-gray-200 rounded-xl text-lg font-bold text-[#0e3a5a]">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-gray-500 uppercase mb-1.5">Montant payé *</label>
                    <input type="number" step="0.01" name="montant_paye" id="ws-montant-paye" required class="w-full px-4 py-2 bg-white border border-emerald-200 rounded-xl text-lg font-bold text-emerald-600" value="0">
                </div>
                <div class="flex justify-between text-xs font-semibold text-slate-600 pt-2 border-t border-slate-200">
                    <span>Reste à payer</span>
                    <span id="summary-montant-reste" class="text-[#0e3a5a] font-bold">0 MAD</span>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-gray-500 uppercase mb-1.5">Mode paiement</label>
                    <select name="payment_mode" class="w-full px-4 py-2 bg-white border border-gray-200 rounded-xl text-sm">
                        <option>Espèces</option>
                        <option>Virement Bancaire</option>
                        <option>Chèque</option>
                        <option>Carte Bancaire</option>
                        <option>CashPlus</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-gray-500 uppercase mb-1.5">Notes internes</label>
                    <textarea name="workspace_notes" rows="2" placeholder="Visible équipe uniquement" class="w-full px-3 py-2 bg-white border border-gray-200 rounded-xl text-sm"></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="pt-4 flex justify-end gap-3 border-t border-gray-100">
        <button type="button" id="btn-cancel-add-reservation" class="px-6 py-2.5 rounded-xl text-sm font-bold text-gray-600 hover:bg-gray-100 border border-transparent">Annuler</button>
        @can('reservations.view')
            <button type="submit" id="ws-booking-submit" class="bg-[#0083c4] hover:bg-[#0e3a5a] text-white px-8 py-2.5 rounded-xl text-sm font-bold shadow-md flex items-center gap-2">
                <i class="fas fa-save"></i> Confirmer la réservation
            </button>
        @endcan
    </div>
</form>
