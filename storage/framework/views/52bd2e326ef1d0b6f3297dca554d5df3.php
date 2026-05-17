
<?php $__env->startSection('title'); ?>
    Commin Soon
<?php $__env->stopSection(); ?>
<?php $__env->startSection('content'); ?>
    <div class="home-btn d-none d-sm-block">
        <a href="index" class="text-white"><i class="fas fa-home h2"></i></a>
    </div>

    <div class="my-5 pt-sm-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="text-center">
                        <a href="index">
                            <img src="<?php echo e(URL::asset('build/images/logo-dark.png')); ?>" alt="logo" height="24" />
                        </a>
                        <div class="row justify-content-center mt-5">
                            <div class="col-sm-4">
                                <div class="maintenance-img">
                                    <img src="<?php echo e(URL::asset('build/images/maintenance.png')); ?>" alt="" class="img-fluid mx-auto d-block">
                                </div>
                            </div>
                        </div>
                        <h4 class="mt-5">Let's get started with AJINSAFRO</h4>
                        <p class="text-muted">It will be as simple as Occidental in fact it will be Occidental.</p>

                        <div class="row justify-content-center mt-5">
                            <div class="col-md-8">
                                <div data-countdown="2025/12/31" class="counter-number"></div>
                            </div>
                            <!-- end col-->
                        </div>
                        <!-- end row-->
                    </div>
                </div>
            </div>
        </div>
    </div>

    
<?php $__env->stopSection(); ?>
<?php $__env->startPush('script'); ?>
<!-- Plugins js-->
<script src="<?php echo e(URL::asset('build/libs/jquery-countdown/jquery.countdown.min.js')); ?>"></script>

<!-- Countdown js -->
<script src="<?php echo e(URL::asset('build/js/pages/coming-soon.init.js')); ?>"></script>
    <script src="<?php echo e(URL::asset('build/js/app.js')); ?>"></script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.master-without-nav', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\pages-comingsoon.blade.php ENDPATH**/ ?>