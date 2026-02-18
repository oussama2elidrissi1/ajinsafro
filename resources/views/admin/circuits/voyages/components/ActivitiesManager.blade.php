<div class="row g-3" id="day-builder-activities">
    {{-- Créer une nouvelle activité (sans quitter le drawer / sans rafraîchir) --}}
    <div class="col-12 mb-2">
        <div class="card border-primary">
            <div class="card-header bg-light py-2">
                <strong><i class="bx bx-plus-circle"></i> Créer une nouvelle activité</strong>
            </div>
            <div id="day-builder-new-activity-form" class="card-body">
                <form id="day-builder-new-activity-form-el" action="{{ route('admin.circuits.activities.store') }}" method="POST">
                    @csrf
                    <div class="row g-2">
                        <div class="col-md-5">
                            <label for="day-builder-activity-title" class="form-label small">Titre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="day-builder-activity-title" name="title" required placeholder="Ex. Visite du château">
                        </div>
                        <div class="col-md-5">
                            <label for="day-builder-activity-description" class="form-label small">Description</label>
                            <input type="text" class="form-control form-control-sm" id="day-builder-activity-description" name="description" placeholder="Optionnel">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-sm btn-primary w-100" id="day-builder-new-activity-submit">
                                <span class="btn-text">Créer</span>
                                <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                            </button>
                        </div>
                    </div>
                    <div id="day-builder-new-activity-error" class="small text-danger mt-2" style="display: none;"></div>
                </form>
            </div>
        </div>
    </div>

    @foreach($activitiesCatalog as $act)
        <div class="col-md-6 col-lg-6">
            <div class="card h-100 programme-catalog-card">
                <div class="card-body d-flex flex-column">
                    <h6 class="card-title">{{ $act->title }}</h6>
                    <p class="card-text small text-muted flex-grow-1">{{ \Illuminate\Support\Str::limit($act->description ?? '', 90) }}</p>
                    <button
                        type="button"
                        class="btn btn-sm btn-primary day-builder-add-activity"
                        data-activity-id="{{ $act->id }}"
                        data-activity-title="{{ e($act->title) }}"
                    >
                        Ajouter au jour
                    </button>
                </div>
            </div>
        </div>
    @endforeach

    @if($activitiesCatalog->isEmpty())
        <div class="col-12 text-muted" id="day-builder-activities-empty-msg">Aucune activité dans le catalogue. Créez-en une ci-dessus.</div>
    @endif
</div>

<script>
(function() {
    var form = document.getElementById('day-builder-new-activity-form-el');
    var formWrap = document.getElementById('day-builder-new-activity-form');
    var submitBtn = document.getElementById('day-builder-new-activity-submit');
    var errorEl = document.getElementById('day-builder-new-activity-error');
    var container = document.getElementById('day-builder-activities');
    var emptyMsg = document.getElementById('day-builder-activities-empty-msg');

    if (!form || !container) return;

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        if (errorEl) { errorEl.style.display = 'none'; errorEl.textContent = ''; }
        var btnText = submitBtn && submitBtn.querySelector('.btn-text');
        var spinner = submitBtn && submitBtn.querySelector('.spinner-border');
        if (submitBtn) submitBtn.disabled = true;
        if (btnText) btnText.classList.add('d-none');
        if (spinner) spinner.classList.remove('d-none');

        var formData = new FormData(form);
        var token = form.querySelector('input[name="_token"]');
        if (token) formData.append('_token', token.value);

        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) {
            if (r.ok) return r.json();
            return r.json().then(function(data) { throw data; });
        })
        .then(function(data) {
            if (!data.activity) return;
            var act = data.activity;
            var col = document.createElement('div');
            col.className = 'col-md-6 col-lg-6';
            col.innerHTML = '<div class="card h-100 programme-catalog-card">' +
                '<div class="card-body d-flex flex-column">' +
                '<h6 class="card-title">' + (act.title || '').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</h6>' +
                '<p class="card-text small text-muted flex-grow-1">' + (act.description || '').replace(/</g, '&lt;').replace(/>/g, '&gt;').substring(0, 90) + '</p>' +
                '<button type="button" class="btn btn-sm btn-primary day-builder-add-activity" data-activity-id="' + act.id + '" data-activity-title="' + (act.title || '').replace(/"/g, '&quot;') + '">Ajouter au jour</button>' +
                '</div></div>';
            if (emptyMsg) {
                container.insertBefore(col, emptyMsg);
            } else {
                container.appendChild(col);
            }
            if (emptyMsg) emptyMsg.style.display = 'none';
            form.reset();
        })
        .catch(function(err) {
            var msg = (err && err.message) || 'Erreur lors de la création.';
            if (err && err.errors) {
                var first = Object.keys(err.errors).map(function(k) { return err.errors[k][0]; })[0];
                if (first) msg = first;
            }
            if (errorEl) { errorEl.textContent = msg; errorEl.style.display = 'block'; }
        })
        .finally(function() {
            if (submitBtn) submitBtn.disabled = false;
            if (btnText) btnText.classList.remove('d-none');
            if (spinner) spinner.classList.add('d-none');
        });
    });
})();
</script>
