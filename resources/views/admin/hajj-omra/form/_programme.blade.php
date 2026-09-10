{{-- Etape 5 : programme jour par jour, en accordeon, reordonnable. --}}
@php
    $dayRows = old('program_days', $package->programDays->map(fn ($d) => [
        'id' => $d->id,
        'day_number' => $d->day_number,
        'title' => $d->title,
        'title_ar' => $d->title_ar,
        'city' => $d->city,
        'description' => $d->description,
        'description_ar' => $d->description_ar,
        'image_path' => $d->image_path,
    ])->values()->all());
@endphp

<div class="ho-panel" data-panel="programme">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h6 class="text-uppercase text-muted small mb-1">Programme du voyage</h6>
            <p class="text-muted small mb-0">
                Les jours sont générés automatiquement selon la durée de l'offre.
                Glissez un jour pour le déplacer ; la renumérotation est automatique.
            </p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" data-role="generate-days">
                Générer les jours manquants
            </button>
            <button type="button" class="btn btn-sm btn-outline-primary" data-repeat-add="day">+ Ajouter un jour</button>
        </div>
    </div>

    @if ($package->exists)
        <p class="small text-muted">
            Vous pouvez aussi générer les jours côté serveur sans perdre votre saisie :
            enregistrez d'abord, puis utilisez le bouton ci-dessus.
        </p>
    @endif

    <div class="accordion" id="hoProgramAccordion" data-repeat-list="day" data-sortable="day">
        @forelse ($dayRows as $i => $row)
            @php $collapseId = 'hoDay'.$i; @endphp
            <div class="accordion-item ho-repeat-item mb-2" data-repeat-item>
                <input type="hidden" name="program_days[{{ $i }}][id]" value="{{ $row['id'] ?? '' }}">
<input type="hidden" name="program_days[{{ $i }}][client_key]" value="{{ $row['client_key'] ?? '' }}">
                <input type="hidden" name="program_days[{{ $i }}][day_number]" value="{{ $row['day_number'] ?? ($i + 1) }}" data-role="day-number">

                <h2 class="accordion-header d-flex align-items-center">
                    <span class="ho-handle px-2" title="Déplacer">⠿</span>
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}">
                        <span class="me-2 fw-semibold" data-role="day-label">Jour {{ str_pad((string) ($row['day_number'] ?? ($i + 1)), 2, '0', STR_PAD_LEFT) }}</span>
                        <span class="text-muted small" data-role="day-summary">
                            {{ $row['city'] ?? '' }}{{ ($row['city'] ?? '') && ($row['title'] ?? '') ? ' — ' : '' }}{{ $row['title'] ?? '' }}
                        </span>
                    </button>
                </h2>

                <div id="{{ $collapseId }}" class="accordion-collapse collapse" data-bs-parent="#hoProgramAccordion">
                    <div class="accordion-body">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <div data-lang-pane="fr">
                                    <label class="form-label small text-muted">Titre du jour <span class="ho-lang-badge">FR</span></label>
                                    <input type="text" name="program_days[{{ $i }}][title]" class="form-control form-control-sm"
                                           value="{{ $row['title'] ?? '' }}" data-role="day-title">
                                </div>
                                <div data-lang-pane="ar">
                                    <label class="form-label small text-muted">عنوان اليوم <span class="ho-lang-badge">AR</span></label>
                                    <input type="text" name="program_days[{{ $i }}][title_ar]" class="form-control form-control-sm"
                                           dir="rtl" lang="ar" value="{{ $row['title_ar'] ?? '' }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small text-muted">Ville</label>
                                <input type="text" name="program_days[{{ $i }}][city]" class="form-control form-control-sm"
                                       value="{{ $row['city'] ?? '' }}" data-role="day-city">
                            </div>

                            <div class="col-md-8">
                                <div data-lang-pane="fr">
                                    <label class="form-label small text-muted">Description <span class="ho-lang-badge">FR</span></label>
                                    <textarea name="program_days[{{ $i }}][description]" rows="4" class="form-control form-control-sm">{{ $row['description'] ?? '' }}</textarea>
                                </div>
                                <div data-lang-pane="ar">
                                    <label class="form-label small text-muted">الوصف <span class="ho-lang-badge">AR</span></label>
                                    <textarea name="program_days[{{ $i }}][description_ar]" rows="4" dir="rtl" lang="ar" class="form-control form-control-sm">{{ $row['description_ar'] ?? '' }}</textarea>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small text-muted">Image du jour</label>
                                @include('admin.hajj-omra.form._image-picker', [
                                    'inputName' => "program_days[{$i}][image_path]",
                                    'value' => $row['image_path'] ?? null,
                                    'context' => 'hajj-omra-program',
                                ])
                                <button type="button" class="btn btn-sm btn-outline-danger mt-3" data-repeat-remove>Supprimer ce jour</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <p class="text-muted small" data-repeat-empty>
                Aucun jour pour le moment. Renseignez la durée à l'étape 1 puis cliquez sur « Générer les jours manquants ».
            </p>
        @endforelse
    </div>

    <template data-repeat-template="day">
        <div class="accordion-item ho-repeat-item mb-2" data-repeat-item>
            <input type="hidden" name="program_days[__INDEX__][id]" value="">
<input type="hidden" name="program_days[__INDEX__][client_key]" value="">
            <input type="hidden" name="program_days[__INDEX__][day_number]" value="" data-role="day-number">
            <h2 class="accordion-header d-flex align-items-center">
                <span class="ho-handle px-2" title="Déplacer">⠿</span>
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#hoDay__INDEX__">
                    <span class="me-2 fw-semibold" data-role="day-label">Jour</span>
                    <span class="text-muted small" data-role="day-summary"></span>
                </button>
            </h2>
            <div id="hoDay__INDEX__" class="accordion-collapse collapse" data-bs-parent="#hoProgramAccordion">
                <div class="accordion-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <div data-lang-pane="fr">
                                <label class="form-label small text-muted">Titre du jour <span class="ho-lang-badge">FR</span></label>
                                <input type="text" name="program_days[__INDEX__][title]" class="form-control form-control-sm" data-role="day-title">
                            </div>
                            <div data-lang-pane="ar">
                                <label class="form-label small text-muted">عنوان اليوم <span class="ho-lang-badge">AR</span></label>
                                <input type="text" name="program_days[__INDEX__][title_ar]" class="form-control form-control-sm" dir="rtl" lang="ar">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-muted">Ville</label>
                            <input type="text" name="program_days[__INDEX__][city]" class="form-control form-control-sm" data-role="day-city">
                        </div>
                        <div class="col-md-8">
                            <div data-lang-pane="fr">
                                <label class="form-label small text-muted">Description <span class="ho-lang-badge">FR</span></label>
                                <textarea name="program_days[__INDEX__][description]" rows="4" class="form-control form-control-sm"></textarea>
                            </div>
                            <div data-lang-pane="ar">
                                <label class="form-label small text-muted">الوصف <span class="ho-lang-badge">AR</span></label>
                                <textarea name="program_days[__INDEX__][description_ar]" rows="4" dir="rtl" lang="ar" class="form-control form-control-sm"></textarea>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-muted">Image du jour</label>
                            @include('admin.hajj-omra.form._image-picker', [
                                'inputName' => 'program_days[__INDEX__][image_path]',
                                'value' => null,
                                'context' => 'hajj-omra-program',
                            ])
                            <button type="button" class="btn btn-sm btn-outline-danger mt-3" data-repeat-remove>Supprimer ce jour</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
