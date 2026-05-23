<?php $__env->startSection('title', 'Documents'); ?>

<?php $__env->startSection('content'); ?>
<div class="mb-6">
    <h1 class="text-2xl sm:text-3xl font-bold text-[#0e3a5a]">Documents</h1>
    <p class="text-sm text-gray-500 mt-1">Contrat partenaire, grilles, conditions et supports (uniquement vos documents).</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 lg:gap-6">
    <?php $__empty_1 = true; $__currentLoopData = $documents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $doc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <div class="bg-white rounded-2xl shadow-custom border border-gray-100 p-5 flex items-start justify-between gap-4">
            <div class="min-w-0">
                <div class="text-xs font-bold text-gray-500 uppercase tracking-wider"><?php echo e($typeLabels[$doc->type] ?? $doc->type); ?></div>
                <div class="font-bold text-[#0e3a5a] mt-1 truncate"><?php echo e($doc->name ?: 'Document'); ?></div>
                <div class="text-[11px] text-gray-400 font-semibold mt-1 truncate"><?php echo e($doc->file_path); ?></div>
            </div>
            <a href="<?php echo e(asset('storage/' . $doc->file_path)); ?>" target="_blank" rel="noopener"
               class="bg-[#0083c4] hover:bg-[#0e3a5a] text-white px-4 py-2 rounded-xl text-xs font-bold transition-colors shrink-0">
                Télécharger
            </a>
        </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <div class="col-span-full">
            <div class="bg-white rounded-2xl shadow-custom border border-gray-100 p-6 text-gray-600">
                Aucun document disponible pour le moment.
            </div>
        </div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('partner_v2.layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\partner\v2\documents\index.blade.php ENDPATH**/ ?>