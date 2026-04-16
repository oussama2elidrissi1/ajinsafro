@php
    $allVoyageThemes = $allVoyageThemes ?? collect();
    $laravelV = $laravelVoyage ?? null;
    $selectedThemeIds = $laravelV ? $laravelV->themes->pluck('id')->map(fn ($id) => (int) $id)->all() : [];
@endphp
<div class="card ve-pane-card mb-4">
    <div class="card-body">
        <h4 class="card-title mb-2"><i class="bx bx-purchase-tag text-primary"></i> Thèmes du voyage</h4>
        <p class="text-muted small mb-3">Thèmes du circuit (catalogue). <strong>Ajouter</strong> ou icônes pour modifier / supprimer.</p>

        <div class="row">
            <div class="col-lg-4 mb-3" data-voyage-themes-block>
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h5 class="mb-0">Thèmes</h5>
                    <button type="button" class="btn btn-sm btn-outline-primary ve-voyage-theme-add" data-label="Thème du voyage">
                        <i class="bx bx-plus"></i> Ajouter
                    </button>
                </div>
                <div
                    class="ve-voyage-themes-list border rounded p-2 bg-light"
                    data-assigned-ids="{{ implode(',', $selectedThemeIds) }}"
                >
                    @foreach ($allVoyageThemes as $th)
                        <div class="d-flex align-items-center gap-2 mb-2 ve-voyage-theme-row" data-theme-id="{{ (int) $th->id }}">
                            <div class="form-check mb-0 flex-grow-1">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    name="voyage_theme_ids[]"
                                    value="{{ (int) $th->id }}"
                                    id="voyage_theme_{{ (int) $th->id }}"
                                    @checked(in_array((int) $th->id, $selectedThemeIds, true))
                                >
                                <label class="form-check-label" for="voyage_theme_{{ (int) $th->id }}">{{ $th->name }}</label>
                            </div>
                            <div class="btn-group btn-group-sm">
                                <button
                                    type="button"
                                    class="btn btn-outline-secondary ve-voyage-theme-edit"
                                    data-theme-id="{{ (int) $th->id }}"
                                    data-name="{{ e($th->name) }}"
                                    data-slug="{{ e($th->slug) }}"
                                    title="Modifier"
                                >
                                    <i class="bx bx-edit"></i>
                                </button>
                                <button
                                    type="button"
                                    class="btn btn-outline-danger ve-voyage-theme-delete"
                                    data-theme-id="{{ (int) $th->id }}"
                                    data-name="{{ e($th->name) }}"
                                    title="Supprimer"
                                >
                                    <i class="bx bx-trash"></i>
                                </button>
                            </div>
                        </div>
                    @endforeach
                    @if ($allVoyageThemes->isEmpty())
                        <p class="text-muted small mb-0 ve-voyage-themes-empty">Aucun thème disponible pour le moment.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal Ajouter / Modifier thème Laravel (même logique que les taxonomies WP) --}}
