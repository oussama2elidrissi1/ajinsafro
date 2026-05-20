<?php
    $user = auth()->user();
    $roleLabel = $user?->getRoleNames()->first() ?? ($user?->is_admin ? 'admin' : 'utilisateur');
    $roleLabel = \Illuminate\Support\Str::title(\Illuminate\Support\Str::replace('_', ' ', (string) $roleLabel));
    $branchLabel = $user?->branch?->name;
    $brandName = \App\Models\Setting::getValue('brand_name', 'Ajinsafro');
    $brandLogo = \App\Models\Setting::brandLogoUrl('dark');
    $menuItems = $agentPortalAdminMenu ?? [];
    $dashboardActive = request()->routeIs('agent.dashboard');
    $profileActive = request()->routeIs('admin.profile.*');
?>

<aside class="w-full lg:w-72 shrink-0">
    <div class="bg-white rounded-2xl shadow-custom overflow-hidden sticky top-6 lg:top-8 border border-gray-100">
        <div class="px-6 py-5 border-b border-gray-100 bg-white">
            <a href="<?php echo e(route('agent.dashboard')); ?>" class="flex items-center justify-center">
                <img src="<?php echo e($brandLogo); ?>" alt="<?php echo e($brandName); ?>" class="max-h-10 w-auto">
            </a>
        </div>
        <div class="p-6 text-center border-b border-gray-100 bg-[#e6f3fa]/30">
            <img src="<?php echo e($user?->avatar_url); ?>" alt="Avatar" class="w-20 h-20 rounded-full object-cover border-4 border-white shadow-sm mx-auto mb-3">
            <h3 class="font-bold text-[#0e3a5a] text-lg leading-tight"><?php echo e($user?->name); ?></h3>
            <p class="text-[10px] font-bold text-[#f37a1f] uppercase tracking-wider mt-1"><?php echo e($roleLabel); ?></p>
            <?php if($branchLabel): ?>
                <p class="text-[10px] font-semibold text-gray-500 mt-1"><?php echo e($branchLabel); ?></p>
            <?php endif; ?>
        </div>

        <nav class="p-4 flex flex-col gap-1 max-h-[70vh] overflow-y-auto text-sm">
            <a href="<?php echo e(route('agent.dashboard')); ?>"
               data-partner-nav
               class="flex items-center gap-3 px-4 py-3 rounded-xl mb-1 <?php echo e($dashboardActive ? 'bg-[#e6f3fa]/60 text-[#0083c4] font-semibold' : 'hover:bg-gray-50 text-gray-600 hover:text-[#0083c4] font-medium'); ?> transition-colors">
                <span class="w-2.5 h-2.5 rounded-full shrink-0 <?php echo e($dashboardActive ? 'bg-[#0083c4]' : 'bg-gray-200'); ?>"></span>
                <span class="leading-snug">Dashboard</span>
            </a>

            <?php $__currentLoopData = $menuItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $node): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php echo $__env->make('agent_v2.partials.sidebar-node', ['node' => $node, 'depth' => 0], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

            <details class="agent-nav-group rounded-xl border border-transparent hover:border-gray-100 mt-1 <?php echo e($profileActive ? 'bg-gray-50/80' : ''); ?>" <?php echo e($profileActive ? 'open' : ''); ?>>
                <summary class="flex items-center justify-between gap-2 px-4 py-2.5 cursor-pointer list-none select-none text-[11px] font-bold uppercase tracking-wider text-[#0e3a5a] [&::-webkit-details-marker]:hidden">
                    <span>Compte</span>
                    <i class="fas fa-chevron-right text-[10px] text-gray-400 transition-transform agent-nav-chevron"></i>
                </summary>
                <div class="pb-2 pt-0 flex flex-col gap-0.5 px-1">
                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('dashboard.view')): ?>
                        <a href="<?php echo e(route('admin.profile.edit')); ?>"
                           data-partner-nav
                           class="flex items-center gap-3 px-3 py-2.5 rounded-lg <?php echo e($profileActive ? 'bg-[#e6f3fa]/60 text-[#0083c4] font-semibold' : 'text-gray-600 hover:bg-white hover:text-[#0083c4] font-medium'); ?> transition-colors">
                            <span class="w-1.5 h-1.5 rounded-full shrink-0 <?php echo e($profileActive ? 'bg-[#0083c4]' : 'bg-gray-300'); ?>"></span>
                            <span class="leading-snug">Mon profil</span>
                        </a>
                    <?php endif; ?>
                    <a href="<?php echo e(route('logout.get')); ?>"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-red-500 hover:bg-red-50 font-medium transition-colors">
                        <span class="w-1.5 h-1.5 rounded-full shrink-0 bg-red-200"></span>
                        Déconnexion
                    </a>
                </div>
            </details>
        </nav>
    </div>
</aside>

<style>
    .agent-nav-group[open] > summary .agent-nav-chevron { transform: rotate(90deg); }
</style>
<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\agent_v2\partials\sidebar.blade.php ENDPATH**/ ?>