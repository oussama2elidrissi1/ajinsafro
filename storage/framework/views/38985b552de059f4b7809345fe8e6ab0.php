<?php $__env->startSection('title'); ?>
    Login
<?php $__env->stopSection(); ?>
<?php $__env->startSection('content'); ?>
    <div class="home-btn d-none d-sm-block">
        <a href="/index" class="text-reset"><i class="fas fa-home h2"></i></a>
    </div>
    <div class="account-pages my-5 pt-sm-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6 col-xl-5">
                    <div class="card overflow-hidden">
                        <div class="bg-login text-center">
                            <div class="bg-login-overlay"></div>
                            <div class="position-relative">
                                <h5 class="text-white font-size-20">Welcome Back !</h5>
                                <p class="text-white-50 mb-0">Sign in to continue to AJINSAFRO.</p>
                                <a href="/index" class="logo logo-admin mt-4">
                                    <img src="<?php echo e(URL::asset('build/images/logo-sm-dark.png')); ?>" alt=""
                                        height="30">
                                </a>
                            </div>
                        </div>
                        <div class="card-body pt-5">
                            <div class="p-2">
                                <form method="POST" action="<?php echo e(route('auth.public-login')); ?>">
                                    <?php echo csrf_field(); ?>
                                    <div class="mb-3">
                                        <label class="form-label" for="login">Email ou nom d'utilisateur <span class="text-danger">*
                                            </span></label>
                                        <input type="text" class="form-control <?php echo e(isset($errors) && $errors->has('login') ? 'is-invalid' : ''); ?>"
                                            name="login" id="login" value="<?php echo e(old('login', old('email', ''))); ?>" required
                                            autocomplete="username" autofocus placeholder="Entrez votre email ou identifiant">
                                        <?php if(isset($errors) && $errors->has('login')): ?>
                                            <span class="invalid-feedback" role="alert">
                                                <strong><?php echo e($errors->first('login')); ?></strong>
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label" for="userpassword">Password <span class="text-danger">*
                                            </span></label>
                                        <input type="password" class="form-control <?php echo e(isset($errors) && $errors->has('password') ? 'is-invalid' : ''); ?>"
                                            id="userpassword" name="password" required
                                            autocomplete="current-password" placeholder="Enter password">
                                        <?php if(isset($errors) && $errors->has('password')): ?>
                                            <span class="invalid-feedback" role="alert">
                                                <strong><?php echo e($errors->first('password')); ?></strong>
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="customControlInline"
                                            name="remember" id="remember" <?php echo e(old('remember') ? 'checked' : ''); ?>>
                                        <label class="form-check-label" for="customControlInline">Remember
                                            me</label>
                                    </div>

                                    <div class="mt-3">
                                        <button class="btn btn-primary w-100 waves-effect waves-light" type="submit">Log
                                            In</button>
                                    </div>

                                    <?php if(Route::has('password.request')): ?>
                                        <div class="mt-4 text-center">
                                            <a href="<?php echo e(route('password.request')); ?>" class="text-muted"><i
                                                    class="mdi mdi-lock me-1"></i> Forgot your password?</a>
                                        </div>
                                    <?php endif; ?>

                                    <div class="mt-4 pt-3 border-top text-center">
                                        <p class="mb-0 text-muted small">Vous êtes un professionnel ?</p>
                                        <a href="<?php echo e(route('partner.registration.form')); ?>" class="fw-medium text-primary">Devenir partenaire</a>
                                    </div>
                                </form>
                            </div>

                        </div>
                    </div>
                    <div class="mt-5 text-center">
                        <p>Don't have an account ? <a href="<?php echo e(route('partner.registration.form')); ?>" class="fw-medium text-primary"> Devenir partenaire </a> </p>
                        <p>©
                            <?php echo e(date('Y')); ?> AJINSAFRO. Crafted with <i class="mdi mdi-heart text-danger"></i> by
                            AJINSAFRO
                        </p>
                    </div>

                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master-without-nav', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\auth\login.blade.php ENDPATH**/ ?>