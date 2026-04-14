<div class="tab-pane" id="program-days" role="tabpanel" data-ve-pane-title="Programme">
    <div class="card ve-programme-tab-card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                <div>
                    <p class="ve-section-kicker mb-2">Programme</p>
                    <h4 class="card-title mb-1">Jour par jour</h4>
                    <p class="text-muted mb-0 small">
                        Structurez les etapes, les villes, les repas et les activites du voyage.
                        @if(Route::has('admin.circuits.activities.index'))
                            <a href="{{ route('admin.circuits.activities.index') }}" target="_blank">Voir le catalogue d'activites</a>.
                        @endif
                    </p>
                </div>

                <div class="d-flex align-items-center gap-3">
                    <span class="badge ve-count-badge fs-6" id="program-days-badge">0 jours</span>
                    <button type="button" class="btn btn-primary" id="btn-add-program-day">
                        <i class="bx bx-plus"></i> Ajouter un jour
                    </button>
                </div>
            </div>

            <div class="accordion" id="accordionProgrammeDays">
                @forelse($programDays as $dayIndex => $entry)
                    @include('admin.circuits.voyages.partials.programme._day_card', [
                        'entry' => $entry,
                        'dayIndex' => $dayIndex,
                    ])
                @empty
                    <div class="alert alert-info d-flex align-items-center justify-content-between flex-wrap gap-2" id="program-no-days-alert">
                        <span><i class="bx bx-info-circle"></i> Aucun jour pour le moment. Ajoutez une etape pour construire le programme.</span>
                        <button type="button" class="btn btn-sm btn-primary" id="btn-add-program-day-empty"><i class="bx bx-plus"></i> Ajouter un jour</button>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
