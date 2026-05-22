<?php
    $paymentMethodOptions = $paymentMethodOptions ?? \App\Services\BusinessReferentialService::paymentMethods();
    $ref = $businessReferentials ?? \App\Services\BusinessReferentialService::allMerged();
    $discountScopes = $ref['discount_scopes'] ?? [];
    $discountConditions = $ref['discount_conditions'] ?? [];
    $discountTypes = $ref['discount_types'] ?? [];
    $discountRules = $discountRules ?? collect();
    $paymentMethodsRefUrl = \Illuminate\Support\Facades\Route::has('admin.settings.referentiels-metier.group')
        ? route('admin.settings.referentiels-metier.group', ['groupKey' => 'payment_methods'])
        : (\Illuminate\Support\Facades\Route::has('admin.settings.index') ? route('admin.settings.index') : '#');
?>
<div class="tab-pane" id="price" role="tabpanel" data-ve-pane-title="Tarifs">
    <div class="card ve-pane-card mb-3">
        <div class="card-body">
            <p class="text-uppercase text-muted small fw-bold mb-2">Prix de base</p>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label" for="min_price">Prix minimum (MAD)</label>
                    <input type="number" class="form-control" id="min_price" name="min_price" value="<?php echo e(old('min_price', $meta['min_price'] ?? '')); ?>" step="0.01" min="0">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="base_price">Prix de base (MAD)</label>
                    <input type="number" class="form-control" id="base_price" name="base_price" value="<?php echo e(old('base_price', $meta['base_price'] ?? '')); ?>" step="0.01" min="0">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="sale_price">Prix soldé (MAD)</label>
                    <input type="number" class="form-control" id="sale_price" name="sale_price" value="<?php echo e(old('sale_price', $meta['sale_price'] ?? '')); ?>" step="0.01" min="0">
                </div>
            </div>
        </div>
    </div>

    <div class="card ve-pane-card mb-3">
        <div class="card-body">
            <p class="text-uppercase text-muted small fw-bold mb-2">Tarifs par profil</p>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label" for="adult_price">Prix adulte (MAD)</label>
                    <input type="number" class="form-control" id="adult_price" name="adult_price" value="<?php echo e(old('adult_price', $meta['adult_price'] ?? '')); ?>" step="0.01" min="0">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="child_price">Prix enfant (MAD)</label>
                    <input type="number" class="form-control" id="child_price" name="child_price" value="<?php echo e(old('child_price', $meta['child_price'] ?? '')); ?>" step="0.01" min="0">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="infant_price">Prix bébé (MAD)</label>
                    <input type="number" class="form-control" id="infant_price" name="infant_price" value="<?php echo e(old('infant_price', $meta['infant_price'] ?? '')); ?>" step="0.01" min="0">
                </div>
            </div>
        </div>
    </div>

    <div class="card ve-pane-card mb-3" id="ve-discount-rules-block">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div>
                    <p class="text-uppercase text-muted small fw-bold mb-0">Réductions</p>
                    <p class="text-muted small mb-0">Règles métier (priorité croissante : la plus forte priorité peut être appliquée en premier selon votre moteur de calcul).</p>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary" id="ve-add-discount-rule">Ajouter une règle</button>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0" id="ve-discount-rules-table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Portée</th>
                            <th>Condition</th>
                            <th class="text-end">Valeur</th>
                            <th class="text-center">Priorité</th>
                            <th class="text-center">Actif</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $discountRules; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $rule): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr class="ve-discount-rule-row">
                                <td>
                                    <select class="form-select form-select-sm" name="discount_rules[<?php echo e($i); ?>][reduction_type]">
                                        <?php $__currentLoopData = $discountTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($dt['value']); ?>" <?php if(($rule->reduction_type ?? '') === $dt['value']): echo 'selected'; endif; ?>><?php echo e($dt['label']); ?></option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select>
                                </td>
                                <td>
                                    <select class="form-select form-select-sm" name="discount_rules[<?php echo e($i); ?>][scope]">
                                        <?php $__currentLoopData = $discountScopes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($sc['value']); ?>" <?php if(($rule->scope ?? '') === $sc['value']): echo 'selected'; endif; ?>><?php echo e($sc['label']); ?></option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select>
                                </td>
                                <td>
                                    <select class="form-select form-select-sm" name="discount_rules[<?php echo e($i); ?>][condition_type]">
                                        <?php $__currentLoopData = $discountConditions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($c['value']); ?>" <?php if(($rule->condition_type ?? '') === $c['value']): echo 'selected'; endif; ?>><?php echo e($c['label']); ?></option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select>
                                </td>
                                <td style="max-width:110px">
                                    <input type="number" step="0.01" class="form-control form-control-sm text-end" name="discount_rules[<?php echo e($i); ?>][value]" value="<?php echo e(old('discount_rules.'.$i.'.value', $rule->value)); ?>">
                                </td>
                                <td style="max-width:90px">
                                    <input type="number" class="form-control form-control-sm text-center" name="discount_rules[<?php echo e($i); ?>][priority]" value="<?php echo e(old('discount_rules.'.$i.'.priority', $rule->priority ?? 100)); ?>">
                                </td>
                                <td class="text-center">
                                    <input type="hidden" name="discount_rules[<?php echo e($i); ?>][is_active]" value="0">
                                    <input type="checkbox" class="form-check-input" name="discount_rules[<?php echo e($i); ?>][is_active]" value="1" <?php if(old('discount_rules.'.$i.'.is_active', $rule->is_active ?? true)): echo 'checked'; endif; ?>>
                                </td>
                                <td><button type="button" class="btn btn-sm btn-outline-danger ve-remove-discount-rule">�-</button></td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card ve-pane-card mb-3">
        <div class="card-body">
            <p class="text-uppercase text-muted small fw-bold mb-2">Commissions</p>
            <div class="row g-3 align-items-end">
                <?php
                    $adultCommissionType = old('commission_adulte_type', $meta['commission_adulte_type'] ?? 'fixed');
                    $childCommissionType = old('commission_enfant_type', $meta['commission_enfant_type'] ?? 'fixed');
                ?>
                <div class="col-md-5">
                    <label class="form-label" for="commission_adulte">Commission adulte</label>
                    <div class="input-group">
                        <input type="number" class="form-control" id="commission_adulte" name="commission_adulte" value="<?php echo e(old('commission_adulte', $meta['commission_adulte'] ?? '')); ?>" step="0.01" min="0" placeholder="0">
                        <div class="input-group-text p-0 border-0">
                            <div class="btn-group" role="group" aria-label="Type commission adulte">
                                <input type="radio" class="btn-check" name="commission_adulte_type" id="commission_adulte_type_percentage" value="percentage" autocomplete="off" <?php echo e($adultCommissionType === 'percentage' ? 'checked' : ''); ?>>
                                <label class="btn btn-outline-secondary" for="commission_adulte_type_percentage" style="border-radius:0;">%</label>
                                <input type="radio" class="btn-check" name="commission_adulte_type" id="commission_adulte_type_fixed" value="fixed" autocomplete="off" <?php echo e($adultCommissionType === 'fixed' ? 'checked' : ''); ?>>
                                <label class="btn btn-outline-secondary" for="commission_adulte_type_fixed">MAD</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-5">
                    <label class="form-label" for="commission_enfant">Commission enfant</label>
                    <div class="input-group">
                        <input type="number" class="form-control" id="commission_enfant" name="commission_enfant" value="<?php echo e(old('commission_enfant', $meta['commission_enfant'] ?? '')); ?>" step="0.01" min="0" placeholder="0">
                        <div class="input-group-text p-0 border-0">
                            <div class="btn-group" role="group" aria-label="Type commission enfant">
                                <input type="radio" class="btn-check" name="commission_enfant_type" id="commission_enfant_type_percentage" value="percentage" autocomplete="off" <?php echo e($childCommissionType === 'percentage' ? 'checked' : ''); ?>>
                                <label class="btn btn-outline-secondary" for="commission_enfant_type_percentage" style="border-radius:0;">%</label>
                                <input type="radio" class="btn-check" name="commission_enfant_type" id="commission_enfant_type_fixed" value="fixed" autocomplete="off" <?php echo e($childCommissionType === 'fixed' ? 'checked' : ''); ?>>
                                <label class="btn btn-outline-secondary" for="commission_enfant_type_fixed">MAD</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card ve-pane-card mb-3">
        <div class="card-body">
            <p class="text-uppercase text-muted small fw-bold mb-2">Moyens de paiement</p>
            <p class="text-muted small mb-3">Liste pilotée depuis <a href="<?php echo e($paymentMethodsRefUrl); ?>">Référence métier �?' Moyens de paiement</a>.</p>
            <div class="row g-2">
                <?php $__currentLoopData = $paymentMethodOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $pm): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php $mk = $pm['meta_key']; ?>
                    <div class="col-md-4">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="<?php echo e($mk); ?>" name="<?php echo e($mk); ?>" value="1" <?php echo e(old($mk, $meta[$mk] ?? '') === 'on' ? 'checked' : ''); ?>>
                            <label class="form-check-label" for="<?php echo e($mk); ?>"><?php echo e($pm['label']); ?></label>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const tbody = document.querySelector('#ve-discount-rules-table tbody');
    const addBtn = document.getElementById('ve-add-discount-rule');
    if (!tbody || !addBtn) return;
    const scopes = <?php echo json_encode($discountScopes, 15, 512) ?>;
    const conds = <?php echo json_encode($discountConditions, 15, 512) ?>;
    const types = <?php echo json_encode($discountTypes, 15, 512) ?>;
    let idx = tbody.querySelectorAll('tr').length;
    function addRow() {
        const tr = document.createElement('tr');
        tr.className = 've-discount-rule-row';
        tr.innerHTML = '<td><select class="form-select form-select-sm" name="discount_rules['+idx+'][reduction_type]">' +
            types.map(o => '<option value="'+o.value+'">'+o.label+'</option>').join('') + '</select></td>' +
            '<td><select class="form-select form-select-sm" name="discount_rules['+idx+'][scope]">' +
            scopes.map(o => '<option value="'+o.value+'">'+o.label+'</option>').join('') + '</select></td>' +
            '<td><select class="form-select form-select-sm" name="discount_rules['+idx+'][condition_type]">' +
            conds.map(o => '<option value="'+o.value+'">'+o.label+'</option>').join('') + '</select></td>' +
            '<td><input type="number" step="0.01" class="form-control form-control-sm text-end" name="discount_rules['+idx+'][value]" value="0"></td>' +
            '<td><input type="number" class="form-control form-control-sm text-center" name="discount_rules['+idx+'][priority]" value="100"></td>' +
            '<td class="text-center"><input type="hidden" name="discount_rules['+idx+'][is_active]" value="0">' +
            '<input type="checkbox" class="form-check-input" name="discount_rules['+idx+'][is_active]" value="1" checked></td>' +
            '<td><button type="button" class="btn btn-sm btn-outline-danger ve-remove-discount-rule">�-</button></td>';
        tbody.appendChild(tr);
        idx++;
    }
    addBtn.addEventListener('click', addRow);
    tbody.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('ve-remove-discount-rule')) {
            const row = e.target.closest('tr');
            if (row) row.remove();
        }
    });
})();
</script>

<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\circuits\voyages\partials\tabs\_pricing.blade.php ENDPATH**/ ?>