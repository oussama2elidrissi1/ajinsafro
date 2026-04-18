@php
    $day = $entry['day'];
    $activities = $entry['activities'];
    $collapseId = 'collapse-day-' . $day->id . '-i' . $dayIndex;
    $isFirst = ($dayIndex === 0);
    $dayTitleDisplay = $day->day_title ?? $day->title ?? ('Jour ' . $day->day_number);
    $dayHotelsTransfers = ($programDayHotelsTransfers ?? [])[$dayIndex] ?? [];
@endphp

<div class="accordion-item programme-day-card" data-day-id="{{ $day->id }}" data-day-index="{{ $dayIndex }}">
    <h2 class="accordion-header programme-day-header">
        <span class="drag-handle me-2 text-muted cursor-grab" title="Déplacer" aria-hidden="true"><i class="bx bx-dots-vertical-rounded"></i></span>
        <button class="accordion-button flex-grow-1 {{ $isFirst ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}" aria-expanded="{{ $isFirst ? 'true' : 'false' }}" aria-controls="{{ $collapseId }}">
            <span class="programme-day-label">JOUR {{ $day->day_number }} — {{ $dayTitleDisplay }}</span>
        </button>
        <button type="button" class="btn btn-sm btn-outline-danger me-2 btn-remove-program-day" title="Supprimer ce jour" data-day-id="{{ $day->id }}">
            <i class="bx bx-trash"></i>
        </button>
    </h2>
    <div id="{{ $collapseId }}" class="accordion-collapse collapse {{ $isFirst ? 'show' : '' }}" data-bs-parent="#accordionProgrammeDays">
        <div class="accordion-body" data-day-index="{{ $dayIndex }}" data-day-id="{{ $day->id }}">
            <input type="hidden" name="programme_days[{{ $dayIndex }}][id]" value="{{ $day->id }}">
            <input type="hidden" name="programme_days[{{ $dayIndex }}][day_id]" value="{{ $day->id }}">

            <div class="program-day-form" data-day-index="{{ $dayIndex }}">
                <div class="field-mode">
                    <label class="form-label">Type / mode</label>
                    <select name="programme_days[{{ $dayIndex }}][mode]" class="form-select programme-day-mode">
                        <option value="program" {{ ($day->mode ?? 'program') === 'program' ? 'selected' : '' }}>Visite / programme</option>
                        <option value="free" {{ ($day->mode ?? '') === 'free' ? 'selected' : '' }}>Libre</option>
                    </select>
                </div>
                <div class="field-type">
                    <label class="form-label">Type de jour</label>
                    @php
                        $dayTypeRef = \App\Services\BusinessReferentialService::programDayTypes();
                        $rawDayType = old('programme_days.'.$dayIndex.'.day_type', $day->day_type ?? 'visite');
                        $selDayType = \App\Models\TravelProgramDay::normalizeDayType($rawDayType);
                    @endphp
                    <select name="programme_days[{{ $dayIndex }}][day_type]" class="form-select">
                        @foreach($dayTypeRef as $opt)
                            <option value="{{ $opt['value'] }}" @selected($selDayType === $opt['value'])>{{ $opt['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field-title">
                    <label class="form-label">Titre du jour</label>
                    <input type="text" class="form-control" name="programme_days[{{ $dayIndex }}][day_title]" value="{{ old('programme_days.'.$dayIndex.'.day_title', $day->day_title ?? $day->title) }}" placeholder="Ex. : Jour 1 — Arrivée">
                </div>
                <div class="field-ville">
                    <label class="form-label">Ville</label>
                    <input type="text" class="form-control" name="programme_days[{{ $dayIndex }}][city]" value="{{ old('programme_days.'.$dayIndex.'.city', $day->city ?? '') }}" placeholder="Ex. : Marrakech">
                </div>
                <div class="field-resume ve-rich-field">
                    <label class="form-label">Résumé</label>
                    <textarea class="form-control programme-plain-editor" name="programme_days[{{ $dayIndex }}][description]" rows="3" placeholder="Résumé du jour">{{ old('programme_days.'.$dayIndex.'.description', $day->description ?? '') }}</textarea>
                </div>
                <div class="field-description programme-day-detail ve-rich-field">
                    <label class="form-label">Notes</label>
                    <textarea class="form-control programme-plain-editor" name="programme_days[{{ $dayIndex }}][content_html]" rows="5" placeholder="Notes du jour">{{ old('programme_days.'.$dayIndex.'.content_html', $day->content_html ?? '') }}</textarea>
                </div>
                <div class="field-notes programme-day-notes ve-rich-field">
                    <label class="form-label">Description détaillée</label>
                    <textarea class="form-control programme-plain-editor" name="programme_days[{{ $dayIndex }}][notes]" rows="4" placeholder="Programme détaillé du jour">{{ old('programme_days.'.$dayIndex.'.notes', $day->notes ?? $day->description) }}</textarea>
                </div>
            </div>

            <input type="hidden" name="programme_days[{{ $dayIndex }}][title]" value="{{ old('programme_days.'.$dayIndex.'.title', $day->title ?? ($day->day_title ?? '')) }}">
            <input type="hidden" name="programme_days[{{ $dayIndex }}][flights]" value="{{ old('programme_days.'.$dayIndex.'.flights', '') }}">
            <input type="hidden" name="programme_days[{{ $dayIndex }}][hotel_id]" value="{{ old('programme_days.'.$dayIndex.'.hotel_id', $dayHotelsTransfers['hotel_id'] ?? '') }}">
            <input type="hidden" name="programme_days[{{ $dayIndex }}][transfer_ids]" value="{{ old('programme_days.'.$dayIndex.'.transfer_ids', implode(',', $dayHotelsTransfers['transfer_ids'] ?? [])) }}">

            <h6 class="mt-4 mb-2">Activités du jour</h6>
            <div class="programme-activities-list mb-3" data-day-index="{{ $dayIndex }}" data-day-id="{{ $day->id }}">
                @foreach($activities as $actIndex => $da)
                    <div class="programme-activity-row card mb-2" data-day-activity-id="{{ $da->id }}" draggable="true">
                        <div class="card-body py-2">
                            <div class="d-flex flex-wrap align-items-start gap-2">
                                <span class="programme-activity-drag-handle text-muted cursor-grab me-1" title="Réordonner"><i class="bx bx-dots-vertical-rounded"></i></span>
                                <input type="hidden" name="programme_days[{{ $dayIndex }}][activities][{{ $actIndex }}][day_activity_id]" value="{{ $da->id }}">
                                <input type="hidden" name="programme_days[{{ $dayIndex }}][activities][{{ $actIndex }}][activity_id]" value="{{ $da->activity_id }}">
                                <input type="hidden" name="programme_days[{{ $dayIndex }}][activities][{{ $actIndex }}][sort_order]" value="{{ $actIndex }}">
                                <span class="fw-medium">{{ $da->activity->title ?? 'Activité #'.$da->activity_id }}</span>
                                <span class="form-check form-check-inline mb-0">
                                    <input type="hidden" name="programme_days[{{ $dayIndex }}][activities][{{ $actIndex }}][is_included]" value="0">
                                    <input class="form-check-input" type="checkbox" name="programme_days[{{ $dayIndex }}][activities][{{ $actIndex }}][is_included]" value="1" {{ $da->is_included ? 'checked' : '' }}>
                                    <label class="form-check-label small">Inclus</label>
                                </span>
                                <span class="form-check form-check-inline mb-0">
                                    <input type="hidden" name="programme_days[{{ $dayIndex }}][activities][{{ $actIndex }}][is_mandatory]" value="0">
                                    <input class="form-check-input" type="checkbox" name="programme_days[{{ $dayIndex }}][activities][{{ $actIndex }}][is_mandatory]" value="1" {{ $da->is_mandatory ? 'checked' : '' }} {{ $da->is_mandatory ? 'readonly' : '' }}>
                                    <label class="form-check-label small">Obligatoire</label>
                                </span>
                                @if($da->is_editable)
                                    <input type="text" class="form-control form-control-sm d-inline-block" style="max-width:220px" name="programme_days[{{ $dayIndex }}][activities][{{ $actIndex }}][custom_title]" value="{{ $da->custom_title }}" placeholder="Titre personnalisé">
                                    <textarea class="form-control form-control-sm programme-plain-editor" name="programme_days[{{ $dayIndex }}][activities][{{ $actIndex }}][custom_description]" rows="2" placeholder="Description personnalisée">{{ $da->custom_description }}</textarea>
                                @else
                                    <input type="hidden" name="programme_days[{{ $dayIndex }}][activities][{{ $actIndex }}][custom_title]" value="{{ $da->custom_title }}">
                                    <input type="hidden" name="programme_days[{{ $dayIndex }}][activities][{{ $actIndex }}][custom_description]" value="{{ $da->custom_description }}">
                                @endif
                                @if(!$da->is_mandatory)
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-programme-activity"><i class="bx bx-trash"></i></button>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

        </div>
    </div>
</div>
