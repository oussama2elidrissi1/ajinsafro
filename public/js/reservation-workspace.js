/**
 * Logique UX alignée sur agent.html : extras par passager, accompagnants, total dynamique, panneau réservation.
 */
(function () {
    'use strict';

    var extrasData = {
        package: [
            { id: 'ext1', name: 'Visite historique', desc: 'Guide', priceAdult: 150, priceChild: 100, icon: 'fa-map-marked-alt' },
            { id: 'ext2', name: 'Assurance multirisque', desc: 'Annulation & santé', priceAdult: 350, priceChild: 200, icon: 'fa-shield-alt' },
            { id: 'ext3', name: 'Demi-pension', desc: 'PD + dîner', priceAdult: 1200, priceChild: 600, icon: 'fa-utensils' },
        ],
        vol: [
            { id: 'ext4', name: 'Bagage soute 23kg', desc: 'Ancillary', priceAdult: 450, priceChild: 450, icon: 'fa-suitcase' },
            { id: 'ext5', name: 'Siège', desc: 'SSR', priceAdult: 100, priceChild: 50, icon: 'fa-chair' },
            { id: 'ext6', name: 'Repas bord', desc: 'Halal / végétarien', priceAdult: 150, priceChild: 100, icon: 'fa-hamburger' },
        ],
        hebergement: [
            { id: 'ext7', name: 'Vue mer', desc: 'Supplément', priceAdult: 200, priceChild: 200, icon: 'fa-water' },
            { id: 'ext8', name: 'Transfert aéroport', desc: 'A/R', priceAdult: 300, priceChild: 150, icon: 'fa-taxi' },
            { id: 'ext9', name: 'Spa', desc: '45 min', priceAdult: 400, priceChild: 0, icon: 'fa-spa' },
        ],
    };

    var amadeusPriceMultiplier = 1.0;

    function getPassengersList() {
        var paxList = [];
        var titType = document.getElementById('titulaire-type');
        var titNom = document.getElementById('titulaire-nom');
        var titPrenom = document.getElementById('titulaire-prenom');
        var label = (titPrenom && titPrenom.value.trim()) || (titNom && titNom.value.trim()) || 'Titulaire';
        if (titType) {
            paxList.push({ id: 'titulaire', label: label, type: titType.value });
        }
        document.querySelectorAll('#companions-container .companion-row').forEach(function (row, idx) {
            var cType = row.querySelector('.companion-type-select');
            var fn = row.querySelector('.companion-first-name');
            var ln = row.querySelector('.companion-last-name');
            var nom = (fn && fn.value.trim() && ln && ln.value.trim())
                ? (fn.value.trim() + ' ' + ln.value.trim())
                : ('Accompagnant ' + (idx + 1));
            paxList.push({ id: 'comp_' + idx, label: nom, type: cType ? cType.value : 'adulte' });
        });
        return paxList;
    }

    function calculateTotal() {
        var badge = document.getElementById('badge-extras-type');
        if (!badge) return;
        var typePrestation = badge.innerText.toLowerCase().trim();
        var basePrices = {
            package: { adulte: 15000, enfant: 10000, bebe: 2000 },
            vol: { adulte: 4000, enfant: 3000, bebe: 500 },
            hebergement: { adulte: 5000, enfant: 2500, bebe: 0 },
        };
        var currentPrices = basePrices[typePrestation] || basePrices.package;
        var counts = { adulte: 0, enfant: 0, bebe: 0 };
        getPassengersList().forEach(function (p) {
            if (counts[p.type] !== undefined) counts[p.type]++;
        });
        var modifier = typePrestation === 'vol' ? amadeusPriceMultiplier : 1.0;
        var baseTotal =
            counts.adulte * currentPrices.adulte * modifier +
            counts.enfant * currentPrices.enfant * modifier +
            counts.bebe * currentPrices.bebe * modifier;

        var extrasTotal = 0;
        document.querySelectorAll('.extra-pax-cb:checked').forEach(function (cb) {
            var extId = cb.dataset.ext;
            var paxId = cb.dataset.pax;
            var paxList = getPassengersList();
            var pax = paxList.find(function (p) { return p.id === paxId; });
            var list = extrasData[typePrestation] || [];
            var extraData = list.find(function (e) { return e.id === extId; });
            if (pax && extraData) {
                extrasTotal += pax.type === 'enfant' ? extraData.priceChild : extraData.priceAdult;
            }
        });

        var customExtrasTotal = 0;
        document.querySelectorAll('#custom-extras-container > div').forEach(function (row) {
            var inputs = row.querySelectorAll('input[type="number"]');
            if (inputs.length >= 2) {
                var priceAdulte = parseFloat(inputs[0].value) || 0;
                var priceEnfant = parseFloat(inputs[1].value) || 0;
                customExtrasTotal += priceAdulte * counts.adulte + priceEnfant * counts.enfant;
            }
        });

        var totalOptions = extrasTotal + customExtrasTotal;
        var grandTotal = baseTotal + totalOptions;

        var elPax = document.getElementById('summary-pax-count');
        var elBase = document.getElementById('summary-base-price');
        var elEx = document.getElementById('summary-extras-price');
        var elGrand = document.getElementById('summary-grand-total');
        var inputMontant = document.getElementById('input-montant-total');
        if (elPax) elPax.innerText = String(getPassengersList().length);
        if (elBase) elBase.innerText = Math.round(baseTotal).toLocaleString('fr-FR') + ' MAD';
        if (elEx) elEx.innerText = '+ ' + Math.round(totalOptions).toLocaleString('fr-FR') + ' MAD';
        if (elGrand) {
            elGrand.innerHTML = Math.round(grandTotal).toLocaleString('fr-FR') +
                ' <span class="text-sm text-gray-500 font-medium">MAD</span>';
        }
        if (inputMontant) inputMontant.value = String(Math.round(grandTotal));
    }

    function renderExtras(type) {
        var container = document.getElementById('extras-container');
        var badge = document.getElementById('badge-extras-type');
        if (!container || !badge) return;

        var checkedState = {};
        container.querySelectorAll('.extra-pax-cb:checked').forEach(function (cb) {
            checkedState[cb.dataset.ext + '_' + cb.dataset.pax] = true;
        });

        badge.innerText = type.toUpperCase();
        container.innerHTML = '';
        var extras = extrasData[type] || [];
        var paxList = getPassengersList().filter(function (p) { return p.type !== 'bebe'; });

        if (extras.length === 0) {
            container.innerHTML = '<p class="text-xs text-gray-500 italic col-span-full">Aucun extra pour ce type.</p>';
            return;
        }

        extras.forEach(function (extra) {
            var paxHtml = '';
            if (paxList.length > 0) {
                paxHtml = '<div class="mt-3 pt-3 border-t border-gray-200/60 flex flex-col gap-2">';
                paxList.forEach(function (pax) {
                    var price = pax.type === 'enfant' ? extra.priceChild : extra.priceAdult;
                    var isChecked = checkedState[extra.id + '_' + pax.id] ? 'checked' : '';
                    var typeDisplay = pax.type === 'adulte' ? 'Adulte' : 'Enfant';
                    paxHtml +=
                        '<label class="flex items-center justify-between cursor-pointer hover:bg-white/50 p-1.5 rounded group/cb">' +
                        '<div class="flex items-center gap-2.5">' +
                        '<input type="checkbox" class="extra-pax-cb w-4 h-4 rounded border-gray-300 text-[#0083c4]" data-ext="' + extra.id + '" data-pax="' + pax.id + '" ' + isChecked + '>' +
                        '<span class="text-[11px] text-gray-700 font-medium">' + pax.label + ' <span class="text-gray-400 font-normal">(' + typeDisplay + ')</span></span>' +
                        '</div>' +
                        '<span class="text-[10px] font-bold text-[#f37a1f]">+ ' + price + ' MAD</span>' +
                        '</label>';
                });
                paxHtml += '</div>';
            } else {
                paxHtml = '<div class="mt-3 pt-2 text-[10px] text-gray-400 italic">Aucun passager éligible.</div>';
            }

            container.innerHTML +=
                '<div class="flex flex-col p-3.5 bg-gray-50 border border-gray-200 rounded-xl hover:border-[#0083c4]/50 hover:shadow-sm">' +
                '<div class="flex flex-col flex-1">' +
                '<span class="text-xs font-bold text-gray-800 flex items-center gap-2"><i class="fas ' + extra.icon + ' text-[#0083c4] w-4 text-center"></i> ' + extra.name + '</span>' +
                '<span class="text-[10px] text-gray-500 mt-0.5 ml-6">' + extra.desc + '</span>' +
                '</div>' + paxHtml + '</div>';
        });

        container.querySelectorAll('.extra-pax-cb').forEach(function (cb) {
            cb.addEventListener('change', calculateTotal);
        });
    }

    function updateExtrasView() {
        var badge = document.getElementById('badge-extras-type');
        if (badge && badge.innerText) {
            renderExtras(badge.innerText.toLowerCase());
            calculateTotal();
        }
    }

    function collectExtrasJson() {
        var badge = document.getElementById('badge-extras-type');
        var typePrestation = badge ? badge.innerText.toLowerCase().trim() : 'package';
        var list = extrasData[typePrestation] || [];
        var out = [];

        document.querySelectorAll('.extra-pax-cb:checked').forEach(function (cb) {
            var extId = cb.dataset.ext;
            var paxId = cb.dataset.pax;
            var extraData = list.find(function (e) { return e.id === extId; });
            var pax = getPassengersList().find(function (p) { return p.id === paxId; });
            if (!extraData || !pax) return;
            var price = pax.type === 'enfant' ? extraData.priceChild : extraData.priceAdult;
            out.push({ name: extraData.name + ' (' + pax.label + ')', price: price, pax: paxId });
        });

        document.querySelectorAll('#custom-extras-container > div').forEach(function (row) {
            var desc = row.querySelector('input[type="text"]');
            var nums = row.querySelectorAll('input[type="number"]');
            if (desc && desc.value.trim() && nums.length >= 2) {
                var pa = parseFloat(nums[0].value) || 0;
                var pe = parseFloat(nums[1].value) || 0;
                var counts = { adulte: 0, enfant: 0, bebe: 0 };
                getPassengersList().forEach(function (p) { if (counts[p.type] !== undefined) counts[p.type]++; });
                out.push({
                    name: desc.value.trim(),
                    price: pa * counts.adulte + pe * counts.enfant,
                    pax: 'custom',
                });
            }
        });

        return JSON.stringify(out);
    }

    function collectPassengersJson() {
        var out = [];
        document.querySelectorAll('#companions-container .companion-row').forEach(function (row) {
            var fn = row.querySelector('.companion-first-name');
            var ln = row.querySelector('.companion-last-name');
            var typeSel = row.querySelector('.companion-type-select');
            var dob = row.querySelector('.companion-dob-input');
            var doc = row.querySelector('.companion-doc-input');
            out.push({
                first_name: fn ? fn.value.trim() : '',
                last_name: ln ? ln.value.trim() : '',
                type: typeSel ? typeSel.value : 'adulte',
                birth_date: dob ? dob.value : null,
                document_number: doc ? doc.value.trim() : null,
            });
        });
        return JSON.stringify(out);
    }

    function syncClientModeUi() {
        var mode = document.getElementById('ws-client-mode');
        var wrap = document.getElementById('ws-client-existing-wrap');
        var sel = document.getElementById('ws-client-external-id');
        if (!mode || !wrap || !sel) return;
        if (mode.value === 'existing') {
            wrap.classList.remove('hidden');
            sel.required = true;
        } else {
            wrap.classList.add('hidden');
            sel.required = false;
        }
    }

    function showAddReservation(btn) {
        var tourId = (btn.getAttribute('data-tour-id') || '').trim();
        if (!tourId) {
            alert('Ce tour n’est pas encore lié à une fiche voyage dans Laravel. Ouvrez « Circuits / voyages », enregistrez la fiche pour créer la liaison (wp_post_id).');
            return;
        }
        var main = document.getElementById('reservations-main-content');
        var add = document.getElementById('add-reservation-view');
        if (!main || !add) return;
        main.classList.add('hidden');
        add.classList.remove('hidden');

        var type = btn.getAttribute('data-type') || 'package';
        var name = btn.getAttribute('data-name') || '';
        document.getElementById('add-res-prestation-name').textContent = name;
        document.getElementById('ws-prestation-type').value = type;
        document.getElementById('ws-tour-id').value = btn.getAttribute('data-tour-id') || '';
        document.getElementById('ws-travel-date-id').value = btn.getAttribute('data-travel-date-id') || '';

        var badge = document.getElementById('badge-extras-type');
        if (badge) badge.innerText = type.toUpperCase();

        document.querySelectorAll('.details-block').forEach(function (el) { el.classList.add('hidden'); });
        var det = document.getElementById('details-' + type);
        if (det) det.classList.remove('hidden');

        document.getElementById('custom-extras-container').innerHTML = '';
        amadeusPriceMultiplier = 1.0;
        var apiBadge = document.getElementById('api-status-badge');
        if (apiBadge) apiBadge.classList.add('hidden');

        updateExtrasView();
    }

    function hideAddReservation() {
        var main = document.getElementById('reservations-main-content');
        var add = document.getElementById('add-reservation-view');
        if (main) main.classList.remove('hidden');
        if (add) add.classList.add('hidden');
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.btn-show-add-reservation').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                showAddReservation(btn);
            });
        });

        var btnBack = document.getElementById('btn-back-from-add-reservation');
        var btnCancel = document.getElementById('btn-cancel-add-reservation');
        if (btnBack) btnBack.addEventListener('click', hideAddReservation);
        if (btnCancel) btnCancel.addEventListener('click', hideAddReservation);

        var titType = document.getElementById('titulaire-type');
        var titNom = document.getElementById('titulaire-nom');
        var titPrenom = document.getElementById('titulaire-prenom');
        if (titType) titType.addEventListener('change', updateExtrasView);
        if (titNom) titNom.addEventListener('input', updateExtrasView);
        if (titPrenom) titPrenom.addEventListener('input', updateExtrasView);

        var btnAdd = document.getElementById('btn-add-companion');
        var companionsContainer = document.getElementById('companions-container');
        var emptyMsg = document.getElementById('empty-companion-msg');
        var companionCount = 0;

        if (btnAdd && companionsContainer) {
            btnAdd.addEventListener('click', function () {
                companionCount++;
                if (emptyMsg) emptyMsg.style.display = 'none';
                var row = document.createElement('div');
                row.className = 'companion-row bg-white p-3.5 rounded-xl border border-gray-100 shadow-sm relative mt-2 flex flex-col gap-3';
                row.innerHTML =
                    '<button type="button" class="btn-remove-companion absolute right-2 top-2 w-7 h-7 flex items-center justify-center rounded-md bg-red-50 text-red-500 hover:bg-red-500 hover:text-white border border-red-100" title="Supprimer"><i class="fas fa-trash text-xs"></i></button>' +
                    '<h5 class="text-xs font-bold text-gray-600 uppercase border-b border-gray-50 pb-1">Accompagnant #' + companionCount + '</h5>' +
                    '<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 pr-8">' +
                    '<select class="companion-type-select w-full px-3 py-1.5 bg-gray-50 border border-gray-200 rounded text-[11px]">' +
                    '<option value="adulte">Adulte</option><option value="enfant">Enfant</option><option value="bebe">Bébé</option></select>' +
                    '<input type="text" class="companion-first-name w-full px-3 py-1.5 bg-gray-50 border border-gray-200 rounded text-[11px] uppercase" placeholder="Prénom">' +
                    '<input type="text" class="companion-last-name w-full px-3 py-1.5 bg-gray-50 border border-gray-200 rounded text-[11px] uppercase" placeholder="Nom">' +
                    '<input type="date" class="companion-dob-input w-full px-3 py-1.5 bg-gray-50 border border-gray-200 rounded text-[11px]">' +
                    '<input type="text" class="companion-doc-input w-full px-3 py-1.5 bg-gray-50 border border-gray-200 rounded text-[11px] uppercase col-span-2" placeholder="N° document">' +
                    '</div>';
                row.querySelector('.btn-remove-companion').addEventListener('click', function () {
                    row.remove();
                    companionCount--;
                    if (companionCount === 0 && emptyMsg) emptyMsg.style.display = 'block';
                    updateExtrasView();
                });
                row.querySelector('.companion-type-select').addEventListener('change', updateExtrasView);
                companionsContainer.appendChild(row);
                updateExtrasView();
            });
        }

        var btnCustom = document.getElementById('btn-add-custom-extra');
        if (btnCustom) {
            btnCustom.addEventListener('click', function () {
                var container = document.getElementById('custom-extras-container');
                var el = document.createElement('div');
                el.className = 'flex flex-col p-3.5 bg-[#e6f3fa]/40 border border-[#0083c4]/30 rounded-xl relative';
                el.innerHTML =
                    '<button type="button" class="btn-remove-custom-extra absolute right-2 top-2 text-gray-400 hover:text-red-500"><i class="fas fa-trash text-xs"></i></button>' +
                    '<input type="text" placeholder="Description" class="text-xs font-bold bg-white border border-gray-200 rounded px-2 py-1.5 w-full mb-2">' +
                    '<div class="flex gap-2"><div class="flex items-center gap-1 bg-white border rounded px-2 py-1"><span class="text-[10px] text-gray-400">Adulte</span>' +
                    '<input type="number" class="custom-extra-a text-[11px] w-14 text-center" placeholder="0"></div>' +
                    '<div class="flex items-center gap-1 bg-white border rounded px-2 py-1"><span class="text-[10px] text-gray-400">Enfant</span>' +
                    '<input type="number" class="custom-extra-e text-[11px] w-14 text-center" placeholder="0"></div></div>';
                el.querySelector('.btn-remove-custom-extra').addEventListener('click', function () {
                    el.remove();
                    calculateTotal();
                });
                el.querySelectorAll('input[type="number"]').forEach(function (i) {
                    i.addEventListener('input', calculateTotal);
                });
                container.appendChild(el);
            });
        }

        var btnAmadeus = document.getElementById('btn-amadeus-pricing');
        if (btnAmadeus) {
            btnAmadeus.addEventListener('click', function () {
                var orig = btnAmadeus.innerHTML;
                btnAmadeus.disabled = true;
                btnAmadeus.textContent = 'Interrogation…';
                setTimeout(function () {
                    amadeusPriceMultiplier = 1.05;
                    var b = document.getElementById('api-status-badge');
                    if (b) {
                        b.classList.remove('hidden');
                        b.textContent = 'Tarif actualisé';
                    }
                    calculateTotal();
                    btnAmadeus.innerHTML = orig;
                    btnAmadeus.disabled = false;
                }, 1200);
            });
        }

        var fileInput = document.getElementById('reservation-files');
        if (fileInput) {
            fileInput.addEventListener('change', function () {
                var fileList = document.getElementById('reservation-file-list');
                Array.from(this.files).forEach(function (file) {
                    var div = document.createElement('div');
                    div.className = 'flex items-center justify-between bg-white p-3 border border-gray-200 rounded-xl text-xs text-gray-700';
                    div.innerHTML = '<span class="truncate">' + file.name + '</span>';
                    fileList.appendChild(div);
                });
                this.value = '';
            });
        }

        var mode = document.getElementById('ws-client-mode');
        if (mode) {
            mode.addEventListener('change', syncClientModeUi);
            syncClientModeUi();
        }

        var form = document.getElementById('workspace-reservation-form');
        if (form) {
            form.addEventListener('submit', function () {
                var ex = document.getElementById('ws-extras-json');
                var pj = document.getElementById('ws-passengers-json');
                if (ex) ex.value = collectExtrasJson();
                if (pj) pj.value = collectPassengersJson();
            });
        }

        if (document.getElementById('extras-container')) {
            updateExtrasView();
        }
    });
})();
