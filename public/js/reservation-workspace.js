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

    /** Tarifs alignés sur le catalogue workspace (null = repli sur grilles statiques) */
    var workspaceLivePricing = null;

    /** Extras depuis form_prefill.extras_catalog (catalogue PHP) */
    var workspaceExtrasLive = null;

    /** Places catalogue pour calcul restant vs participants */
    var workspaceLivePlaces = null;

    /** Badge disponibilité (availability.key / label) */
    var workspaceAvailability = null;

    /** Annulation fetch GET reservation-data (changement date rapide) */
    var workspacePlacesFetchController = null;

    function buildReservationDataUrl(tourId, prestationType, travelDateId) {
        var tpl = document.getElementById('ws-reservation-data-url-template');
        if (!tpl || !tpl.value) return null;
        var base = String(tpl.value).split('__VOYAGE__').join(String(tourId));
        try {
            var u = new URL(base, window.location.href);
            u.searchParams.set('prestation_type', prestationType || 'package');
            if (travelDateId) {
                u.searchParams.set('travel_date_id', String(travelDateId));
            } else {
                u.searchParams.delete('travel_date_id');
            }
            return u.toString();
        } catch (e) {
            return null;
        }
    }

    /**
     * Places / disponibilité alignées sur la date choisie (réservations filtrées par travel_date_id, comme le store).
     */
    function syncPlacesFromServer(travelDateId) {
        var elTid = document.getElementById('ws-tour-id');
        var tourId = elTid && elTid.value ? elTid.value.trim() : '';
        var pt = document.getElementById('ws-prestation-type');
        var prest = pt ? pt.value : 'package';
        var url = buildReservationDataUrl(tourId, prest, travelDateId);
        if (!url || !tourId) {
            return;
        }
        if (workspacePlacesFetchController) {
            workspacePlacesFetchController.abort();
        }
        workspacePlacesFetchController = new AbortController();
        fetch(url, {
            credentials: 'same-origin',
            signal: workspacePlacesFetchController.signal,
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then(function (r) {
                return r.ok ? r.json() : Promise.reject(new Error('reservation-data'));
            })
            .then(function (data) {
                var pf = data && data.form_prefill;
                if (pf && pf.places) {
                    workspaceLivePlaces = pf.places;
                }
                if (pf && pf.availability) {
                    workspaceAvailability = pf.availability;
                }
                calculateTotal();
            })
            .catch(function () {
                /* ignore abort */
            });
    }

    function formatIsoDateFr(iso) {
        if (!iso || typeof iso !== 'string') return '';
        var p = iso.split('-');
        if (p.length !== 3) return iso;
        var y = parseInt(p[0], 10);
        var m = parseInt(p[1], 10) - 1;
        var d = parseInt(p[2], 10);
        if (isNaN(y) || isNaN(m) || isNaN(d)) return iso;
        var dt = new Date(y, m, d);
        return dt.toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' });
    }

    function parseWsFormPrefillMap() {
        var el = document.getElementById('ws-form-prefill-json');
        if (!el) return {};
        try {
            return JSON.parse(el.textContent || '{}');
        } catch (e) {
            return {};
        }
    }

    function parseAmountFromFrLabel(label) {
        if (!label) return null;
        var s = String(label).replace(/\u00a0/g, ' ').replace(/\s+/g, '').replace(',', '.');
        var m = s.match(/(\d+(\.\d+)?)/);
        return m ? parseFloat(m[1]) : null;
    }

    function getEffectiveBasePrices(typePrestation) {
        if (workspaceLivePricing && workspaceLivePricing.adult != null && !isNaN(Number(workspaceLivePricing.adult))) {
            var ad = Number(workspaceLivePricing.adult);
            var ch = workspaceLivePricing.child;
            if (ch == null || isNaN(Number(ch))) {
                ch = parseAmountFromFrLabel(workspaceLivePricing.childLabel) || Math.round(ad * 0.75);
            } else {
                ch = Number(ch);
            }
            var bb = workspaceLivePricing.bebe != null ? Number(workspaceLivePricing.bebe) : 0;
            if (isNaN(bb)) bb = 0;
            return { adulte: ad, enfant: ch, bebe: bb };
        }
        var basePrices = {
            package: { adulte: 15000, enfant: 10000, bebe: 2000 },
            vol: { adulte: 4000, enfant: 3000, bebe: 500 },
            hebergement: { adulte: 5000, enfant: 2500, bebe: 0 },
        };
        return basePrices[typePrestation] || basePrices.package;
    }

    function getExtrasListForType(typePrestation) {
        if (workspaceExtrasLive !== null && workspaceExtrasLive !== undefined) {
            if (workspaceExtrasLive.length === 0) {
                return [];
            }
            return workspaceExtrasLive.map(function (e) {
                return {
                    id: e.id,
                    name: e.name,
                    desc: e.desc || '',
                    priceAdult: e.price_adult != null ? Number(e.price_adult) : 0,
                    priceChild: e.price_child != null ? Number(e.price_child) : 0,
                    icon: e.icon || 'fa-plus-circle',
                };
            });
        }
        return extrasData[typePrestation] || [];
    }

    function renderPrefillSections(pf) {
        if (!pf) return '';
        var cur = (pf.prices && pf.prices.currency) ? pf.prices.currency : 'MAD';
        var h = '';

        h += '<div class="rounded-xl bg-white/80 border border-slate-200/80 p-4 space-y-2">';
        h += '<p class="text-[10px] font-extrabold uppercase tracking-wide text-slate-400">1. Informations prestation</p>';
        h += '<dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1.5 text-xs">';
        h += '<div><dt class="text-slate-400 font-semibold">Référence</dt><dd class="font-bold text-slate-800 font-mono">' + escapeWsHtml(String(pf.code || '—')) + '</dd></div>';
        h += '<div><dt class="text-slate-400 font-semibold">Type</dt><dd class="font-bold text-slate-800">' + escapeWsHtml(String(pf.kind || '')) + '</dd></div>';
        if (pf.destination) h += '<div class="sm:col-span-2"><dt class="text-slate-400 font-semibold">Destination</dt><dd class="font-bold text-slate-800">' + escapeWsHtml(String(pf.destination)) + '</dd></div>';
        if (pf.duration) h += '<div class="sm:col-span-2"><dt class="text-slate-400 font-semibold">Durée</dt><dd class="font-bold text-slate-800">' + escapeWsHtml(String(pf.duration)) + '</dd></div>';
        var refs = [];
        if (pf.wp_post_id) refs.push('WP #' + pf.wp_post_id);
        if (pf.laravel_voyage_id) refs.push('Laravel #' + pf.laravel_voyage_id);
        if (refs.length) h += '<div class="sm:col-span-2"><dt class="text-slate-400 font-semibold">Références</dt><dd class="font-bold text-slate-800">' + escapeWsHtml(refs.join(' · ')) + '</dd></div>';
        if (pf.post_status_label) h += '<div class="sm:col-span-2"><dt class="text-slate-400 font-semibold">Statut publication</dt><dd><span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700">' + escapeWsHtml(String(pf.post_status_label)) + '</span></dd></div>';
        h += '</dl></div>';

        var tds = pf.travel_dates || [];
        h += '<div class="rounded-xl bg-white/80 border border-slate-200/80 p-4 space-y-2">';
        h += '<p class="text-[10px] font-extrabold uppercase tracking-wide text-slate-400">2. Départs disponibles</p>';
        if (tds.length) {
            h += '<p class="text-xs text-slate-600">' + tds.length + ' date(s) — la capacité « restantes » se met à jour selon la date sélectionnée (réservations sur ce départ).</p>';
        } else {
            h += '<p class="text-xs text-amber-700 font-medium">Aucune date dans la disponibilité WordPress pour ce voyage.</p>';
        }
        h += '</div>';

        var pr = pf.prices || {};
        h += '<div class="rounded-xl bg-white/80 border border-slate-200/80 p-4 space-y-2">';
        h += '<p class="text-[10px] font-extrabold uppercase tracking-wide text-slate-400">3. Tarification (catalogue)</p>';
        h += '<dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1.5 text-xs">';
        h += '<div><dt class="text-slate-400 font-semibold">Adulte</dt><dd class="font-bold text-emerald-800">' + escapeWsHtml(String(pr.adult_label || (pr.adult_amount != null ? Math.round(pr.adult_amount) + ' ' + cur : '—'))) + '</dd></div>';
        h += '<div><dt class="text-slate-400 font-semibold">Enfant</dt><dd class="font-bold text-slate-800">' + escapeWsHtml(String(pr.child_label || (pr.child_amount != null ? Math.round(pr.child_amount) + ' ' + cur : '—'))) + '</dd></div>';
        h += '<div><dt class="text-slate-400 font-semibold">Devise</dt><dd class="font-bold text-slate-800">' + escapeWsHtml(String(cur)) + '</dd></div>';
        h += '<div><dt class="text-slate-400 font-semibold">Mode</dt><dd class="font-bold text-slate-800">' + escapeWsHtml(String(pr.pricing_mode || '—')) + '</dd></div>';
        h += '</dl></div>';

        var pl = pf.places || {};
        var av = pf.availability || {};
        var avLabel = av.label ? escapeWsHtml(String(av.label)) : '';
        var avCls = 'bg-slate-100 text-slate-800 border-slate-200';
        if (av.key === 'past') avCls = 'bg-amber-50 text-amber-900 border-amber-200';
        else if (av.key === 'full') avCls = 'bg-red-50 text-red-800 border-red-200';
        else if (av.key === 'ok') avCls = 'bg-emerald-50 text-emerald-900 border-emerald-200';
        else if (av.key === 'low') avCls = 'bg-orange-50 text-orange-900 border-orange-200';
        h += '<div class="rounded-xl bg-white/80 border border-slate-200/80 p-4 space-y-2">';
        h += '<div class="flex flex-wrap items-center justify-between gap-2 mb-2">';
        h += '<p class="text-[10px] font-extrabold uppercase tracking-wide text-slate-400">4. Capacité</p>';
        if (avLabel) h += '<span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-extrabold ' + avCls + '">' + avLabel + '</span>';
        h += '</div>';
        h += '<dl class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs">';
        h += '<div class="bg-slate-50 rounded-lg p-2 text-center border border-slate-100"><dt class="text-[10px] text-slate-400 font-bold">Total</dt><dd class="font-extrabold text-slate-800">' + (pl.total != null ? pl.total : '—') + '</dd></div>';
        h += '<div class="bg-slate-50 rounded-lg p-2 text-center border border-slate-100"><dt class="text-[10px] text-slate-400 font-bold">Réservées</dt><dd class="font-extrabold text-slate-800">' + (pl.reserved != null ? pl.reserved : '—') + '</dd></div>';
        h += '<div class="bg-slate-50 rounded-lg p-2 text-center border border-slate-100"><dt class="text-[10px] text-slate-400 font-bold">Restantes</dt><dd class="font-extrabold text-emerald-800">' + (pl.remaining != null ? pl.remaining : '—') + '</dd></div>';
        h += '<div class="bg-slate-50 rounded-lg p-2 text-center border border-slate-100"><dt class="text-[10px] text-slate-400 font-bold">Calcul</dt><dd class="font-extrabold text-slate-800 text-[10px]">TourPlacesCalculator</dd></div>';
        h += '</dl></div>';

        var rooms = pf.rooms || [];
        if (rooms.length) {
            h += '<div class="rounded-xl bg-white/80 border border-slate-200/80 p-4 space-y-2">';
            h += '<p class="text-[10px] font-extrabold uppercase tracking-wide text-slate-400">Chambres (détail)</p>';
            h += '<ul class="space-y-1.5 text-xs text-slate-700">';
            rooms.forEach(function (r) {
                var line = r.detail_label || ((r.room_type || '') + ' — ' + (r.product != null ? r.product + ' pl.' : ''));
                h += '<li class="flex gap-2"><span class="text-slate-400 font-bold">•</span><span>' + escapeWsHtml(String(line)) + '</span></li>';
            });
            h += '</ul></div>';
        }

        h += '<div class="rounded-xl bg-white/80 border border-slate-200/80 p-4 space-y-2">';
        h += '<p class="text-[10px] font-extrabold uppercase tracking-wide text-slate-400">5. Extras</p>';
        h += '<p class="text-xs text-slate-600">Extras définis dans le CRUD voyage (onglet Extras) — cochables par passager ci-dessous.</p>';
        h += '</div>';

        h += '<div class="rounded-xl bg-[#0e3a5a]/5 border border-[#0e3a5a]/10 p-4 space-y-1">';
        h += '<p class="text-[10px] font-extrabold uppercase tracking-wide text-[#0e3a5a]">6. Réservation</p>';
        h += '<p class="text-xs text-slate-600">Renseignez le titulaire, les participants et validez le total en bas de formulaire.</p>';
        h += '</div>';

        return h;
    }

    function escapeWsHtml(s) {
        if (s == null || s === '') return '';
        var d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    function applyWorkspacePrefill(pf, type, nameDisplay) {
        workspaceLivePricing = null;
        workspaceExtrasLive = null;
        workspaceLivePlaces = null;
        workspaceAvailability = null;
        var panel = document.getElementById('ws-prefill-panel');
        var sec = document.getElementById('ws-prefill-sections');
        var head = document.getElementById('ws-prefill-heading');
        var sub = document.getElementById('ws-prefill-sub');
        var badge = document.getElementById('ws-prefill-type-badge');
        var depWrap = document.getElementById('ws-departure-wrap');
        var depSel = document.getElementById('ws-departure-select');
        var depHint = document.getElementById('ws-departure-hint');
        var hidTravel = document.getElementById('ws-travel-date-id');

        if (!panel || !sec) return;

        if (!pf) {
            panel.classList.add('hidden');
            if (depWrap) depWrap.classList.add('hidden');
            return;
        }

        panel.classList.remove('hidden');
        if (head) head.textContent = pf.title || nameDisplay || '—';
        if (sub) {
            var bits = [];
            if (pf.code) bits.push(String(pf.code));
            if (pf.destination) bits.push(String(pf.destination));
            sub.textContent = bits.join(' · ');
            sub.classList.toggle('hidden', bits.length === 0);
        }
        if (badge) badge.textContent = (type || pf.kind || 'package').toUpperCase();
        sec.innerHTML = renderPrefillSections(pf);

        var pr = pf.prices || {};
        var adultAmt = pr.adult_amount != null ? Number(pr.adult_amount) : parseAmountFromFrLabel(pr.adult_label);
        var childAmt = pr.child_amount != null ? Number(pr.child_amount) : parseAmountFromFrLabel(pr.child_label);
        if (isNaN(adultAmt)) adultAmt = null;
        if (isNaN(childAmt)) childAmt = null;
        workspaceLivePricing = {
            adult: adultAmt,
            child: childAmt,
            bebe: 0,
            currency: pr.currency || 'MAD',
            childLabel: pr.child_label || '',
        };

        workspaceExtrasLive = Array.isArray(pf.extras_catalog) ? pf.extras_catalog : [];
        if (pf.places) {
            workspaceLivePlaces = pf.places;
        }
        if (pf.availability) {
            workspaceAvailability = pf.availability;
        }

        var roomSel = document.getElementById('ws-package-room-type');
        if (roomSel) {
            if (pf.rooms && pf.rooms.length) {
                roomSel.innerHTML = '';
                var seen = {};
                pf.rooms.forEach(function (r) {
                    var rt = (r.room_type || '').trim();
                    if (!rt || seen[rt]) return;
                    seen[rt] = true;
                    var opt = document.createElement('option');
                    opt.value = rt;
                    var dl = r.detail_label || '';
                    opt.textContent = dl || (rt + (r.product != null ? ' — ' + r.product + ' pl.' : ''));
                    roomSel.appendChild(opt);
                });
                if (roomSel.options.length) roomSel.selectedIndex = 0;
            } else if (type === 'package') {
                roomSel.innerHTML = '<option value="">— Choisir —</option><option>Chambre Double</option><option>Chambre Twin</option><option>Chambre Triple</option>';
            }
        }

        var tds = pf.travel_dates || [];
        if (depWrap && depSel && hidTravel) {
            depSel.innerHTML = '';
            depSel.onchange = null;
            if (tds.length) {
                depWrap.classList.remove('hidden');
                tds.forEach(function (td) {
                    var opt = document.createElement('option');
                    var idVal = td.id != null ? String(td.id) : '';
                    opt.value = idVal;
                    opt.setAttribute('data-is-past', td.is_past ? '1' : '0');
                    var lbl = formatIsoDateFr(td.date_iso || '');
                    if (!lbl) lbl = String(td.date_label || '').trim();
                    opt.textContent = lbl + (td.is_past ? ' (passé)' : '');
                    depSel.appendChild(opt);
                });
                var defId = pf.default_travel_date_id != null ? String(pf.default_travel_date_id) : (pf.form && pf.form.travel_date_id != null ? String(pf.form.travel_date_id) : '');
                if (defId && Array.prototype.some.call(depSel.options, function (o) { return o.value === defId; })) {
                    depSel.value = defId;
                } else if (depSel.options.length) {
                    depSel.selectedIndex = 0;
                }
                hidTravel.value = depSel.value || '';
                var onDepChange = function () {
                    hidTravel.value = depSel.value || '';
                    syncPlacesFromServer(depSel.value || '');
                    calculateTotal();
                };
                depSel.onchange = onDepChange;
                syncPlacesFromServer(depSel.value || '');
                if (depHint) depHint.textContent = 'Dates = disponibilité WordPress (format long français, aligné catalogue).';
            } else {
                depWrap.classList.add('hidden');
                hidTravel.value = (pf.form && pf.form.travel_date_id != null) ? String(pf.form.travel_date_id) : '';
                if (depHint) depHint.textContent = '';
            }
        }
    }

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
        var currentPrices = getEffectiveBasePrices(typePrestation);
        var cur = workspaceLivePricing && workspaceLivePricing.currency ? workspaceLivePricing.currency : 'MAD';
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
            var list = getExtrasListForType(typePrestation);
            var extraData = list.find(function (e) { return e.id === extId; });
            if (pax && extraData) {
                extrasTotal += pax.type === 'enfant' ? extraData.priceChild : extraData.priceAdult;
            }
        });

        var totalOptions = extrasTotal;
        var grandTotal = baseTotal + totalOptions;

        var elPax = document.getElementById('summary-pax-count');
        var elBase = document.getElementById('summary-base-price');
        var elEx = document.getElementById('summary-extras-price');
        var elGrand = document.getElementById('summary-grand-total');
        var inputMontant = document.getElementById('input-montant-total');
        if (elPax) elPax.innerText = String(getPassengersList().length);
        if (elBase) elBase.innerText = Math.round(baseTotal).toLocaleString('fr-FR') + ' ' + cur;
        if (elEx) elEx.innerText = '+ ' + Math.round(totalOptions).toLocaleString('fr-FR') + ' ' + cur;
        if (elGrand) {
            elGrand.innerHTML = Math.round(grandTotal).toLocaleString('fr-FR') +
                ' <span class="text-sm text-gray-500 font-medium">' + escapeWsHtml(cur) + '</span>';
        }
        if (inputMontant) inputMontant.value = String(Math.round(grandTotal));

        var elReste = document.getElementById('summary-montant-reste');
        var mpayeEl = document.getElementById('ws-montant-paye');
        if (elReste && mpayeEl) {
            var paye = parseFloat(mpayeEl.value) || 0;
            var reste = grandTotal - paye;
            elReste.textContent = Math.round(reste).toLocaleString('fr-FR') + ' ' + cur;
        }

        var capEl = document.getElementById('ws-capacity-live');
        var submitBtn = document.getElementById('ws-booking-submit');
        var paxN = getPassengersList().length;
        var rem0 = workspaceLivePlaces && workspaceLivePlaces.remaining != null ? Number(workspaceLivePlaces.remaining) : null;
        var st = workspaceLivePlaces && workspaceLivePlaces.state ? String(workspaceLivePlaces.state) : '';
        var avKey = workspaceAvailability && workspaceAvailability.key ? String(workspaceAvailability.key) : '';
        var depSel = document.getElementById('ws-departure-select');
        var pastDep = false;
        if (depSel && depSel.selectedOptions && depSel.selectedOptions.length) {
            pastDep = depSel.selectedOptions[0].getAttribute('data-is-past') === '1';
        }
        var blockedByAvail = avKey === 'full' || avKey === 'past' || (st === 'ok' && rem0 !== null && !isNaN(rem0) && rem0 <= 0);
        var over = st === 'ok' && rem0 !== null && !isNaN(rem0) && paxN > rem0;
        if (capEl) {
            if (pastDep) {
                capEl.classList.remove('hidden');
                capEl.innerHTML = '<span class="font-bold text-amber-800">Départ passé</span> — choisissez une date à venir pour une nouvelle réservation.';
            } else if (blockedByAvail && avKey === 'full') {
                capEl.classList.remove('hidden');
                capEl.innerHTML = '<span class="font-bold text-red-800">Complet</span> — plus de places disponibles sur ce départ.';
            } else if (blockedByAvail && avKey === 'past') {
                capEl.classList.remove('hidden');
                capEl.innerHTML = '<span class="font-bold text-amber-800">Départs passés</span> — aucune date future dans la disponibilité.';
            } else if (st === 'ok' && rem0 !== null && !isNaN(rem0)) {
                var after = rem0 - paxN;
                capEl.classList.remove('hidden');
                capEl.innerHTML = '<span class="font-bold text-slate-800">Places (catalogue)</span> : ' + rem0 + ' restante(s) pour ce départ · <span class="font-extrabold">' + paxN + '</span> voyageur(s) dans le formulaire' +
                    (after >= 0 ? ' → <span class="text-emerald-700 font-bold">il restera ' + after + ' après réservation</span>' : ' → <span class="text-red-700 font-bold">dépassement de ' + Math.abs(after) + '</span>');
            } else {
                capEl.classList.add('hidden');
                capEl.textContent = '';
            }
        }
        if (submitBtn) {
            var blocked = blockedByAvail || over || pastDep;
            submitBtn.disabled = !!blocked;
            submitBtn.classList.toggle('opacity-50', !!blocked);
            submitBtn.classList.toggle('cursor-not-allowed', !!blocked);
            if (pastDep) {
                submitBtn.title = 'Ce départ est passé — sélectionnez une date à venir.';
            } else if (blockedByAvail) {
                submitBtn.title = 'Places insuffisantes ou départ indisponible.';
            } else if (over) {
                submitBtn.title = 'Réduisez le nombre de voyageurs ou choisissez une autre date.';
            } else {
                submitBtn.title = '';
            }
        }
    }

    function renderExtras(type) {
        var container = document.getElementById('extras-container');
        var badge = document.getElementById('badge-extras-type');
        if (!container || !badge) return;

        var curX = workspaceLivePricing && workspaceLivePricing.currency ? workspaceLivePricing.currency : 'MAD';

        var checkedState = {};
        container.querySelectorAll('.extra-pax-cb:checked').forEach(function (cb) {
            checkedState[cb.dataset.ext + '_' + cb.dataset.pax] = true;
        });

        badge.innerText = type.toUpperCase();
        container.innerHTML = '';
        var extras = getExtrasListForType(type);
        var paxList = getPassengersList().filter(function (p) { return p.type !== 'bebe'; });

        if (extras.length === 0) {
            var emptyMsg = (workspaceExtrasLive !== null && workspaceExtrasLive !== undefined && workspaceExtrasLive.length === 0)
                ? 'Aucun extra configuré pour ce voyage. Ajoutez-en dans Circuits → voyages → onglet « Extras ».'
                : 'Aucun extra pour ce type.';
            container.innerHTML = '<p class="text-xs text-gray-500 italic col-span-full">' + emptyMsg + '</p>';
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
                        '<span class="text-[10px] font-bold text-[#f37a1f]">+ ' + price + ' ' + curX + '</span>' +
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
        var list = getExtrasListForType(typePrestation);
        var out = [];

        document.querySelectorAll('.extra-pax-cb:checked').forEach(function (cb) {
            var extId = cb.dataset.ext;
            var paxId = cb.dataset.pax;
            var extraData = list.find(function (e) { return e.id === extId; });
            var pax = getPassengersList().find(function (p) { return p.id === paxId; });
            if (!extraData || !pax) return;
            var price = pax.type === 'enfant' ? extraData.priceChild : extraData.priceAdult;
            out.push({
                voyage_extra_id: extId,
                name: extraData.name + ' (' + pax.label + ')',
                price: price,
                pax: paxId,
            });
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
        var rowCode = (btn.getAttribute('data-row-code') || '').trim();
        var prefillMap = parseWsFormPrefillMap();
        var pf = rowCode && prefillMap[rowCode] ? prefillMap[rowCode] : null;

        document.getElementById('ws-prestation-type').value = type;
        document.getElementById('ws-tour-id').value = tourId;
        document.getElementById('ws-travel-date-id').value = btn.getAttribute('data-travel-date-id') || '';

        if (pf && pf.form && pf.form.tour_id != null) {
            document.getElementById('ws-tour-id').value = String(pf.form.tour_id);
        }

        document.getElementById('add-res-prestation-name').textContent = pf && pf.title ? pf.title : name;

        if (pf) {
            applyWorkspacePrefill(pf, type, name);
        } else {
            workspaceLivePricing = null;
            workspaceExtrasLive = null;
            workspaceLivePlaces = null;
            workspaceAvailability = null;
            var panelOff = document.getElementById('ws-prefill-panel');
            if (panelOff) panelOff.classList.add('hidden');
            var depOff = document.getElementById('ws-departure-wrap');
            if (depOff) depOff.classList.add('hidden');
        }

        var badge = document.getElementById('badge-extras-type');
        if (badge) badge.innerText = type.toUpperCase();

        document.querySelectorAll('.details-block').forEach(function (el) { el.classList.add('hidden'); });
        var det = document.getElementById('details-' + type);
        if (det) det.classList.remove('hidden');

        amadeusPriceMultiplier = 1.0;
        var apiBadge = document.getElementById('api-status-badge');
        if (apiBadge) apiBadge.classList.add('hidden');

        var mp = document.getElementById('ws-montant-paye');
        if (mp) mp.value = '0';

        updateExtrasView();
    }

    function hideAddReservation() {
        workspaceLivePricing = null;
        workspaceExtrasLive = null;
        workspaceLivePlaces = null;
        workspaceAvailability = null;
        if (workspacePlacesFetchController) {
            try {
                workspacePlacesFetchController.abort();
            } catch (e) { /* ignore */ }
            workspacePlacesFetchController = null;
        }
        var capEl = document.getElementById('ws-capacity-live');
        if (capEl) {
            capEl.classList.add('hidden');
            capEl.textContent = '';
        }
        var submitBtn = document.getElementById('ws-booking-submit');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            submitBtn.title = '';
        }
        var panel = document.getElementById('ws-prefill-panel');
        if (panel) panel.classList.add('hidden');
        var main = document.getElementById('reservations-main-content');
        var add = document.getElementById('add-reservation-view');
        if (main) main.classList.remove('hidden');
        if (add) add.classList.add('hidden');
    }

    /**
     * Ouverture du formulaire workspace depuis le modal détails (données catalogue via code).
     */
    window.wsOpenReservationForm = function (opts) {
        opts = opts || {};
        var code = String(opts.code || '').trim();
        var map = parseWsFormPrefillMap();
        var pf = code && map[code] ? map[code] : null;
        var fake = document.createElement('button');
        fake.className = 'btn-show-add-reservation';
        fake.setAttribute('data-row-code', code);
        fake.setAttribute('data-tour-id', String(opts.tourId || (pf && pf.form && pf.form.tour_id) || ''));
        fake.setAttribute('data-type', opts.type || (pf && pf.kind) || 'package');
        fake.setAttribute('data-name', String(opts.name || (pf && pf.title) || ''));
        fake.setAttribute('data-travel-date-id', String(opts.travelDateId !== undefined && opts.travelDateId !== null ? opts.travelDateId : (pf && pf.form && pf.form.travel_date_id != null ? pf.form.travel_date_id : '')));
        showAddReservation(fake);
    };

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

        var mpInput = document.getElementById('ws-montant-paye');
        if (mpInput) {
            mpInput.addEventListener('input', calculateTotal);
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

        var roomTypeEl = document.getElementById('ws-package-room-type');
        if (roomTypeEl) {
            roomTypeEl.addEventListener('change', calculateTotal);
        }
    });
})();
