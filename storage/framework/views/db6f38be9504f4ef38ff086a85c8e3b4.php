<?php $__env->startSection('title', 'Catalogue voyages'); ?>

<?php $__env->startSection('content'); ?>
<div class="mb-6">
    <h1 class="text-2xl sm:text-3xl font-bold text-[#0e3a5a]">Catalogue voyages</h1>
    <p class="text-sm text-gray-500 mt-1">Voyages que vous pouvez proposer et vendre. Prix public et commission applicables.</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 lg:gap-6">
    <?php $__empty_1 = true; $__currentLoopData = $voyages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $voyage): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <div class="bg-white rounded-2xl shadow-custom border border-gray-100 overflow-hidden flex flex-col">
            <div class="p-6 flex-1">
                <h3 class="font-bold text-[#0e3a5a] text-base line-clamp-2"><?php echo e($voyage->name); ?></h3>
                <?php if($voyage->destination): ?>
                    <p class="text-xs text-gray-500 mt-1"><?php echo e($voyage->destination); ?></p>
                <?php endif; ?>

                <div class="mt-4 grid grid-cols-2 gap-3">
                    <div class="bg-gray-50 border border-gray-100 rounded-xl p-3">
                        <div class="text-[10px] font-bold text-gray-500 uppercase tracking-wider">Prix public</div>
                        <div class="text-sm font-black text-[#0e3a5a] mt-1"><?php echo e($voyage->catalog_public_price_display ?? '—'); ?></div>
                    </div>
                    <div class="bg-[#e6f3fa]/60 border border-[#e6f3fa] rounded-xl p-3">
                        <div class="text-[10px] font-bold text-gray-500 uppercase tracking-wider">Commission</div>
                        <div class="text-sm font-black text-green-700 mt-1"><?php echo e($voyage->catalog_commission_display ?? '—'); ?></div>
                    </div>
                </div>
            </div>
            <div class="p-5 border-t border-gray-100 bg-gray-50/40 flex items-center justify-between">
                <a href="<?php echo e(route('partner.reservations.create')); ?>?tour_id=<?php echo e($voyage->id); ?>"
                   class="bg-[#0083c4] hover:bg-[#0e3a5a] text-white px-5 py-2 rounded-xl text-xs font-bold transition-colors">
                    Réserver
                </a>
                <span class="text-[10px] font-bold text-gray-400">ID: <?php echo e($voyage->id); ?></span>
            </div>
        </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <div class="col-span-full">
            <div class="bg-white rounded-2xl shadow-custom border border-gray-100 p-6 text-gray-600">
                Aucun voyage disponible pour le moment.
            </div>
        </div>
    <?php endif; ?>
</div>

<div class="mt-6">
    <?php echo e($voyages->links()); ?>

</div>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('partner_v2.layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\partner\v2\catalogue\index.blade.php ENDPATH**/ ?>