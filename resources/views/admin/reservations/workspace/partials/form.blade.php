{{-- Formulaire aligné sur agent.html (titulaire, accompagnants, extras, documents, finances) --}}
<form id="workspace-reservation-form" method="post" action="{{ route('admin.reservations.workspace.store') }}" enctype="multipart/form-data" class="space-y-8">
    @csrf
    <input type="hidden" name="tour_id" id="ws-tour-id" value="">
    <input type="hidden" name="travel_date_id" id="ws-travel-date-id" value="">
    <input type="hidden" name="prestation_type" id="ws-prestation-type" value="package">
    <input type="hidden" name="extras_json" id="ws-extras-json" value="[]">
    <input type="hidden" name="passengers_json" id="ws-passengers-json" value="[]">

    {{-- Résumé catalogue : prérempli depuis ws-form-prefill-json (même source que la ligne) --}}
    <div id="ws-prefill-panel" class="hidden rounded-2xl border border-[#0083c4]/25 bg-gradient-to-br from-[#f8fcfe] via-white to-[#e6f3fa]/35 p-5 sm:p-6 shadow-sm space-y-5">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="text-[10px] font-bold uppercase tracking-wider text-[#0083c4] mb-1">Prestation sélectionnée (catalogue)</p>
                <h3 id="ws-prefill-heading" class="text-base font-extrabold text-[#0e3a5a] leading-snug">—</h3>
                <p id="ws-prefill-sub" class="text-xs text-slate-500 mt-1 hidden"></p>
            </div>
            <span id="ws-prefill-type-badge" class="text-[10px] font-extrabold uppercase px-2.5 py-1 rounded-lg bg-[#e6f3fa] text-[#0083c4] shrink-0 border border-[#0083c4]/15">—</span>
        </div>
        <div id="ws-prefill-sections" class="space-y-4 text-sm"></div>
        <div id="ws-departure-wrap" class="hidden">
            <label for="ws-departure-select" class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Date de départ</label>
            <select id="ws-departure-select" class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm font-semibold text-[#0e3a5a] focus:outline-none focus:border-[#0083c4] cursor-pointer"></select>
            <p class="text-[10px] text-gray-500 mt-1.5" id="ws-departure-hint"></p>
        </div>
    </div>

    <div>
        <h4 class="text-sm font-bold text-[#0083c4] border-b border-gray-100 pb-2 mb-4 flex items-center gap-2">
            <i class="fas fa-user-tie"></i> Titulaire
        </h4>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Client lié</label>
                <select name="client_mode" id="ws-client-mode" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-[#0083c4] cursor-pointer">
                    <option value="new">Nouveau (saisie ci-dessous)</option>
                    <option value="existing">Client existant</option>
                </select>
            </div>
            <div class="lg:col-span-3" id="ws-client-existing-wrap">
                <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Client</label>
                <select name="client_external_id" id="ws-client-external-id" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-[#0083c4] cursor-pointer">
                    <option value="">—</option>
                    @foreach($clients as $c)
                        <option value="{{ $c->id }}">{{ $c->client_code }} — {{ $c->full_name ?: $c->email }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Type *</label>
                <select name="titulaire_type" id="titulaire-type" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-[#0083c4] cursor-pointer">
                    <option value="adulte" selected>Adulte (ADT)</option>
                    <option value="enfant">Enfant (CHD)</option>
                    <option value="bebe">Bébé (INF)</option>
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Civilité</label>
                <select name="titulaire_civilite" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-[#0083c4] cursor-pointer">
                    <option value="MR">Monsieur (MR)</option>
                    <option value="MRS">Madame (MRS)</option>
                    <option value="MS">Mademoiselle (MS)</option>
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
                <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Date de naissance</label>
                <input type="date" name="titulaire_dob" id="titulaire-dob" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-[#0083c4]">
            </div>
            <div>
                <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">CIN / Passeport *</label>
                <input type="text" name="titulaire_document" required class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-[#0083c4] uppercase">
            </div>
            <div>
                <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Nationalité</label>
                <input type="text" name="titulaire_nationalite" value="MA" maxlength="10" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-[#0083c4] uppercase">
            </div>
            <div>
                <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Expiration document</label>
                <input type="date" name="titulaire_doc_expires" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-[#0083c4]">
            </div>
            <div>
                <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Téléphone *</label>
                <input type="tel" name="titulaire_phone" required class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-[#0083c4]">
            </div>
            <div>
                <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Email</label>
                <input type="email" name="titulaire_email" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-[#0083c4]">
            </div>
        </div>
    </div>

    <div>
        <div class="flex justify-between items-end border-b border-gray-100 pb-2 mb-4">
            <h4 class="text-sm font-bold text-[#0083c4] flex items-center gap-2"><i class="fas fa-users"></i> Accompagnants</h4>
            <button type="button" id="btn-add-companion" class="text-[10px] font-bold text-[#0083c4] bg-[#e6f3fa] px-3 py-1.5 rounded-lg hover:bg-[#0083c4] hover:text-white transition-colors shadow-sm flex items-center gap-1">
                <i class="fas fa-plus"></i> Ajouter
            </button>
        </div>
        <div id="companions-container" class="bg-gray-50 p-4 rounded-xl border border-gray-200 space-y-3 min-h-[80px]">
            <p id="empty-companion-msg" class="text-xs text-gray-500 text-center italic">Aucun accompagnant.</p>
        </div>
    </div>

    <div id="section-extras">
        <div class="flex justify-between items-end border-b border-gray-100 pb-2 mb-4">
            <div class="flex items-center gap-3">
                <h4 class="text-sm font-bold text-[#0083c4] flex items-center gap-2"><i class="fas fa-magic"></i> Extras</h4>
                <span id="badge-extras-type" class="text-[10px] bg-[#e6f3fa] text-[#0083c4] px-2 py-1 rounded font-bold uppercase">PACKAGE</span>
            </div>
            <button type="button" id="btn-add-custom-extra" class="text-[10px] font-bold text-[#0083c4] bg-[#e6f3fa] px-3 py-1.5 rounded-lg hover:bg-[#0083c4] hover:text-white transition-colors shadow-sm flex items-center gap-1">
                <i class="fas fa-plus"></i> Extra perso
            </button>
        </div>
        <div id="extras-container" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4"></div>
        <div id="custom-extras-container" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mt-4"></div>
    </div>

    <div>
        <h4 class="text-sm font-bold text-[#0083c4] border-b border-gray-100 pb-2 mb-4 flex items-center gap-2">
            <i class="fas fa-file-upload"></i> Documents
        </h4>
        <div class="bg-gray-50 border-2 border-dashed border-gray-300 rounded-xl p-6 text-center hover:bg-gray-100 transition-colors cursor-pointer relative" onclick="document.getElementById('reservation-files').click()">
            <input type="file" name="workspace_documents[]" id="reservation-files" multiple accept=".pdf,.png,.jpg,.jpeg" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer hidden">
            <i class="fas fa-cloud-upload-alt text-2xl text-[#0083c4] mb-2"></i>
            <p class="text-sm font-bold text-[#0e3a5a]">Glisser / cliquer</p>
        </div>
        <div id="reservation-file-list" class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-3"></div>
    </div>

    <div id="details-dynamic-container">
        <div id="details-package" class="space-y-4 details-block">
            <h4 class="text-sm font-bold text-[#0e3a5a] mb-2">Détails package</h4>
            <div>
                <label class="block text-[11px] font-bold text-gray-500 uppercase mb-1.5">Type de chambre</label>
                <select name="package_room_type" id="ws-package-room-type" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-[#0083c4]">
                    <option value="">— Choisir —</option>
                    <option>Chambre Double</option>
                    <option>Chambre Twin</option>
                    <option>Chambre Triple</option>
                </select>
            </div>
            <textarea name="package_remarks" placeholder="Remarques" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-[#0083c4] h-20 resize-none"></textarea>
        </div>
        <div id="details-vol" class="space-y-3 details-block hidden bg-blue-50/40 p-4 rounded-xl border border-blue-200">
            <h5 class="text-sm font-bold text-[#0e3a5a] flex items-center gap-2"><i class="fas fa-plane text-[#0083c4]"></i> Vol</h5>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Classe (RBD)</label>
                    <select name="vol_rbd" class="w-full px-3 py-1.5 bg-white border border-gray-200 rounded text-xs">
                        <option value="Y">Y</option>
                        <option value="V">V</option>
                        <option value="C">C</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Tarif</label>
                    <select name="vol_tarif_type" class="w-full px-3 py-1.5 bg-white border border-gray-200 rounded text-xs">
                        <option>Public</option>
                        <option>Négocié</option>
                    </select>
                </div>
                <div class="col-span-2">
                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Carte fidélité</label>
                    <input type="text" name="vol_ff_number" class="w-full px-3 py-1.5 bg-white border border-gray-200 rounded text-xs uppercase">
                </div>
            </div>
            <button type="button" id="btn-amadeus-pricing" class="w-full bg-[#0e3a5a] hover:bg-[#0083c4] text-white px-3 py-2 rounded-lg text-xs font-bold">
                <i class="fas fa-sync-alt"></i> Vérifier tarif (simulation)
            </button>
        </div>
        <div id="details-hebergement" class="space-y-4 details-block hidden">
            <h5 class="text-sm font-bold text-[#0e3a5a]">Hébergement</h5>
            <select name="hotel_room_type" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm">
                <option>Standard</option>
                <option>Supérieure</option>
                <option>Suite</option>
            </select>
            <select name="hotel_pension" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm">
                <option>RO</option>
                <option>BB</option>
                <option>HB</option>
                <option>AI</option>
            </select>
            <textarea name="hotel_remarks" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm h-16 resize-none" placeholder="Demandes spéciales"></textarea>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="md:col-span-2 bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
            <h4 class="text-sm font-bold text-[#0083c4] mb-3 flex items-center gap-2">
                <i class="fas fa-calculator"></i> Récapitulatif
                <span id="api-status-badge" class="hidden ml-2 bg-green-100 text-green-700 text-[9px] px-2 py-0.5 rounded uppercase font-bold">Tarif à jour</span>
            </h4>
            <div class="flex flex-col gap-2 text-xs text-gray-600 font-medium">
                <div class="flex justify-between border-b border-gray-100 pb-1.5">
                    <span>Base (<span id="summary-pax-count">1</span> pax)</span>
                    <span id="summary-base-price" class="font-bold text-[#0e3a5a]">0 MAD</span>
                </div>
                <div class="flex justify-between border-b border-gray-100 pb-1.5">
                    <span>Extras</span>
                    <span id="summary-extras-price" class="font-bold text-[#f37a1f]">+ 0 MAD</span>
                </div>
            </div>
            <div class="mt-4 text-center bg-[#e6f3fa]/30 p-3 rounded-xl border border-[#0083c4]/20">
                <span class="block text-[10px] text-gray-500 font-bold uppercase mb-1">Total</span>
                <span id="summary-grand-total" class="text-2xl font-bold text-[#0e3a5a]">0 <span class="text-sm text-gray-500 font-medium">MAD</span></span>
            </div>
        </div>
        <div class="bg-[#f8fbfd] p-5 rounded-xl border border-blue-100">
            <label class="block text-[11px] font-bold text-gray-500 uppercase mb-1.5">Montant total *</label>
            <input type="number" step="0.01" name="montant_total" id="input-montant-total" required class="w-full px-4 py-2 bg-white border border-gray-200 rounded-xl text-lg font-bold text-[#0e3a5a] mb-3">
            <label class="block text-[11px] font-bold text-gray-500 uppercase mb-1.5">Montant payé *</label>
            <input type="number" step="0.01" name="montant_paye" id="ws-montant-paye" required class="w-full px-4 py-2 bg-white border border-green-200 rounded-xl text-lg font-bold text-green-600 mb-3" value="0">
            <label class="block text-[11px] font-bold text-gray-500 uppercase mb-1.5">Mode paiement</label>
            <select name="payment_mode" class="w-full px-4 py-2 bg-white border border-gray-200 rounded-xl text-sm">
                <option>Espèces</option>
                <option>Virement Bancaire</option>
                <option>Chèque</option>
                <option>Carte Bancaire</option>
                <option>CashPlus</option>
            </select>
            <label class="block text-[11px] font-bold text-gray-500 uppercase mt-3 mb-1.5">Notes internes</label>
            <textarea name="workspace_notes" rows="2" class="w-full px-3 py-2 bg-white border border-gray-200 rounded-xl text-sm"></textarea>
        </div>
    </div>

    <div class="pt-4 flex justify-end gap-3 border-t border-gray-100">
        <button type="button" id="btn-cancel-add-reservation" class="px-6 py-2.5 rounded-xl text-sm font-bold text-gray-600 hover:bg-gray-100 border border-transparent">Annuler</button>
        @can('reservations.view')
            <button type="submit" class="bg-[#0083c4] hover:bg-[#0e3a5a] text-white px-8 py-2.5 rounded-xl text-sm font-bold shadow-md flex items-center gap-2">
                <i class="fas fa-save"></i> Confirmer la réservation
            </button>
        @endcan
    </div>
</form>
