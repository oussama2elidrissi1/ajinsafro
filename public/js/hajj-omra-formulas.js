/* Relational formula editor: references existing rows, never copies prices/hotels. */
(function () {
    'use strict';
    const editor = document.querySelector('[data-ho-editor]');
    const host = editor?.querySelector('[data-formula-editor]');
    if (!host) return;
    const list = host.querySelector('[data-formula-list]');
    const ar = () => editor.classList.contains('lang-ar');
    const t = (fr, arabic) => ar() ? arabic : fr;
    const basePriceInput = editor.querySelector('[data-role="price-current"]');
    function el(tag, attrs = {}, text = '') {
        const node = document.createElement(tag);
        Object.entries(attrs).forEach(([key, value]) => node.setAttribute(key, value));
        node.textContent = text;
        return node;
    }
    function caption(node, fr, arabic) {
        node.dataset.hoFr = fr; node.dataset.hoAr = arabic; node.textContent = t(fr, arabic);
        return node;
    }
    function field(parent, key, fr, arabic, value = '', type = 'text') {
        const wrap = el('label', {class: 'ho-formula-field small mb-3'});
        wrap.append(caption(el('span', {class: 'form-label mb-0'}), fr, arabic));
        const input = el(type === 'textarea' ? 'textarea' : 'input', {'data-f': key, class: 'form-control form-control-sm mt-1'});
        if (type !== 'textarea') input.type = type;
        input.value = value ?? '';
        if (key.endsWith('_ar')) { input.dir = 'rtl'; input.lang = 'ar'; wrap.dataset.langPane = 'ar'; }
        if (key.endsWith('_fr')) { input.dir = 'ltr'; input.lang = 'fr'; wrap.dataset.langPane = 'fr'; }
        if (type === 'number') { input.min = '0'; input.dir = 'ltr'; }
        if (key === 'nights_override') input.max = '365';
        if (key === 'sort_order') input.max = '9999';
        if (type === 'text') input.maxLength = 255;
        if (type === 'textarea') { input.rows = 2; input.maxLength = 4000; }
        wrap.append(input); parent.append(wrap); return input;
    }
    function select(parent, key, source, fr, arabic, value, multiple = false) {
        const wrap = el('label', {class: 'd-block small mb-3'});
        wrap.append(caption(el('span', {class: 'form-label mb-0'}), fr, arabic));
        const input = el('select', {'data-f': key, 'data-source': source, class: 'form-select form-select-sm mt-1'});
        input.multiple = multiple;
        if (source === 'departure' || source === 'day') input.dir = 'ltr';
        if (multiple) input.size = 5;
        input._selectedRefs = multiple ? (value || []).map(String) : [String(value ?? '')];
        wrap.append(input); parent.append(wrap); return input;
    }
    function button(parent, fr, arabic, action, variant = 'btn-outline-secondary mb-3') {
        const btn = caption(el('button', {type: 'button', class: 'btn btn-sm ' + variant}), fr, arabic);
        btn.addEventListener('click', action); parent.append(btn);
    }
    function addStay(parent, row = {}) {
        const stay = el('div', {class: 'ho-row', 'data-formula-stay': ''});
        select(stay, 'hotel_id', 'hotel', 'Hébergement', 'الإقامة', row.hotel_id);
        const grid = el('div', {class: 'row'}), left = el('div', {class: 'col-md-6'}), right = el('div', {class: 'col-md-6'});
        select(left, 'program_day_id', 'day', 'Début au jour du programme (facultatif)', 'بداية الإقامة من يوم البرنامج (اختياري)', row.program_day_id);
        field(right, 'nights_override', 'Nuits spécifiques (vide = nuits de l’hôtel)', 'ليالي خاصة (فارغ = ليالي الفندق)', row.nights_override, 'number');
        grid.append(left, right); stay.append(grid);
        button(stay, 'Retirer cet hébergement', 'إزالة هذه الإقامة', () => { stay.remove(); nameFields(); refreshPrice(); }, 'btn-link text-danger p-0 mb-0');
        parent.append(stay);
    }
    function addFormula(row = {}) {
        // Carte repliable : l'en-tete resume la formule, le corps porte la saisie.
        const card = el('div', {class: 'ho-acc is-open', 'data-formula-card': ''});
        const head = el('button', {type: 'button', class: 'ho-acc__head', 'data-acc-toggle': ''});
        const tag = el('span', {class: 'ho-acc__tag'});
        const title = el('span', {class: 'ho-acc__title'});
        const name = el('span');
        const subtitle = el('span', {class: 'ho-acc__sub'});
        const state = el('span', {class: 'ho-badge'});
        const from = el('span', {class: 'ho-acc__from'});
        const end = el('span', {class: 'ho-acc__end'});
        title.append(name, subtitle);
        end.append(state, from, el('span', {class: 'ho-acc__chevron', 'aria-hidden': 'true'}, '⌄'));
        head.append(tag, title, end);
        const body = el('div', {class: 'ho-acc__body'});
        card.append(head, body);
        card._head = {tag, name, subtitle, state, from};

        body.append(el('input', {type: 'hidden', 'data-f': 'id', value: row.id || ''}));
        const identity = el('div', {class: 'row'});
        const left = el('div', {class: 'col-md-6'});
        const right = el('div', {class: 'col-md-6'});
        field(left, 'name_fr', 'Nom de la formule', 'اسم الباقة', row.name_fr);
        field(left, 'name_ar', 'Nom arabe', 'اسم الباقة', row.name_ar);
        select(right, 'departure_id', 'departure', 'Départ (vide = tous les départs)', 'الموعد (فارغ = جميع المواعيد)', row.departure_id);
        field(right, 'sort_order', 'Ordre d’affichage', 'ترتيب العرض', row.sort_order ?? list.children.length, 'number');
        identity.append(left, right);
        body.append(identity);
        field(body, 'description_fr', 'Description (facultative)', 'الوصف (اختياري)', row.description_fr, 'textarea');
        field(body, 'description_ar', 'Description arabe', 'الوصف (اختياري)', row.description_ar, 'textarea');

        const stays = el('div', {'data-formula-stays': ''}); body.append(stays);
        (row.hotels || []).forEach(stay => addStay(stays, stay));
        button(body, '+ Lier un hébergement', '+ ربط إقامة', () => { addStay(stays); refresh(); }, 'btn-outline-primary mb-3');
        select(body, 'tariff_ids', 'room', 'Tarifs liés (Ctrl / Cmd pour sélectionner plusieurs)', 'الأسعار المرتبطة (Ctrl / Cmd لاختيار عدة أسعار)', row.tariff_ids, true);

        const footer = el('div', {class: 'd-flex align-items-center gap-2 flex-wrap border-top pt-3'});
        footer.append(el('input', {type: 'hidden', 'data-f': 'is_active', value: '0'}));
        const activeLabel = el('label', {class: 'd-inline-flex align-items-center gap-2 small mb-0', style: 'cursor:pointer'});
        const active = el('input', {type: 'checkbox', 'data-f': 'is_active', value: '1', class: 'form-check-input mt-0'});
        active.checked = row.is_active === undefined || !!Number(row.is_active);
        activeLabel.append(active, caption(el('span'), 'Formule active', 'باقة مفعّلة'));
        footer.append(activeLabel);
        const actions = el('span', {class: 'ms-auto d-flex gap-2 flex-wrap'});
        button(actions, 'Dupliquer pour un autre départ', 'نسخ لموعد آخر', () => { addFormula(readFormula(card)); refresh(); }, 'btn-outline-primary');
        button(actions, 'Supprimer', 'حذف', () => { card.remove(); nameFields(); refreshPrice(); }, 'btn-outline-danger');
        footer.append(actions);
        body.append(footer);
        list.append(card);
    }
    /** En-tete replie : nom, contexte, etat et prix d'appel de la formule. */
    function describe(card, index, price) {
        const head = card._head;
        if (!head) return;
        const own = key => Array.from(card.querySelectorAll('[data-f="' + key + '"]')).find(node => !node.closest('[data-formula-stay]'));
        const label = (ar() ? own('name_ar')?.value || own('name_fr')?.value : own('name_fr')?.value || own('name_ar')?.value) || '';
        const departure = own('departure_id');
        const hotels = card.querySelectorAll('[data-formula-stay]').length;
        const tariffs = card.querySelectorAll('[data-f="tariff_ids"] option:checked').length;
        const isActive = !!card.querySelector('input[type="checkbox"][data-f="is_active"]')?.checked;
        head.tag.textContent = String(index + 1);
        head.name.textContent = label || t('Formule sans nom', 'باقة بلا اسم');
        head.subtitle.textContent = [
            departure?.value ? departure.selectedOptions[0]?.textContent : t('Tous les départs', 'جميع المواعيد'),
            hotels + ' ' + t('hébergement(s)', 'إقامة'),
            tariffs + ' ' + t('tarif(s)', 'سعر'),
        ].join(' · ');
        head.state.textContent = isActive ? t('Active', 'مفعّلة') : t('Inactive', 'معطّلة');
        head.state.classList.toggle('ho-badge--warm', !isActive);
        head.from.textContent = price === null
            ? t('Tarifs à compléter', 'الأسعار غير مكتملة')
            : t('dès ', 'ابتداءً من ') + price.toLocaleString('fr-FR').replace(/ | /g, ' ') + ' ' + (editor.querySelector('[name="currency"]')?.value || 'DH');
    }
    /** Etat courant d'une carte, sans son identifiant : la copie sera creee comme nouvelle formule. */
    function readFormula(card) {
        const own = key => Array.from(card.querySelectorAll('[data-f="' + key + '"]')).find(node => !node.closest('[data-formula-stay]'));
        return {
            name_fr: own('name_fr')?.value, name_ar: own('name_ar')?.value,
            description_fr: own('description_fr')?.value, description_ar: own('description_ar')?.value,
            departure_id: own('departure_id')?.value,
            sort_order: list.children.length,
            is_active: card.querySelector('input[type="checkbox"][data-f="is_active"]')?.checked ? 1 : 0,
            tariff_ids: Array.from(own('tariff_ids')?.selectedOptions || []).map(option => option.value),
            hotels: Array.from(card.querySelectorAll('[data-formula-stay]')).map(stay => ({
                hotel_id: stay.querySelector('[data-f="hotel_id"]')?.value,
                program_day_id: stay.querySelector('[data-f="program_day_id"]')?.value,
                nights_override: stay.querySelector('[data-f="nights_override"]')?.value,
            })),
        };
    }
    const sourcePrefixes = {room: 'room_prices', hotel: 'hotels', departure: 'departures', day: 'program_days'};
    function sources(kind) {
        return Array.from(editor.querySelectorAll('[data-repeat-list="' + kind + '"] [data-repeat-item]')).map((row, index) => {
            const val = suffix => row.querySelector('[name$="[' + suffix + ']"]')?.value || '';
            const idField = row.querySelector('[name$="[id]"]');
            if (!idField) return null;
            let client = row.querySelector('[name$="[client_key]"]');
            if (!client) { client = el('input', {type: 'hidden', name: sourcePrefixes[kind] + '[' + index + '][client_key]'}); idField.after(client); }
            if (!client.value) client.value = window.crypto?.randomUUID ? window.crypto.randomUUID() : 'row_' + Date.now() + '_' + Math.random().toString(36).slice(2);
            const ref = idField.value || 'new:' + client.value;
            let label;
            if (kind === 'hotel') label = [row.querySelector('[name$="[city]"]')?.selectedOptions[0]?.textContent || val('city'), ar() ? val('name_ar') || val('name') : val('name') || val('name_ar')].filter(Boolean).join(' · ');
            if (kind === 'room') {
                const type = row.querySelector('[name$="[room_type]"]');
                label = [val('label'), type?.selectedOptions[0]?.textContent || '', val('price') + ' ' + (editor.querySelector('[name="currency"]')?.value || 'DH')]
                    .filter(Boolean).join(' · ');
            }
            if (kind === 'departure') label = val('departure_date') + ' → ' + val('return_date');
            if (kind === 'day') label = t('Jour ', 'اليوم ') + val('day_number') + ' · ' + (ar() ? val('title_ar') || val('title') : val('title') || val('title_ar'));
            return {ref, label: '#' + (index + 1) + ' · ' + label, price: val('price'),
                active: row.querySelector('input[type="checkbox"][name$="[is_active]"]')?.checked ?? true};
        }).filter(Boolean);
    }
    function nameFields() {
        Array.from(list.children).forEach((card, i) => {
            card.querySelectorAll('[data-f]').forEach(input => {
                if (!input.closest('[data-formula-stay]')) input.name = 'formulas[' + i + '][' + input.dataset.f + ']' + (input.multiple ? '[]' : '');
            });
            card.querySelectorAll('[data-formula-stay]').forEach((stay, j) => stay.querySelectorAll('[data-f]').forEach(input => {
                input.name = 'formulas[' + i + '][hotels][' + j + '][' + input.dataset.f + ']';
            }));
        });
    }
    function refresh() {
        const catalogs = Object.fromEntries(Object.keys(sourcePrefixes).map(kind => [kind, sources(kind)]));
        list.querySelectorAll('select[data-source]').forEach(select => {
            const selected = select._selectedRefs || Array.from(select.selectedOptions).map(opt => opt.value);
            delete select._selectedRefs;
            const options = catalogs[select.dataset.source];
            select.replaceChildren();
            if (!select.multiple) select.append(el('option', {value: ''}, '—'));
            options.forEach(item => select.append(el('option', {value: item.ref}, item.label)));
            selected.filter(ref => ref && !options.some(item => item.ref === ref)).forEach(ref => {
                select.append(el('option', {value: ref}, t('Sélection supprimée — choisir à nouveau', 'تم حذف الاختيار — يرجى الاختيار مجدداً')));
            });
            Array.from(select.options).forEach(opt => { opt.selected = selected.includes(opt.value); });
        });
        nameFields();
        refreshPrice(catalogs.room);
    }
    function refreshPrice(tariffs = sources('room')) {
        const cards = Array.from(list.children);
        let linked = null;
        if (cards.length) {
            linked = new Set();
            cards.forEach(card => {
                const named = card.querySelector('[data-f="name_fr"]').value.trim() || card.querySelector('[data-f="name_ar"]').value.trim();
                const hasHotel = Array.from(card.querySelectorAll('[data-f="hotel_id"]')).some(input => !!input.value);
                if (!named || !hasHotel || !card.querySelector('input[type="checkbox"][data-f="is_active"]').checked) return;
                card.querySelectorAll('[data-f="tariff_ids"] option:checked').forEach(option => linked.add(option.value));
            });
        }
        const prices = tariffs.filter(tariff => tariff.active && tariff.price !== '' && (!linked || linked.has(tariff.ref))).map(tariff => Number(tariff.price)).filter(Number.isFinite);
        // Le champ reste saisissable : une valeur tapee fait autorite cote client.
        // Laisse vide, il suit le tarif actif le plus bas, propose ici en indication.
        const computed = prices.length ? Math.min(...prices) : null;
        const hasSource = !!(tariffs.length || cards.length);
        if (basePriceInput) {
            basePriceInput.disabled = false;
            basePriceInput.placeholder = hasSource && computed !== null
                ? t('Auto : ', 'تلقائي: ') + computed
                : t('Sur demande', 'عند الطلب');
        }
        const hint = editor.querySelector('[data-from-price-note]');
        if (hint) hint.hidden = !hasSource;
        cards.forEach((card, index) => {
            const picker = Array.from(card.querySelectorAll('[data-f="tariff_ids"]')).find(node => !node.closest('[data-formula-stay]'));
            const picked = Array.from(picker?.selectedOptions || [])
                .map(option => Number(tariffs.find(item => item.ref === option.value)?.price))
                .filter(Number.isFinite);
            describe(card, index, picked.length ? Math.min(...picked) : null);
        });
    }
    JSON.parse(host.querySelector('[data-formula-initial]').textContent).forEach(addFormula);
    host.querySelector('[data-formula-add]').addEventListener('click', () => { addFormula(); refresh(); });
    editor.addEventListener('input', event => { if (!host.contains(event.target)) refresh(); });
    editor.addEventListener('change', event => { if (!host.contains(event.target)) refresh(); });
    host.addEventListener('input', () => refreshPrice());
    host.addEventListener('change', () => refreshPrice());
    host.addEventListener('click', () => refreshPrice());
    editor.addEventListener('ho:language', refresh);
    editor.addEventListener('ho:before-submit', refresh);
    editor.querySelector('form').addEventListener('submit', refresh);
    Object.keys(sourcePrefixes).forEach(kind => {
        const source = editor.querySelector('[data-repeat-list="' + kind + '"]');
        // subtree : la grille tarifaire est regroupee, les lignes n'arrivent plus a la racine.
        if (source) new MutationObserver(refresh).observe(source, {childList: true, subtree: true});
    });
    refresh();
}());
