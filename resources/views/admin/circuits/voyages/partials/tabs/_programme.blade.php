<div class="tab-pane" id="program-days" role="tabpanel">
                <div class="card ve-programme-tab-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
                            <div>
                                <h4 class="card-title mb-1">Programme</h4>
                                <p class="text-muted mb-0 small">Chaque jour : mode, titre, notes, activitÃ©s. @if(Route::has('admin.circuits.activities.index'))<a href="{{ route('admin.circuits.activities.index') }}" target="_blank">Catalogue d'activitÃ©s</a>.@endif</p>
                            </div>
                            <div class="d-flex align-items-center gap-3">
                                <span class="badge bg-primary fs-6" id="program-days-badge">0 jours</span>
                                <button type="button" class="btn btn-success" id="btn-add-program-day">
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
                                <span><i class="bx bx-info-circle"></i> Aucun jour. Cliquez sur Ã‚Â« Ajouter un jour Ã‚Â» pour dÃ©finir le programme.</span>
                                <button type="button" class="btn btn-sm btn-success" id="btn-add-program-day-empty"><i class="bx bx-plus"></i> Ajouter un jour</button>
                            </div>
                        @endforelse
                        </div>
                    </div>
                </div>
            </div>

