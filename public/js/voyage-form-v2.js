/* Voyage — Formulaire (Espace Admin v2)
 * Complément d'interface au-dessus de voyage-v2.js (qui gère les étapes et l'enregistrement) :
 * hauteurs collantes, blocs latéraux par étape, aperçu Google, compteur d'accroche, pastille statut. */
(function () {
    'use strict';

    var page = document.querySelector('.vf-page');
    var form = document.getElementById('edit-voyage-form');
    if (!page || !form) return;

    var root = document.documentElement;
    var header = document.querySelector('.ea-header');
    var subhead = page.querySelector('.vf-subhead');

    function measure() {
        if (header) root.style.setProperty('--vf-header-h', Math.round(header.getBoundingClientRect().height) + 'px');
        if (subhead) root.style.setProperty('--vf-subhead-h', Math.round(subhead.getBoundingClientRect().height) + 'px');
    }
    measure();
    window.addEventListener('resize', measure);
    if (typeof ResizeObserver !== 'undefined' && subhead) {
        new ResizeObserver(measure).observe(subhead);
    }

    /* Blocs latéraux liés à l'étape courante (attribut posé par voyage-v2.js). */
    var asideBlocks = Array.prototype.slice.call(page.querySelectorAll('[data-vf-for]'));
    var aside = page.querySelector('.vf-aside');
    function syncAside() {
        var current = String(form.getAttribute('data-v2-current-step') || 's-general');
        var anyActive = false;
        asideBlocks.forEach(function (block) {
            var active = block.getAttribute('data-vf-for') === current;
            block.classList.toggle('is-active', active);
            if (active) anyActive = true;
        });
        // Sans bloc actif, le rail est masqué : le contenu occupe toute la largeur.
        if (aside) aside.classList.toggle('is-empty', !anyActive);
        var chip = page.querySelector('.vf-step.active');
        if (chip && typeof chip.scrollIntoView === 'function') {
            try { chip.scrollIntoView({ block: 'nearest', inline: 'center', behavior: 'smooth' }); } catch (e) { /* ignore */ }
        }
    }
    function onStepChange() {
        syncAside();
        var current = String(form.getAttribute('data-v2-current-step') || '');
        // Filet de sécurité : les listes « Jour » sont aussi rafraîchies à l'ouverture des étapes concernées.
        if (current === 's-flights' || current === 's-transfers') {
            refreshDayOptions();
        }
    }
    syncAside();
    if (typeof MutationObserver !== 'undefined') {
        new MutationObserver(onStepChange).observe(form, { attributes: true, attributeFilter: ['data-v2-current-step'] });
    }

    /* Liens de sections (étape 1) : défilement doux + état actif. */
    var sectionLinks = Array.prototype.slice.call(page.querySelectorAll('[data-vf-section]'));
    sectionLinks.forEach(function (link) {
        link.addEventListener('click', function (event) {
            var target = document.getElementById(link.getAttribute('data-vf-section'));
            if (!target) return;
            event.preventDefault();
            try { target.scrollIntoView({ behavior: 'smooth', block: 'start' }); } catch (e) { target.scrollIntoView(); }
            sectionLinks.forEach(function (l) { l.classList.toggle('is-active', l === link); });
        });
    });
    if (typeof IntersectionObserver !== 'undefined' && sectionLinks.length) {
        var sections = sectionLinks.map(function (l) { return document.getElementById(l.getAttribute('data-vf-section')); }).filter(Boolean);
        var visible = {};
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) { visible[entry.target.id] = entry.isIntersecting ? entry.intersectionRatio : 0; });
            var best = null;
            sections.forEach(function (sec) { if (visible[sec.id] && (!best || visible[sec.id] > visible[best.id])) best = sec; });
            if (!best) return;
            sectionLinks.forEach(function (l) { l.classList.toggle('is-active', l.getAttribute('data-vf-section') === best.id); });
        }, { rootMargin: '-45% 0px -45% 0px', threshold: [0, 0.25, 0.5, 1] });
        sections.forEach(function (sec) { io.observe(sec); });
    }

    /* Aperçu Google + compteur d'accroche + nuits. */
    var titleInput = document.getElementById('title');
    var slugInput = document.getElementById('slug');
    var excerptInput = document.getElementById('excerpt');
    var durationInput = document.getElementById('duration_text');
    var nightsOut = page.querySelector('[data-vf-nights]');
    var gTitle = page.querySelector('[data-vf-google-title]');
    var gUrl = page.querySelector('[data-vf-google-url]');
    var gDesc = page.querySelector('[data-vf-google-desc]');
    var counter = page.querySelector('[data-v3-counter-for="excerpt"]');
    var counterHint = page.querySelector('[data-vf-excerpt-hint]');
    var brand = String(page.getAttribute('data-vf-brand') || '');
    var baseDisplay = String(page.getAttribute('data-vf-base-display') || '');
    var hintDefault = counterHint ? counterHint.textContent : '';

    function slugify(value) {
        return String(value || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '')
            .replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
    }

    function refresh() {
        var title = titleInput ? String(titleInput.value || '').trim() : '';
        var slug = slugInput ? String(slugInput.value || '').trim().replace(/^\/+/, '') : '';
        if (!slug && title) slug = slugify(title);
        var excerpt = excerptInput ? String(excerptInput.value || '').trim() : '';

        if (gTitle) gTitle.textContent = (title || 'Titre du voyage') + (brand ? ' — ' + brand : '');
        if (gUrl) gUrl.textContent = baseDisplay.replace(/\/+$/, '').split('/').join(' › ') + ' › ' + (slug || '…');
        if (gDesc) gDesc.textContent = excerpt ? (excerpt.length > 160 ? excerpt.slice(0, 157) + '…' : excerpt) : 'L’accroche courte sert de description dans les résultats de recherche.';

        var len = excerpt.length;
        var over = len > 160;
        if (counter) counter.classList.toggle('is-over', over);
        if (excerptInput) excerptInput.classList.toggle('is-missing', over);
        if (counterHint) {
            counterHint.classList.toggle('is-over', over);
            counterHint.textContent = over ? (len - 160) + ' caractère(s) de trop, la fin sera tronquée dans les listes.' : hintDefault;
        }

        if (durationInput && nightsOut) {
            var days = parseInt(String(durationInput.value || '').replace(/[^0-9]/g, ''), 10);
            nightsOut.value = days > 0 ? String(Math.max(0, days - 1)) : '';
        }
    }
    [titleInput, slugInput, excerptInput, durationInput].forEach(function (el) {
        if (el) el.addEventListener('input', refresh);
    });
    refresh();

    /* Pastille de statut : classe de couleur suivant la sélection. */
    var statusSelect = document.getElementById('post_status');
    var statusPill = document.getElementById('v2-live-status');
    if (statusSelect && statusPill) {
        var applyStatus = function () {
            var value = String(statusSelect.value || 'draft');
            ['publish', 'draft', 'pending', 'private'].forEach(function (s) { statusPill.classList.toggle('is-' + s, s === value); });
        };
        statusSelect.addEventListener('change', applyStatus);
        applyStatus();
    }

    /* État d'enregistrement : voyage-v2.js écrit dans #v2-save-card / #v2-save-state / #v2-save-help,
       recopiés dans la barre d'actions de chaque étape. */
    var saveCard = document.getElementById('v2-save-card');
    var saveState = document.getElementById('v2-save-state');
    var saveHelp = document.getElementById('v2-save-help');
    var statusBars = Array.prototype.slice.call(page.querySelectorAll('.vf-bottombar__status'));
    function mirrorSaveState() {
        var st = saveCard ? (saveCard.getAttribute('data-state') || 'idle') : 'idle';
        var title = saveState ? saveState.textContent : '';
        var help = saveHelp ? saveHelp.textContent : '';
        statusBars.forEach(function (bar) {
            bar.setAttribute('data-state', st);
            var t = bar.querySelector('[data-vf-save-state]');
            var h = bar.querySelector('[data-vf-save-help]');
            if (t && title) t.textContent = title;
            if (h && help) h.textContent = help;
        });
    }
    if (statusBars.length && typeof MutationObserver !== 'undefined') {
        if (saveCard) new MutationObserver(mirrorSaveState).observe(saveCard, { attributes: true, attributeFilter: ['data-state'] });
        [saveState, saveHelp].forEach(function (el) {
            if (el) new MutationObserver(mirrorSaveState).observe(el, { childList: true, characterData: true, subtree: true });
        });
        mirrorSaveState();
    }

    /* ------------------------------------------------------------------
       Jours du programme
       Les listes « Jour » des étapes Vols et Transferts sont rendues côté
       serveur au chargement de la page. Sans ce rafraîchissement, un jour
       ajouté à l'étape Programme n'apparaissait qu'après un rechargement
       complet (Ctrl+F5). Les hôtels ont déjà leur propre rafraîchissement
       (window.VoyageHotelDays).
       ------------------------------------------------------------------ */
    function programDayCount() {
        var accordion = document.getElementById('accordionProgrammeDays');
        if (accordion) {
            var cards = accordion.querySelectorAll('.programme-day-card').length;
            if (cards > 0) return cards;
        }
        var hidden = document.getElementById('duration_day');
        var fromHidden = parseInt(String((hidden && hidden.value) || '').trim(), 10);
        if (fromHidden > 0) return fromHidden;
        var fromText = parseInt(String((durationInput && durationInput.value) || '').replace(/[^0-9]/g, ''), 10);
        return fromText > 0 ? fromText : 1;
    }

    function clampDay(value, fallback, maxDay) {
        var parsed = parseInt(String(value === null || value === undefined ? '' : value).trim(), 10);
        if (!(parsed > 0)) parsed = fallback;
        if (!(parsed > 0)) parsed = 1;
        return Math.min(parsed, maxDay);
    }

    function rebuildDaySelect(select, maxDay) {
        if (!select) return;
        var selected = clampDay(select.value, 1, maxDay);
        if (select.options.length === maxDay && String(select.value) === String(selected)) return;
        var html = '';
        for (var day = 1; day <= maxDay; day++) {
            html += '<option value="' + day + '">Jour ' + day + '</option>';
        }
        select.innerHTML = html;
        select.value = String(selected);
    }

    function refreshDayOptions(dayCount) {
        var maxDay = clampDay(dayCount, programDayCount(), 365);

        // Vols : liste des segments, jour caché envoyé au serveur, libellé et boutons d'ajout.
        Array.prototype.slice.call(document.querySelectorAll('.flight-opt-card')).forEach(function (card) {
            var typeInput = card.querySelector('input[name$="[type]"]');
            var type = typeInput ? String(typeInput.value || '') : '';
            var hiddenDay = card.querySelector('input[name$="[day_number]"]');
            if (type === 'segment') {
                var daySelect = card.querySelector('select.flight-opt-day');
                rebuildDaySelect(daySelect, maxDay);
                if (hiddenDay && daySelect) hiddenDay.value = daySelect.value || '1';
            } else if (type === 'return' && hiddenDay) {
                hiddenDay.value = String(maxDay);
            }
        });
        Array.prototype.slice.call(document.querySelectorAll('.btn-add-flight-opt[data-type="return"], .btn-add-flight-opt[data-type="segment"]')).forEach(function (btn) {
            btn.setAttribute('data-day', String(maxDay));
        });
        var returnDayLabel = document.querySelector('[data-flight-return-day]');
        if (returnDayLabel) returnDayLabel.textContent = String(maxDay);

        // Transferts : une liste par ligne (les nouvelles lignes sont clonées de la dernière).
        Array.prototype.slice.call(document.querySelectorAll('select[name^="tour_transfers["][name$="[day_number]"]')).forEach(function (select) {
            rebuildDaySelect(select, maxDay);
        });
    }

    document.addEventListener('voyage:program-days-changed', function (event) {
        refreshDayOptions(event && event.detail ? event.detail.days : null);
    });

    /* ------------------------------------------------------------------
       Étape Hôtels : séjours repliables + couverture du séjour.
       La délégation d'évènements couvre aussi les séjours ajoutés en cours
       de saisie (ils sont clonés depuis une ligne existante).
       ------------------------------------------------------------------ */
    function setHotelOpen(row, open) {
        if (!row) return;
        row.classList.toggle('is-open', open);
        var label = row.querySelector('[data-vf-hotel-toggle-label]');
        if (label) label.textContent = open ? 'Replier ▲' : 'Modifier ▼';
    }

    page.addEventListener('click', function (event) {
        var target = event.target;
        if (!target || !target.closest) return;
        if (target.closest('.tour-remove-row')) return;
        var head = target.closest('[data-vf-hotel-toggle]');
        if (!head) return;
        var row = head.closest('.vf-hotel');
        if (!row) return;
        setHotelOpen(row, !row.classList.contains('is-open'));
    });

    page.addEventListener('keydown', function (event) {
        if (event.key !== 'Enter' && event.key !== ' ') return;
        var head = event.target && event.target.closest ? event.target.closest('[data-vf-hotel-toggle]') : null;
        if (!head) return;
        event.preventDefault();
        var row = head.closest('.vf-hotel');
        if (row) setHotelOpen(row, !row.classList.contains('is-open'));
    });

    function refreshStayCoverage() {
        var bar = page.querySelector('[data-vf-coverage-bar]');
        var label = page.querySelector('[data-vf-coverage-label]');
        if (!bar) return;

        var maxDay = programDayCount();
        var nightsTotal = Math.max(0, maxDay - 1);
        var rows = Array.prototype.slice.call(page.querySelectorAll('.tour-hotel-row'));
        var covered = {};
        var segments = [];

        rows.forEach(function (row) {
            var ci = clampDay((row.querySelector('.tour-hotel-check-in') || {}).value, 1, maxDay);
            var co = clampDay((row.querySelector('.tour-hotel-check-out') || {}).value, ci, maxDay);
            if (co < ci) co = ci;
            for (var d = ci; d < co; d++) covered[d] = true;
            segments.push({ from: ci, to: co, nights: Math.max(0, co - ci) });
        });

        var coveredCount = Object.keys(covered).length;
        if (label) {
            label.textContent = coveredCount + ' nuit' + (coveredCount !== 1 ? 's' : '')
                + ' couverte' + (coveredCount !== 1 ? 's' : '') + ' sur ' + nightsTotal;
            var ok = nightsTotal > 0 && coveredCount >= nightsTotal;
            label.classList.toggle('is-ok', ok);
            label.classList.toggle('is-warn', !ok);
        }

        if (!segments.length) {
            bar.innerHTML = '<span class="vf-coverage__seg is-empty" style="flex:1">aucun séjour configuré</span>';
            return;
        }
        bar.innerHTML = segments.map(function (seg, i) {
            return '<span class="vf-coverage__seg vf-coverage__seg--' + (i % 3) + '" style="flex:'
                + Math.max(1, seg.nights) + '">J' + seg.from + '→J' + seg.to + '</span>';
        }).join('');
    }

    page.addEventListener('change', function (event) {
        var target = event.target;
        if (!target || !target.classList) return;
        if (target.classList.contains('tour-hotel-check-in') || target.classList.contains('tour-hotel-check-out')) {
            window.setTimeout(refreshStayCoverage, 0);
        }
    });
    document.addEventListener('voyage:program-days-changed', function () {
        window.setTimeout(refreshStayCoverage, 0);
    });
    // Un séjour ajouté ou supprimé modifie la couverture : on observe le conteneur.
    var hotelsContainer = document.getElementById('tour-hotels-container');
    if (hotelsContainer && typeof MutationObserver !== 'undefined') {
        new MutationObserver(function () { window.setTimeout(refreshStayCoverage, 0); })
            .observe(hotelsContainer, { childList: true });
    }
    refreshStayCoverage();

    /* Suggestions de présentation : insère un titre de section dans l'éditeur. */
    Array.prototype.slice.call(page.querySelectorAll('[data-vf-suggest]')).forEach(function (btn) {
        btn.addEventListener('click', function () {
            var text = String(btn.getAttribute('data-vf-suggest') || '');
            var editor = window.tinymce && window.tinymce.get ? window.tinymce.get('content') : null;
            if (editor) {
                editor.execCommand('mceInsertContent', false, '<h3>' + text + '</h3><p></p>');
                editor.focus();
                return;
            }
            var area = document.getElementById('content');
            if (area) {
                area.value = (area.value ? area.value.replace(/\s+$/, '') + '\n\n' : '') + text + '\n';
                area.dispatchEvent(new Event('input', { bubbles: true }));
                area.focus();
            }
        });
    });
})();
