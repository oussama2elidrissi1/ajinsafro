{{-- Etape 3 : dates de depart multiples. --}}
@php
    $departureRows = old('departures', $package->departures->map(fn ($d) => [
        'id' => $d->id,
        'departure_date' => optional($d->departure_date)->format('Y-m-d'),
        'return_date' => optional($d->return_date)->format('Y-m-d'),
        'departure_city' => $d->departure_city,
        'status' => $d->status,
        'available_places' => $d->available_places,
        'reserved_places' => $d->reserved_places,
        'price_from' => $d->price_from,
        'internal_notes' => $d->internal_notes,
    ])->values()->all());
@endphp

<div class="ho-panel" data-panel="departs">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h6 class="text-uppercase text-muted small mb-1">Départs</h6>
            <p class="text-muted small mb-0">Une offre peut proposer plusieurs dates. Le prochain départ à venir alimente le site public.</p>
        </div>
        <button type="button" class="btn btn-sm btn-outline-primary" data-repeat-add="departure">+ Ajouter un départ</button>
    </div>

    <div data-repeat-list="departure">
        @forelse ($departureRows as $i => $row)
            @php
                $available = (int) ($row['available_places'] ?? 0);
                $reserved = (int) ($row['reserved_places'] ?? 0);
                $ratio = $available > 0 ? min(100, round($reserved / $available * 100)) : 0;
            @endphp
            <div class="ho-row" data-repeat-item>
                <input type="hidden" name="departures[{{ $i }}][id]" value="{{ $row['id'] ?? '' }}">
<input type="hidden" name="departures[{{ $i }}][client_key]" value="{{ $row['client_key'] ?? '' }}">
                <div class="row g-2 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label small text-muted">Date de départ</label>
                        <input type="date" name="departures[{{ $i }}][departure_date]" class="form-control form-control-sm" value="{{ $row['departure_date'] ?? '' }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted">Date de retour</label>
                        <input type="date" name="departures[{{ $i }}][return_date]" class="form-control form-control-sm" value="{{ $row['return_date'] ?? '' }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted">Ville de départ</label>
                        <input type="text" name="departures[{{ $i }}][departure_city]" class="form-control form-control-sm" value="{{ $row['departure_city'] ?? '' }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted">Statut</label>
                        <select name="departures[{{ $i }}][status]" class="form-select form-select-sm">
                            @foreach ($departureStatusOptions as $key => $label)
                                <option value="{{ $key }}" @selected(($row['status'] ?? '') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted">Prix à partir de</label>
                        <input type="number" step="0.01" min="0" name="departures[{{ $i }}][price_from]" class="form-control form-control-sm" value="{{ $row['price_from'] ?? '' }}">
                    </div>
                    <div class="col-md-2 text-end">
                        <button type="button" class="btn btn-sm btn-outline-danger" data-repeat-remove>Retirer</button>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small text-muted">Places disponibles</label>
                        <input type="number" min="0" name="departures[{{ $i }}][available_places]" class="form-control form-control-sm" value="{{ $available }}" data-role="dep-available">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted">Places réservées</label>
                        <input type="number" min="0" name="departures[{{ $i }}][reserved_places]" class="form-control form-control-sm" value="{{ $reserved }}" data-role="dep-reserved">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Occupation</label>
                        <div class="d-flex align-items-center gap-2">
                            <div class="progress flex-grow-1" style="height:6px;">
                                <div class="progress-bar" role="progressbar" style="width: {{ $ratio }}%" data-role="dep-bar"></div>
                            </div>
                            <span class="small text-muted text-nowrap" data-role="dep-count">{{ $reserved }} / {{ $available }}</span>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small text-muted">Notes internes</label>
                        <input type="text" name="departures[{{ $i }}][internal_notes]" class="form-control form-control-sm" value="{{ $row['internal_notes'] ?? '' }}">
                    </div>
                </div>
            </div>
        @empty
            <p class="text-muted small" data-repeat-empty>Aucun départ programmé pour le moment.</p>
        @endforelse
    </div>

    @foreach ($errors->get('departures.*') as $messages)
        @foreach ($messages as $message)<div class="text-danger small mt-1">{{ $message }}</div>@endforeach
    @endforeach

    <template data-repeat-template="departure">
        <div class="ho-row" data-repeat-item>
            <input type="hidden" name="departures[__INDEX__][id]" value="">
<input type="hidden" name="departures[__INDEX__][client_key]" value="">
            <div class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small text-muted">Date de départ</label>
                    <input type="date" name="departures[__INDEX__][departure_date]" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Date de retour</label>
                    <input type="date" name="departures[__INDEX__][return_date]" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Ville de départ</label>
                    <input type="text" name="departures[__INDEX__][departure_city]" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Statut</label>
                    <select name="departures[__INDEX__][status]" class="form-select form-select-sm">
                        @foreach ($departureStatusOptions as $key => $label)
                            <option value="{{ $key }}" @selected($key === 'published')>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Prix à partir de</label>
                    <input type="number" step="0.01" min="0" name="departures[__INDEX__][price_from]" class="form-control form-control-sm">
                </div>
                <div class="col-md-2 text-end">
                    <button type="button" class="btn btn-sm btn-outline-danger" data-repeat-remove>Retirer</button>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Places disponibles</label>
                    <input type="number" min="0" name="departures[__INDEX__][available_places]" class="form-control form-control-sm" value="0" data-role="dep-available">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Places réservées</label>
                    <input type="number" min="0" name="departures[__INDEX__][reserved_places]" class="form-control form-control-sm" value="0" data-role="dep-reserved">
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted">Occupation</label>
                    <div class="d-flex align-items-center gap-2">
                        <div class="progress flex-grow-1" style="height:6px;">
                            <div class="progress-bar" role="progressbar" style="width:0%" data-role="dep-bar"></div>
                        </div>
                        <span class="small text-muted text-nowrap" data-role="dep-count">0 / 0</span>
                    </div>
                </div>
                <div class="col-md-5">
                    <label class="form-label small text-muted">Notes internes</label>
                    <input type="text" name="departures[__INDEX__][internal_notes]" class="form-control form-control-sm">
                </div>
            </div>
        </div>
    </template>
</div>
