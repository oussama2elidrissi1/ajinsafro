<div class="tab-pane" id="availability" role="tabpanel">
    <div class="card ve-pane-card">
        <div class="card-body">
            @php
                $departureRows = ($laravelVoyage ?? null)
                    ? $laravelVoyage->departures()->orderBy('start_date')->get()
                    : collect();
                $firstDepartureRow = $departureRows->first();
                $cancellationTerms = $cancellationTerms ?? collect();
            @endphp

            @if($laravelVoyage ?? null)
                <input type="hidden" name="cancellation_terms_submitted" value="1">
            @endif

            <p class="ve-section-kicker mb-2">Départs & disponibilités</p>
            <h4 class="card-title mb-3">Dates, stock et réservation</h4>

            <div class="ve-inline-note mb-4">
                <div>
                    <strong>{{ $departureRows->count() }}</strong> départ(s) synchronisé(s)
                    @if(isset($laravelVoyage) && $laravelVoyage && $firstDepartureRow)
                        <span class="ve-inline-note__sep">|</span>
                        <a href="{{ route('admin.circuits.voyages.departures.show', [$laravelVoyage, $firstDepartureRow]) }}" target="_blank" rel="noopener">Ouvrir un départ</a>
                    @endif
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-lg-6">
                    <div class="ve-settings-block h-100">
                        <h5 class="ve-subsection-title">Réservation</h5>
                        <div class="mb-3">
                            <label for="tours_booking_period" class="form-label">Période de réservation</label>
                            <input type="text" class="form-control" id="tours_booking_period" name="tours_booking_period" value="{{ old('tours_booking_period', $meta['tours_booking_period'] ?? '') }}">
                        </div>
                        <div class="mb-0">
                            <label for="st_booking_option_type" class="form-label">Type d’option de réservation</label>
                            <input type="text" class="form-control" id="st_booking_option_type" name="st_booking_option_type" value="{{ old('st_booking_option_type', $meta['st_booking_option_type'] ?? '') }}">
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="ve-settings-block h-100">
                        <h5 class="ve-subsection-title">Horaires</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label for="check_in" class="form-label">Check-in</label>
                                <input type="time" class="form-control" id="check_in" name="check_in" value="{{ old('check_in', $meta['check_in'] ?? '') }}">
                            </div>
                            <div class="col-md-6">
                                <label for="check_out" class="form-label">Check-out</label>
                                <input type="time" class="form-control" id="check_out" name="check_out" value="{{ old('check_out', $meta['check_out'] ?? '') }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-lg-8">
                    <div class="ve-settings-block">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                            <h5 class="ve-subsection-title mb-0">Conditions d’annulation</h5>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="ve-add-cancellation-term">
                                <i class="bx bx-plus"></i> Ajouter une condition
                            </button>
                        </div>
                        <p class="text-muted small mb-3">Plusieurs paliers (jours avant départ, % remboursé). Ordre = priorité d’affichage.</p>

                        <div id="ve-cancellation-terms-rows">
                            @forelse($cancellationTerms as $ci => $term)
                                <div class="card mb-2 ve-cancellation-term-row border">
                                    <div class="card-body py-3">
                                        <div class="row g-2 align-items-end">
                                            <div class="col-6 col-md-2">
                                                <label class="form-label small">Jours avant départ</label>
                                                <input type="number" min="0" class="form-control form-control-sm" name="cancellation_terms[{{ $ci }}][days_before_departure]" value="{{ old('cancellation_terms.'.$ci.'.days_before_departure', $term->days_before_departure) }}">
                                            </div>
                                            <div class="col-6 col-md-2">
                                                <label class="form-label small">% remboursé</label>
                                                <input type="number" step="0.01" min="0" max="100" class="form-control form-control-sm" name="cancellation_terms[{{ $ci }}][refund_percent]" value="{{ old('cancellation_terms.'.$ci.'.refund_percent', $term->refund_percent) }}">
                                            </div>
                                            <div class="col-6 col-md-2">
                                                <label class="form-label small">Ordre</label>
                                                <input type="number" min="0" class="form-control form-control-sm" name="cancellation_terms[{{ $ci }}][sort_order]" value="{{ old('cancellation_terms.'.$ci.'.sort_order', $term->sort_order ?? $ci + 1) }}">
                                            </div>
                                            <div class="col-6 col-md-2">
                                                <div class="form-check mb-0">
                                                    <input type="hidden" name="cancellation_terms[{{ $ci }}][is_active]" value="0">
                                                    <input type="checkbox" class="form-check-input" name="cancellation_terms[{{ $ci }}][is_active]" value="1" id="ct_act_{{ $ci }}" {{ old('cancellation_terms.'.$ci.'.is_active', $term->is_active) ? 'checked' : '' }}>
                                                    <label class="form-check-label small" for="ct_act_{{ $ci }}">Actif</label>
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-4">
                                                <label class="form-label small">Note (optionnel)</label>
                                                <input type="text" class="form-control form-control-sm" name="cancellation_terms[{{ $ci }}][note]" value="{{ old('cancellation_terms.'.$ci.'.note', $term->note) }}">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                @if($laravelVoyage ?? null)
                                    <div class="card mb-2 ve-cancellation-term-row border">
                                        <div class="card-body py-3">
                                            <div class="row g-2 align-items-end">
                                                <div class="col-6 col-md-2">
                                                    <label class="form-label small">Jours avant départ</label>
                                                    <input type="number" min="0" class="form-control form-control-sm" name="cancellation_terms[0][days_before_departure]" value="{{ old('cancellation_terms.0.days_before_departure', '') }}">
                                                </div>
                                                <div class="col-6 col-md-2">
                                                    <label class="form-label small">% remboursé</label>
                                                    <input type="number" step="0.01" min="0" max="100" class="form-control form-control-sm" name="cancellation_terms[0][refund_percent]" value="{{ old('cancellation_terms.0.refund_percent', '') }}">
                                                </div>
                                                <div class="col-6 col-md-2">
                                                    <label class="form-label small">Ordre</label>
                                                    <input type="number" min="0" class="form-control form-control-sm" name="cancellation_terms[0][sort_order]" value="{{ old('cancellation_terms.0.sort_order', 1) }}">
                                                </div>
                                                <div class="col-6 col-md-2">
                                                    <div class="form-check mb-0">
                                                        <input type="hidden" name="cancellation_terms[0][is_active]" value="0">
                                                        <input type="checkbox" class="form-check-input" name="cancellation_terms[0][is_active]" value="1" id="ct_act_0" checked>
                                                        <label class="form-check-label small" for="ct_act_0">Actif</label>
                                                    </div>
                                                </div>
                                                <div class="col-12 col-md-4">
                                                    <label class="form-label small">Note (optionnel)</label>
                                                    <input type="text" class="form-control form-control-sm" name="cancellation_terms[0][note]" value="{{ old('cancellation_terms.0.note', '') }}">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endforelse
                        </div>

                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="ve-settings-block h-100">
                        <h5 class="ve-subsection-title">Synchronisation calendrier</h5>
                        <label for="ical_url" class="form-label">URL iCal</label>
                        <input type="text" class="form-control" id="ical_url" name="ical_url" value="{{ old('ical_url', $meta['ical_url'] ?? '') }}" placeholder="https://…">
                    </div>
                </div>
            </div>

            <div class="ve-dates-section border-top pt-4">
                <div class="ve-settings-block">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                        <h5 class="ve-subsection-title mb-0"><i class="bx bx-calendar-check"></i> Dates de départ</h5>
                        <button type="button" class="btn btn-primary btn-sm" id="add-travel-date">
                            <i class="bx bx-plus"></i> Ajouter une date
                        </button>
                    </div>
                    <p class="text-muted small mb-3">Une ligne par date de vente : stock, supplément et activation.</p>

                <div id="travel-dates-container">
                    @php $datesList = $travelDates ?? collect(); @endphp
                    @forelse($datesList as $di => $dateItem)
                        <div class="card mb-2 travel-date-row border shadow-sm" data-index="{{ $di }}">
                            <div class="card-body py-3">
                                <div class="row g-2 align-items-end">
                                    <div class="col-6 col-md-3">
                                        <label class="form-label small mb-1">Date <span class="text-danger">*</span></label>
                                        @if(!empty($dateItem->id))
                                            <input type="hidden" name="travel_dates[{{ $di }}][id]" value="{{ $dateItem->id }}">
                                        @endif
                                        <input type="date" class="form-control form-control-sm" name="travel_dates[{{ $di }}][date]" value="{{ old("travel_dates.{$di}.date", optional($dateItem)->date ? $dateItem->date->format('Y-m-d') : '') }}" required>
                                    </div>
                                    <div class="col-6 col-md-2">
                                        <label class="form-label small mb-1">Places <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control form-control-sm" name="travel_dates[{{ $di }}][seats]" value="{{ old("travel_dates.{$di}.seats", $dateItem->seats ?? 0) }}" min="0" required>
                                    </div>
                                    <div class="col-6 col-md-2">
                                        <label class="form-label small mb-1">Supplément date</label>
                                        <input type="number" step="0.01" class="form-control form-control-sm" name="travel_dates[{{ $di }}][price_override]" value="{{ old("travel_dates.{$di}.price_override", $dateItem->price_override ?? '') }}" placeholder="—">
                                    </div>
                                    <div class="col-6 col-md-2">
                                        <div class="form-check mb-0 pb-1">
                                            <input type="checkbox" class="form-check-input" name="travel_dates[{{ $di }}][is_active]" value="1" {{ old("travel_dates.{$di}.is_active", $dateItem->is_active ?? true) ? 'checked' : '' }}>
                                            <label class="form-check-label small">Actif</label>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-1 text-md-end">
                                        <button type="button" class="btn btn-sm btn-outline-danger remove-travel-date" aria-label="Supprimer cette date">×</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="alert alert-warning mb-0 border-0">
                            Aucune date configurée. Ajoutez une date pour ouvrir la vente.
                        </div>
                    @endforelse
                </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if($laravelVoyage ?? null)
