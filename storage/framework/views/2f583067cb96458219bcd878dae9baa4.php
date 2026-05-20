<?php
    $isEdit = isset($client) && $client->exists;
    $sources = ['website' => 'Site web', 'whatsapp' => 'WhatsApp', 'phone' => 'Téléphone', 'facebook' => 'Facebook', 'instagram' => 'Instagram', 'referral' => 'Parrainage', 'walkin' => 'Walk-in', 'admin' => 'Admin'];
?>


<div class="card mb-3">
    <div class="card-header bg-light">
        <h5 class="mb-0">Informations générales</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Type de client <span class="text-danger">*</span></label>
                <select name="client_type" class="form-select" required>
                    <option value="individual" <?php echo e(old('client_type', $client->client_type ?? 'individual') === 'individual' ? 'selected' : ''); ?>>Particulier</option>
                    <option value="company" <?php echo e(old('client_type', $client->client_type ?? '') === 'company' ? 'selected' : ''); ?>>Société</option>
                    <option value="agency" <?php echo e(old('client_type', $client->client_type ?? '') === 'agency' ? 'selected' : ''); ?>>Agence</option>
                </select>
                <?php $__errorArgs = ['client_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback d-block"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div class="col-md-6">
                <label class="form-label">Statut <span class="text-danger">*</span></label>
                <select name="status" class="form-select" required>
                    <option value="active" <?php echo e(old('status', $client->status ?? 'active') === 'active' ? 'selected' : ''); ?>>Actif</option>
                    <option value="inactive" <?php echo e(old('status', $client->status ?? '') === 'inactive' ? 'selected' : ''); ?>>Inactif</option>
                    <option value="blocked" <?php echo e(old('status', $client->status ?? '') === 'blocked' ? 'selected' : ''); ?>>Bloqué</option>
                    <option value="vip" <?php echo e(old('status', $client->status ?? '') === 'vip' ? 'selected' : ''); ?>>VIP</option>
                </select>
                <?php $__errorArgs = ['status'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback d-block"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div class="col-md-6">
                <label class="form-label">Source</label>
                <select name="source" class="form-select">
                    <option value="">�?"</option>
                    <?php $__currentLoopData = $sources; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => $v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($k); ?>" <?php echo e(old('source', $client->source ?? '') === $k ? 'selected' : ''); ?>><?php echo e($v); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Assigné à</label>
                <select name="assigned_to" class="form-select">
                    <option value="">�?"</option>
                    <?php $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($u->id); ?>" <?php echo e(old('assigned_to', $client->assigned_to ?? '') == $u->id ? 'selected' : ''); ?>><?php echo e($u->name); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
        </div>
    </div>
</div>


<div class="card mb-3">
    <div class="card-header bg-light">
        <h5 class="mb-0">Identité</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Prénom <span class="text-danger">*</span></label>
                <input type="text" name="first_name" class="form-control <?php $__errorArgs = ['first_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" value="<?php echo e(old('first_name', $client->first_name ?? '')); ?>" required>
                <?php $__errorArgs = ['first_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div class="col-md-4">
                <label class="form-label">Nom <span class="text-danger">*</span></label>
                <input type="text" name="last_name" class="form-control <?php $__errorArgs = ['last_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" value="<?php echo e(old('last_name', $client->last_name ?? '')); ?>" required>
                <?php $__errorArgs = ['last_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div class="col-md-4">
                <label class="form-label">Nom complet</label>
                <input type="text" class="form-control bg-light" value="<?php echo e(old('full_name', $client->full_name ?? '')); ?>" readonly placeholder="Rempli automatiquement">
            </div>
            <div class="col-md-4">
                <label class="form-label">Genre</label>
                <select name="gender" class="form-select">
                    <option value="">�?"</option>
                    <option value="male" <?php echo e(old('gender', $client->gender ?? '') === 'male' ? 'selected' : ''); ?>>Homme</option>
                    <option value="female" <?php echo e(old('gender', $client->gender ?? '') === 'female' ? 'selected' : ''); ?>>Femme</option>
                    <option value="other" <?php echo e(old('gender', $client->gender ?? '') === 'other' ? 'selected' : ''); ?>>Autre</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Date de naissance</label>
                <input type="date" name="date_of_birth" class="form-control" value="<?php echo e(old('date_of_birth', $client->date_of_birth?->format('Y-m-d') ?? '')); ?>">
                <?php $__errorArgs = ['date_of_birth'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback d-block"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div class="col-md-4">
                <label class="form-label">Nationalité</label>
                <input type="text" name="nationality" class="form-control" value="<?php echo e(old('nationality', $client->nationality ?? '')); ?>" placeholder="ex. Maroc">
            </div>
            <div class="col-md-6">
                <label class="form-label">Langue préférée</label>
                <select name="preferred_language" class="form-select">
                    <option value="fr" <?php echo e(old('preferred_language', $client->preferred_language ?? 'fr') === 'fr' ? 'selected' : ''); ?>>Français</option>
                    <option value="en" <?php echo e(old('preferred_language', $client->preferred_language ?? '') === 'en' ? 'selected' : ''); ?>>English</option>
                    <option value="ar" <?php echo e(old('preferred_language', $client->preferred_language ?? '') === 'ar' ? 'selected' : ''); ?>>ا�"عرب�Sة</option>
                </select>
            </div>
        </div>
    </div>
</div>


<div class="card mb-3">
    <div class="card-header bg-light">
        <h5 class="mb-0">Contact</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" value="<?php echo e(old('email', $client->email ?? '')); ?>">
                <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div class="col-md-6">
                <label class="form-label">Téléphone</label>
                <input type="text" name="phone" class="form-control" value="<?php echo e(old('phone', $client->phone ?? '')); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Téléphone secondaire</label>
                <input type="text" name="phone_alt" class="form-control" value="<?php echo e(old('phone_alt', $client->phone_alt ?? '')); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">WhatsApp</label>
                <input type="text" name="whatsapp_number" class="form-control" value="<?php echo e(old('whatsapp_number', $client->whatsapp_number ?? '')); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Site web</label>
                <input type="url" name="website" class="form-control" value="<?php echo e(old('website', $client->website ?? '')); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Préférence de contact</label>
                <select name="contact_method_preference" class="form-select">
                    <option value="">�?"</option>
                    <option value="phone" <?php echo e(old('contact_method_preference', $client->contact_method_preference ?? '') === 'phone' ? 'selected' : ''); ?>>Téléphone</option>
                    <option value="email" <?php echo e(old('contact_method_preference', $client->contact_method_preference ?? '') === 'email' ? 'selected' : ''); ?>>Email</option>
                    <option value="whatsapp" <?php echo e(old('contact_method_preference', $client->contact_method_preference ?? '') === 'whatsapp' ? 'selected' : ''); ?>>WhatsApp</option>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label">Adresse ligne 1</label>
                <input type="text" name="address_line_1" class="form-control" value="<?php echo e(old('address_line_1', $client->address_line_1 ?? '')); ?>">
            </div>
            <div class="col-12">
                <label class="form-label">Adresse ligne 2</label>
                <input type="text" name="address_line_2" class="form-control" value="<?php echo e(old('address_line_2', $client->address_line_2 ?? '')); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Ville</label>
                <input type="text" name="city" class="form-control" value="<?php echo e(old('city', $client->city ?? '')); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Pays de résidence</label>
                <input type="text" name="country_of_residence" class="form-control" value="<?php echo e(old('country_of_residence', $client->country_of_residence ?? '')); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Code postal</label>
                <input type="text" name="postal_code" class="form-control" value="<?php echo e(old('postal_code', $client->postal_code ?? '')); ?>">
            </div>
        </div>
    </div>
</div>


<div class="card mb-3">
    <div class="card-header bg-light">
        <h5 class="mb-0">Documents</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">N° CIN / Pièce d'identité</label>
                <input type="text" name="national_id_number" class="form-control" value="<?php echo e(old('national_id_number', $client->national_id_number ?? '')); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">N° Passeport</label>
                <input type="text" name="passport_number" class="form-control <?php $__errorArgs = ['passport_number'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" value="<?php echo e(old('passport_number', $client->passport_number ?? '')); ?>">
                <?php $__errorArgs = ['passport_number'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div class="col-md-6">
                <label class="form-label">Pays d'émission passeport</label>
                <input type="text" name="passport_issue_country" class="form-control" value="<?php echo e(old('passport_issue_country', $client->passport_issue_country ?? '')); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Date émission</label>
                <input type="date" name="passport_issue_date" class="form-control" value="<?php echo e(old('passport_issue_date', $client->passport_issue_date?->format('Y-m-d') ?? '')); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Date expiration</label>
                <input type="date" name="passport_expiry_date" class="form-control" value="<?php echo e(old('passport_expiry_date', $client->passport_expiry_date?->format('Y-m-d') ?? '')); ?>">
                <?php $__errorArgs = ['passport_expiry_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback d-block"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div class="col-md-4">
                <label class="form-label d-block">Visa requis</label>
                <div class="form-check form-check-inline">
                    <input type="hidden" name="visa_required" value="0">
                    <input type="checkbox" name="visa_required" class="form-check-input" value="1" <?php echo e(old('visa_required', $client->visa_required ?? false) ? 'checked' : ''); ?>>
                    <label class="form-check-label">Oui</label>
                </div>
            </div>
            <div class="col-md-4">
                <label class="form-label">Statut visa</label>
                <select name="visa_status" class="form-select">
                    <option value="not_required" <?php echo e(old('visa_status', $client->visa_status ?? 'not_required') === 'not_required' ? 'selected' : ''); ?>>Non requis</option>
                    <option value="pending" <?php echo e(old('visa_status', $client->visa_status ?? '') === 'pending' ? 'selected' : ''); ?>>En attente</option>
                    <option value="approved" <?php echo e(old('visa_status', $client->visa_status ?? '') === 'approved' ? 'selected' : ''); ?>>Approuvé</option>
                    <option value="rejected" <?php echo e(old('visa_status', $client->visa_status ?? '') === 'rejected' ? 'selected' : ''); ?>>Refusé</option>
                </select>
            </div>
        </div>
    </div>
</div>


<div class="card mb-3">
    <div class="card-header bg-light">
        <h5 class="mb-0">Préférences voyage</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Catégorie voyageur</label>
                <select name="traveler_category" class="form-select">
                    <option value="">�?"</option>
                    <option value="solo" <?php echo e(old('traveler_category', $client->traveler_category ?? '') === 'solo' ? 'selected' : ''); ?>>Solo</option>
                    <option value="couple" <?php echo e(old('traveler_category', $client->traveler_category ?? '') === 'couple' ? 'selected' : ''); ?>>Couple</option>
                    <option value="family" <?php echo e(old('traveler_category', $client->traveler_category ?? '') === 'family' ? 'selected' : ''); ?>>Famille</option>
                    <option value="group" <?php echo e(old('traveler_category', $client->traveler_category ?? '') === 'group' ? 'selected' : ''); ?>>Groupe</option>
                    <option value="business" <?php echo e(old('traveler_category', $client->traveler_category ?? '') === 'business' ? 'selected' : ''); ?>>Business</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Ville de départ préférée</label>
                <input type="text" name="preferred_departure_city" class="form-control" value="<?php echo e(old('preferred_departure_city', $client->preferred_departure_city ?? '')); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Destination préférée</label>
                <input type="text" name="preferred_destination" class="form-control" value="<?php echo e(old('preferred_destination', $client->preferred_destination ?? '')); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Mois de voyage préféré</label>
                <input type="text" name="preferred_travel_month" class="form-control" value="<?php echo e(old('preferred_travel_month', $client->preferred_travel_month ?? '')); ?>" placeholder="ex. Juillet">
            </div>
            <div class="col-md-4">
                <label class="form-label">Budget min (DH)</label>
                <input type="number" name="budget_min" class="form-control" step="0.01" min="0" value="<?php echo e(old('budget_min', $client->budget_min ?? '')); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Budget max (DH)</label>
                <input type="number" name="budget_max" class="form-control" step="0.01" min="0" value="<?php echo e(old('budget_max', $client->budget_max ?? '')); ?>">
                <?php $__errorArgs = ['budget_max'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback d-block"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div class="col-12">
                <label class="form-label">Demandes spéciales</label>
                <textarea name="special_requests" class="form-control" rows="2"><?php echo e(old('special_requests', $client->special_requests ?? '')); ?></textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label">Notes médicales</label>
                <textarea name="medical_notes" class="form-control" rows="2"><?php echo e(old('medical_notes', $client->medical_notes ?? '')); ?></textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label">Régime alimentaire</label>
                <textarea name="dietary_requirements" class="form-control" rows="2"><?php echo e(old('dietary_requirements', $client->dietary_requirements ?? '')); ?></textarea>
            </div>
        </div>
    </div>
</div>


<div class="card mb-3">
    <div class="card-header bg-light">
        <h5 class="mb-0">Contact d'urgence</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Nom</label>
                <input type="text" name="emergency_contact_name" class="form-control" value="<?php echo e(old('emergency_contact_name', $client->emergency_contact_name ?? '')); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Téléphone</label>
                <input type="text" name="emergency_contact_phone" class="form-control" value="<?php echo e(old('emergency_contact_phone', $client->emergency_contact_phone ?? '')); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Lien</label>
                <input type="text" name="emergency_contact_relation" class="form-control" value="<?php echo e(old('emergency_contact_relation', $client->emergency_contact_relation ?? '')); ?>" placeholder="ex. Conjoint, Parent">
            </div>
        </div>
    </div>
</div>


<div class="card mb-3">
    <div class="card-header bg-light">
        <h5 class="mb-0">Société & Facturation</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Raison sociale</label>
                <input type="text" name="company_name" class="form-control" value="<?php echo e(old('company_name', $client->company_name ?? '')); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">N° registre / IF</label>
                <input type="text" name="company_registration_number" class="form-control" value="<?php echo e(old('company_registration_number', $client->company_registration_number ?? '')); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">N° TVA / Taxe</label>
                <input type="text" name="tax_number" class="form-control" value="<?php echo e(old('tax_number', $client->tax_number ?? '')); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Contact société</label>
                <input type="text" name="company_contact_person" class="form-control" value="<?php echo e(old('company_contact_person', $client->company_contact_person ?? '')); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Nom facturation</label>
                <input type="text" name="billing_name" class="form-control" value="<?php echo e(old('billing_name', $client->billing_name ?? '')); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Email facturation</label>
                <input type="email" name="billing_email" class="form-control" value="<?php echo e(old('billing_email', $client->billing_email ?? '')); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Téléphone facturation</label>
                <input type="text" name="billing_phone" class="form-control" value="<?php echo e(old('billing_phone', $client->billing_phone ?? '')); ?>">
            </div>
            <div class="col-12">
                <label class="form-label">Adresse facturation</label>
                <textarea name="billing_address" class="form-control" rows="2"><?php echo e(old('billing_address', $client->billing_address ?? '')); ?></textarea>
            </div>
            <div class="col-md-4">
                <label class="form-label">Ville</label>
                <input type="text" name="billing_city" class="form-control" value="<?php echo e(old('billing_city', $client->billing_city ?? '')); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Pays</label>
                <input type="text" name="billing_country" class="form-control" value="<?php echo e(old('billing_country', $client->billing_country ?? '')); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Code postal</label>
                <input type="text" name="billing_postal_code" class="form-control" value="<?php echo e(old('billing_postal_code', $client->billing_postal_code ?? '')); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Conditions de paiement</label>
                <input type="text" name="payment_terms" class="form-control" value="<?php echo e(old('payment_terms', $client->payment_terms ?? '')); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Plafond crédit (DH)</label>
                <input type="number" name="credit_limit" class="form-control" step="0.01" min="0" value="<?php echo e(old('credit_limit', $client->credit_limit ?? '')); ?>">
            </div>
        </div>
    </div>
</div>


<div class="card mb-3">
    <div class="card-header bg-light">
        <h5 class="mb-0">Relation client & interne</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <div class="form-check">
                    <input type="hidden" name="newsletter_opt_in" value="0">
                    <input type="checkbox" name="newsletter_opt_in" class="form-check-input" value="1" <?php echo e(old('newsletter_opt_in', $client->newsletter_opt_in ?? false) ? 'checked' : ''); ?>>
                    <label class="form-check-label">Newsletter</label>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-check">
                    <input type="hidden" name="sms_opt_in" value="0">
                    <input type="checkbox" name="sms_opt_in" class="form-check-input" value="1" <?php echo e(old('sms_opt_in', $client->sms_opt_in ?? false) ? 'checked' : ''); ?>>
                    <label class="form-check-label">SMS</label>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-check">
                    <input type="hidden" name="whatsapp_opt_in" value="0">
                    <input type="checkbox" name="whatsapp_opt_in" class="form-check-input" value="1" <?php echo e(old('whatsapp_opt_in', $client->whatsapp_opt_in ?? false) ? 'checked' : ''); ?>>
                    <label class="form-check-label">WhatsApp</label>
                </div>
            </div>
            <div class="col-md-4">
                <label class="form-label">Points fidélité</label>
                <input type="number" name="loyalty_points" class="form-control" min="0" value="<?php echo e(old('loyalty_points', $client->loyalty_points ?? 0)); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Dernier contact</label>
                <input type="datetime-local" name="last_contacted_at" class="form-control" value="<?php echo e(old('last_contacted_at', $client->last_contacted_at ? $client->last_contacted_at->format('Y-m-d\TH:i') : '')); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Prochain suivi</label>
                <input type="datetime-local" name="next_follow_up_at" class="form-control" value="<?php echo e(old('next_follow_up_at', $client->next_follow_up_at ? $client->next_follow_up_at->format('Y-m-d\TH:i') : '')); ?>">
            </div>
            <div class="col-12">
                <label class="form-label">Notes internes</label>
                <textarea name="internal_notes" class="form-control" rows="4"><?php echo e(old('internal_notes', $client->internal_notes ?? '')); ?></textarea>
            </div>
            <div class="col-12">
                <label class="form-label">Raison blacklist (si bloqué)</label>
                <textarea name="blacklist_reason" class="form-control" rows="2"><?php echo e(old('blacklist_reason', $client->blacklist_reason ?? '')); ?></textarea>
            </div>
        </div>
    </div>
</div>

<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\customers\clients\_form.blade.php ENDPATH**/ ?>