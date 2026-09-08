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
    function syncAside() {
        var current = String(form.getAttribute('data-v2-current-step') || 's-general');
        asideBlocks.forEach(function (block) {
            block.classList.toggle('is-active', block.getAttribute('data-vf-for') === current);
        });
        var chip = page.querySelector('.vf-step.active');
        if (chip && typeof chip.scrollIntoView === 'function') {
            try { chip.scrollIntoView({ block: 'nearest', inline: 'center', behavior: 'smooth' }); } catch (e) { /* ignore */ }
        }
    }
    syncAside();
    if (typeof MutationObserver !== 'undefined') {
        new MutationObserver(syncAside).observe(form, { attributes: true, attributeFilter: ['data-v2-current-step'] });
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
