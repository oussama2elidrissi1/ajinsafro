<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
    'user',
    'isManager' => false,
    'stats' => [],
    'quickRange' => null,
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
    'user',
    'isManager' => false,
    'stats' => [],
    'quickRange' => null,
]); ?>
<?php foreach (array_filter(([
    'user',
    'isManager' => false,
    'stats' => [],
    'quickRange' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<?php
    use Illuminate\Support\Facades\Route;
    use Carbon\Carbon;

    $displayName = $user?->name ?: 'Agent';
    $agencyName = $user?->branch?->name ?: ($user?->partner?->name ?: null);
    $roleLabel = $isManager ? 'Manager' : ($user?->isAgent() ? 'Agent' : ($user?->job_title ?: 'Agent'));
    $today = Carbon::now()->locale('fr')->isoFormat('dddd D MMMM YYYY');

    $total = (int) ($stats['reservations_total'] ?? 0);
    $pending = (int) ($stats['reservations_en_cours'] ?? 0);
    $confirmed = (int) ($stats['reservations_validees'] ?? 0);

    $catalogueVoyageUrl = Route::has('admin.reservations.workspace')
        ? route('admin.reservations.workspace')
        : url('/admin/reservations/workspace');
?>

<div class="agent-dashboard-hero rounded-2xl border border-gray-100 bg-white shadow-custom p-5 sm:p-6 mb-6">
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-5">
        <div class="min-w-0">
            <div class="flex items-center gap-3">
                <img src="<?php echo e($user?->avatar_url); ?>" alt="<?php echo e($displayName); ?>" class="w-11 h-11 rounded-xl object-cover ring-1 ring-gray-200">
                <div class="min-w-0">
                    <p class="text-[11px] text-gray-500 mb-0">Bienvenue,</p>
                    <h1 class="text-xl sm:text-2xl font-black text-[#0e3a5a] leading-tight truncate">
                        Welcome back, <?php echo e($displayName); ?>

                    </h1>
                </div>
            </div>
            <div class="mt-3 flex flex-wrap items-center gap-2 text-[12px] text-gray-600">
                <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-gray-50 border border-gray-200">
                    <i class="fa-regular fa-calendar"></i>
                    <span class="font-semibold"><?php echo e($today); ?></span>
                </span>
                <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-[#e6f3fa]/60 border border-[#0083c4]/15 text-[#0e3a5a]">
                    <i class="fa-solid fa-id-badge text-[#0083c4]"></i>
                    <span class="font-semibold"><?php echo e($roleLabel); ?></span>
                    <?php if($agencyName): ?>
                        <span class="text-gray-500">·</span>
                        <span class="text-gray-700"><?php echo e($agencyName); ?></span>
                    <?php endif; ?>
                </span>
                <span class="text-gray-500">
                    Résumé: <span class="font-semibold text-gray-700"><?php echo e($total); ?></span> réservations ·
                    <span class="font-semibold text-gray-700"><?php echo e($confirmed); ?></span> confirmées ·
                    <span class="font-semibold text-gray-700"><?php echo e($pending); ?></span> en attente
                </span>
            </div>
        </div>

        <div class="flex flex-wrap items-stretch sm:items-center justify-stretch sm:justify-end gap-2 w-full lg:w-auto">
            <a href="<?php echo e($catalogueVoyageUrl); ?>"
               class="inline-flex items-center justify-center gap-2 px-5 py-3 sm:py-2.5 rounded-xl bg-[#0083c4] text-white text-sm font-bold shadow-sm hover:opacity-95 transition-opacity w-full sm:w-auto min-h-[48px]">
                <i class="fas fa-map-marked-alt" aria-hidden="true"></i>
                Catalogue de voyage
            </a>
        </div>
    </div>
</div>

<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\agent\partials\dashboard-header.blade.php ENDPATH**/ ?>