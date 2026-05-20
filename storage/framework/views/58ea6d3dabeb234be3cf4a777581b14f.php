<?php $__env->startSection('title'); ?>
    Page en cours de construction
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <?php
        $brandName = \App\Models\Setting::getValue('brand_name', 'Ajinsafro');
        $brandLogo = \App\Models\Setting::brandLogoUrl('dark');
    ?>
    <section class="my-5 pt-sm-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-9 text-center">
                    <div class="home-wrapper">
                        <div class="mb-4">
                            <a href="<?php echo e(route('front.home')); ?>" class="d-inline-flex align-items-center">
                                <img src="<?php echo e($brandLogo); ?>" alt="<?php echo e($brandName); ?>" height="34" />
                            </a>
                        </div>

                        <div class="mb-4">
                            <img src="<?php echo e(URL::asset('build/images/maintenance.png')); ?>" alt="Maintenance"
                                class="img-fluid mx-auto d-block" style="max-height: 220px;">
                        </div>

                        <h2 class="mb-3">Page en cours de construction</h2>
                        <p class="text-muted mb-4">
                            Cette section sera bientot disponible.<br>
                            Nous preparons cette page pour vous offrir une meilleure experience.
                        </p>

                        <div class="d-flex justify-content-center gap-2 flex-wrap">
                            <a href="<?php echo e(route('front.home')); ?>" class="btn btn-primary">Retour a l'accueil</a>
                            <a href="mailto:contact@ajinsafro.net" class="btn btn-outline-secondary">Contacter Ajinsafro</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('script'); ?>
    <script src="<?php echo e(URL::asset('build/js/app.js')); ?>"></script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.master-without-nav', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\front\maintenance.blade.php ENDPATH**/ ?>