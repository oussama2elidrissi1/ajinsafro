<div class="tab-pane" id="availability" role="tabpanel">
                <div class="card ve-pane-card">
                    <div class="card-body">
                        <h4 class="card-title mb-2">DisponibilitÃ© & RÃ©servation</h4>
                        <p class="text-muted small mb-3">Dates de dÃ©part, rÃ©servation et annulation. Les dates sont enregistrÃ©es avec le tour.</p>
                        <div class="alert alert-info border-0 small mb-4 py-3"><i class="bx bx-calendar-check me-1"></i> <strong>DÃ©parts</strong> : une ligne par date â€” bouton <strong>+ Ajouter une date</strong>, <strong>Ã—</strong> pour supprimer. Places = capacitÃ© hÃ´tels (lecture seule).</div>

                        @php
                            $departureRows = ($laravelVoyage ?? null)
                                ? ($laravelVoyage->departures()->orderBy('start_date')->get())
                                : collect();
                        @endphp
                        <h5 class="mb-3"><i class="bx bx-calendar"></i> Départs (nouvelle architecture)</h5>
                        @if(isset($laravelVoyage) && $laravelVoyage)
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#voyageRoomAvailabilityModal">
                                    <i class="bx bx-bed"></i> Gérer le stock chambres
                                </button>
                                <span class="text-muted small">Par date de départ : hôtels et disponibilités dans une fenêtre dédiée.</span>
                            </div>
                        @endif
                        <div class="alert alert-light border small mb-4 py-3">
                            <strong class="d-block mb-1">Une seule source pour les dates</strong>
                            Les dates de départ se gèrent dans la section <strong>« Dates disponibles (Travelling on) »</strong> ci-dessous (table WordPress <code>aj_travel_dates</code>).
                            La synchronisation vers les <strong>départs Laravel</strong> est automatique (onglet disponibilité, modal stock, page détail départ) avec déduplication.
                            Si une date WP est supprimée/inactive, le départ Laravel est archivé (statut fermé) au lieu d’être supprimé pour préserver les réservations.
                            @if(isset($laravelVoyage) && $laravelVoyage)
                                @php $firstDepartureRow = $departureRows->first(); @endphp
                                <span class="d-block mt-2">
                                    Départs Laravel synchronisés : <strong>{{ $departureRows->count() }}</strong>
                                    @if($firstDepartureRow)
                                        · <a href="{{ route('admin.circuits.voyages.departures.show', [$laravelVoyage, $firstDepartureRow]) }}" target="_blank" rel="noopener">Ouvrir un départ (exemple)</a>
                                    @endif
                                </span>
                            @endif
                        </div>
                        
                        <div class="mb-3">
                            <label for="tours_booking_period" class="form-label">PÃ©riode de rÃ©servation</label>
                            <input type="text" class="form-control" id="tours_booking_period" name="tours_booking_period" value="{{ old('tours_booking_period', $meta['tours_booking_period'] ?? '') }}">
                        </div>
                        
                        <div class="mb-3">
                            <label for="st_booking_option_type" class="form-label">Type d'option de rÃ©servation</label>
                            <input type="text" class="form-control" id="st_booking_option_type" name="st_booking_option_type" value="{{ old('st_booking_option_type', $meta['st_booking_option_type'] ?? '') }}">
                        </div>
                        
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label for="check_in" class="form-label">Check-in (heure)</label>
                                    <input type="time" class="form-control" id="check_in" name="check_in" value="{{ old('check_in', $meta['check_in'] ?? '') }}">
                                </div>
                            </div>
                            
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label for="check_out" class="form-label">Check-out (heure)</label>
                                    <input type="time" class="form-control" id="check_out" name="check_out" value="{{ old('check_out', $meta['check_out'] ?? '') }}">
                                </div>
                            </div>
                        </div>
                        
                        <h5 class="mb-3 mt-4">Politique d'annulation</h5>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="st_allow_cancel" name="st_allow_cancel" value="1" {{ old('st_allow_cancel', $meta['st_allow_cancel'] ?? '') === 'on' ? 'checked' : '' }}>
                            <label class="form-check-label" for="st_allow_cancel">
                                Autoriser l'annulation
                            </label>
                        </div>
                        
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label for="st_cancel_percent" class="form-label">% de remboursement</label>
                                    <input type="number" class="form-control" id="st_cancel_percent" name="st_cancel_percent" value="{{ old('st_cancel_percent', $meta['st_cancel_percent'] ?? '') }}" min="0" max="100">
                                </div>
                            </div>
                            
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label for="st_cancel_number_day" class="form-label">Nombre de jours avant dÃ©part</label>
                                    <input type="number" class="form-control" id="st_cancel_number_day" name="st_cancel_number_day" value="{{ old('st_cancel_number_day', $meta['st_cancel_number_day'] ?? '') }}" min="0">
                                </div>
                            </div>
                        </div>
                        
                        <h5 class="mb-3 mt-4">iCal Sync</h5>
                        
                        <div class="mb-3">
                            <label for="ical_url" class="form-label">URL calendrier iCal</label>
                            <input type="text" class="form-control" id="ical_url" name="ical_url" value="{{ old('ical_url', $meta['ical_url'] ?? '') }}" placeholder="https://...">
                        </div>

                        <h5 class="mb-3 mt-4"><i class="bx bx-calendar-check"></i> Dates disponibles (Travelling on)</h5>
                        <p class="alert alert-info py-2 mb-3 small">
                            <i class="bx bx-info-circle"></i> <strong>Configuration des dates</strong> â€”
                            Ajoutez les dates disponibles pour ce voyage. Seules ces dates seront sÃ©lectionnables dans le calendrier sur la page du tour.
                            Si aucune date n'est configurÃ©e, un message "No dates available" sera affichÃ©.
                        </p>
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
                                            <label class="form-label small mb-1">Places <span class="text-muted fw-normal">(auto)</span></label>
                                            <input type="number" class="form-control form-control-sm bg-light" name="travel_dates[{{ $di }}][seats]" value="{{ old("travel_dates.{$di}.seats", $dateItem->seats ?? 0) }}" readonly title="RecalculÃ© Ã  l'enregistrement (hÃ´tels)">
                                        </div>
                                        <div class="col-6 col-md-2">
                                            <label class="form-label small mb-1">Prix spÃ©cifique</label>
                                            <input type="number" step="0.01" class="form-control form-control-sm" name="travel_dates[{{ $di }}][price_override]" value="{{ old("travel_dates.{$di}.price_override", $dateItem->price_override ?? '') }}" placeholder="â€”">
                                        </div>
                                        <div class="col-6 col-md-2">
                                            <div class="form-check mb-0 pb-1">
                                                <input type="checkbox" class="form-check-input" name="travel_dates[{{ $di }}][is_active]" value="1" {{ old("travel_dates.{$di}.is_active", $dateItem->is_active ?? true) ? 'checked' : '' }}>
                                                <label class="form-check-label small">Actif</label>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-1 text-md-end">
                                            <button type="button" class="btn btn-sm btn-outline-danger remove-travel-date" aria-label="Supprimer cette date">Ã—</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @empty
                            <div class="alert alert-warning">
                                Aucune date disponible configurÃ©e. Cliquez sur "Ajouter une date" pour commencer.
                            </div>
                            @endforelse
                        </div>
                        <button type="button" class="btn btn-primary btn-sm mb-4" id="add-travel-date">
                            <i class="bx bx-plus"></i> Ajouter une date
                        </button>
</div>
                </div>
            </div>


