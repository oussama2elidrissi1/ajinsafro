<?php $__env->startSection('title', 'Profil'); ?>

<?php $__env->startSection('content'); ?>
<div class="mb-6 flex items-end justify-between gap-4">
    <div>
        <h1 class="text-2xl sm:text-3xl font-bold text-[#0e3a5a]">Mon profil</h1>
        <p class="text-sm text-gray-500 mt-1">Informations du compte partenaire (lecture). Les modifications sensibles se font via le siège.</p>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    <div class="xl:col-span-1 space-y-6">
        <div class="bg-white p-6 rounded-2xl shadow-custom border border-gray-100 text-center">
            <img src="<?php echo e(auth()->user()->avatar_url); ?>" alt="Avatar" class="w-28 h-28 rounded-full object-cover border-4 border-[#e6f3fa] shadow-sm mx-auto mb-4">
            <h3 class="font-bold text-[#0e3a5a] text-xl"><?php echo e(auth()->user()->name); ?></h3>
            <p class="text-xs font-bold text-[#f37a1f] uppercase tracking-wider mt-1 mb-4"><?php echo e($partner?->partner_type_label ?? 'Partenaire'); ?></p>
            <div class="text-left space-y-2">
                <div class="flex items-center gap-3 text-sm text-gray-600 bg-gray-50 p-3 rounded-xl border border-gray-100">
                    <span class="font-medium">Email: <?php echo e(auth()->user()->email); ?></span>
                </div>
                <div class="flex items-center gap-3 text-sm text-gray-600 bg-gray-50 p-3 rounded-xl border border-gray-100">
                    <span class="font-medium">Téléphone: <?php echo e($partner?->telephone ?? auth()->user()->phone ?? '—'); ?></span>
                </div>
                <div class="flex items-center gap-3 text-sm text-gray-600 bg-gray-50 p-3 rounded-xl border border-gray-100">
                    <span class="font-medium">Ville: <?php echo e($partner?->ville ?? '—'); ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="xl:col-span-2 space-y-6">
        <div class="bg-white rounded-2xl shadow-custom border border-gray-100 overflow-hidden">
            <div class="p-5 border-b border-gray-100 bg-gray-50/50">
                <h3 class="font-bold text-[#0e3a5a]">Entreprise</h3>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5 text-sm">
                <div>
                    <div class="text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Raison sociale</div>
                    <div class="font-semibold text-gray-800"><?php echo e($partner?->raison_sociale ?? '—'); ?></div>
                </div>
                <div>
                    <div class="text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Nom commercial</div>
                    <div class="font-semibold text-gray-800"><?php echo e($partner?->nom_commercial ?? '—'); ?></div>
                </div>
                <div>
                    <div class="text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Responsable</div>
                    <div class="font-semibold text-gray-800"><?php echo e($partner?->nom_responsable ?? '—'); ?></div>
                </div>
                <div>
                    <div class="text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Statut</div>
                    <div class="font-semibold text-gray-800"><?php echo e($partner?->status ?? '—'); ?></div>
                </div>
                <div class="md:col-span-2">
                    <div class="text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Adresse</div>
                    <div class="font-semibold text-gray-800"><?php echo e($partner?->adresse ?? '—'); ?></div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('partner_v2.layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\partner\v2\profile\show.blade.php ENDPATH**/ ?>