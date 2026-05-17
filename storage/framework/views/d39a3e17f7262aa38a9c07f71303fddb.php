<?php
    $voyageActivityPricingTypes = \App\Services\BusinessReferentialService::voyageActivityPricingTypes();
    $activityDayOptions = collect($programDays ?? [])->map(function ($entry, $index) {
        $day = is_array($entry) ? ($entry['day'] ?? null) : ($entry->day ?? null);
        $dayNumber = (int) (data_get($day, 'day_number') ?? ($index + 1));
        if ($dayNumber < 1) {
            $dayNumber = $index + 1;
        }
        $rawTitle = trim((string) (data_get($day, 'day_title') ?? data_get($day, 'title') ?? ''));
        return [
            'number' => $dayNumber,
            'label' => 'Jour ' . $dayNumber . ($rawTitle !== '' ? (' - ' . $rawTitle) : ''),
        ];
    })->filter(fn ($row) => !empty($row['number']))
        ->unique('number')
        ->sortBy('number')
        ->values();

    if ($activityDayOptions->isEmpty()) {
        $activityDayOptions = collect([['number' => 1, 'label' => 'Jour 1']]);
    }
?>
<div class="tab-pane" id="activities" role="tabpanel" data-ve-pane-title="Activites">
    <div class="card ve-pane-card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <h4 class="card-title mb-0">Activites</h4>
                <button type="button" class="btn btn-primary" id="btn-open-activities-modal" data-bs-toggle="modal" data-bs-target="#activitiesCatalogModal">
                    <i class="bx bx-plus me-1"></i> Ajouter une activite
                </button>
            </div>

            <div class="table-responsive" style="overflow-x:auto;">
                <table class="table table-bordered align-middle mb-0" style="min-width:1760px;">
                    <thead class="table-light">
                        <tr>
                            <th style="width:70px;">Ordre</th>
                            <th style="min-width:220px;">Activite</th>
                            <th style="min-width:340px;">Jour / visibilite</th>
                            <th style="min-width:190px;">Horaires</th>
                            <th style="min-width:150px;">Statut</th>
                            <th style="min-width:220px;">Titre affiche</th>
                            <th style="min-width:260px;">Description</th>
                            <th style="min-width:130px;">Type</th>
                            <th style="min-width:120px;">Prix adulte</th>
                            <th style="min-width:120px;">Prix enfant</th>
                            <th style="min-width:120px;">Total ligne</th>
                            <th style="width:110px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="voyage-activities-rows" data-day-options='<?php echo json_encode($activityDayOptions->values()->all(), 15, 512) ?>'>
                        <?php $__empty_1 = true; $__currentLoopData = ($tourActivities ?? collect()); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $tourActivity): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <?php
                                $opts = is_array($tourActivity->options_json ?? null) ? $tourActivity->options_json : [];
                                $activityId = (int) ($opts['activity_id'] ?? 0);
                                $pricingType = in_array(($opts['pricing_type'] ?? 'per_person'), ['per_person', 'fixed'], true) ? ($opts['pricing_type'] ?? 'per_person') : 'per_person';
                                $unitPrice = (float) ($opts['unit_price'] ?? ($pricingType === 'per_person' ? ((int) ($tourActivity->price_delta_per_person ?? 0) / 100) : 0));
                                $childPrice = (float) ($opts['child_price'] ?? 0);
                                $description = (string) ($tourActivity->details ?? ($opts['description'] ?? ''));
                                $displayTitle = (string) old('tour_activities.'.$idx.'.title', $tourActivity->title ?? ($opts['title'] ?? ''));
                                $metaJson = is_array($tourActivity->meta_json ?? null) ? $tourActivity->meta_json : [];
                                $groupUuid = (string) old('tour_activities.'.$idx.'.group_uuid', $metaJson['group_uuid'] ?? '');
                                $isIncluded = (int) old('tour_activities.'.$idx.'.included', (int) ($tourActivity->included ?? 1)) === 1;
                                $dayScope = (string) old('tour_activities.'.$idx.'.day_scope', (string) ($opts['day_scope'] ?? 'fixed'));
                                if (!in_array($dayScope, ['fixed', 'open'], true)) {
                                    $dayScope = 'fixed';
                                }
                                $selectedDay = (int) old('tour_activities.'.$idx.'.day_number', $tourActivity->day_number ?? ($opts['day_number'] ?? 1));
                                if ($selectedDay < 1) {
                                    $selectedDay = (int) ($activityDayOptions->first()['number'] ?? 1);
                                }
                                $visibilityMode = (string) old('tour_activities.'.$idx.'.visibility_mode', (string) ($opts['visibility_mode'] ?? ($dayScope === 'open' ? 'all_days' : 'single_day')));
                                if (!in_array($visibilityMode, ['single_day', 'multiple_days', 'all_days'], true)) {
                                    $visibilityMode = $dayScope === 'open' ? 'all_days' : 'single_day';
                                }
                                $rawDays = old('tour_activities.'.$idx.'.days', $opts['days'] ?? [$selectedDay]);
                                if (is_string($rawDays)) {
                                    $decoded = json_decode($rawDays, true);
                                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                        $rawDays = $decoded;
                                    } else {
                                        $rawDays = array_filter(array_map('trim', explode(',', $rawDays)), fn ($v) => $v !== '');
                                    }
                                }
                                if (!is_array($rawDays)) {
                                    $rawDays = [$selectedDay];
                                }
                                $selectedDays = collect($rawDays)->map(fn ($d) => (int) $d)->filter(fn ($d) => $d > 0)->unique()->values()->all();
                                if (empty($selectedDays)) {
                                    $selectedDays = [$selectedDay];
                                }
                                $startTime = (string) old('tour_activities.'.$idx.'.start_time', (string) ($opts['start_time'] ?? ''));
                                $endTime = (string) old('tour_activities.'.$idx.'.end_time', (string) ($opts['end_time'] ?? ''));
                            ?>
                            <tr class="voyage-activity-row <?php echo e($isIncluded ? '' : 'table-warning'); ?>" data-activity-id="<?php echo e($activityId); ?>" data-included="<?php echo e($isIncluded ? '1' : '0'); ?>">
                                <td class="text-center">
                                    <span class="badge rounded-pill bg-light text-dark voyage-activity-order"><?php echo e($idx + 1); ?></span>
                                    <input type="hidden" data-field="sort_order" name="tour_activities[<?php echo e($idx); ?>][sort_order]" value="<?php echo e(old('tour_activities.'.$idx.'.sort_order', $tourActivity->sort_order ?? $idx)); ?>">
                                </td>
                                <td>
                                    <div class="fw-medium voyage-activity-title"><?php echo e($tourActivity->title); ?></div>
                                    <input type="hidden" data-field="id" name="tour_activities[<?php echo e($idx); ?>][id]" value="<?php echo e($tourActivity->id); ?>">
                                    <input type="hidden" data-field="activity_id" name="tour_activities[<?php echo e($idx); ?>][activity_id]" value="<?php echo e($activityId); ?>">
                                    <input type="hidden" data-field="group_uuid" name="tour_activities[<?php echo e($idx); ?>][group_uuid]" value="<?php echo e($groupUuid); ?>">
                                </td>
                                <td>
                                    <select class="form-select form-select-sm voyage-activity-visibility-mode" data-field="visibility_mode" name="tour_activities[<?php echo e($idx); ?>][visibility_mode]">
                                        <option value="single_day" <?php if($visibilityMode === 'single_day'): echo 'selected'; endif; ?>>Jour unique</option>
                                        <option value="multiple_days" <?php if($visibilityMode === 'multiple_days'): echo 'selected'; endif; ?>>Plusieurs jours</option>
                                        <option value="all_days" <?php if($visibilityMode === 'all_days'): echo 'selected'; endif; ?>>Tous les jours</option>
                                    </select>
                                    <div class="mt-2 voyage-activity-single-day-wrap">
                                        <select class="form-select form-select-sm voyage-activity-day-select">
                                            <?php $__currentLoopData = $activityDayOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dayOption): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <option value="<?php echo e($dayOption['number']); ?>" <?php if(in_array((int) $dayOption['number'], $selectedDays, true)): echo 'selected'; endif; ?>><?php echo e($dayOption['label']); ?></option>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </select>
                                    </div>
                                    <div class="mt-2 voyage-activity-multi-days-wrap d-none">
                                        <div class="d-flex flex-wrap gap-2">
                                            <?php $__currentLoopData = $activityDayOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dayOption): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <label class="form-check form-check-inline mb-0 small">
                                                    <input type="checkbox" class="form-check-input voyage-activity-day-checkbox" value="<?php echo e($dayOption['number']); ?>" <?php if(in_array((int) $dayOption['number'], $selectedDays, true)): echo 'checked'; endif; ?>>
                                                    <span class="form-check-label">J<?php echo e($dayOption['number']); ?></span>
                                                </label>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </div>
                                    </div>
                                    <input type="hidden" data-field="day_number" name="tour_activities[<?php echo e($idx); ?>][day_number]" value="<?php echo e($selectedDay); ?>">
                                    <input type="hidden" data-field="days" name="tour_activities[<?php echo e($idx); ?>][days]" value="<?php echo e(implode(',', $selectedDays)); ?>">
                                    <input type="hidden" data-field="day_scope" name="tour_activities[<?php echo e($idx); ?>][day_scope]" value="<?php echo e($visibilityMode === 'all_days' ? 'open' : 'fixed'); ?>">
                                    <div class="small text-muted mt-1 voyage-activity-scope-text"></div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-1">
                                        <input type="time" class="form-control form-control-sm" data-field="start_time" name="tour_activities[<?php echo e($idx); ?>][start_time]" value="<?php echo e($startTime); ?>">
                                        <span class="small text-muted">a</span>
                                        <input type="time" class="form-control form-control-sm" data-field="end_time" name="tour_activities[<?php echo e($idx); ?>][end_time]" value="<?php echo e($endTime); ?>">
                                    </div>
                                    <div class="small text-muted mt-1">Optionnel</div>
                                </td>
                                <td>
                                    <select class="form-select form-select-sm voyage-activity-included" data-field="included" name="tour_activities[<?php echo e($idx); ?>][included]">
                                        <option value="1" <?php if($isIncluded): echo 'selected'; endif; ?>>Inclus</option>
                                        <option value="0" <?php if(!$isIncluded): echo 'selected'; endif; ?>>Option client</option>
                                    </select>
                                    <div class="small text-muted mt-1 voyage-activity-state-text"><?php echo e($isIncluded ? 'Activite incluse dans le programme.' : 'Activite proposee au client comme option.'); ?></div>
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm voyage-activity-title" data-field="title" name="tour_activities[<?php echo e($idx); ?>][title]" value="<?php echo e(old('tour_activities.'.$idx.'.title', $displayTitle)); ?>" placeholder="Titre affiche dans le voyage">
                                </td>
                                <td>
                                    <textarea class="form-control form-control-sm voyage-activity-description" data-field="description" name="tour_activities[<?php echo e($idx); ?>][description]" rows="2" placeholder="-"><?php echo e(old('tour_activities.'.$idx.'.description', $description)); ?></textarea>
                                </td>
                                <td>
                                    <select class="form-select form-select-sm voyage-activity-pricing" data-field="pricing_type" name="tour_activities[<?php echo e($idx); ?>][pricing_type]">
                                        <?php $__currentLoopData = $voyageActivityPricingTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $pt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($pt['value']); ?>" <?php if($pricingType === $pt['value']): echo 'selected'; endif; ?>><?php echo e($pt['label']); ?></option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select>
                                </td>
                                <td>
                                    <input type="number" class="form-control form-control-sm voyage-activity-price" data-field="unit_price" name="tour_activities[<?php echo e($idx); ?>][unit_price]" value="<?php echo e(number_format($unitPrice, 2, '.', '')); ?>" min="0" step="0.01">
                                </td>
                                <td>
                                    <input type="number" class="form-control form-control-sm voyage-activity-child-price" data-field="child_price" name="tour_activities[<?php echo e($idx); ?>][child_price]" value="<?php echo e(number_format($childPrice, 2, '.', '')); ?>" min="0" step="0.01">
                                </td>
                                <td>
                                    <span class="voyage-activity-line-total fw-semibold">0.00</span>
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <button type="button" class="btn btn-sm btn-outline-secondary voyage-activity-duplicate" title="Dupliquer"><i class="bx bx-copy"></i></button>
                                        <button type="button" class="btn btn-sm btn-outline-primary voyage-activity-edit"><i class="bx bx-pencil"></i></button>
                                        <button type="button" class="btn btn-sm btn-outline-danger voyage-activity-remove"><i class="bx bx-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr class="voyage-activities-empty-row">
                                <td colspan="12" class="text-center text-muted py-3">Aucune activite ajoutee pour ce voyage.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <p class="small text-muted mt-2 mb-0">
                Les activites sont classees par jour. Pour une <strong>Option client</strong>, utilisez le select <strong>Jour / visibilite</strong> : jour precis ou tous les jours du programme.
            </p>

            <div class="alert alert-info mt-3 mb-0" id="voyage-activities-empty-state" style="display:none;">
                Aucune activite ajoutee. Cliquez sur <strong>Ajouter une activite</strong> pour commencer.
            </div>
        </div>
    </div>
