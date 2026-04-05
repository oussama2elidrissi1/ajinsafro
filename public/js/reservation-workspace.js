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

    var workspaceCurrentStep = 1;

    function getWsFlowSteps() {
        return Array.prototype.slice.call(document.querySelectorAll('.ws-flow-step[data-ws-step]'));
    }

    function getSelectedDepartureLabel() {
        var depSel = document.getElementById('ws-departure-select');
        if (!depSel || !depSel.selectedOptions || !depSel.selectedOptions.length) return '—';
        return depSel.selectedOptions[0].textContent || '—';
    }

    function updateStickySummary(partial) {
        partial = partial || {};
        var titleEl = document.getElementById('ws-sticky-title');
        var dateEl = document.getElementById('ws-sticky-date');
        var paxEl = document.getElementById('ws-sticky-pax');
        var totalEl = document.getElementById('ws-sticky-total');

        if (titleEl && partial.title !== undefined) titleEl.textContent = partial.title || 'Aucune sélection';
        if (dateEl && partial.date !== undefined) dateEl.textContent = partial.date || '—';
        if (paxEl && partial.pax !== undefined) paxEl.textContent = String(partial.pax || 0);
        if (totalEl && partial.total !== undefined) totalEl.textContent = partial.total || '0 MAD';
    }

    function setWorkspaceStep(step) {
        var steps = getWsFlowSteps();
        if (!steps.length) return;
        var stepNum = Number(step) || 1;
        var max = steps.length;
        if (stepNum < 1) stepNum = 1;
        if (stepNum > max) stepNum = max;
        workspaceCurrentStep = stepNum;

        steps.forEach(function (section) {
            var isActive = Number(section.getAttribute('data-ws-step')) === stepNum;
            section.classList.toggle('is-active', isActive);
            section.hidden = !isActive;
        });

        document.querySelectorAll('[data-ws-step-nav]').forEach(function (btn) {
            var isActive = Number(btn.getAttribute('data-ws-step-nav')) === stepNum;
            btn.classList.toggle('is-active', isActive);
            btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
            btn.tabIndex = isActive ? 0 : -1;
        });
    }

    function resetWorkspaceFlow() {
        setWorkspaceStep(1);
    }

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

    /** Libellé agent (pas les codes techniques package/vol/hebergement). */
    function wsKindLabel(kind) {
        var k = String(kind || 'package').toLowerCase();
        if (k === 'vol') return 'Vol';
        if (k === 'hebergement') return 'Hébergement';
        return 'Circuit';
    }

    function getWorkspacePrestationType() {
        var badge = document.getElementById('badge-extras-type');
        if (!badge) return 'package';
        var d = badge.getAttribute('data-ws-prestation');
        if (d) return d.toLowerCase().trim();
        var t = badge.innerText.toLowerCase().trim();
        if (t === 'circuit') return 'package';
        return t || 'package';
    }

    function setWorkspacePrestationType(type) {
        var raw = String(type || 'package').toLowerCase();
        var normalized = raw === 'vol' || raw === 'hebergement' ? raw : 'package';
        var label = wsKindLabel(normalized);
        ['badge-extras-type', 'ws-prefill-type-badge'].forEach(function (id) {
            var badge = document.getElementById(id);
            if (!badge) return;
            badge.setAttribute('data-ws-prestation', normalized);
            badge.textContent = label;
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
                    selectionMode: e.selection_mode || 'per_pax',
                    pricingType: e.pricing_type || 'per_person',
                    unitPrice: e.unit_price != null ? Number(e.unit_price) : (e.price_adult != null ? Number(e.price_adult) : 0),
                    quantityDefault: e.quantity_default != null ? Math.max(1, Number(e.quantity_default) || 1) : 1,
                    extraType: e.extra_type || '',
                    activityId: e.activity_id != null ? Number(e.activity_id) : null,
                };
            });
        }
        return extrasData[typePrestation] || [];
    }

    /** Résumé voyage lisible agent uniquement (aucun ID / debug / logique interne). */
    function renderPrefillSections(pf) {
        if (!pf) return '';
        var cur = (pf.prices && pf.prices.currency) ? pf.prices.currency : 'MAD';
        var pr = pf.prices || {};
        var adultLabel = pr.adult_label || (pr.adult_amount != null ? Math.round(pr.adult_amount) + ' ' + cur : '—');
        var pl = pf.places || {};
        var rem = pl.remaining != null ? pl.remaining : '—';
        var av = pf.availability || {};
        var avKey = av.key || '';
        var avLabel = av.label ? escapeWsHtml(String(av.label)) : '';
        var stClass = 'ws-pill ws-pill--muted';
        if (avKey === 'ok') stClass = 'ws-pill ws-pill--ok';
        else if (avKey === 'full') stClass = 'ws-pill ws-pill--danger';
        else if (avKey === 'past' || avKey === 'low') stClass = 'ws-pill ws-pill--warn';

        var h = '';
        h += '<div class="ws-agent-summary">';
        h += '<div class="ws-agent-summary__row">';
        h += '<div class="ws-agent-kv"><span class="ws-agent-kv__label">Prix adulte</span><span class="ws-agent-kv__value ws-agent-kv__value--accent">' + escapeWsHtml(String(adultLabel)) + '</span></div>';
        h += '<div class="ws-agent-kv"><span class="ws-agent-kv__label">Places restantes</span><span class="ws-agent-kv__value">' + escapeWsHtml(String(rem));
        if (avLabel) h += ' <span class="' + stClass + '">' + avLabel + '</span>';
        h += '</span></div>';
        h += '</div>';
        var tds = pf.travel_dates || [];
        if (!tds.length) {
            h += '<p class="ws-agent-note ws-agent-note--warn">Aucune date de départ disponible pour l’instant.</p>';
        }
        h += '</div>';
        return h;
    }

    function escapeWsHtml(s) {
        if (s == null || s === '') return '';
        var d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    function applyWorkspacePrefill(pf, type, nameDisplay, preferredTravelDateId) {
        preferredTravelDateId = preferredTravelDateId != null ? String(preferredTravelDateId).trim() : '';
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
            if (pf.destination) {
                sub.textContent = String(pf.destination);
                sub.classList.remove('hidden');
            } else {
                sub.textContent = '';
                sub.classList.add('hidden');
            }
        }
        setWorkspacePrestationType(type || pf.kind || 'package');
        updateStickySummary({
            title: pf.title || nameDisplay || 'Aucune sélection',
        });
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
                var pick = preferredTravelDateId;
                if (pick && Array.prototype.some.call(depSel.options, function (o) { return o.value === pick; })) {
                    depSel.value = pick;
                } else if (defId && Array.prototype.some.call(depSel.options, function (o) { return o.value === defId; })) {
                    depSel.value = defId;
                } else if (depSel.options.length) {
                    depSel.selectedIndex = 0;
                }
                hidTravel.value = depSel.value || preferredTravelDateId || '';
                var onDepChange = function () {
                hidTravel.value = depSel.value || '';
                syncPlacesFromServer(depSel.value || '');
                updateStickySummary({ date: getSelectedDepartureLabel() });
                calculateTotal();
            };
                depSel.onchange = onDepChange;
                syncPlacesFromServer(depSel.value || '');
                updateStickySummary({ date: getSelectedDepartureLabel() });
                if (depHint) depHint.textContent = 'Choisissez la date de départ.';
            } else {
                depWrap.classList.add('hidden');
                hidTravel.value = preferredTravelDateId || ((pf.form && pf.form.travel_date_id != null) ? String(pf.form.travel_date_id) : '');
                updateStickySummary({ date: '—' });
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
        var typePrestation = getWorkspacePrestationType();
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
        var list = getExtrasListForType(typePrestation);
        document.querySelectorAll('.extra-pax-cb:checked').forEach(function (cb) {
            var extId = cb.dataset.ext;
            var paxId = cb.dataset.pax;
            var paxList = getPassengersList();
            var pax = paxList.find(function (p) { return p.id === paxId; });
            var extraData = list.find(function (e) { return e.id === extId; });
            if (pax && extraData) {
                extrasTotal += pax.type === 'enfant' ? extraData.priceChild : extraData.priceAdult;
            }
        });
        document.querySelectorAll('.extra-item-cb:checked').forEach(function (cb) {
            var extId = cb.dataset.ext;
            var extraData = list.find(function (e) { return e.id === extId; });
            if (!extraData) return;
            var qtyInput = document.querySelector('.extra-item-qty[data-ext="' + extId + '"]');
            var qty = qtyInput ? parseInt(qtyInput.value || '1', 10) : 1;
            if (!qty || qty < 1) qty = 1;
            extrasTotal += (Number(extraData.unitPrice) || 0) * qty;
            var lineTotal = document.querySelector('.extra-item-total[data-ext="' + extId + '"]');
            if (lineTotal) {
                lineTotal.textContent = (Math.round(((Number(extraData.unitPrice) || 0) * qty))).toLocaleString('fr-FR') + ' ' + cur;
            }
        });
        document.querySelectorAll('.extra-item-qty').forEach(function (qtyInput) {
            var extId = qtyInput.dataset.ext;
            var extraData = list.find(function (e) { return e.id === extId; });
            if (!extraData) return;
            var qty = parseInt(qtyInput.value || '1', 10);
            if (!qty || qty < 1) qty = 1;
            var lineTotal = document.querySelector('.extra-item-total[data-ext="' + extId + '"]');
            if (lineTotal) {
                lineTotal.textContent = (Math.round(((Number(extraData.unitPrice) || 0) * qty))).toLocaleString('fr-FR') + ' ' + cur;
            }
        });

        var totalOptions = extrasTotal;
        var grandTotal = baseTotal + totalOptions;

        var elPax = document.getElementById('summary-pax-count');
        var elBase = document.getElementById('summary-base-price');
        var elEx = document.getElementById('summary-extras-price');
        var elGrand = document.getElementById('summary-grand-total');
        var inputMontant = document.getElementById('input-montant-total');
        var paxCount = getPassengersList().length;
        if (elPax) elPax.innerText = String(paxCount);
        var paxDisp = document.getElementById('ws-pax-total-display');
        if (paxDisp) paxDisp.textContent = String(paxCount);
        if (elBase) elBase.innerText = Math.round(baseTotal).toLocaleString('fr-FR') + ' ' + cur;
        if (elEx) elEx.innerText = '+ ' + Math.round(totalOptions).toLocaleString('fr-FR') + ' ' + cur;
        if (elGrand) {
            elGrand.innerHTML = Math.round(grandTotal).toLocaleString('fr-FR') +
                ' <span class="text-sm text-gray-500 font-medium">' + escapeWsHtml(cur) + '</span>';
        }
        if (inputMontant) inputMontant.value = String(Math.round(grandTotal));
        updateStickySummary({
            pax: paxCount,
            total: Math.round(grandTotal).toLocaleString('fr-FR') + ' ' + cur,
        });

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
        // Ne pas bloquer sur avKey === 'past' seul : packages Laravel (places.state = na) peuvent
        // exposer une pastille « past » sans gestion de capacité WP. Le passé réel = pastDep (date choisie).
        var blockedByAvail = avKey === 'full' || (st === 'ok' && rem0 !== null && !isNaN(rem0) && rem0 <= 0);
        var over = st === 'ok' && rem0 !== null && !isNaN(rem0) && paxN > rem0;
        if (capEl) {
            capEl.classList.remove('ws-capacity-banner--ok', 'ws-capacity-banner--danger', 'ws-capacity-banner--warn');
            capEl.classList.add('hidden');
            capEl.innerHTML = '';
            if (pastDep) {
                capEl.classList.remove('hidden');
                capEl.classList.add('ws-capacity-banner--warn');
                capEl.innerHTML = '<strong>Départ passé</strong> — choisissez une date à venir.';
            } else if (blockedByAvail && avKey === 'full') {
                capEl.classList.remove('hidden');
                capEl.classList.add('ws-capacity-banner--danger');
                capEl.innerHTML = '<strong>Complet</strong> — plus de places sur ce départ.';
            } else if (blockedByAvail && avKey === 'past') {
                capEl.classList.remove('hidden');
                capEl.classList.add('ws-capacity-banner--warn');
                capEl.innerHTML = '<strong>Départs passés</strong> — aucune date future disponible.';
            } else if (st === 'ok' && rem0 !== null && !isNaN(rem0)) {
                var after = rem0 - paxN;
                capEl.classList.remove('hidden');
                capEl.classList.add(after >= 0 && !over ? 'ws-capacity-banner--ok' : 'ws-capacity-banner--danger');
                capEl.innerHTML = '<span class="ws-capacity-banner__line"><strong>' + rem0 + '</strong> place(s) restante(s) · <strong>' + paxN + '</strong> voyageur(s) saisi(s)' +
                    (after >= 0 ? ' · après réservation : <strong>' + after + '</strong> restante(s)' : ' · <strong>Capacité dépassée</strong>') + '</span>';
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

    function renderExtras() {
        var container = document.getElementById('extras-container');
        if (!container) return;

        var type = getWorkspacePrestationType();
        var curX = workspaceLivePricing && workspaceLivePricing.currency ? workspaceLivePricing.currency : 'MAD';

        var checkedState = {};
        container.querySelectorAll('.extra-pax-cb:checked').forEach(function (cb) {
            checkedState[cb.dataset.ext + '_' + cb.dataset.pax] = true;
        });
        container.querySelectorAll('.extra-item-cb:checked').forEach(function (cb) {
            checkedState[cb.dataset.ext + '_item'] = true;
        });

        container.innerHTML = '';
        var extras = getExtrasListForType(type);
        var paxList = getPassengersList().filter(function (p) { return p.type !== 'bebe'; });

        if (extras.length === 0) {
            var emptyMsg = (workspaceExtrasLive !== null && workspaceExtrasLive !== undefined && workspaceExtrasLive.length === 0)
                ? 'Aucun extra pour ce voyage.'
                : 'Aucun extra disponible.';
            container.innerHTML = '<p class="ws-extras-empty">' + emptyMsg + '</p>';
            return;
        }

        extras.forEach(function (extra) {
            if (extra.selectionMode === 'line_item') {
                var checked = checkedState[extra.id + '_item'] ? 'checked' : '';
                var qty = Math.max(1, Number(extra.quantityDefault) || 1);
                var priceLabel = extra.pricingType === 'fixed' ? 'Fixe' : 'Par personne';
                container.innerHTML +=
                    '<div class="ws-extra-card">' +
                    '<div class="ws-extra-card__head"><i class="fas ' + extra.icon + ' ws-extra-card__ico"></i><div>' +
                    '<div class="ws-extra-card__title">' + escapeWsHtml(extra.name) + '</div>' +
                    '<div class="ws-extra-card__desc">' + escapeWsHtml(extra.desc || '') + '</div></div></div>' +
                    '<label class="ws-extra-line">' +
                    '<span class="ws-extra-line__cb"><input type="checkbox" class="extra-item-cb" data-ext="' + extra.id + '" ' + checked + '></span>' +
                    '<span class="ws-extra-line__who">' + escapeWsHtml(priceLabel) + '</span>' +
                    '<span class="ws-extra-line__price">+ ' + (Math.round(Number(extra.unitPrice) || 0)).toLocaleString('fr-FR') + ' ' + curX + '</span>' +
                    '</label>' +
                    '<div class="mt-3 flex items-end gap-3">' +
                    '<label class="flex-1 text-xs font-semibold text-slate-500">Quantité' +
                    '<input type="number" min="1" step="1" value="' + qty + '" class="extra-item-qty mt-1 w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm" data-ext="' + extra.id + '">' +
                    '</label>' +
                    '<div class="text-right">' +
                    '<div class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Total</div>' +
                    '<div class="extra-item-total text-sm font-bold text-[#0e3a5a]" data-ext="' + extra.id + '">' + ((Math.round((Number(extra.unitPrice) || 0) * qty))).toLocaleString('fr-FR') + ' ' + curX + '</div>' +
                    '</div></div></div>';
                return;
            }
            var paxHtml = '';
            if (paxList.length > 0) {
                paxHtml = '<div class="ws-extra-pax">';
                paxList.forEach(function (pax) {
                    var price = pax.type === 'enfant' ? extra.priceChild : extra.priceAdult;
                    var isChecked = checkedState[extra.id + '_' + pax.id] ? 'checked' : '';
                    var typeDisplay = pax.type === 'adulte' ? 'Adulte' : 'Enfant';
                    paxHtml +=
                        '<label class="ws-extra-line">' +
                        '<span class="ws-extra-line__cb"><input type="checkbox" class="extra-pax-cb" data-ext="' + extra.id + '" data-pax="' + pax.id + '" ' + isChecked + '></span>' +
                        '<span class="ws-extra-line__who">' + escapeWsHtml(pax.label) + ' <span class="ws-extra-line__tag">' + typeDisplay + '</span></span>' +
                        '<span class="ws-extra-line__price">+ ' + price + ' ' + curX + '</span>' +
                        '</label>';
                });
                paxHtml += '</div>';
            } else {
                paxHtml = '<p class="ws-extra-pax-none">Ajoutez le type des voyageurs ci-dessus.</p>';
            }

            container.innerHTML +=
                '<div class="ws-extra-card">' +
                '<div class="ws-extra-card__head"><i class="fas ' + extra.icon + ' ws-extra-card__ico"></i><div>' +
                '<div class="ws-extra-card__title">' + escapeWsHtml(extra.name) + '</div>' +
                '<div class="ws-extra-card__desc">' + escapeWsHtml(extra.desc || '') + '</div></div></div>' + paxHtml + '</div>';
        });

        container.querySelectorAll('.extra-pax-cb').forEach(function (cb) {
            cb.addEventListener('change', calculateTotal);
        });
        container.querySelectorAll('.extra-item-cb').forEach(function (cb) {
            cb.addEventListener('change', calculateTotal);
        });
        container.querySelectorAll('.extra-item-qty').forEach(function (input) {
            input.addEventListener('input', calculateTotal);
            input.addEventListener('change', calculateTotal);
        });
    }

    function updateExtrasView() {
        renderExtras();
        calculateTotal();
    }

    function collectExtrasJson() {
        var typePrestation = getWorkspacePrestationType();
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
                activity_id: extraData.activityId,
                selection_mode: 'per_pax',
                pricing_type: extraData.pricingType || 'per_person',
                item_type: extraData.extraType || '',
            });
        });

        document.querySelectorAll('.extra-item-cb:checked').forEach(function (cb) {
            var extId = cb.dataset.ext;
            var extraData = list.find(function (e) { return e.id === extId; });
            if (!extraData) return;
            var qtyInput = document.querySelector('.extra-item-qty[data-ext="' + extId + '"]');
            var qty = qtyInput ? parseInt(qtyInput.value || '1', 10) : 1;
            if (!qty || qty < 1) qty = 1;
            var unitPrice = Number(extraData.unitPrice) || 0;
            out.push({
                voyage_extra_id: extId,
                activity_id: extraData.activityId,
                name: extraData.name,
                price: unitPrice * qty,
                pax: null,
                quantity: qty,
                unit_price: unitPrice,
                pricing_type: extraData.pricingType || 'per_person',
                selection_mode: 'line_item',
                item_type: extraData.extraType || 'activity',
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
            alert('Ce voyage n’est pas encore prêt à être réservé. Ouvrez « Circuits / voyages », enregistrez la fiche du circuit, puis réessayez.');
            return;
        }
        var main = document.getElementById('reservations-main-content');
        var add = document.getElementById('add-reservation-view');
        if (!main || !add) return;
        main.classList.add('hidden');
        add.classList.remove('hidden');
        resetWorkspaceFlow();

        var type = btn.getAttribute('data-type') || 'package';
        var name = btn.getAttribute('data-name') || '';
        var rowCode = (btn.getAttribute('data-row-code') || '').trim();
        var preferredTd = (btn.getAttribute('data-travel-date-id') || '').trim();
        var prefillMap = parseWsFormPrefillMap();
        var pf = rowCode && prefillMap[rowCode] ? prefillMap[rowCode] : null;

        document.getElementById('ws-prestation-type').value = type;
        document.getElementById('ws-tour-id').value = tourId;
        document.getElementById('ws-travel-date-id').value = preferredTd;

        if (pf && pf.form && pf.form.tour_id != null) {
            document.getElementById('ws-tour-id').value = String(pf.form.tour_id);
        }

        document.getElementById('add-res-prestation-name').textContent = pf && pf.title ? pf.title : name;
        updateStickySummary({
            title: (pf && pf.title) ? pf.title : (name || 'Aucune sélection'),
            date: '—',
        });

        if (pf) {
            applyWorkspacePrefill(pf, type, name, preferredTd);
        } else {
            workspaceLivePricing = null;
            workspaceExtrasLive = null;
            workspaceLivePlaces = null;
            workspaceAvailability = null;
            var panelOff = document.getElementById('ws-prefill-panel');
            if (panelOff) panelOff.classList.add('hidden');
            var depOff = document.getElementById('ws-departure-wrap');
            if (depOff) depOff.classList.add('hidden');
            updateStickySummary({ date: '—' });
        }

        setWorkspacePrestationType(type);

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
        resetWorkspaceFlow();
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
            capEl.classList.remove('ws-capacity-banner--ok', 'ws-capacity-banner--danger', 'ws-capacity-banner--warn');
            capEl.classList.add('hidden');
            capEl.innerHTML = '';
        }
        var submitBtn = document.getElementById('ws-booking-submit');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            submitBtn.title = '';
        }
        updateStickySummary({
            title: 'Aucune sélection',
            date: '—',
            pax: 1,
            total: '0 MAD',
        });
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
        var heroSub = document.querySelector('.ws-hero__sub');
        if (heroSub) {
            heroSub.textContent = 'Consultez une prestation ou démarrez une réservation en un clic.';
        }
        resetWorkspaceFlow();

        document.querySelectorAll('.btn-show-add-reservation').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                showAddReservation(btn);
            });
        });

        document.querySelectorAll('[data-ws-step-nav]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                setWorkspaceStep(Number(btn.getAttribute('data-ws-step-nav')) || 1);
            });
        });
        document.querySelectorAll('[data-ws-step-next]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                setWorkspaceStep(workspaceCurrentStep + 1);
            });
        });
        document.querySelectorAll('[data-ws-step-prev]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                setWorkspaceStep(workspaceCurrentStep - 1);
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
                row.className = 'companion-row ws-traveler-card';
                row.innerHTML =
                    '<button type="button" class="btn-remove-companion ws-traveler-card__remove" title="Supprimer"><i class="fas fa-trash"></i></button>' +
                    '<div class="ws-traveler-card__head">' +
                    '<h5 class="ws-traveler-card__title">Accompagnant #' + companionCount + '</h5>' +
                    '<span class="ws-traveler-card__badge">Voyageur</span>' +
                    '</div>' +
                    '<div class="ws-form-grid ws-form-grid--traveler">' +
                    '<div class="ws-form-field"><label class="ws-form-label">Type</label><select class="companion-type-select ws-input-shell">' +
                    '<option value="adulte">Adulte</option><option value="enfant">Enfant</option><option value="bebe">Bébé</option></select>' +
                    '</div>' +
                    '<div class="ws-form-field"><label class="ws-form-label">Prénom</label><input type="text" class="companion-first-name ws-input-shell" placeholder="Prénom"></div>' +
                    '<div class="ws-form-field"><label class="ws-form-label">Nom</label><input type="text" class="companion-last-name ws-input-shell" placeholder="Nom"></div>' +
                    '<div class="ws-form-field"><label class="ws-form-label">Naissance</label><input type="date" class="companion-dob-input ws-input-shell"></div>' +
                    '<div class="ws-form-field ws-form-field--full"><label class="ws-form-label">CIN / Passeport</label><input type="text" class="companion-doc-input ws-input-shell" placeholder="N° document"></div>' +
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
