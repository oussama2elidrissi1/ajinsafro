
<?php $__env->startSection('title'); ?>
    Register
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
                                <h5 class="text-white font-size-20">Free Register</h5>
                                <p class="text-white-50 mb-0">Get your free AJINSAFRO account now</p>
                                <a href="index" class="logo logo-admin mt-4">
                                    <img src="<?php echo e(URL::asset('build/images/logo-sm-dark.png')); ?>" alt="" height="30">
                                </a>
                            </div>
                        </div>
                        <div class="card-body pt-5">

                            <div class="p-2">
                                <form class="form-horizontal" action="index">

                                    <div class="mb-3">
                                        <label class="form-label" for="useremail">Email</label>
                                        <input type="email" class="form-control" id="useremail"
                                            placeholder="Enter email">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label" for="username">Username</label>
                                        <input type="text" class="form-control" id="username"
                                            placeholder="Enter username">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label" for="userpassword">Password</label>
                                        <input type="password" class="form-control" id="userpassword"
                                            placeholder="Enter password">
                                    </div>

                                    <div class="mt-4">
                                        <button class="btn btn-primary w-100 waves-effect waves-light"
                                            type="submit">Register</button>
                                    </div>

                                    <div class="mt-4 text-center">
                                        <p class="mb-0">By registering you agree to the Skote <a href="#"
                                                class="text-primary">Terms of Use</a></p>
                                    </div>
                                </form>
                            </div>

                        </div>
                    </div>
                    <div class="mt-5 text-center">
                        <p>Already have an account ? <a href="pages-login" class="fw-medium text-primary">
                                Login</a> </p>
                        <p>©
                            <script>document.write(new Date().getFullYear())</script> AJINSAFRO. Crafted with <i
                                class="mdi mdi-heart text-danger"></i> by AJINSAFRO
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
    
<?php echo $__env->make('layouts.master-without-nav', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\pages-register.blade.php ENDPATH**/ ?>