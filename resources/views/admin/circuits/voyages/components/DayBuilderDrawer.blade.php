<div
    class="offcanvas offcanvas-end"
    tabindex="-1"
    id="day-builder-drawer"
    aria-labelledby="day-builder-drawer-label"
    data-day-index=""
    data-day-id=""
    data-day-number=""
>
    <div class="offcanvas-header border-bottom">
        <div>
            <h5 class="offcanvas-title mb-0" id="day-builder-drawer-label">Jour — Ajouter</h5>
            <div class="small text-muted" id="day-builder-drawer-context">Ajoutez des éléments au jour sélectionné.</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Fermer"></button>
    </div>

    <div class="offcanvas-body">
        <ul class="nav nav-pills nav-justified gap-2 mb-3" id="day-builder-tabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#day-builder-tab-activities" type="button" role="tab">Activités</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#day-builder-tab-hotels" type="button" role="tab">Hôtels</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#day-builder-tab-transfers" type="button" role="tab">Transferts</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#day-builder-tab-flights" type="button" role="tab">Vols</button>
            </li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="day-builder-tab-activities" role="tabpanel">
                @include('admin.circuits.voyages.components.ActivitiesManager', ['activitiesCatalog' => $activitiesCatalog])
            </div>
            <div class="tab-pane fade" id="day-builder-tab-hotels" role="tabpanel">
                @include('admin.circuits.voyages.components.HotelsManager')
            </div>
            <div class="tab-pane fade" id="day-builder-tab-transfers" role="tabpanel">
                @include('admin.circuits.voyages.components.TransfersManager')
            </div>
            <div class="tab-pane fade" id="day-builder-tab-flights" role="tabpanel">
                @include('admin.circuits.voyages.components.FlightsManager', [
                    'flightOptionsWithIndex' => $flightOptionsWithIndex ?? [],
                    'nextFlightOptionIndex' => $nextFlightOptionIndex ?? 0,
                    'lastDayNumber' => $lastDayNumber ?? (($programDays && $programDays->isNotEmpty()) ? $programDays->count() : 1),
                    'airlines' => $airlines ?? collect(),
                    'programDays' => $programDays ?? collect()
                ])
            </div>
        </div>
    </div>
</div>
