<div class="bg-[#0e3a5a] text-white text-[11px] sm:text-xs py-2 w-full relative z-50">
    <div class="max-w-[1400px] mx-auto px-4 flex justify-between items-center">
        <div class="flex items-center space-x-4">
            <div class="hidden sm:flex items-center space-x-4">
                <span class="opacity-90 font-medium">Portail Partenaire Ajinsafro</span>
            </div>
        </div>
        <div class="flex items-center space-x-2">
            <div class="hidden sm:flex items-center gap-2 bg-white/5 px-3 py-1.5 rounded-lg">
                <img src="<?php echo e(auth()->user()->avatar_url); ?>" alt="Avatar" class="w-7 h-7 rounded-full border border-white/20 object-cover">
                <div class="flex flex-col leading-tight">
                    <span class="font-bold text-white text-[11px]"><?php echo e(auth()->user()->name); ?></span>
                    <span class="text-[9px] text-[#ffb300] font-semibold uppercase tracking-wider"><?php echo e(auth()->user()->partner?->partner_type_label ?? 'Partenaire'); ?></span>
                </div>
            </div>
            <a href="<?php echo e(route('logout.get')); ?>" class="bg-white/10 hover:bg-white/15 text-white px-3 py-2 rounded-lg text-[11px] font-bold transition-colors">
                Déconnexion
            </a>
        </div>
    </div>
</div>

<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\partner\v2\partials\topbar.blade.php ENDPATH**/ ?>