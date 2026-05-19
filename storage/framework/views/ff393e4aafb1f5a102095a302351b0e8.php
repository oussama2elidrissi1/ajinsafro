<?php
    $day = $entry['day'];
    $activities = $entry['activities'];
    $collapseId = 'collapse-day-' . $day->id . '-i' . $dayIndex;
    $isFirst = ($dayIndex === 0);
    $dayTitleDisplay = $day->day_title ?? $day->title ?? ('Jour ' . $day->day_number);
    $dayHotelsTransfers = ($programDayHotelsTransfers ?? [])[$dayIndex] ?? [];
?>

<div class="accordion-item programme-day-card" data-day-id="<?php echo e($day->id); ?>" data-day-index="<?php echo e($dayIndex); ?>" data-day-number="<?php echo e((int) $day->day_number); ?>">
    <h2 class="accordion-header programme-day-header">
        <span class="drag-handle me-2 text-muted cursor-grab" title="Déplacer" aria-hidden="true"><i class="bx bx-dots-vertical-rounded"></i></span>
        <button class="accordion-button flex-grow-1 <?php echo e($isFirst ? '' : 'collapsed'); ?>" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo e($collapseId); ?>" aria-expanded="<?php echo e($isFirst ? 'true' : 'false'); ?>" aria-controls="<?php echo e($collapseId); ?>">
            <span class="programme-day-label">JOUR <?php echo e($day->day_number); ?> — <?php echo e($dayTitleDisplay); ?></span>
        </button>
        <button type="button" class="btn btn-sm btn-outline-danger me-2 btn-remove-program-day" title="Supprimer ce jour" data-day-id="<?php echo e($day->id); ?>">
            <i class="bx bx-trash"></i>
        </button>
    </h2>
    <div id="<?php echo e($collapseId); ?>" class="accordion-collapse collapse <?php echo e($isFirst ? 'show' : ''); ?>" data-bs-parent="#accordionProgrammeDays">
        <div class="accordion-body" data-day-index="<?php echo e($dayIndex); ?>" data-day-id="<?php echo e($day->id); ?>">
            <input type="hidden" name="programme_days[<?php echo e($dayIndex); ?>][id]" value="<?php echo e($day->id); ?>">
            <input type="hidden" name="programme_days[<?php echo e($dayIndex); ?>][day_id]" value="<?php echo e($day->id); ?>">

            <div class="program-day-form" data-day-index="<?php echo e($dayIndex); ?>">
                <div class="field-mode">
                    <label class="form-label">Type / mode</label>
                    <select name="programme_days[<?php echo e($dayIndex); ?>][mode]" class="form-select programme-day-mode">
                        <option value="program" <?php echo e(($day->mode ?? 'program') === 'program' ? 'selected' : ''); ?>>Visite / programme</option>
                        <option value="free" <?php echo e(($day->mode ?? '') === 'free' ? 'selected' : ''); ?>>Libre</option>
                    </select>
                </div>
                <div class="field-type">
                    <label class="form-label">Type de jour</label>
                    <?php
                        $dayTypeRef = \App\Services\BusinessReferentialService::programDayTypes();
                        $rawDayType = old('programme_days.'.$dayIndex.'.day_type', $day->day_type ?? 'visite');
                        $selDayType = \App\Models\TravelProgramDay::normalizeDayType($rawDayType);
                    ?>
                    <select name="programme_days[<?php echo e($dayIndex); ?>][day_type]" class="form-select">
                        <?php $__currentLoopData = $dayTypeRef; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($opt['value']); ?>" <?php if($selDayType === $opt['value']): echo 'selected'; endif; ?>><?php echo e($opt['label']); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="field-title">
                    <label class="form-label">Titre du jour</label>
                    <input type="text" class="form-control" name="programme_days[<?php echo e($dayIndex); ?>][day_title]" value="<?php echo e(old('programme_days.'.$dayIndex.'.day_title', $day->day_title ?? $day->title)); ?>" placeholder="Ex. : Jour 1 — Arrivée">
                </div>
                <div class="field-ville">
                    <label class="form-label">Ville</label>
                    <input type="text" class="form-control" name="programme_days[<?php echo e($dayIndex); ?>][city]" value="<?php echo e(old('programme_days.'.$dayIndex.'.city', $day->city ?? '')); ?>" placeholder="Ex. : Marrakech">
                </div>
                <div class="field-resume ve-rich-field">
                    <label class="form-label">Résumé</label>
                    <textarea class="form-control programme-plain-editor" name="programme_days[<?php echo e($dayIndex); ?>][description]" rows="3" placeholder="Résumé du jour"><?php echo e(old('programme_days.'.$dayIndex.'.description', $day->description ?? '')); ?></textarea>
                </div>
                <div class="field-description programme-day-detail ve-rich-field">
                    <label class="form-label">Description détaillée</label>
                    <textarea class="form-control programme-plain-editor" name="programme_days[<?php echo e($dayIndex); ?>][content_html]" rows="5" placeholder="Programme détaillé du jour"><?php echo e(old('programme_days.'.$dayIndex.'.content_html', $day->content_html ?? '')); ?></textarea>
                </div>
                <div class="field-notes programme-day-notes ve-rich-field">
                    <label class="form-label">Notes</label>
                    <textarea class="form-control programme-plain-editor" name="programme_days[<?php echo e($dayIndex); ?>][notes]" rows="4" placeholder="Notes du jour"><?php echo e(old('programme_days.'.$dayIndex.'.notes', $day->notes ?? $day->description)); ?></textarea>
                </div>
            </div>

            <input type="hidden" name="programme_days[<?php echo e($dayIndex); ?>][title]" value="<?php echo e(old('programme_days.'.$dayIndex.'.title', $day->title ?? ($day->day_title ?? ''))); ?>">
            <input type="hidden" name="programme_days[<?php echo e($dayIndex); ?>][flights]" value="<?php echo e(old('programme_days.'.$dayIndex.'.flights', '')); ?>">
            <input type="hidden" name="programme_days[<?php echo e($dayIndex); ?>][hotel_id]" value="<?php echo e(old('programme_days.'.$dayIndex.'.hotel_id', $dayHotelsTransfers['hotel_id'] ?? '')); ?>">
            <input type="hidden" name="programme_days[<?php echo e($dayIndex); ?>][transfer_ids]" value="<?php echo e(old('programme_days.'.$dayIndex.'.transfer_ids', implode(',', $dayHotelsTransfers['transfer_ids'] ?? []))); ?>">

            <h6 class="mt-4 mb-2">Activités du jour</h6>
            <div class="programme-activities-list mb-3" data-day-index="<?php echo e($dayIndex); ?>" data-day-id="<?php echo e($day->id); ?>">
                <?php $__currentLoopData = $activities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $actIndex => $da): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="programme-activity-row card mb-2" data-day-activity-id="<?php echo e($da->id); ?>" draggable="true">
                        <div class="card-body py-2">
                            <div class="d-flex flex-wrap align-items-start gap-2">
                                <span class="programme-activity-drag-handle text-muted cursor-grab me-1" title="Réordonner"><i class="bx bx-dots-vertical-rounded"></i></span>
                                <input type="hidden" name="programme_days[<?php echo e($dayIndex); ?>][activities][<?php echo e($actIndex); ?>][day_activity_id]" value="<?php echo e($da->id); ?>">
                                <input type="hidden" name="programme_days[<?php echo e($dayIndex); ?>][activities][<?php echo e($actIndex); ?>][activity_id]" value="<?php echo e($da->activity_id); ?>">
                                <input type="hidden" name="programme_days[<?php echo e($dayIndex); ?>][activities][<?php echo e($actIndex); ?>][sort_order]" value="<?php echo e($actIndex); ?>">
                                <span class="fw-medium"><?php echo e($da->activity->title ?? 'Activité #'.$da->activity_id); ?></span>
                                <span class="form-check form-check-inline mb-0">
                                    <input type="hidden" name="programme_days[<?php echo e($dayIndex); ?>][activities][<?php echo e($actIndex); ?>][is_included]" value="0">
                                    <input class="form-check-input programme-act-is-included" type="checkbox" name="programme_days[<?php echo e($dayIndex); ?>][activities][<?php echo e($actIndex); ?>][is_included]" value="1" <?php echo e($da->is_included ? 'checked' : ''); ?>>
                                    <label class="form-check-label small">Inclus</label>
                                </span>
                                <span class="programme-act-scope-wrap ms-1<?php echo e($da->is_included ? ' d-none' : ''); ?>">
                                    <select name="programme_days[<?php echo e($dayIndex); ?>][activities][<?php echo e($actIndex); ?>][day_scope]" class="form-select form-select-sm programme-act-scope" style="width:auto;display:inline-block">
                                        <option value="fixed" <?php echo e(($da->day_scope ?? 'fixed') === 'fixed' ? 'selected' : ''); ?>>Jour défini</option>
                                        <option value="open" <?php echo e(($da->day_scope ?? 'fixed') === 'open' ? 'selected' : ''); ?>>Ouvert à tous les jours</option>
                                    </select>
                                </span>
                                <span class="form-check form-check-inline mb-0">
                                    <input type="hidden" name="programme_days[<?php echo e($dayIndex); ?>][activities][<?php echo e($actIndex); ?>][is_mandatory]" value="0">
                                    <input class="form-check-input" type="checkbox" name="programme_days[<?php echo e($dayIndex); ?>][activities][<?php echo e($actIndex); ?>][is_mandatory]" value="1" <?php echo e($da->is_mandatory ? 'checked' : ''); ?> <?php echo e($da->is_mandatory ? 'readonly' : ''); ?>>
                                    <label class="form-check-label small">Obligatoire</label>
                                </span>
                                <?php if($da->is_editable): ?>
                                    <input type="text" class="form-control form-control-sm d-inline-block" style="max-width:220px" name="programme_days[<?php echo e($dayIndex); ?>][activities][<?php echo e($actIndex); ?>][custom_title]" value="<?php echo e($da->custom_title); ?>" placeholder="Titre personnalisé">
                                    <textarea class="form-control form-control-sm programme-plain-editor" name="programme_days[<?php echo e($dayIndex); ?>][activities][<?php echo e($actIndex); ?>][custom_description]" rows="2" placeholder="Description personnalisée"><?php echo e($da->custom_description); ?></textarea>
                                <?php else: ?>
                                    <input type="hidden" name="programme_days[<?php echo e($dayIndex); ?>][activities][<?php echo e($actIndex); ?>][custom_title]" value="<?php echo e($da->custom_title); ?>">
                                    <input type="hidden" name="programme_days[<?php echo e($dayIndex); ?>][activities][<?php echo e($actIndex); ?>][custom_description]" value="<?php echo e($da->custom_description); ?>">
                                <?php endif; ?>
                                <?php if(!$da->is_mandatory): ?>
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-programme-activity"><i class="bx bx-trash"></i></button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>

        </div>
    </div>
</div>
<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\circuits\voyages\partials\programme\_day_card.blade.php ENDPATH**/ ?>