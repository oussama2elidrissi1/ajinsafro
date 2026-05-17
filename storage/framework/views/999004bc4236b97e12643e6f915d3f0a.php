<?php
    $veOld = old('voyage_extras');
    if ($veOld !== null && is_array($veOld)) {
        $veRows = $veOld;
    } elseif (isset($voyageExtras) && $voyageExtras->isNotEmpty()) {
        $veRows = $voyageExtras->map(function ($e) {
            return [
                'id' => $e->id,
                'name' => $e->name,
                'description' => $e->description,
                'price_adult' => $e->price_adult,
                'price_child' => $e->price_child,
                'is_active' => $e->is_active ? '1' : '',
                'extra_type' => $e->extra_type,
                'icon' => $e->icon ?: 'fa-plus-circle',
            ];
        })->values()->all();
    } else {
        $veRows = [];
    }
    if ($veRows === []) {
        $veRows = [['id' => '', 'name' => '', 'description' => '', 'price_adult' => '', 'price_child' => '', 'is_active' => '1', 'extra_type' => '', 'icon' => 'fa-plus-circle']];
    }
?>
<div class="table-responsive">
    <table class="table table-sm table-bordered align-middle" id="voyage-extras-table">
        <thead class="table-light">
            <tr>
                <th style="width:18%">Titre <span class="text-danger">*</span></th>
                <th style="width:22%">Description</th>
                <th style="width:9%">Prix adulte</th>
                <th style="width:9%">Prix enfant</th>
                <th style="width:8%">Actif</th>
                <th style="width:10%">Type</th>
                <th style="width:10%">Icône (FA)</th>
                <th style="width:6%"></th>
            </tr>
        </thead>
        <tbody id="voyage-extras-tbody">
            <?php $__currentLoopData = $veRows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $vi => $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr class="voyage-extra-row">
                    <td>
                        <input type="hidden" name="voyage_extras[<?php echo e($vi); ?>][id]" value="<?php echo e($row['id'] ?? ''); ?>">
                        <input type="text" class="form-control form-control-sm" name="voyage_extras[<?php echo e($vi); ?>][name]" value="<?php echo e($row['name'] ?? ''); ?>" placeholder="ex. Demi-pension" maxlength="255">
                    </td>
                    <td><input type="text" class="form-control form-control-sm" name="voyage_extras[<?php echo e($vi); ?>][description]" value="<?php echo e($row['description'] ?? ''); ?>" placeholder="Courte description"></td>
                    <td><input type="number" step="0.01" min="0" class="form-control form-control-sm" name="voyage_extras[<?php echo e($vi); ?>][price_adult]" value="<?php echo e($row['price_adult'] ?? ''); ?>"></td>
                    <td><input type="number" step="0.01" min="0" class="form-control form-control-sm" name="voyage_extras[<?php echo e($vi); ?>][price_child]" value="<?php echo e($row['price_child'] ?? ''); ?>"></td>
                    <td class="text-center">
                        <input type="hidden" name="voyage_extras[<?php echo e($vi); ?>][is_active]" value="0">
                        <input type="checkbox" class="form-check-input" name="voyage_extras[<?php echo e($vi); ?>][is_active]" value="1" <?php echo e(!empty($row['is_active']) ? 'checked' : ''); ?>>
                    </td>
                    <td><input type="text" class="form-control form-control-sm" name="voyage_extras[<?php echo e($vi); ?>][extra_type]" value="<?php echo e($row['extra_type'] ?? ''); ?>" placeholder="optionnel"></td>
                    <td><input type="text" class="form-control form-control-sm" name="voyage_extras[<?php echo e($vi); ?>][icon]" value="<?php echo e($row['icon'] ?? 'fa-plus-circle'); ?>" placeholder="fa-utensils"></td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-danger voyage-extra-remove" title="Supprimer">&times;</button>
                    </td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
    </table>
</div>
<button type="button" class="btn btn-sm btn-outline-primary" id="voyage-extras-add"><i class="bx bx-plus"></i> Ajouter un extra</button>

<?php $__env->startPush('scripts'); ?>
<script>
(function () {
    var tbody = document.getElementById('voyage-extras-tbody');
    var addBtn = document.getElementById('voyage-extras-add');
    if (!tbody || !addBtn) return;
    function nextIndex() {
        return tbody.querySelectorAll('tr.voyage-extra-row').length;
    }
    addBtn.addEventListener('click', function () {
        var i = nextIndex();
        var tr = document.createElement('tr');
        tr.className = 'voyage-extra-row';
        tr.innerHTML =
            '<td><input type="hidden" name="voyage_extras[' + i + '][id]" value="">' +
            '<input type="text" class="form-control form-control-sm" name="voyage_extras[' + i + '][name]" value="" placeholder="ex. Demi-pension" maxlength="255"></td>' +
            '<td><input type="text" class="form-control form-control-sm" name="voyage_extras[' + i + '][description]" value="" placeholder="Courte description"></td>' +
            '<td><input type="number" step="0.01" min="0" class="form-control form-control-sm" name="voyage_extras[' + i + '][price_adult]" value=""></td>' +
            '<td><input type="number" step="0.01" min="0" class="form-control form-control-sm" name="voyage_extras[' + i + '][price_child]" value=""></td>' +
            '<td class="text-center"><input type="hidden" name="voyage_extras[' + i + '][is_active]" value="0">' +
            '<input type="checkbox" class="form-check-input" name="voyage_extras[' + i + '][is_active]" value="1" checked></td>' +
            '<td><input type="text" class="form-control form-control-sm" name="voyage_extras[' + i + '][extra_type]" value="" placeholder="optionnel"></td>' +
            '<td><input type="text" class="form-control form-control-sm" name="voyage_extras[' + i + '][icon]" value="fa-plus-circle" placeholder="fa-utensils"></td>' +
            '<td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger voyage-extra-remove" title="Supprimer">&times;</button></td>';
        tbody.appendChild(tr);
        tr.querySelector('.voyage-extra-remove').addEventListener('click', function () { tr.remove(); });
    });
    tbody.querySelectorAll('.voyage-extra-remove').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var row = btn.closest('tr');
            if (row && tbody.querySelectorAll('tr.voyage-extra-row').length > 1) row.remove();
        });
    });
})();
</script>
<?php $__env->stopPush(); ?>
<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\circuits\voyages\partials\_voyage_extras.blade.php ENDPATH**/ ?>