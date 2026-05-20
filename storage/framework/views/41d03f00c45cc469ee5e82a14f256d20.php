
<?php $__env->startSection('title'); ?>
    Lock screen
<?php $__env->stopSection(); ?>
<?php $__env->startSection('content'); ?>
    <div class="home-btn d-none d-sm-block">
        <a href="index" class="text-reset"><i class="fas fa-home h2"></i></a>
    </div>
    <div class="account-pages my-5 pt-sm-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6 col-xl-5">
                    <div class="card overflow-hidden">
                        <div class="bg-login text-center">
                            <div class="bg-login-overlay"></div>
                            <div class="position-relative">
                                <h5 class="text-white font-size-20">Lock screen</h5>
                                <p class="text-white-50 mb-0">Enter your password to unlock the screen!</p>
                                <a href="index" class="logo logo-admin mt-4">
                                    <img src="<?php echo e(URL::asset('build/images/logo-sm-dark.png')); ?>" alt="" height="30">
                                </a>
                            </div>
                        </div>
                        <div class="card-body pt-5">

                            <div class="p-2">
                                <form class="form-horizontal" action="index">

                                    <div class="user-thumb text-center mb-4">
                                        <img src="<?php echo e(URL::asset('build/images/users/avatar-1.jpg')); ?>"
                                            class="rounded-circle img-thumbnail avatar-md" alt="thumbnail">
                                        <h5 class="font-size-15 mt-3">Maria Laird</h5>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label" for="userpassword">Password</label>
                                        <input type="password" class="form-control" id="userpassword"
                                            placeholder="Enter password">
                                    </div>

                                    <div class="row mb-0">
                                        <div class="col-12 text-end">
                                            <button class="btn btn-primary w-md waves-effect waves-light"
                                                type="submit">Unlock</button>
                                        </div>
                                    </div>
                                </form>
                            </div>

                        </div>
                    </div>
                    <div class="mt-5 text-center">
                        <p>Not you ? return <a href="pages-login" class="fw-medium text-primary"> Sign In
                            </a> </p>
                        <p>©
                            <script>
                                document.write(new Date().getFullYear())
                            </script> AJINSAFRO. Crafted with <i class="mdi mdi-heart text-danger"></i> by
                            AJINSAFRO
                        </p>
                    </div>

                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>
<?php $__env->startPush('script'); ?>
    <script src="<?php echo e(URL::asset('build/js/app.js')); ?>"></script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.master-without-nav', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\pages-lock-screen.blade.php ENDPATH**/ ?>