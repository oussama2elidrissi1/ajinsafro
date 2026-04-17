<div class="tab-pane" id="availability" role="tabpanel" data-ve-pane-title="Départs">
    <div class="card ve-pane-card">
        <div class="card-body">
            @php
                $departureRows = ($laravelVoyage ?? null)
                    ? $laravelVoyage->departures()->orderBy('start_date')->get()
                    : collect();
                $firstDepartureRow = $departureRows->first();
                $departureService = app(\App\Services\DepartureManagementService::class);
                $departureMetrics = $departureService->buildDepartureMetrics($departureRows);
                $departureSummary = $departureService->summarizeMetrics($departureMetrics);
            @endphp

            <p class="ve-section-kicker mb-2">Departs & disponibilites</p>
            <h4 class="card-title mb-2">Dates, stock et reservation</h4>
            <p class="text-muted small mb-4">Renseignez les dates ouvertes a la vente, le stock et les regles de reservation du voyage.</p>

            <div class="ve-inline-note mb-4">
                <div>
                    <strong>{{ $departureRows->count() }}</strong> depart(s) synchronise(s)
                    @if(isset($laravelVoyage) && $laravelVoyage && $firstDepartureRow)
                        <span class="ve-inline-note__sep">|</span>
                        <a href="{{ route('admin.circuits.voyages.departures.show', [$laravelVoyage, $firstDepartureRow]) }}" target="_blank" rel="noopener">Ouvrir un depart</a>
                    @endif
                </div>
                <span class="text-muted small">Chaque date active met a jour le depart correspondant.</span>
            </div>

            <div class="card border mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                        <div>
                            <h5 class="mb-1">Gestion opérationnelle des départs</h5>
                            <p class="text-muted small mb-0">Même logique que la page globale Départs & Disponibilités.</p>
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#voyageRoomAvailabilityModal">
                            <i class="bx bx-bed me-1"></i> Gérer départs et chambres
                        </button>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-3"><div class="border rounded p-2 bg-light"><small class="text-muted d-block">Départs</small><strong>{{ (int) ($departureSummary['total_departures'] ?? 0) }}</strong></div></div>
                        <div class="col-md-3"><div class="border rounded p-2 bg-light"><small class="text-muted d-block">Actifs</small><strong class="text-success">{{ (int) ($departureSummary['active_departures'] ?? 0) }}</strong></div></div>
                        <div class="col-md-3"><div class="border rounded p-2 bg-light"><small class="text-muted d-block">Réservées</small><strong>{{ (int) ($departureSummary['reserved_capacity'] ?? 0) }}</strong></div></div>
                        <div class="col-md-3"><div class="border rounded p-2 bg-light"><small class="text-muted d-block">Disponibles</small><strong class="text-primary">{{ (int) ($departureSummary['available_capacity'] ?? 0) }}</strong></div></div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Départ</th>
                                    <th>Retour</th>
                                    <th>Total</th>
                                    <th>Réservées</th>
                                    <th>Disponibles</th>
                                    <th>Statut</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($departureMetrics as $metric)
                                    <tr>
                                        <td>{{ $metric['start_date_label'] ?? '—' }}</td>
                                        <td>{{ $metric['end_date_label'] ?? '—' }}</td>
                                        <td>{{ (int) ($metric['total_capacity'] ?? 0) }}</td>
                                        <td>{{ (int) ($metric['reserved_capacity'] ?? 0) }}</td>
                                        <td><strong>{{ (int) ($metric['available_capacity'] ?? 0) }}</strong></td>
                                        <td>{{ $metric['status_label'] ?? ($metric['status'] ?? '') }}</td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-sm btn-outline-primary" data-ra-open-modal="1" data-ra-select-departure="{{ (int) ($metric['id'] ?? 0) }}" data-bs-toggle="modal" data-bs-target="#voyageRoomAvailabilityModal">Ouvrir</button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-center text-muted">Aucun départ synchronisé.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="ve-dates-section mb-4">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                    <div>
                        <h5 class="ve-subsection-title mb-1"><i class="bx bx-calendar-check"></i> Dates de depart</h5>
                        <p class="text-muted small mb-0">Une ligne par date, avec stock et prix specifique si besoin.</p>
                    </div>

                    <button type="button" class="btn btn-primary btn-sm" id="add-travel-date">
                        <i class="bx bx-plus"></i> Ajouter une date
                    </button>
                </div>

                <div id="travel-dates-container">
                    @php $datesList = $travelDates ?? collect(); @endphp
                    @forelse($datesList as $di => $dateItem)
                        <div class="card mb-2 bg-light travel-date-row" data-index="{{ $di }}">
                            <div class="card-body py-2">
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
                                        <label class="form-label small mb-1">Prix specifique</label>
                                        <input type="number" step="0.01" class="form-control form-control-sm" name="travel_dates[{{ $di }}][price_override]" value="{{ old("travel_dates.{$di}.price_override", $dateItem->price_override ?? '') }}" placeholder="-">
                                    </div>

                                    <div class="col-6 col-md-2">
                                        <div class="form-check mb-0 pb-1">
                                            <input type="checkbox" class="form-check-input" name="travel_dates[{{ $di }}][is_active]" value="1" {{ old("travel_dates.{$di}.is_active", $dateItem->is_active ?? true) ? 'checked' : '' }}>
                                            <label class="form-check-label small">Actif</label>
                                        </div>
                                    </div>

                                    <div class="col-12 col-md-1 text-md-end">
                                        <button type="button" class="btn btn-sm btn-outline-danger remove-travel-date" aria-label="Supprimer cette date">x</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="alert alert-warning mb-0">
                            Aucune date configuree pour le moment. Ajoutez un depart pour ouvrir la vente.
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="ve-settings-grid mb-4">
                <div class="ve-settings-block">
                    <h5 class="ve-subsection-title">Reservation</h5>

                    <div class="mb-3">
                        <label for="tours_booking_period" class="form-label">Periode de reservation</label>
                        <input type="text" class="form-control" id="tours_booking_period" name="tours_booking_period" value="{{ old('tours_booking_period', $meta['tours_booking_period'] ?? '') }}">
                    </div>

                    <div class="mb-0">
                        <label for="st_booking_option_type" class="form-label">Type d'option de reservation</label>
                        <input type="text" class="form-control" id="st_booking_option_type" name="st_booking_option_type" value="{{ old('st_booking_option_type', $meta['st_booking_option_type'] ?? '') }}">
                    </div>
                </div>

                <div class="ve-settings-block">
                    <h5 class="ve-subsection-title">Horaires</h5>

                    <div class="row">
                        <div class="col-lg-6">
                            <div class="mb-3 mb-lg-0">
                                <label for="check_in" class="form-label">Check-in</label>
                                <input type="time" class="form-control" id="check_in" name="check_in" value="{{ old('check_in', $meta['check_in'] ?? '') }}">
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="mb-0">
                                <label for="check_out" class="form-label">Check-out</label>
                                <input type="time" class="form-control" id="check_out" name="check_out" value="{{ old('check_out', $meta['check_out'] ?? '') }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="ve-settings-grid mb-4">
                <div class="ve-settings-block">
                    <h5 class="ve-subsection-title">Annulation</h5>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="st_allow_cancel" name="st_allow_cancel" value="1" {{ old('st_allow_cancel', $meta['st_allow_cancel'] ?? '') === 'on' ? 'checked' : '' }}>
                        <label class="form-check-label" for="st_allow_cancel">Autoriser l'annulation</label>
                    </div>

                    <div class="row">
                        <div class="col-lg-6">
                            <div class="mb-3 mb-lg-0">
                                <label for="st_cancel_percent" class="form-label">% de remboursement</label>
                                <input type="number" class="form-control" id="st_cancel_percent" name="st_cancel_percent" value="{{ old('st_cancel_percent', $meta['st_cancel_percent'] ?? '') }}" min="0" max="100">
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="mb-0">
                                <label for="st_cancel_number_day" class="form-label">Jours avant depart</label>
                                <input type="number" class="form-control" id="st_cancel_number_day" name="st_cancel_number_day" value="{{ old('st_cancel_number_day', $meta['st_cancel_number_day'] ?? '') }}" min="0">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ve-settings-block">
                    <h5 class="ve-subsection-title">Synchronisation calendrier</h5>

                    <div class="mb-0">
                        <label for="ical_url" class="form-label">URL iCal</label>
                        <input type="text" class="form-control" id="ical_url" name="ical_url" value="{{ old('ical_url', $meta['ical_url'] ?? '') }}" placeholder="https://...">
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