<div class="modal fade" id="activitiesCatalogModal" tabindex="-1" aria-labelledby="activitiesCatalogModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="activitiesCatalogModalLabel">Catalogue d’activités</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div id="activities-catalog-list-view">
                        <div class="mb-3">
                            <input type="text" class="form-control" id="activities-catalog-search" placeholder="Rechercher une activité…">
                        </div>
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                            <div class="alert alert-info py-2 small mb-0 flex-grow-1" id="activities-catalog-region-hint">
                                Le catalogue est filtré automatiquement selon la destination du voyage.
                            </div>
                            <button type="button" class="btn btn-outline-primary" id="activities-catalog-open-create">
                                <i class="bx bx-plus me-1"></i> Nouvelle activité
                            </button>
                        </div>
                        <div class="alert d-none py-2" id="activities-catalog-list-alert" role="alert"></div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Nom</th>
                                        <th>Type / région</th>
                                        <th style="width:120px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="activities-catalog-body"></tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <small class="text-muted" id="activities-catalog-count">0 résultat</small>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-secondary" id="activities-catalog-prev">Précédent</button>
                                <button type="button" class="btn btn-outline-secondary" id="activities-catalog-next">Suivant</button>
                            </div>
                        </div>
                    </div>

                    <div id="activities-catalog-form-view" class="d-none">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div>
                                <h6 class="mb-1">Créer une nouvelle activité</h6>
                                <p class="text-muted small mb-0">Création rapide sans quitter l’édition du voyage.</p>
                            </div>
                            <button type="button" class="btn btn-sm btn-light" id="activities-catalog-back-to-list">
                                <i class="bx bx-arrow-back me-1"></i> Retour à la liste
                            </button>
                        </div>

                        <div class="alert d-none" id="activities-catalog-form-alert" role="alert"></div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="activities-create-title" class="form-label">Nom de l’activité <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="activities-create-title">
                                <div class="small text-danger mt-1 d-none" data-error="title"></div>
                            </div>
                            <div class="col-md-6">
                                <label for="activities-create-type" class="form-label">Type d’activité <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="activities-create-type" placeholder="Ex. : excursion, quad">
                                <div class="small text-danger mt-1 d-none" data-error="activity_type"></div>
                            </div>
                            <div class="col-12">
                                <label for="activities-create-region" class="form-label">Région / localisation <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="activities-create-region" placeholder="Prérempli depuis la destination du voyage">
                                <div class="small text-danger mt-1 d-none" data-error="region_name"></div>
                            </div>
                            <div class="col-md-6">
                                <label for="activities-create-adult-price" class="form-label">Prix adulte <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="activities-create-adult-price" min="0" step="0.01" placeholder="0.00">
                                    <span class="input-group-text">MAD</span>
                                </div>
                                <div class="small text-danger mt-1 d-none" data-error="adult_price"></div>
                            </div>
                            <div class="col-md-6">
                                <label for="activities-create-child-price" class="form-label">Prix enfant <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="activities-create-child-price" min="0" step="0.01" placeholder="0.00">
                                    <span class="input-group-text">MAD</span>
                                </div>
                                <div class="small text-danger mt-1 d-none" data-error="child_price"></div>
                            </div>
                            <div class="col-md-4">
                                <label for="activities-create-min-age" class="form-label">Âge minimum <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="activities-create-min-age" min="0" max="120">
                                <div class="small text-danger mt-1 d-none" data-error="min_age"></div>
                            </div>
                            <div class="col-md-4">
                                <label for="activities-create-max-age" class="form-label">Âge maximum <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="activities-create-max-age" min="0" max="120">
                                <div class="small text-danger mt-1 d-none" data-error="max_age"></div>
                            </div>
                            <div class="col-md-4">
                                <label for="activities-create-duration" class="form-label">Durée <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="activities-create-duration" min="1" step="1" placeholder="60">
                                    <span class="input-group-text">min</span>
                                </div>
                                <div class="small text-danger mt-1 d-none" data-error="default_duration_minutes"></div>
                            </div>
                            <div class="col-12">
                                <label for="activities-create-gallery" class="form-label">Images multiples</label>
                                <input type="file" class="form-control" id="activities-create-gallery" accept="image/jpeg,image/png,image/webp" multiple>
                                <div class="form-text">Optionnel. Vous pourrez compléter depuis le CRUD activité.</div>
                                <div class="small text-danger mt-1 d-none" data-error="gallery_images"></div>
                                <div class="small text-danger mt-1 d-none" data-error="image"></div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <button type="button" class="btn btn-light" id="activities-catalog-form-reset">Réinitialiser</button>
                            <button type="button" class="btn btn-primary" id="activities-catalog-form-submit">
                                <span class="btn-text"><i class="bx bx-save me-1"></i> Enregistrer l’activité</span>
                                <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    if (window.__voyageActivitiesTabInit) {
        return;
    }
    window.__voyageActivitiesTabInit = true;

    var rowsRoot = document.getElementById('voyage-activities-rows');
    if (!rowsRoot) {
        return;
    }

    var openModalBtn = document.getElementById('btn-open-activities-modal');
    var modalEl = document.getElementById('activitiesCatalogModal');

    function parsePrice(value) {
        var parsed = parseFloat(String(value || '').replace(',', '.'));
        return isNaN(parsed) ? 0 : parsed;
    }

    function formatPrice(value) {
        var amount = Number(value || 0);
        return amount.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' MAD';
    }

    function rowTotalLabel(row) {
        var includedField = row.querySelector('[data-field="included"]');
        var pricingField = row.querySelector('[data-field="pricing_type"]');
        var adultField = row.querySelector('[data-field="unit_price"]');
        var childField = row.querySelector('[data-field="child_price"]');
        var included = !includedField || String(includedField.value) === '1';
        var pricingType = pricingField ? String(pricingField.value || 'per_person') : 'per_person';
        var adult = adultField ? parsePrice(adultField.value) : 0;
        var child = childField ? parsePrice(childField.value) : 0;

        if (!included) {
            return 'Option client';
        }

        if (pricingType === 'fixed' && adult > 0) {
            return formatPrice(adult);
        }

        if (adult > 0) {
            return formatPrice(adult);
        }

        if (child > 0) {
            return formatPrice(child);
        }

        return 'Inclus';
    }

    function syncScopeFields(row) {
        var includedField = row.querySelector('[data-field="included"]');
        var visibilityModeField = row.querySelector('[data-field="visibility_mode"]');
        var singleDaySelect = row.querySelector('.voyage-activity-day-select');
        var multiDaysWrap = row.querySelector('.voyage-activity-multi-days-wrap');
        var singleDayWrap = row.querySelector('.voyage-activity-single-day-wrap');
        var dayCheckboxes = Array.prototype.slice.call(row.querySelectorAll('.voyage-activity-day-checkbox'));
        var daysField = row.querySelector('[data-field="days"]');
        var dayNumberField = row.querySelector('[data-field="day_number"]');
        var dayScopeField = row.querySelector('[data-field="day_scope"]');
        var scopeText = row.querySelector('.voyage-activity-scope-text');

        if (!visibilityModeField || !dayNumberField || !dayScopeField || !daysField) {
            return;
        }

        var included = !includedField || String(includedField.value) === '1';
        var visibilityMode = String(visibilityModeField.value || 'single_day');
        if (['single_day', 'multiple_days', 'all_days'].indexOf(visibilityMode) === -1) {
            visibilityMode = 'single_day';
            visibilityModeField.value = visibilityMode;
        }

        var selectedDays = [];
        if (visibilityMode === 'all_days') {
            selectedDays = dayCheckboxes.map(function (cb) { return parseInt(cb.value || '0', 10); }).filter(function (d) { return Number.isFinite(d) && d > 0; });
            dayScopeField.value = 'open';
        } else if (visibilityMode === 'multiple_days') {
            selectedDays = dayCheckboxes
                .filter(function (cb) { return cb.checked; })
                .map(function (cb) { return parseInt(cb.value || '0', 10); })
                .filter(function (d) { return Number.isFinite(d) && d > 0; });
            if (!selectedDays.length && dayCheckboxes.length) {
                dayCheckboxes[0].checked = true;
                selectedDays = [parseInt(dayCheckboxes[0].value || '1', 10) || 1];
            }
            dayScopeField.value = 'fixed';
        } else {
            var singleDay = singleDaySelect ? parseInt(singleDaySelect.value || '1', 10) : 1;
            if (!Number.isFinite(singleDay) || singleDay <= 0) {
                singleDay = 1;
            }
            selectedDays = [singleDay];
            dayScopeField.value = 'fixed';

            if (dayCheckboxes.length) {
                dayCheckboxes.forEach(function (cb) {
                    cb.checked = parseInt(cb.value || '0', 10) === singleDay;
                });
            }
        }

        if (!selectedDays.length) {
            selectedDays = [1];
        }

        dayNumberField.value = String(selectedDays[0]);
        daysField.value = selectedDays.join(',');

        if (singleDayWrap) {
            singleDayWrap.classList.toggle('d-none', visibilityMode !== 'single_day');
        }
        if (multiDaysWrap) {
            multiDaysWrap.classList.toggle('d-none', visibilityMode === 'single_day');
        }

        if (scopeText) {
            if (visibilityMode === 'all_days') {
                scopeText.textContent = included
                    ? 'Activite incluse visible sur tous les jours du programme.'
                    : 'Option client visible sur tous les jours du programme.';
            } else if (visibilityMode === 'multiple_days') {
                scopeText.textContent = included
                    ? 'Activite incluse sur plusieurs jours selectionnes.'
                    : 'Option client visible sur plusieurs jours selectionnes.';
            } else {
                scopeText.textContent = included
                    ? 'Affichee sur un jour unique du programme.'
                    : 'Option client visible sur un jour unique.';
            }
        }

        if (!singleDaySelect && !dayCheckboxes.length) {
            return;
        }
    }

    function refreshRow(row) {
        var includedField = row.querySelector('[data-field="included"]');
        var statusText = row.querySelector('.voyage-activity-state-text');
        var totalEl = row.querySelector('.voyage-activity-line-total');
        var included = !includedField || String(includedField.value) === '1';

        row.classList.toggle('table-warning', !included);
        row.setAttribute('data-included', included ? '1' : '0');

        syncScopeFields(row);

        if (statusText) {
            statusText.textContent = included ? 'Activite incluse dans le programme.' : 'Activite proposee au client comme option.';
        }

        if (totalEl) {
            totalEl.textContent = rowTotalLabel(row);
        }
    }

    function refreshAllRows() {
        Array.prototype.slice.call(rowsRoot.querySelectorAll('.voyage-activity-row')).forEach(function(row) {
            refreshRow(row);
        });
    }

    rowsRoot.addEventListener('input', function(event) {
        var row = event.target.closest('.voyage-activity-row');
        if (!row) {
            return;
        }
        refreshRow(row);
    });

    rowsRoot.addEventListener('change', function(event) {
        var row = event.target.closest('.voyage-activity-row');
        if (!row) {
            return;
        }
        refreshRow(row);
    });

    if (openModalBtn && modalEl) {
        openModalBtn.addEventListener('click', function(event) {
            if (event) {
                event.preventDefault();
            }

            if (window.bootstrap && window.bootstrap.Modal) {
                window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
                return;
            }

            if (window.jQuery && typeof window.jQuery(modalEl).modal === 'function') {
                window.jQuery(modalEl).modal('show');
            }
        });
    }

    refreshAllRows();
})();
</script>
<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\circuits\voyages\partials\tabs\_activities.blade.php ENDPATH**/ ?>