<style>
#day-builder-drawer {
    --drawer-w: clamp(560px, 40vw, 820px);
    --bs-offcanvas-width: var(--drawer-w);
    width: var(--drawer-w);
    max-width: 100vw;
    height: 100vh;
    display: flex;
    flex-direction: column;
}
#day-builder-drawer .offcanvas-header,
#day-builder-drawer .offcanvas-body,
#day-builder-drawer .offcanvas-footer {
    padding: 18px;
}
#day-builder-drawer .offcanvas-header,
#day-builder-drawer .offcanvas-footer {
    flex-shrink: 0;
    background: #fff;
    z-index: 2;
}
#day-builder-drawer .offcanvas-header {
    position: sticky;
    top: 0;
}
#day-builder-drawer .offcanvas-footer {
    position: sticky;
    bottom: 0;
    border-top: 1px solid #e9ecef;
}
#day-builder-drawer .offcanvas-title {
    font-size: 1.1rem;
    font-weight: 700;
}
#day-builder-drawer .offcanvas-body {
    overflow-y: auto;
    flex: 1 1 auto;
}
#day-builder-drawer .day-builder-summary {
    font-size: 0.875rem;
    color: #6c757d;
}
#day-builder-drawer .day-builder-tabs .nav-link {
    font-size: 0.92rem;
    font-weight: 600;
}
@media (max-width: 992px) {
    #day-builder-drawer {
        --drawer-w: min(70vw, 720px);
        --bs-offcanvas-width: var(--drawer-w);
        width: var(--drawer-w);
    }
}
@media (max-width: 768px) {
    #day-builder-drawer {
        --drawer-w: 100vw;
        --bs-offcanvas-width: var(--drawer-w);
        width: 100vw;
    }
}
</style>

<div
    class="offcanvas offcanvas-end"
    tabindex="-1"
    id="day-builder-drawer"
    aria-labelledby="day-builder-drawer-label"
    data-bs-backdrop="true"
    data-bs-scroll="false"
    data-day-index=""
    data-day-id=""
    data-day-number=""
>
    <div class="offcanvas-header border-bottom">
        <div>
            <h5 class="offcanvas-title mb-0" id="day-builder-drawer-label">Jour — Ajouter</h5>
            <div class="day-builder-summary" id="day-builder-day-summary">Jour X — Ajouter (0 élément)</div>
            <div class="small text-muted" id="day-builder-drawer-context">Ajoutez des éléments au jour sélectionné.</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Fermer"></button>
    </div>

    <div class="offcanvas-body">
        <ul class="nav nav-pills nav-justified gap-2 mb-3 day-builder-tabs" id="day-builder-tabs" role="tablist">
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

    <div class="offcanvas-footer">
        <div class="d-flex justify-content-end">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="offcanvas">Fermer</button>
        </div>
    </div>
</div>
