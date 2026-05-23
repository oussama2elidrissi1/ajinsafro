<?php
    use App\Models\CustomReservationRequest;

    $serviceOptions = $serviceOptions ?? CustomReservationRequest::serviceOptions();
    $statusOptions = $statusOptions ?? CustomReservationRequest::statusOptions();
    $priorityOptions = $priorityOptions ?? CustomReservationRequest::priorityOptions();
    $sourceOptions = $sourceOptions ?? CustomReservationRequest::sourceOptions();
    $services = old('services', $customRequest->services ?: []);
    $children = old('children', $customRequest->children ?: []);
    $infants = old('infants', $customRequest->infants ?: []);
    $channels = old('preferred_channels', $customRequest->preferred_channels ?: []);
    $isEdit = $customRequest->exists;
?>

<?php $__env->startPush('styles'); ?>
<style>
    .crq-page { display: grid; gap: 18px; }
    .crq-hero { background: linear-gradient(135deg, #073b5c, #0f5f8f); color: #fff; border-radius: 20px; padding: 22px; display: flex; justify-content: space-between; gap: 16px; align-items: center; box-shadow: 0 14px 30px rgba(15,39,66,.16); }
    .crq-hero h2 { margin: 0; font-weight: 900; font-size: 22px; }
    .crq-hero p { margin: 6px 0 0; color: rgba(255,255,255,.78); font-weight: 600; }
    .crq-btn { border: 0; border-radius: 12px; padding: 11px 15px; font-weight: 900; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; cursor: pointer; }
    .crq-btn-primary { background: #ff7a1a; color: #fff; }
    .crq-btn-blue { background: #0f5f8f; color: #fff; }
    .crq-btn-soft { background: #eef6fb; color: #0f5f8f; border: 1px solid #d9e8f2; }
    .crq-shell { background: #fff; border: 1px solid #dce8f1; border-radius: 20px; box-shadow: 0 10px 26px rgba(15,39,66,.06); overflow: hidden; }
    .crq-steps { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); border-bottom: 1px solid #e6edf5; background: #f8fbfd; }
    .crq-step { padding: 16px; border: 0; background: transparent; color: #6b7f95; font-weight: 900; text-align: left; display: flex; align-items: center; gap: 10px; }
    .crq-step span { width: 28px; height: 28px; border-radius: 999px; background: #e8f2f8; color: #0f5f8f; display: grid; place-items: center; }
    .crq-step.is-active { color: #0f2742; background: #fff; }
    .crq-step.is-active span { background: #ff7a1a; color: #fff; }
    .crq-pane { display: none; padding: 22px; }
    .crq-pane.is-active { display: block; }
    .crq-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
    .crq-grid-3 { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; }
    .crq-field { display: grid; gap: 6px; }
    .crq-field.full { grid-column: 1 / -1; }
    .crq-label { color: #334155; font-size: 12px; font-weight: 900; text-transform: uppercase; }
    .crq-required { width: 7px; height: 7px; display: inline-block; background: #ef4444; border-radius: 50%; margin-left: 5px; vertical-align: middle; }
    .crq-input, .crq-select, .crq-textarea { width: 100%; border: 1px solid #dce8f1; border-radius: 12px; padding: 11px 12px; color: #0f2742; font-weight: 700; background: #fff; }
    .crq-textarea { min-height: 110px; resize: vertical; }
    .crq-error { color: #dc2626; font-size: 12px; font-weight: 700; }
    .crq-checks { display: flex; flex-wrap: wrap; gap: 10px; }
    .crq-check { display: inline-flex; align-items: center; gap: 7px; padding: 9px 11px; border: 1px solid #dce8f1; border-radius: 12px; background: #f8fbfd; font-weight: 800; color: #334155; }
    .crq-service-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; }
    .crq-service { border: 1px solid #dce8f1; border-radius: 16px; background: #fff; padding: 14px; cursor: pointer; display: flex; align-items: center; gap: 12px; font-weight: 900; color: #0f2742; }
    .crq-service i { width: 38px; height: 38px; border-radius: 12px; background: #eef6fb; color: #0f5f8f; display: grid; place-items: center; font-size: 18px; }
    .crq-service.is-active { border-color: #ff7a1a; box-shadow: 0 10px 22px rgba(255,122,26,.14); }
    .crq-service-config { display: none; margin-top: 14px; padding: 16px; border: 1px solid #e6edf5; background: #fbfdff; border-radius: 16px; }
    .crq-service-config.is-active { display: block; }
    .crq-service-config h4 { margin: 0 0 12px; font-size: 15px; font-weight: 900; color: #0f2742; }
    .crq-row-list { display: grid; gap: 10px; }
    .crq-row { display: grid; grid-template-columns: 1fr 1fr auto; gap: 10px; align-items: end; }
    .crq-remove { border: 0; border-radius: 10px; width: 42px; height: 42px; color: #dc2626; background: #fee2e2; }
    .crq-summary { background: #f8fbfd; border: 1px solid #dce8f1; border-radius: 16px; padding: 16px; color: #334155; font-weight: 700; line-height: 1.7; }
    .crq-actions { padding: 18px 22px; border-top: 1px solid #e6edf5; display: flex; justify-content: space-between; gap: 12px; flex-wrap: wrap; background: #fbfdff; }
    @media (max-width: 1100px) { .crq-service-grid { grid-template-columns: repeat(2, 1fr); } .crq-grid-3 { grid-template-columns: 1fr 1fr; } }
    @media (max-width: 720px) { .crq-hero, .crq-actions { flex-direction: column; align-items: stretch; } .crq-steps, .crq-grid, .crq-grid-3, .crq-service-grid { grid-template-columns: 1fr; } .crq-row { grid-template-columns: 1fr; } }
</style>
<?php $__env->stopPush(); ?>

<form method="POST" action="<?php echo e($formAction); ?>" class="crq-page" id="crq-form">
    <?php echo csrf_field(); ?>
    <?php if($formMethod !== 'POST'): ?>
        <?php echo method_field($formMethod); ?>
    <?php endif; ?>

    <div class="crq-hero">
        <div>
            <h2><?php echo e($isEdit ? 'Modifier la demande' : 'Créer une demande à la carte'); ?></h2>
            <p><?php echo e($isEdit ? $customRequest->reference : 'Workflow séparé des réservations standard, sans voyage obligatoire.'); ?></p>
        </div>
        <a href="<?php echo e(route('admin.reservations.custom-requests.index')); ?>" class="crq-btn crq-btn-soft"><i class="bx bx-list-ul"></i> Liste des demandes</a>
    </div>

    <?php if($errors->any()): ?>
        <div class="alert alert-danger mb-0">
            Vérifiez les champs du formulaire. Les erreurs sont indiquées sous chaque champ.
        </div>
    <?php endif; ?>

    <div class="crq-shell">
        <div class="crq-steps" role="tablist">
            <button type="button" class="crq-step is-active" data-crq-step="1"><span>1</span> Client</button>
            <button type="button" class="crq-step" data-crq-step="2"><span>2</span> Voyageurs & dates</button>
            <button type="button" class="crq-step" data-crq-step="3"><span>3</span> Services</button>
            <button type="button" class="crq-step" data-crq-step="4"><span>4</span> Validation</button>
        </div>

        <section class="crq-pane is-active" data-crq-pane="1">
            <div class="crq-grid">
                <div class="crq-field">
                    <label class="crq-label">Type titulaire</label>
                    <select name="client_type" class="crq-select">
                        <?php $__currentLoopData = ['particular' => 'Particulier', 'company' => 'Entreprise', 'agency' => 'Agence']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($value); ?>" <?php if(old('client_type', $customRequest->client_type) === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="crq-field">
                    <label class="crq-label">Genre</label>
                    <select name="client_gender" class="crq-select">
                        <option value="">Non precise</option>
                        <option value="M" <?php if(old('client_gender', $customRequest->client_gender) === 'M'): echo 'selected'; endif; ?>>M.</option>
                        <option value="F" <?php if(old('client_gender', $customRequest->client_gender) === 'F'): echo 'selected'; endif; ?>>Mme</option>
                    </select>
                </div>
                <div class="crq-field">
                    <label class="crq-label">Nom complet <span class="crq-required"></span></label>
                    <input name="client_name" class="crq-input" value="<?php echo e(old('client_name', $customRequest->client_name)); ?>" required>
                    <?php $__errorArgs = ['client_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="crq-error"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="crq-field">
                    <label class="crq-label">Téléphone <span class="crq-required"></span></label>
                    <input name="client_phone" class="crq-input" value="<?php echo e(old('client_phone', $customRequest->client_phone)); ?>" required>
                    <?php $__errorArgs = ['client_phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="crq-error"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="crq-field">
                    <label class="crq-label">WhatsApp</label>
                    <input name="client_whatsapp" id="client_whatsapp" class="crq-input" value="<?php echo e(old('client_whatsapp', $customRequest->client_whatsapp)); ?>">
                    <label class="crq-check mt-1"><input type="checkbox" name="whatsapp_same_as_phone" id="whatsapp_same_as_phone" value="1" <?php if(old('whatsapp_same_as_phone', $customRequest->whatsapp_same_as_phone)): echo 'checked'; endif; ?>> WhatsApp = téléphone</label>
                </div>
                <div class="crq-field">
                    <label class="crq-label">Email</label>
                    <input type="email" name="client_email" class="crq-input" value="<?php echo e(old('client_email', $customRequest->client_email)); ?>">
                    <?php $__errorArgs = ['client_email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="crq-error"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="crq-field full">
                    <label class="crq-label">Canal préféré</label>
                    <div class="crq-checks">
                        <?php $__currentLoopData = ['call' => 'Appel', 'whatsapp' => 'WhatsApp', 'email' => 'Email']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <label class="crq-check"><input type="checkbox" name="preferred_channels[]" value="<?php echo e($value); ?>" <?php if(in_array($value, $channels, true)): echo 'checked'; endif; ?>> <?php echo e($label); ?></label>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
            </div>
        </section>

        <section class="crq-pane" data-crq-pane="2">
            <div class="crq-grid-3">
                <div class="crq-field">
                    <label class="crq-label">Adultes <span class="crq-required"></span></label>
                    <input type="number" min="1" name="adults" class="crq-input" value="<?php echo e(old('adults', $customRequest->adults ?: 1)); ?>">
                    <?php $__errorArgs = ['adults'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="crq-error"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="crq-field">
                    <label class="crq-label">Ville de départ</label>
                    <input name="departure_city_text" class="crq-input" value="<?php echo e(old('departure_city_text', $customRequest->departure_city_text)); ?>">
                </div>
                <div class="crq-field">
                    <label class="crq-label">Destination</label>
                    <input name="destination_text" class="crq-input" value="<?php echo e(old('destination_text', $customRequest->destination_text)); ?>">
                    <?php $__errorArgs = ['destination_text'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="crq-error"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="crq-field">
                    <label class="crq-label">Date départ</label>
                    <input type="date" name="departure_date" class="crq-input" value="<?php echo e(old('departure_date', optional($customRequest->departure_date)->toDateString())); ?>">
                </div>
                <div class="crq-field">
                    <label class="crq-label">Date retour</label>
                    <input type="date" name="return_date" class="crq-input" value="<?php echo e(old('return_date', optional($customRequest->return_date)->toDateString())); ?>">
                    <?php $__errorArgs = ['return_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="crq-error"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="crq-field">
                    <label class="crq-label">Devise</label>
                    <input name="currency" class="crq-input" value="<?php echo e(old('currency', $customRequest->currency ?: 'MAD')); ?>">
                </div>
                <div class="crq-field">
                    <label class="crq-label">Budget min</label>
                    <input type="number" step="0.01" name="budget_min" class="crq-input" value="<?php echo e(old('budget_min', $customRequest->budget_min)); ?>">
                </div>
                <div class="crq-field">
                    <label class="crq-label">Budget max</label>
                    <input type="number" step="0.01" name="budget_max" class="crq-input" value="<?php echo e(old('budget_max', $customRequest->budget_max)); ?>">
                    <?php $__errorArgs = ['budget_max'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="crq-error"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="crq-field">
                    <label class="crq-label">Flexibilité</label>
                    <label class="crq-check"><input type="checkbox" name="flexible_dates" value="1" <?php if(old('flexible_dates', $customRequest->flexible_dates)): echo 'checked'; endif; ?>> Dates flexibles</label>
                </div>
                <div class="crq-field full">
                    <label class="crq-label">Enfants</label>
                    <div class="crq-row-list" data-repeat-list="children">
                        <?php $__currentLoopData = ($children ?: [[]]); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $child): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="crq-row">
                                <input type="number" min="0" max="17" name="children[<?php echo e($idx); ?>][age]" class="crq-input" placeholder="Age" value="<?php echo e($child['age'] ?? ''); ?>">
                                <input type="date" name="children[<?php echo e($idx); ?>][birth_date]" class="crq-input" value="<?php echo e($child['birth_date'] ?? ''); ?>">
                                <button type="button" class="crq-remove" data-repeat-remove><i class="bx bx-trash"></i></button>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                    <button type="button" class="crq-btn crq-btn-soft mt-2" data-repeat-add="children">Ajouter enfant</button>
                </div>
                <div class="crq-field full">
                    <label class="crq-label">Bébés</label>
                    <div class="crq-row-list" data-repeat-list="infants">
                        <?php $__currentLoopData = ($infants ?: [[]]); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $infant): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="crq-row">
                                <input type="number" min="0" max="3" name="infants[<?php echo e($idx); ?>][age]" class="crq-input" placeholder="Age" value="<?php echo e($infant['age'] ?? ''); ?>">
                                <input type="date" name="infants[<?php echo e($idx); ?>][birth_date]" class="crq-input" value="<?php echo e($infant['birth_date'] ?? ''); ?>">
                                <button type="button" class="crq-remove" data-repeat-remove><i class="bx bx-trash"></i></button>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                    <button type="button" class="crq-btn crq-btn-soft mt-2" data-repeat-add="infants">Ajouter bébé</button>
                </div>
                <div class="crq-field full">
                    <label class="crq-label">Note voyageurs</label>
                    <textarea name="passengers_note" class="crq-textarea"><?php echo e(old('passengers_note', $customRequest->passengers_note)); ?></textarea>
                </div>
            </div>
        </section>

        <section class="crq-pane" data-crq-pane="3">
            <div class="crq-service-grid">
                <?php $__currentLoopData = $serviceOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php $enabled = (bool) data_get($services, $key.'.enabled', array_key_exists($key, $services)); ?>
                    <label class="crq-service <?php echo e($enabled ? 'is-active' : ''); ?>" data-service-card="<?php echo e($key); ?>">
                        <input type="checkbox" name="services[<?php echo e($key); ?>][enabled]" value="1" class="d-none" <?php if($enabled): echo 'checked'; endif; ?>>
                        <i class="bx <?php echo e(match($key) { 'flights' => 'bx-plane', 'accommodation' => 'bx-hotel', 'transfers' => 'bx-car', 'excursions' => 'bx-camera', 'omra' => 'bx-building-house', 'visa' => 'bx-id-card', 'insurance' => 'bx-shield-quarter', default => 'bx-dots-horizontal-rounded' }); ?>"></i>
                        <?php echo e($label); ?>

                    </label>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>

            <?php echo $__env->make('admin.reservations.custom-requests.service-configs', ['services' => $services], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        </section>

        <section class="crq-pane" data-crq-pane="4">
            <div class="crq-grid">
                <div class="crq-field">
                    <label class="crq-label">Priorité</label>
                    <select name="priority" class="crq-select">
                        <option value="">Non definie</option>
                        <?php $__currentLoopData = $priorityOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($value); ?>" <?php if(old('priority', $customRequest->priority) === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="crq-field">
                    <label class="crq-label">Agent assigné</label>
                    <select name="assigned_to" class="crq-select">
                        <option value="">Non assigne</option>
                        <?php $__currentLoopData = $agents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $agent): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($agent->id); ?>" <?php if((int) old('assigned_to', $customRequest->assigned_to) === (int) $agent->id): echo 'selected'; endif; ?>><?php echo e($agent->name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="crq-field">
                    <label class="crq-label">Statut initial</label>
                    <select name="status" class="crq-select">
                        <?php $__currentLoopData = $statusOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($value); ?>" <?php if(old('status', $customRequest->status ?: 'new') === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="crq-field">
                    <label class="crq-label">Source</label>
                    <select name="source" class="crq-select">
                        <option value="">Non precisee</option>
                        <?php $__currentLoopData = $sourceOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($value); ?>" <?php if(old('source', $customRequest->source ?: 'admin') === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="crq-field full">
                    <label class="crq-label">Notes client</label>
                    <textarea name="client_notes" class="crq-textarea"><?php echo e(old('client_notes', $customRequest->client_notes)); ?></textarea>
                </div>
                <div class="crq-field full">
                    <label class="crq-label">Notes internes</label>
                    <textarea name="internal_notes" class="crq-textarea"><?php echo e(old('internal_notes', $customRequest->internal_notes)); ?></textarea>
                </div>
                <div class="crq-field full">
                    <label class="crq-label">Réponse commerciale / devis</label>
                    <textarea name="admin_response" class="crq-textarea"><?php echo e(old('admin_response', $customRequest->admin_response)); ?></textarea>
                </div>
                <div class="crq-field">
                    <label class="crq-label">Montant devis</label>
                    <input type="number" step="0.01" name="quoted_amount" class="crq-input" value="<?php echo e(old('quoted_amount', $customRequest->quoted_amount)); ?>">
                </div>
                <div class="crq-field full">
                    <label class="crq-label">Résumé</label>
                    <div class="crq-summary" id="crq-summary">Le résumé sera généré depuis les champs saisis.</div>
                </div>
            </div>
        </section>

        <div class="crq-actions">
            <div>
                <button type="button" class="crq-btn crq-btn-soft" data-crq-prev>Précédent</button>
                <button type="button" class="crq-btn crq-btn-blue" data-crq-next>Suivant</button>
            </div>
            <div>
                <button type="submit" name="submit_action" value="draft" class="crq-btn crq-btn-soft">Enregistrer brouillon</button>
                <button type="submit" name="submit_action" value="create" class="crq-btn crq-btn-primary"><?php echo e($submitLabel); ?></button>
                <button type="submit" name="submit_action" value="create_open" class="crq-btn crq-btn-blue">Créer et ouvrir détail</button>
            </div>
        </div>
    </div>
</form>

<?php $__env->startPush('scripts'); ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var current = 1;
    var form = document.getElementById('crq-form');
    var summary = document.getElementById('crq-summary');

    function showStep(step) {
        current = Math.max(1, Math.min(4, step));
        document.querySelectorAll('[data-crq-step]').forEach(function (button) {
            button.classList.toggle('is-active', Number(button.dataset.crqStep) === current);
        });
        document.querySelectorAll('[data-crq-pane]').forEach(function (pane) {
            pane.classList.toggle('is-active', Number(pane.dataset.crqPane) === current);
        });
        renderSummary();
    }

    function val(name) {
        var el = form ? form.querySelector('[name="' + name + '"]') : null;
        return el ? el.value : '';
    }

    function selectedServices() {
        return Array.prototype.slice.call(document.querySelectorAll('[data-service-card].is-active')).map(function (card) {
            return card.textContent.trim();
        });
    }

    function renderSummary() {
        if (!summary || !form) return;
        var bits = [
            '<strong>Client:</strong> ' + (val('client_name') || '-'),
            '<strong>Telephone:</strong> ' + (val('client_phone') || '-'),
            '<strong>Destination:</strong> ' + (val('destination_text') || '-'),
            '<strong>Dates:</strong> ' + (val('departure_date') || '-') + ' au ' + (val('return_date') || '-'),
            '<strong>Adultes:</strong> ' + (val('adults') || '1'),
            '<strong>Budget:</strong> ' + (val('budget_min') || '-') + ' - ' + (val('budget_max') || '-') + ' ' + (val('currency') || 'MAD'),
            '<strong>Services:</strong> ' + (selectedServices().join(', ') || '-')
        ];
        summary.innerHTML = bits.join('<br>');
    }

    document.querySelectorAll('[data-crq-step]').forEach(function (button) {
        button.addEventListener('click', function () { showStep(Number(button.dataset.crqStep)); });
    });
    document.querySelector('[data-crq-next]')?.addEventListener('click', function () { showStep(current + 1); });
    document.querySelector('[data-crq-prev]')?.addEventListener('click', function () { showStep(current - 1); });

    document.querySelectorAll('[data-service-card]').forEach(function (card) {
        card.addEventListener('click', function () {
            var input = card.querySelector('input[type="checkbox"]');
            window.setTimeout(function () {
                var active = input && input.checked;
                card.classList.toggle('is-active', active);
                var config = document.querySelector('[data-service-config="' + card.dataset.serviceCard + '"]');
                if (config) config.classList.toggle('is-active', active);
                renderSummary();
            }, 0);
        });
    });

    document.querySelectorAll('[data-repeat-add]').forEach(function (button) {
        button.addEventListener('click', function () {
            var key = button.dataset.repeatAdd;
            var list = document.querySelector('[data-repeat-list="' + key + '"]');
            if (!list) return;
            var index = list.querySelectorAll('.crq-row').length;
            var row = document.createElement('div');
            row.className = 'crq-row';
            row.innerHTML = '<input type="number" min="0" name="' + key + '[' + index + '][age]" class="crq-input" placeholder="Age"><input type="date" name="' + key + '[' + index + '][birth_date]" class="crq-input"><button type="button" class="crq-remove" data-repeat-remove><i class="bx bx-trash"></i></button>';
            list.appendChild(row);
        });
    });

    document.addEventListener('click', function (event) {
        var remove = event.target.closest('[data-repeat-remove]');
        if (remove) remove.closest('.crq-row')?.remove();
    });

    form?.addEventListener('input', renderSummary);
    showStep(1);
});
</script>
<?php $__env->stopPush(); ?>
<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\reservations\custom-requests\form.blade.php ENDPATH**/ ?>