<div class="modal fade" id="voyageThemeModal" tabindex="-1" aria-labelledby="voyageThemeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="voyageThemeModalLabel">Ajouter un thème</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <form id="voyageThemeForm">
                    <input type="hidden" name="theme_id" id="voyageThemeId" value="">
                    <div class="mb-3">
                        <label for="voyageThemeName" class="form-label">Nom <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="voyageThemeName" name="name" autocomplete="off">
                    </div>
                    <div class="mb-3">
                        <label for="voyageThemeSlug" class="form-label">Slug (optionnel)</label>
                        <input type="text" class="form-control" id="voyageThemeSlug" name="slug" placeholder="auto si vide" autocomplete="off">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="voyageThemeSubmit">
                    <i class="bx bx-save"></i> Enregistrer
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const baseUrl = @json(route('admin.circuits.voyage-themes.index'));
    const csrf = @json(csrf_token());

    function getCheckedThemeIds(container) {
        return Array.from(container.querySelectorAll('input[type="checkbox"][name="voyage_theme_ids[]"]:checked')).map(function(cb) { return cb.value; });
    }

    function renderThemesList(themes, assignedIds) {
        assignedIds = assignedIds || [];
        const listEl = document.querySelector('.ve-voyage-themes-list');
        if (!listEl) return;
        let html = '';
        (themes || []).forEach(function(t) {
            const id = String(t.id);
            const checked = assignedIds.indexOf(id) !== -1 ? ' checked' : '';
            const safeName = String(t.name || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;');
            const safeSlug = String(t.slug || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;');
            html += '<div class="d-flex align-items-center gap-2 mb-2 ve-voyage-theme-row" data-theme-id="' + id + '">' +
                '<div class="form-check mb-0 flex-grow-1">' +
                '<input class="form-check-input" type="checkbox" name="voyage_theme_ids[]" value="' + id + '" id="voyage_theme_' + id + '"' + checked + '>' +
                '<label class="form-check-label" for="voyage_theme_' + id + '">' + safeName + '</label></div>' +
                '<div class="btn-group btn-group-sm">' +
                '<button type="button" class="btn btn-outline-secondary ve-voyage-theme-edit" data-theme-id="' + id + '" data-name="' + safeName + '" data-slug="' + safeSlug + '" title="Modifier"><i class="bx bx-edit"></i></button>' +
                '<button type="button" class="btn btn-outline-danger ve-voyage-theme-delete" data-theme-id="' + id + '" data-name="' + safeName + '" title="Supprimer"><i class="bx bx-trash"></i></button>' +
                '</div></div>';
        });
        listEl.innerHTML = html || '<p class="text-muted small mb-0 ve-voyage-themes-empty">Aucun thème disponible pour le moment.</p>';
        listEl.setAttribute('data-assigned-ids', assignedIds.join(','));
        bindThemeListEvents(listEl);
    }

    function refreshVoyageThemes() {
        const listEl = document.querySelector('.ve-voyage-themes-list');
        const assignedIds = listEl ? getCheckedThemeIds(listEl) : [];
        fetch(baseUrl, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        }).then(function(r) { return r.json(); }).then(function(data) {
            if (data.success && data.themes) {
                renderThemesList(data.themes, assignedIds);
            }
        }).catch(function() {});
    }

    function bindThemeListEvents(container) {
        if (!container) return;
        container.querySelectorAll('.ve-voyage-theme-edit').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const modal = document.getElementById('voyageThemeModal');
                document.getElementById('voyageThemeModalLabel').textContent = 'Modifier le thème';
                document.getElementById('voyageThemeId').value = this.dataset.themeId || '';
                document.getElementById('voyageThemeName').value = this.getAttribute('data-name') || '';
                document.getElementById('voyageThemeSlug').value = this.getAttribute('data-slug') || '';
                if (window.bootstrap && modal) {
                    bootstrap.Modal.getOrCreateInstance(modal).show();
                }
            });
        });
        container.querySelectorAll('.ve-voyage-theme-delete').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const themeId = this.dataset.themeId;
                const name = this.getAttribute('data-name') || themeId;
                const self = this;

                function runDelete() {
                    self.disabled = true;
                    fetch(baseUrl + '/' + encodeURIComponent(themeId), {
                        method: 'DELETE',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'Content-Type': 'application/json'
                        }
                    }).then(function(r) { return r.json(); }).then(function(data) {
                        self.disabled = false;
                        if (data.success) {
                            refreshVoyageThemes();
                        } else {
                            alert(data.message || 'Erreur');
                        }
                    }).catch(function() {
                        self.disabled = false;
                        alert('Erreur réseau');
                    });
                }

                self.disabled = true;
                fetch(baseUrl + '/' + encodeURIComponent(themeId) + '/impact', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                }).then(function(r) { return r.json(); }).then(function(impact) {
                    self.disabled = false;
                    let msg = 'Supprimer le thème « ' + name + ' » ? Les liaisons avec les voyages seront retirées.';
                    if (impact && impact.success && Array.isArray(impact.voyages) && impact.voyages.length) {
                        const lines = impact.voyages.map(function(v) {
                            const id = v.id != null ? '#' + v.id : '';
                            const vn = (v.name || '').toString();
                            const slug = v.slug ? ' — ' + v.slug : '';
                            return '• ' + id + ' ' + vn + slug;
                        });
                        msg = 'Voyages concernés (' + impact.voyages.length + ') :\n\n' + lines.join('\n') + '\n\nConfirmer la suppression du thème « ' + name + ' » ?';
                    }
                    if (!confirm(msg)) return;
                    runDelete();
                }).catch(function() {
                    self.disabled = false;
                    if (!confirm('Impossible de charger l’impact. Supprimer quand même le thème « ' + name + ' » ?')) return;
                    runDelete();
                });
            });
        });
    }

    document.querySelectorAll('.ve-voyage-theme-add').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const modal = document.getElementById('voyageThemeModal');
            document.getElementById('voyageThemeModalLabel').textContent = 'Ajouter un thème — ' + (this.dataset.label || 'Voyage');
            document.getElementById('voyageThemeId').value = '';
            document.getElementById('voyageThemeName').value = '';
            document.getElementById('voyageThemeSlug').value = '';
            if (window.bootstrap && modal) {
                bootstrap.Modal.getOrCreateInstance(modal).show();
            }
        });
    });

    document.querySelectorAll('.ve-voyage-themes-list').forEach(function(listEl) {
        bindThemeListEvents(listEl);
    });

    var submitBtn = document.getElementById('voyageThemeSubmit');
    if (submitBtn) {
        submitBtn.addEventListener('click', function() {
            var name = document.getElementById('voyageThemeName').value.trim();
            var themeId = document.getElementById('voyageThemeId').value.trim();
            var slug = document.getElementById('voyageThemeSlug').value.trim();
            if (!name) {
                document.getElementById('voyageThemeName').focus();
                return;
            }
            var isEdit = !!themeId;
            var url = isEdit ? baseUrl + '/' + encodeURIComponent(themeId) : baseUrl;
            var method = isEdit ? 'PUT' : 'POST';
            var body = isEdit ? { name: name, slug: slug, _token: csrf } : { name: name, slug: slug, _token: csrf };
            var self = this;
            self.disabled = true;
            fetch(url, {
                method: method,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf
                },
                body: JSON.stringify(body)
            }).then(function(r) { return r.json(); }).then(function(data) {
                self.disabled = false;
                if (data.success) {
                    var modal = document.getElementById('voyageThemeModal');
                    if (window.bootstrap && modal) {
                        bootstrap.Modal.getOrCreateInstance(modal).hide();
                    }
                    refreshVoyageThemes();
                } else {
                    alert(data.message || 'Erreur');
                }
            }).catch(function() {
                self.disabled = false;
                alert('Erreur réseau');
            });
        });
    }
})();
</script>