<script>
(function () {
    var btn = document.getElementById('ve-add-cancellation-term');
    var wrap = document.getElementById('ve-cancellation-terms-rows');
    if (!btn || !wrap) return;
    btn.addEventListener('click', function () {
        var rows = wrap.querySelectorAll('.ve-cancellation-term-row');
        var idx = rows.length;
        var div = document.createElement('div');
        div.className = 'card mb-2 ve-cancellation-term-row border';
        div.innerHTML = '<div class="card-body py-3"><div class="row g-2 align-items-end">' +
            '<div class="col-6 col-md-2"><label class="form-label small">Jours avant départ</label>' +
            '<input type="number" min="0" class="form-control form-control-sm" name="cancellation_terms[' + idx + '][days_before_departure]" value=""></div>' +
            '<div class="col-6 col-md-2"><label class="form-label small">% remboursé</label>' +
            '<input type="number" step="0.01" min="0" max="100" class="form-control form-control-sm" name="cancellation_terms[' + idx + '][refund_percent]" value=""></div>' +
            '<div class="col-6 col-md-2"><label class="form-label small">Ordre</label>' +
            '<input type="number" min="0" class="form-control form-control-sm" name="cancellation_terms[' + idx + '][sort_order]" value="' + (idx + 1) + '"></div>' +
            '<div class="col-6 col-md-2"><div class="form-check mb-0">' +
            '<input type="hidden" name="cancellation_terms[' + idx + '][is_active]" value="0">' +
            '<input type="checkbox" class="form-check-input" name="cancellation_terms[' + idx + '][is_active]" value="1" id="ct_act_dyn_' + idx + '" checked>' +
            '<label class="form-check-label small" for="ct_act_dyn_' + idx + '">Actif</label></div></div>' +
            '<div class="col-12 col-md-4"><label class="form-label small">Note (optionnel)</label>' +
            '<input type="text" class="form-control form-control-sm" name="cancellation_terms[' + idx + '][note]" value=""></div>' +
            '</div></div>';
        wrap.appendChild(div);
    });
})();
</script>
@endif
