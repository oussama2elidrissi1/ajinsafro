<div class="row g-3" id="day-builder-activities">
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
        <div class="col-12 text-muted">Aucune activité dans le catalogue.</div>
    @endif
</div>
