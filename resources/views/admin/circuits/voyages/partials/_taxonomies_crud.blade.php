@php
    // Certaines installs WP exposent "language" ou "languages". On n'affiche qu'un seul bloc "Langue".
    $taxonomyConfig = [
        'st_tour_type' => 'Type de tour',
        'durations'    => 'Durée',
        'language'     => 'Langue',
    ];
    $availableTaxonomies = $availableTaxonomies ?? [];
    $assignedTaxonomies = $assignedTaxonomies ?? [];

    // Fallback compat : si la clé principale n'existe pas mais "languages" est remplie.
    if (empty($availableTaxonomies['language']) && ! empty($availableTaxonomies['languages'])) {
        $availableTaxonomies['language'] = $availableTaxonomies['languages'];
    }
    if (empty($assignedTaxonomies['language']) && ! empty($assignedTaxonomies['languages'])) {
        $assignedTaxonomies['language'] = $assignedTaxonomies['languages'];
    }
@endphp
<div class="row">
    @foreach($taxonomyConfig as $taxKey => $taxLabel)
    <div class="col-lg-3 mb-3" data-taxonomy-block="{{ $taxKey }}">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <h5 class="mb-0">{{ $taxLabel }}</h5>
            <button type="button" class="btn btn-sm btn-outline-primary ve-taxonomy-add" data-taxonomy="{{ $taxKey }}" data-label="{{ $taxLabel }}">
                <i class="bx bx-plus"></i> Ajouter
            </button>
        </div>
        <div class="ve-taxonomy-terms-list border rounded p-2 bg-light" data-taxonomy="{{ $taxKey }}" data-assigned-ids="{{ implode(',', $assignedTaxonomies[$taxKey] ?? []) }}">
            @foreach($availableTaxonomies[$taxKey] ?? [] as $term)
            <div class="d-flex align-items-center gap-2 mb-2 ve-term-row" data-term-id="{{ $term->term_id }}">
                <div class="form-check mb-0 flex-grow-1">
                    <input class="form-check-input" type="checkbox" name="{{ $taxKey }}[]" value="{{ $term->term_id }}" id="{{ $taxKey }}_{{ $term->term_id }}" {{ in_array($term->term_id, $assignedTaxonomies[$taxKey] ?? []) ? 'checked' : '' }}>
                    <label class="form-check-label" for="{{ $taxKey }}_{{ $term->term_id }}">{{ $term->name }}</label>
                </div>
                <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-outline-secondary ve-taxonomy-edit" data-taxonomy="{{ $taxKey }}" data-term-id="{{ $term->term_id }}" data-name="{{ e($term->name) }}" data-slug="{{ e($term->slug) }}" title="Modifier">
                        <i class="bx bx-edit"></i>
                    </button>
                    <button type="button" class="btn btn-outline-danger ve-taxonomy-delete" data-taxonomy="{{ $taxKey }}" data-term-id="{{ $term->term_id }}" data-name="{{ e($term->name) }}" title="Supprimer">
                        <i class="bx bx-trash"></i>
                    </button>
                </div>
            </div>
            @endforeach
            @if(empty($availableTaxonomies[$taxKey]) || $availableTaxonomies[$taxKey]->isEmpty())
            <p class="text-muted small mb-0 ve-taxonomy-empty">Aucune catégorie. Cliquez sur Ajouter.</p>
            @endif
        </div>
    </div>
    @endforeach
</div>

