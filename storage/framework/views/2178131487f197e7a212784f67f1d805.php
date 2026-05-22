<form id="calendar-filters" class="space-y-4 mb-6">
    <input type="hidden" name="date_from" id="ajin-cal-date-from" value="<?php echo e($dateFrom ?? ''); ?>">
    <input type="hidden" name="date_to" id="ajin-cal-date-to" value="<?php echo e($dateTo ?? ''); ?>">

    <div class="bg-white p-3 sm:p-4 rounded-2xl shadow-[0_4px_20px_rgba(0,0,0,0.08)] border border-gray-100 flex flex-wrap items-center gap-3">
        <div class="flex-1 min-w-[200px] w-full sm:w-auto relative shrink-0">
            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
            <input type="text" name="search" value="<?php echo e($search ?? ''); ?>" placeholder="Rechercher (client, voyage, ref�?�)"
                   class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-100 rounded-xl text-sm focus:outline-none focus:border-[#0083c4] focus:bg-white transition-colors text-[#0e3a5a] font-medium placeholder-gray-400">
        </div>

        <div class="w-full sm:w-auto min-w-[180px]">
            <select name="voyage" id="filter-voyage"
                    class="w-full bg-gray-50 border border-gray-100 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-[#0083c4] text-[#0e3a5a] font-medium cursor-pointer">
                <option value="">Tous les voyages</option>
                <?php $__currentLoopData = $voyages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($v->id); ?>" <?php echo e((int)($selectedVoyageId ?? 0) === (int)$v->id ? 'selected' : ''); ?>><?php echo e(Str::limit($v->name, 42)); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>

        <div class="relative flex items-center bg-gray-50 border border-gray-100 rounded-xl px-4 py-2 focus-within:border-[#0083c4] focus-within:bg-white transition-colors flex-1 min-w-[220px] sm:flex-none">
            <i class="far fa-calendar-alt text-[#0083c4] mr-2"></i>
            <input type="text" id="ajin-cal-range" placeholder="Période (optionnel)�?�" autocomplete="off"
                   class="bg-transparent border-none outline-none text-[#0e3a5a] font-medium text-sm w-full min-w-0 cursor-pointer placeholder-gray-400">
        </div>

        <button type="button" id="btn-apply-filters"
                class="bg-[#0083c4] hover:opacity-95 text-white px-5 py-2.5 rounded-xl text-sm font-bold transition-all shadow-sm flex items-center justify-center gap-2 flex-1 sm:flex-none">
            <i class="fas fa-filter"></i> Filtrer
        </button>

        <a href="<?php echo e(route('admin.reservations.calendrier')); ?>"
           class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl border border-gray-200 text-sm font-semibold text-gray-600 hover:bg-gray-50 transition-colors">
            Réinitialiser
        </a>
    </div>

    <details class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden group">
        <summary class="px-4 py-3 cursor-pointer text-sm font-bold text-[#0e3a5a] list-none flex items-center justify-between [&::-webkit-details-marker]:hidden">
            <span><i class="fas fa-sliders-h text-[#0083c4] mr-2"></i> Filtres avancés</span>
            <i class="fas fa-chevron-down text-gray-400 group-open:rotate-180 transition-transform"></i>
        </summary>
        <div class="px-4 pb-4 pt-0 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3 border-t border-gray-100">
            <div>
                <label for="filter-destination" class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Destination</label>
                <select name="destination" id="filter-destination"
                        class="w-full bg-gray-50 border border-gray-100 rounded-xl px-3 py-2 text-sm focus:outline-none focus:border-[#0083c4]">
                    <option value="">Toutes</option>
                    <?php $__currentLoopData = $destinations ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $d): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($d); ?>" <?php echo e(($selectedDestination ?? '') === $d ? 'selected' : ''); ?>><?php echo e($d); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div>
                <label for="filter-status" class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Statut voyage</label>
                <select name="status" id="filter-status"
                        class="w-full bg-gray-50 border border-gray-100 rounded-xl px-3 py-2 text-sm focus:outline-none focus:border-[#0083c4]">
                    <option value="">Tous</option>
                    <?php $__currentLoopData = $statuses ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($s); ?>" <?php echo e(($selectedStatus ?? '') === $s ? 'selected' : ''); ?>><?php echo e($s); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div>
                <label for="filter-budget-min" class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Budget min (DH)</label>
                <input type="number" name="budget_min" id="filter-budget-min" min="0" value="<?php echo e($budgetMin ?? ''); ?>" placeholder="Min"
                       class="w-full bg-gray-50 border border-gray-100 rounded-xl px-3 py-2 text-sm focus:outline-none focus:border-[#0083c4]">
            </div>
            <div>
                <label for="filter-budget-max" class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Budget max (DH)</label>
                <input type="number" name="budget_max" id="filter-budget-max" min="0" value="<?php echo e($budgetMax ?? ''); ?>" placeholder="Max"
                       class="w-full bg-gray-50 border border-gray-100 rounded-xl px-3 py-2 text-sm focus:outline-none focus:border-[#0083c4]">
            </div>
        </div>
    </details>
</form>

<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\reservations\calendrier\partials\filters.blade.php ENDPATH**/ ?>