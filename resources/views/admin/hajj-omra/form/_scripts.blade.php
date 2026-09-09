{{--
    Comportements de l'editeur d'offre Hajj & Omra.

    Aucune librairie nouvelle : Bootstrap 5 (deja charge) pour les onglets et l'accordeon,
    et dragula (deja present dans public/build/libs) pour le reordonnancement.
    Les televersements passent par la route existante admin.local-media.upload.
--}}
<link rel="stylesheet" href="{{ asset('build/libs/dragula/dragula.min.css') }}">
<script src="{{ asset('build/libs/dragula/dragula.min.js') }}"></script>

<script>
(function () {
    'use strict';

    var editor = document.querySelector('[data-ho-editor]');
    if (!editor) { return; }

    var uploadUrl = editor.dataset.uploadUrl;
    var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        || editor.querySelector('input[name="_token"]')?.value;

    // ------------------------------------------------------------------
    // Navigation par etapes
    // ------------------------------------------------------------------
    var steps = editor.querySelectorAll('[data-step]');
    var panels = editor.querySelectorAll('[data-panel]');
    var activeTabInput = editor.querySelector('[data-role="active-tab"]');

    function showStep(name) {
        steps.forEach(function (s) { s.classList.toggle('active', s.dataset.step === name); });
        panels.forEach(function (p) { p.classList.toggle('active', p.dataset.panel === name); });
        if (activeTabInput) { activeTabInput.value = name; }
        try { window.localStorage.setItem('ho_editor_tab', name); } catch (e) {}
    }

    steps.forEach(function (step) {
        step.addEventListener('click', function () { showStep(step.dataset.step); });
    });

    showStep(editor.dataset.activeTab || 'offre');

    // Une etape contenant un champ en erreur est signalee et ouverte en priorite.
    var firstInvalid = editor.querySelector('.is-invalid');
    if (firstInvalid) {
        var panel = firstInvalid.closest('[data-panel]');
        if (panel) {
            var stepBtn = editor.querySelector('[data-step="' + panel.dataset.panel + '"]');
            if (stepBtn) { stepBtn.classList.add('has-error'); }
            showStep(panel.dataset.panel);
        }
    }

    // ------------------------------------------------------------------
    // Bascule de langue FR / AR
    // ------------------------------------------------------------------
    editor.querySelectorAll('[data-lang-switch]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var lang = btn.dataset.langSwitch;
            editor.classList.toggle('lang-ar', lang === 'ar');
            editor.querySelectorAll('[data-lang-switch]').forEach(function (b) {
                b.classList.toggle('btn-primary', b === btn);
                b.classList.toggle('btn-outline-secondary', b !== btn);
            });
            try { window.localStorage.setItem('ho_editor_lang', lang); } catch (e) {}
        });
    });

    try {
        if (window.localStorage.getItem('ho_editor_lang') === 'ar') {
            editor.querySelector('[data-lang-switch="ar"]')?.click();
        }
    } catch (e) {}

    // ------------------------------------------------------------------
    // Listes repetables
    // ------------------------------------------------------------------
    // Les index de champs sont recalcules apres chaque ajout/suppression pour rester
    // contigus : Laravel recoit un tableau propre, sans trous.
    function reindex(list, prefix) {
        var i = 0;
        list.querySelectorAll('[data-repeat-item]').forEach(function (item) {
            item.querySelectorAll('[name]').forEach(function (field) {
                field.name = field.name.replace(
                    new RegExp('^' + prefix + '\\[(\\d+|__INDEX__)\\]'),
                    prefix + '[' + i + ']'
                );
            });
            i++;
        });
    }

    // Correspondance liste -> prefixe de champ cote serveur.
    var PREFIXES = {
        room: 'room_prices',
        departure: 'departures',
        hotel: 'hotels',
        day: 'program_days',
        'service-included': 'service_items',
        'service-excluded': 'service_items'
    };

    // Les deux listes de prestations partagent le meme tableau : l'index doit etre
    // unique sur l'ensemble, pas par colonne.
    function nextServiceIndex() {
        var max = -1;
        editor.querySelectorAll('[name^="service_items["]').forEach(function (field) {
            var m = field.name.match(/^service_items\[(\d+)\]/);
            if (m) { max = Math.max(max, parseInt(m[1], 10)); }
        });
        return max + 1;
    }

    editor.querySelectorAll('[data-repeat-add]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var key = btn.dataset.repeatAdd;
            var list = editor.querySelector('[data-repeat-list="' + key + '"]');
            var tpl = editor.querySelector('[data-repeat-template="' + key + '"]');
            if (!list || !tpl) { return; }

            var isService = key.indexOf('service-') === 0;
            var index = isService
                ? nextServiceIndex()
                : list.querySelectorAll('[data-repeat-item]').length;

            var html = tpl.innerHTML.split('__INDEX__').join(String(index));
            var holder = document.createElement(list.tagName === 'TBODY' ? 'tbody' : 'div');
            holder.innerHTML = html.trim();
            var node = holder.firstElementChild;

            list.querySelector('[data-repeat-empty]')?.remove();
            list.appendChild(node);

            if (!isService) { reindex(list, PREFIXES[key]); }
            if (key === 'day') { renumberDays(); }

            bindImagePickers(node);
        });
    });

    editor.addEventListener('click', function (event) {
        var btn = event.target.closest('[data-repeat-remove]');
        if (!btn || !editor.contains(btn)) { return; }

        var item = btn.closest('[data-repeat-item]');
        var list = item?.closest('[data-repeat-list]');
        if (!item || !list) { return; }

        item.remove();

        var key = list.dataset.repeatList;
        if (key.indexOf('service-') !== 0) { reindex(list, PREFIXES[key]); }
        if (key === 'day') { renumberDays(); }
    });

    // ------------------------------------------------------------------
    // Programme : numerotation et generation des jours
    // ------------------------------------------------------------------
    function renumberDays() {
        var list = editor.querySelector('[data-repeat-list="day"]');
        if (!list) { return; }

        var n = 1;
        list.querySelectorAll('[data-repeat-item]').forEach(function (item) {
            var numberInput = item.querySelector('[data-role="day-number"]');
            if (numberInput) { numberInput.value = n; }

            var label = item.querySelector('[data-role="day-label"]');
            if (label) { label.textContent = 'Jour ' + String(n).padStart(2, '0'); }

            // Identifiants d'accordeon uniques apres reordonnancement.
            var collapse = item.querySelector('.accordion-collapse');
            var toggle = item.querySelector('[data-bs-toggle="collapse"]');
            if (collapse && toggle) {
                var id = 'hoDay' + n;
                collapse.id = id;
                toggle.setAttribute('data-bs-target', '#' + id);
            }

            updateDaySummary(item);
            n++;
        });
    }

    function updateDaySummary(item) {
        var city = item.querySelector('[data-role="day-city"]')?.value || '';
        var title = item.querySelector('[data-role="day-title"]')?.value || '';
        var summary = item.querySelector('[data-role="day-summary"]');
        if (summary) {
            summary.textContent = city && title ? city + ' — ' + title : (city || title);
        }
    }

    editor.addEventListener('input', function (event) {
        if (event.target.matches('[data-role="day-city"], [data-role="day-title"]')) {
            var item = event.target.closest('[data-repeat-item]');
            if (item) { updateDaySummary(item); }
        }
    });

    // Generation cote client : cree les jours manquants sans perdre la saisie en cours.
    editor.querySelector('[data-role="generate-days"]')?.addEventListener('click', function () {
        var days = parseInt(editor.querySelector('[data-role="duration-days"]')?.value || '0', 10);
        if (!days || days < 1) {
            window.alert('Renseignez d\'abord le nombre de jours à l\'étape 1.');
            return;
        }

        var list = editor.querySelector('[data-repeat-list="day"]');
        var addBtn = editor.querySelector('[data-repeat-add="day"]');
        if (!list || !addBtn) { return; }

        var current = list.querySelectorAll('[data-repeat-item]').length;
        for (var i = current; i < days; i++) { addBtn.click(); }

        renumberDays();
    });

    // ------------------------------------------------------------------
    // Occupation des departs
    // ------------------------------------------------------------------
    editor.addEventListener('input', function (event) {
        if (!event.target.matches('[data-role="dep-available"], [data-role="dep-reserved"]')) { return; }

        var row = event.target.closest('[data-repeat-item]');
        if (!row) { return; }

        var available = parseInt(row.querySelector('[data-role="dep-available"]')?.value || '0', 10);
        var reserved = parseInt(row.querySelector('[data-role="dep-reserved"]')?.value || '0', 10);
        var ratio = available > 0 ? Math.min(100, Math.round(reserved / available * 100)) : 0;

        var bar = row.querySelector('[data-role="dep-bar"]');
        if (bar) { bar.style.width = ratio + '%'; }

        var count = row.querySelector('[data-role="dep-count"]');
        if (count) { count.textContent = reserved + ' / ' + available; }
    });

    // ------------------------------------------------------------------
    // Economie calculee
    // ------------------------------------------------------------------
    function refreshDiscountHint() {
        var oldPrice = parseFloat(editor.querySelector('[data-role="price-old"]')?.value || '');
        var current = parseFloat(editor.querySelector('[data-role="price-current"]')?.value || '');
        var hint = editor.querySelector('[data-role="discount-hint"]');
        if (!hint) { return; }

        if (oldPrice > 0 && current > 0 && oldPrice > current) {
            hint.textContent = 'Économie calculée : ' + (oldPrice - current).toFixed(2);
        } else {
            hint.textContent = 'Calculée automatiquement si laissée vide.';
        }
    }
    editor.addEventListener('input', function (event) {
        if (event.target.matches('[data-role="price-old"], [data-role="price-current"]')) { refreshDiscountHint(); }
    });
    refreshDiscountHint();

    editor.querySelector('[data-role="currency"]')?.addEventListener('change', function (event) {
        var label = editor.querySelector('[data-role="currency-label"]');
        if (label) { label.textContent = event.target.value; }
    });

    // ------------------------------------------------------------------
    // Televersement (uploader existant)
    // ------------------------------------------------------------------
    function upload(file, context) {
        var data = new FormData();
        data.append('image', file);
        data.append('context', context || 'hajj-omra');

        return fetch(uploadUrl, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: data
        }).then(function (response) {
            return response.json().then(function (payload) {
                if (!response.ok || !payload.success) {
                    throw new Error(payload.message || 'Téléversement impossible.');
                }
                return payload;
            });
        });
    }

    function bindImagePickers(scope) {
        (scope || editor).querySelectorAll('[data-image-picker]').forEach(function (picker) {
            if (picker.dataset.bound === '1') { return; }
            picker.dataset.bound = '1';

            var input = picker.querySelector('[data-role="path"]');
            var file = picker.querySelector('[data-role="file"]');
            var status = picker.querySelector('[data-role="status"]');
            var wrap = picker.querySelector('[data-role="preview-wrap"]');
            var img = picker.querySelector('[data-role="preview"]');

            picker.querySelector('[data-role="browse"]')?.addEventListener('click', function () { file.click(); });

            picker.querySelector('[data-role="clear"]')?.addEventListener('click', function () {
                input.value = '';
                if (wrap) { wrap.hidden = true; }
            });

            file?.addEventListener('change', function () {
                if (!file.files || !file.files[0]) { return; }
                status.textContent = 'Téléversement…';

                upload(file.files[0], picker.dataset.context).then(function (payload) {
                    input.value = payload.path;
                    if (img) { img.src = payload.url; }
                    if (wrap) { wrap.hidden = false; }
                    status.textContent = 'Image ajoutée.';
                }).catch(function (error) {
                    status.textContent = error.message;
                });

                file.value = '';
            });
        });
    }

    bindImagePickers(editor);

    // --- Image principale ---
    var mainZone = editor.querySelector('[data-main-dropzone]');
    if (mainZone) {
        var mainFile = mainZone.querySelector('[data-role="file"]');
        var mainPath = editor.querySelector('[data-role="main-path"]');
        var mainStatus = editor.querySelector('[data-role="main-status"]');
        var mainWrap = editor.querySelector('[data-role="main-preview-wrap"]');
        var mainImg = editor.querySelector('[data-role="main-preview"]');

        function handleMain(file) {
            mainStatus.textContent = 'Téléversement…';
            upload(file, 'hajj-omra-main').then(function (payload) {
                mainPath.value = payload.path;
                mainImg.src = payload.url;
                mainWrap.hidden = false;
                mainStatus.textContent = 'Image principale mise à jour.';
            }).catch(function (error) { mainStatus.textContent = error.message; });
        }

        mainZone.addEventListener('click', function () { mainFile.click(); });
        mainFile.addEventListener('change', function () {
            if (mainFile.files[0]) { handleMain(mainFile.files[0]); }
            mainFile.value = '';
        });
        ['dragenter', 'dragover'].forEach(function (type) {
            mainZone.addEventListener(type, function (e) { e.preventDefault(); mainZone.classList.add('dragover'); });
        });
        ['dragleave', 'drop'].forEach(function (type) {
            mainZone.addEventListener(type, function (e) { e.preventDefault(); mainZone.classList.remove('dragover'); });
        });
        mainZone.addEventListener('drop', function (e) {
            if (e.dataTransfer.files[0]) { handleMain(e.dataTransfer.files[0]); }
        });

        editor.querySelector('[data-role="main-clear"]')?.addEventListener('click', function () {
            mainPath.value = '';
            mainWrap.hidden = true;
            mainStatus.textContent = 'Image principale retirée.';
        });
    }

    // --- Galerie ---
    var galleryList = editor.querySelector('[data-gallery-list]');
    if (galleryList) {
        var galleryFile = editor.querySelector('[data-role="gallery-file"]');
        var galleryTpl = editor.querySelector('[data-gallery-template]');
        var galleryEmpty = editor.querySelector('[data-role="gallery-empty"]');

        function reindexGallery() {
            var i = 0;
            galleryList.querySelectorAll('[data-gallery-item]').forEach(function (item) {
                item.querySelectorAll('[name]').forEach(function (field) {
                    field.name = field.name.replace(/^gallery\[(\d+|__INDEX__)\]/, 'gallery[' + i + ']');
                });
                i++;
            });
            if (galleryEmpty) { galleryEmpty.hidden = i > 0; }
        }

        editor.querySelector('[data-role="gallery-browse"]')?.addEventListener('click', function () { galleryFile.click(); });

        galleryFile?.addEventListener('change', function () {
            Array.prototype.forEach.call(galleryFile.files, function (file) {
                upload(file, 'hajj-omra-gallery').then(function (payload) {
                    var holder = document.createElement('div');
                    holder.innerHTML = galleryTpl.innerHTML.trim();
                    var node = holder.firstElementChild;
                    node.querySelector('[name$="[image_path]"]').value = payload.path;
                    node.querySelector('img').src = payload.url;
                    galleryList.appendChild(node);
                    reindexGallery();
                }).catch(function (error) { window.alert(error.message); });
            });
            galleryFile.value = '';
        });

        galleryList.addEventListener('click', function (event) {
            if (!event.target.closest('[data-role="gallery-remove"]')) { return; }
            event.target.closest('[data-gallery-item]').remove();
            reindexGallery();
        });

        reindexGallery();
    }

    // ------------------------------------------------------------------
    // Reordonnancement (dragula, deja embarque dans le projet)
    // ------------------------------------------------------------------
    if (window.dragula) {
        var dayList = editor.querySelector('[data-sortable="day"]');
        if (dayList) {
            window.dragula([dayList], {
                moves: function (el, container, handle) { return handle.classList.contains('ho-handle'); }
            }).on('drop', function () {
                reindex(dayList, 'program_days');
                renumberDays();
            });
        }

        if (galleryList) {
            window.dragula([galleryList], {
                moves: function (el, container, handle) { return !handle.closest('[data-role="gallery-remove"]'); }
            }).on('drop', function () {
                var i = 0;
                galleryList.querySelectorAll('[data-gallery-item]').forEach(function (item) {
                    item.querySelectorAll('[name]').forEach(function (field) {
                        field.name = field.name.replace(/^gallery\[(\d+)\]/, 'gallery[' + i + ']');
                    });
                    i++;
                });
            });
        }
    }

    // ------------------------------------------------------------------
    // Barre d'actions
    // ------------------------------------------------------------------
    var form = editor.querySelector('form');

    editor.querySelector('[data-role="save-draft"]')?.addEventListener('click', function () {
        var status = editor.querySelector('#status');
        if (status) { status.value = 'draft'; }
        form.submit();
    });

    editor.querySelector('[data-role="publish"]')?.addEventListener('click', function () {
        var status = editor.querySelector('#status');
        if (status) { status.value = 'published'; }
        form.submit();
    });

    renumberDays();
})();
</script>