{{-- Modal Ajouter / Modifier catégorie --}}
<div class="modal fade" id="taxonomyTermModal" tabindex="-1" aria-labelledby="taxonomyTermModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="taxonomyTermModalLabel">Ajouter une catégorie</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <form id="taxonomyTermForm">
                    <input type="hidden" name="taxonomy" id="taxonomyTermTaxonomy">
                    <input type="hidden" name="term_id" id="taxonomyTermId">
                    <div class="mb-3">
                        <label for="taxonomyTermName" class="form-label">Nom <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="taxonomyTermName" name="name">
                    </div>
                    <div class="mb-3">
                        <label for="taxonomyTermSlug" class="form-label">Slug (optionnel)</label>
                        <input type="text" class="form-control" id="taxonomyTermSlug" name="slug" placeholder="auto si vide">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="taxonomyTermSubmit">
                    <i class="bx bx-save"></i> Enregistrer
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const baseUrl = "{{ url('admin/circuits/taxonomy-terms') }}";
    const csrf = "{{ csrf_token() }}";
    const taxonomyLabels = @json($taxonomyConfig);

    function getCheckedIds(container) {
        return Array.from(container.querySelectorAll('input[type="checkbox"]:checked')).map(cb => cb.value);
    }

    function renderTermsList(taxonomy, terms, assignedIds) {
        assignedIds = assignedIds || [];
        const listEl = document.querySelector('.ve-taxonomy-terms-list[data-taxonomy="' + taxonomy + '"]');
        if (!listEl) return;
        const names = (terms || []).map(t => t.name);
        const slugs = (terms || []).map(t => t.slug || '');
        let html = '';
        (terms || []).forEach((term, i) => {
            const checked = assignedIds.indexOf(String(term.term_id)) !== -1 ? ' checked' : '';
            const safeName = (term.name || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;');
            const safeSlug = (term.slug || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;');
            html += '<div class="d-flex align-items-center gap-2 mb-2 ve-term-row" data-term-id="' + term.term_id + '">' +
                '<div class="form-check mb-0 flex-grow-1">' +
                '<input class="form-check-input" type="checkbox" name="' + taxonomy + '[]" value="' + term.term_id + '" id="' + taxonomy + '_' + term.term_id + '"' + checked + '>' +
                '<label class="form-check-label" for="' + taxonomy + '_' + term.term_id + '">' + safeName + '</label></div>' +
                '<div class="btn-group btn-group-sm">' +
                '<button type="button" class="btn btn-outline-secondary ve-taxonomy-edit" data-taxonomy="' + taxonomy + '" data-term-id="' + term.term_id + '" data-name="' + safeName + '" data-slug="' + safeSlug + '" title="Modifier"><i class="bx bx-edit"></i></button>' +
                '<button type="button" class="btn btn-outline-danger ve-taxonomy-delete" data-taxonomy="' + taxonomy + '" data-term-id="' + term.term_id + '" data-name="' + safeName + '" title="Supprimer"><i class="bx bx-trash"></i></button>' +
                '</div></div>';
        });
        listEl.innerHTML = html || '<p class="text-muted small mb-0 ve-taxonomy-empty">Aucune catégorie. Cliquez sur Ajouter.</p>';
        listEl.setAttribute('data-assigned-ids', assignedIds.join(','));
        bindTaxonomyListEvents(listEl);
    }

    function refreshTaxonomy(taxonomy) {
        const listEl = document.querySelector('.ve-taxonomy-terms-list[data-taxonomy="' + taxonomy + '"]');
        const assignedIds = listEl ? getCheckedIds(listEl) : [];
        fetch(baseUrl + '?taxonomy=' + encodeURIComponent(taxonomy), {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        }).then(r => r.json()).then(data => {
            if (data.success && data.terms) {
                renderTermsList(taxonomy, data.terms, assignedIds);
            }
        }).catch(() => {});
    }

    function bindTaxonomyListEvents(container) {
        if (!container) return;
        container.querySelectorAll('.ve-taxonomy-edit').forEach(btn => {
            btn.addEventListener('click', function() {
                const modal = document.getElementById('taxonomyTermModal');
                document.getElementById('taxonomyTermModalLabel').textContent = 'Modifier la catégorie';
                document.getElementById('taxonomyTermTaxonomy').value = this.dataset.taxonomy;
                document.getElementById('taxonomyTermId').value = this.dataset.termId;
                document.getElementById('taxonomyTermName').value = this.dataset.name || '';
                document.getElementById('taxonomyTermSlug').value = this.dataset.slug || '';
                (window.bootstrap && bootstrap.Modal.getOrCreateInstance(modal)).show();
            });
        });
        container.querySelectorAll('.ve-taxonomy-delete').forEach(btn => {
            btn.addEventListener('click', function() {
                const taxonomy = this.dataset.taxonomy;
                const termId = this.dataset.termId;
                const name = this.dataset.name || termId;
                if (!confirm('Supprimer la catégorie « ' + name + ' » ?')) return;
                fetch(baseUrl + '/' + termId, {
                    method: 'DELETE',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Content-Type': 'application/json'
                    }
                }).then(r => r.json()).then(data => {
                    if (data.success) {
                        refreshTaxonomy(taxonomy);
                    } else {
                        alert(data.message || 'Erreur');
                    }
                }).catch(() => alert('Erreur réseau'));
            });
        });
    }

    document.querySelectorAll('.ve-taxonomy-add').forEach(btn => {
        btn.addEventListener('click', function() {
            const taxonomy = this.dataset.taxonomy;
            const modal = document.getElementById('taxonomyTermModal');
            document.getElementById('taxonomyTermModalLabel').textContent = 'Ajouter une catégorie — ' + (this.dataset.label || taxonomy);
            document.getElementById('taxonomyTermTaxonomy').value = taxonomy;
            document.getElementById('taxonomyTermId').value = '';
            document.getElementById('taxonomyTermName').value = '';
            document.getElementById('taxonomyTermSlug').value = '';
            (window.bootstrap && bootstrap.Modal.getOrCreateInstance(modal)).show();
        });
    });

    document.querySelectorAll('.ve-taxonomy-terms-list').forEach(listEl => {
        bindTaxonomyListEvents(listEl);
    });

    document.getElementById('taxonomyTermSubmit').addEventListener('click', function() {
        const form = document.getElementById('taxonomyTermForm');
        const name = document.getElementById('taxonomyTermName').value.trim();
        const termId = document.getElementById('taxonomyTermId').value;
        const taxonomy = document.getElementById('taxonomyTermTaxonomy').value;
        const slug = document.getElementById('taxonomyTermSlug').value.trim();
        if (!name) {
            document.getElementById('taxonomyTermName').focus();
            return;
        }
        const isEdit = !!termId;
        const url = isEdit ? baseUrl + '/' + termId : baseUrl;
        const method = isEdit ? 'PUT' : 'POST';
        const body = isEdit ? { name: name, slug: slug, _token: csrf } : { name: name, taxonomy: taxonomy, slug: slug, _token: csrf };
        this.disabled = true;
        fetch(url, {
            method: method,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf
            },
            body: JSON.stringify(body)
        }).then(r => r.json()).then(data => {
            this.disabled = false;
            if (data.success) {
                (window.bootstrap && bootstrap.Modal.getInstance(document.getElementById('taxonomyTermModal'))).hide();
                refreshTaxonomy(taxonomy);
            } else {
                alert(data.message || 'Erreur');
            }
        }).catch(() => {
            this.disabled = false;
            alert('Erreur réseau');
        });
    });
})();
</script>
