

<?php $__env->startSection('title', 'Créer une réservation'); ?>
<?php $__env->startSection('hidePageFooter', '1'); ?>

<?php $__env->startPush('styles'); ?>
    <link rel="stylesheet" href="<?php echo e(asset('css/reservation-create.css')); ?>">
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
    <div class="reservation-create">
        <header class="reservation-create__header">
            <nav class="reservation-create__breadcrumb" aria-label="Breadcrumb">
                <a href="<?php echo e(route('admin.reservations.index')); ?>">Réservations</a>
                <span>/</span>
                <span>Nouvelle</span>
            </nav>
            <div class="reservation-create__header-main">
                <div>
                    <h1 class="reservation-create__title">Créer une réservation</h1>
                    <p class="reservation-create__subtitle">Tunnel dédié pour ouvrir un dossier de réservation sans confusion avec le workspace.</p>
                </div>
                <a href="<?php echo e(route('admin.reservations.workspace')); ?>" class="reservation-create__back-link">Retour au workspace</a>
            </div>
        </header>

        <?php if($errors->any()): ?>
            <div class="reservation-create__alert reservation-create__alert--error">
                <strong>Le dossier contient des erreurs.</strong>
                <ul>
                    <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li><?php echo e($error); ?></li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="<?php echo e(route('admin.reservations.store')); ?>" enctype="multipart/form-data" id="reservation-create-form">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="extras_json" id="reservation-create-extras-json" value="[]">
            <input type="hidden" name="travelers_json" id="reservation-travelers-json" value="[]">
            <input type="hidden" name="room_allocations_json" id="reservation-room-allocations-json" value="<?php echo e(old('room_allocations_json', '[]')); ?>">
            <input type="hidden" name="accommodation_mode" id="reservation-accommodation-mode" value="rooms">
            <input type="hidden" name="total_base" id="reservation-total-base-input" value="<?php echo e(old('total_base', 0)); ?>">
            <input type="hidden" name="room_supplement_total" id="reservation-room-supplement-total-input" value="<?php echo e(old('room_supplement_total', 0)); ?>">
            <input type="hidden" name="extras_total" id="reservation-extras-total-input" value="<?php echo e(old('extras_total', 0)); ?>">
            <input type="hidden" name="total_amount" id="reservation-total-amount-input" value="<?php echo e(old('total_amount', 0)); ?>">

            <div class="reservation-create__workflow">
                <?php echo $__env->make('admin.reservations.create.partials.workflow', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
            </div>

            <div class="reservation-create__content-grid">
                <main class="reservation-create__main">
                    <?php echo $__env->make('admin.reservations.create.partials.step-prestation', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                    <?php echo $__env->make('admin.reservations.create.partials.step-client', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                    <?php echo $__env->make('admin.reservations.create.partials.step-voyageurs', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                    <?php echo $__env->make('admin.reservations.create.partials.step-extras', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                    <?php echo $__env->make('admin.reservations.create.partials.step-payment', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                    <?php echo $__env->make('admin.reservations.create.partials.step-dossier', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                </main>

                <aside class="reservation-create__summary">
                    <?php echo $__env->make('admin.reservations.create.partials.summary', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                </aside>
            </div>
        </form>

        <script type="application/json" id="reservation-create-extras-map"><?php echo json_encode($extrasByVoyage ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE); ?></script>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
    <script src="<?php echo e(asset('js/reservation-create.js')); ?>"></script>
<?php $__env->stopPush(); ?>


<?php echo $__env->make('layouts.admin-v6', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\reservations\create.blade.php ENDPATH**/ ?>