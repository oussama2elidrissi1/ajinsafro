<?php $modalAjax = $modalAjax ?? false; ?>
<div class="card border shadow-sm">
    <div class="card-header bg-white py-3 border-bottom">
        <h5 class="mb-0"><i class="bx bx-cog me-1 text-primary"></i> Paramètres du départ</h5>
    </div>
    <div class="card-body">
        <form method="post" action="<?php echo e(route('admin.circuits.voyages.departures.settings.update', [$voyage, $departure])); ?>">
            <?php echo csrf_field(); ?>
            <?php echo method_field('PUT'); ?>
            <?php echo $__env->make('admin.circuits.voyages.departures.partials._modal_ajax_hidden', ['modalAjax' => $modalAjax], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
            <input type="hidden" name="redirect_to" value="<?php echo e(request()->fullUrl()); ?>">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Date début <span class="text-danger">*</span></label>
                    <input type="date" name="start_date" class="form-control" required
                           value="<?php echo e(old('start_date', optional($departure->start_date)->format('Y-m-d'))); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date fin</label>
                    <input type="date" name="end_date" class="form-control"
                           value="<?php echo e(old('end_date', optional($departure->end_date)->format('Y-m-d'))); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Statut <span class="text-danger">*</span></label>
                    <select name="status" class="form-select" required>
                        <?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $st): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($st); ?>" <?php echo e(old('status', $departure->status) === $st ? 'selected' : ''); ?>><?php echo e(\App\Models\Departure::make(['status' => $st])->status_label); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="col-md-3">
                      <label class="form-label">Places restantes (calculées)</label>
                      <input type="number" class="form-control" min="0"
                          value="<?php echo e((int) ($departure->available_capacity ?? 0)); ?>" readonly>
                      <small class="text-muted">Valeur calculée automatiquement depuis capacité totale et réservations actives.</small>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Capacité totale</label>
                    <input type="number" name="total_capacity" class="form-control" min="0"
                           value="<?php echo e(old('total_capacity', $departure->total_capacity)); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Prix de base (MAD)</label>
                    <input type="number" name="base_price" class="form-control" min="0" step="0.01"
                           value="<?php echo e(old('base_price', $departure->base_price)); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Prix promo (MAD)</label>
                    <input type="number" name="sale_price" class="form-control" min="0" step="0.01"
                           value="<?php echo e(old('sale_price', $departure->sale_price)); ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="3" placeholder="Notes internes�?�"><?php echo e(old('notes', $departure->notes)); ?></textarea>
                </div>
            </div>
            <div class="mt-3 text-end">
                <button type="submit" class="btn btn-primary"><i class="bx bx-save"></i> Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\circuits\voyages\departures\partials\_settings_card.blade.php ENDPATH**/ ?>