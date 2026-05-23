<?php
    $clientMode = old('client_mode', 'new');
    $oldPassengers = collect(old('passengers', []));
?>

<section class="reservation-create__panel" data-create-step="2" data-reservation-step="2" hidden>
    <div class="reservation-create__card">
        <div class="reservation-create__section-head">
            <div>
                <p class="reservation-create__eyebrow">Étape 2</p>
                <h3 class="reservation-create__section-title">Voyageurs</h3>
                <p class="reservation-create__section-subtitle">Le client principal est le voyageur principal. Ajoutez les accompagnants avant la repartition des chambres.</p>
            </div>
            <div class="reservation-create__metric">
                <span>Total</span>
                <strong id="create-travelers-badge">1</strong>
            </div>
        </div>

        <div class="reservation-create__choice-row">
            <label class="reservation-create__choice">
                <input type="radio" name="client_mode" id="client_mode_new" value="new" <?php echo e($clientMode === 'new' ? 'checked' : ''); ?>>
                <span>Nouveau client</span>
            </label>
            <label class="reservation-create__choice">
                <input type="radio" name="client_mode" id="client_mode_existing" value="existing" <?php echo e($clientMode === 'existing' ? 'checked' : ''); ?>>
                <span>Client existant</span>
            </label>
        </div>

        <div id="existing-client-block" class="<?php echo e($clientMode === 'existing' ? '' : 'd-none'); ?>">
            <div class="reservation-create__field" style="position:relative;z-index:20;">
                <label class="reservation-create__label" for="reservation-client-search">Client existant <span class="required-star">*</span></label>
                <input type="hidden" name="client_external_id" id="client_external_id" value="<?php echo e(old('client_external_id')); ?>">
                <input type="search" id="reservation-client-search" class="reservation-create__input mb-1" placeholder="Recherche par nom, téléphone, email ou CIN" autocomplete="off" value="">
                <div id="client-search-results" class="reservation-create__search-results" hidden></div>
                <div id="client-search-selected" class="reservation-create__search-selected <?php echo e(old('client_external_id') ? '' : 'd-none'); ?>">
                    <span class="reservation-create__search-selected-label" id="client-search-selected-label">
                        <?php if(old('client_external_id')): ?>
                            <?php $oldClient = $clients->firstWhere('id', old('client_external_id')); ?>
                            <?php echo e($oldClient ? '['.$oldClient->client_code.'] '.$oldClient->full_name : 'Client sélectionné'); ?>

                        <?php endif; ?>
                    </span>
                    <button type="button" class="reservation-create__search-selected-clear" id="client-search-clear" aria-label="Effacer">&times;</button>
                </div>
                <p id="reservation-client-search-empty" class="reservation-create__helper d-none">Aucun client trouvé.</p>
                <a href="<?php echo e(route('admin.customers.clients.create')); ?>" class="reservation-create__helper d-inline-flex mt-2" target="_blank" rel="noopener">Créer un nouveau client</a>
            </div>
        </div>

        <div id="new-client-block" class="<?php echo e($clientMode === 'new' ? '' : 'd-none'); ?>">
            <div class="reservation-create__grid reservation-create__grid--two">
                <div class="reservation-create__field">
                    <label class="reservation-create__label" for="client_first_name">Prénom <span class="required-star">*</span></label>
                    <input type="text" name="client_first_name" id="client_first_name" class="reservation-create__input" value="<?php echo e(old('client_first_name')); ?>" autocomplete="given-name">
                </div>
                <div class="reservation-create__field">
                    <label class="reservation-create__label" for="client_last_name">Nom <span class="required-star">*</span></label>
                    <input type="text" name="client_last_name" id="client_last_name" class="reservation-create__input" value="<?php echo e(old('client_last_name')); ?>" autocomplete="family-name">
                </div>
                <div class="reservation-create__field">
                    <label class="reservation-create__label" for="client_phone">Téléphone <span class="required-star">*</span></label>
                    <input type="text" name="client_phone" id="client_phone" class="reservation-create__input" value="<?php echo e(old('client_phone')); ?>" autocomplete="tel">
                </div>
                <div class="reservation-create__field">
                    <label class="reservation-create__label" for="client_email">Email</label>
                    <input type="email" name="client_email" id="client_email" class="reservation-create__input" value="<?php echo e(old('client_email')); ?>" autocomplete="email">
                </div>
                <div class="reservation-create__field">
                    <label class="reservation-create__label" for="client_document_type">Type de document</label>
                    <select name="client_document_type" id="client_document_type" class="reservation-create__input">
                        <option value="">Sélectionner...</option>
                        <option value="cin" <?php echo e(old('client_document_type') === 'cin' ? 'selected' : ''); ?>>CIN</option>
                        <option value="passport" <?php echo e(old('client_document_type') === 'passport' ? 'selected' : ''); ?>>Passeport</option>
                    </select>
                </div>
                <div class="reservation-create__field">
                    <label class="reservation-create__label" for="client_document_number">Numéro document</label>
                    <input type="text" name="client_document_number" id="client_document_number" class="reservation-create__input" value="<?php echo e(old('client_document_number')); ?>">
                </div>
                <div class="reservation-create__field">
                    <label class="reservation-create__label" for="client_nationality">Nationalité</label>
                    <input type="text" name="client_nationality" id="client_nationality" class="reservation-create__input" value="<?php echo e(old('client_nationality')); ?>">
                </div>
                <div class="reservation-create__field">
                    <label class="reservation-create__label" for="client_gender">Sexe</label>
                    <select name="client_gender" id="client_gender" class="reservation-create__input">
                        <option value="">Sélectionner...</option>
                        <option value="male" <?php echo e(old('client_gender') === 'male' ? 'selected' : ''); ?>>Homme</option>
                        <option value="female" <?php echo e(old('client_gender') === 'female' ? 'selected' : ''); ?>>Femme</option>
                    </select>
                </div>
                <div class="reservation-create__field">
                    <label class="reservation-create__label" for="client_birth_date">Date naissance</label>
                    <input type="date" name="client_birth_date" id="client_birth_date" class="reservation-create__input" value="<?php echo e(old('client_birth_date')); ?>">
                </div>
                <div class="reservation-create__field">
                    <label class="reservation-create__label" for="client_traveler_type">Type voyageur</label>
                    <select name="client_traveler_type" id="client_traveler_type" class="reservation-create__input">
                        <option value="adult" <?php echo e(old('client_traveler_type', 'adult') === 'adult' ? 'selected' : ''); ?>>Adulte</option>
                        <option value="child" <?php echo e(old('client_traveler_type') === 'child' ? 'selected' : ''); ?>>Enfant</option>
                        <option value="infant" <?php echo e(old('client_traveler_type') === 'infant' ? 'selected' : ''); ?>>Bébé</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="reservation-create__traveler-stats" id="reservation-traveler-stats">
            <span>Total: <strong data-traveler-stat="total">1</strong></span>
            <span>Adultes: <strong data-traveler-stat="adult">1</strong></span>
            <span>Enfants: <strong data-traveler-stat="child">0</strong></span>
            <span>Bebes: <strong data-traveler-stat="infant">0</strong></span>
            <span>Hommes: <strong data-traveler-stat="male">0</strong></span>
            <span>Femmes: <strong data-traveler-stat="female">0</strong></span>
            <span>Adultes sans sexe: <strong data-traveler-stat="gender_unknown">0</strong></span>
            <span>Enfants sans sexe: <strong data-traveler-stat="gender_unknown_children">0</strong></span>
        </div>

        <div class="reservation-create__toolbar">
            <p>Les accompagnants seront utilises pour la repartition automatique des chambres.</p>
            <button type="button" class="reservation-create__button reservation-create__button--ghost" id="btn-add-companion">Ajouter un accompagnant</button>
        </div>

        <div id="companions-container" class="reservation-create__companions">
            <?php $companionCounter = 0; ?>
            <?php $__currentLoopData = $oldPassengers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $passenger): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php $companionKey = 'companion_' . $i; ?>
                <div class="companion-row reservation-create__companion" data-companion-id="<?php echo e($companionKey); ?>" data-traveler-key="<?php echo e($companionKey); ?>">
                    <div class="reservation-create__companion-head">
                        <h4 class="reservation-create__companion-title">Accompagnant #<?php echo e($loop->iteration); ?></h4>
                        <button type="button" class="btn-remove-companion reservation-create__remove" aria-label="Supprimer">x</button>
                    </div>
                    <div class="reservation-create__grid reservation-create__grid--two">
                        <input type="hidden" name="passengers[<?php echo e($companionKey); ?>][traveler_key]" value="<?php echo e($companionKey); ?>">
                        <div class="reservation-create__field"><label class="reservation-create__label">Prénom <span class="required-star">*</span></label><input type="text" name="passengers[<?php echo e($companionKey); ?>][first_name]" class="reservation-create__input" value="<?php echo e($passenger['first_name'] ?? ''); ?>"></div>
                        <div class="reservation-create__field"><label class="reservation-create__label">Nom <span class="required-star">*</span></label><input type="text" name="passengers[<?php echo e($companionKey); ?>][last_name]" class="reservation-create__input" value="<?php echo e($passenger['last_name'] ?? ''); ?>"></div>
                        <div class="reservation-create__field"><label class="reservation-create__label">Type voyageur</label><select name="passengers[<?php echo e($companionKey); ?>][type]" class="reservation-create__input"><option value="adult" <?php echo e(($passenger['type'] ?? '') === 'adult' ? 'selected' : ''); ?>>Adulte</option><option value="child" <?php echo e(($passenger['type'] ?? '') === 'child' ? 'selected' : ''); ?>>Enfant</option><option value="infant" <?php echo e(($passenger['type'] ?? '') === 'infant' ? 'selected' : ''); ?>>Bébé</option></select></div>
                        <div class="reservation-create__field"><label class="reservation-create__label">Sexe</label><select name="passengers[<?php echo e($companionKey); ?>][gender]" class="reservation-create__input"><option value="">Sélectionner...</option><option value="male" <?php echo e(($passenger['gender'] ?? '') === 'male' ? 'selected' : ''); ?>>Homme</option><option value="female" <?php echo e(($passenger['gender'] ?? '') === 'female' ? 'selected' : ''); ?>>Femme</option></select></div>
                        <div class="reservation-create__field"><label class="reservation-create__label">Relation</label><select name="passengers[<?php echo e($companionKey); ?>][relationship_to_main]" class="reservation-create__input"><?php $__currentLoopData = ['spouse' => 'Conjoint / conjointe', 'child' => 'Enfant', 'parent' => 'Parent', 'friend' => 'Ami', 'group' => 'Groupe', 'solo' => 'Seul']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($value); ?>" <?php echo e(($passenger['relationship_to_main'] ?? 'group') === $value ? 'selected' : ''); ?>><?php echo e($label); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
                        <div class="reservation-create__field"><label class="reservation-create__label">Date naissance</label><input type="date" name="passengers[<?php echo e($companionKey); ?>][birth_date]" class="reservation-create__input" value="<?php echo e($passenger['birth_date'] ?? ''); ?>"></div>
                        <div class="reservation-create__field"><label class="reservation-create__label">Type document</label><input type="text" name="passengers[<?php echo e($companionKey); ?>][document_type]" class="reservation-create__input" value="<?php echo e($passenger['document_type'] ?? ''); ?>"></div>
                        <div class="reservation-create__field"><label class="reservation-create__label">N° document</label><input type="text" name="passengers[<?php echo e($companionKey); ?>][document_number]" class="reservation-create__input" value="<?php echo e($passenger['document_number'] ?? ''); ?>"></div>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <p id="create-no-companions" class="reservation-create__empty-state <?php echo e($oldPassengers->isNotEmpty() ? 'd-none' : ''); ?>">Aucun accompagnant pour le moment.</p>
    </div>

    <div class="reservation-create__step-errors" id="step-2-errors" hidden></div>
    <div class="reservation-create__actions">
        <button type="button" class="reservation-create__button reservation-create__button--secondary" data-create-prev data-step-back="1">
            <i class="bx bx-left-arrow-alt" aria-hidden="true"></i><span>Retour</span>
        </button>
        <button type="button" class="reservation-create__button reservation-create__button--primary" data-create-next data-step-next="3">
            <span>Continuer</span><i class="bx bx-right-arrow-alt" aria-hidden="true"></i>
        </button>
    </div>
</section>

<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\reservations\create\partials\step-client.blade.php ENDPATH**/ ?>