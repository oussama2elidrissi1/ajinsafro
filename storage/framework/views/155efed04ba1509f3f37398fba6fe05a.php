<?php $__env->startSection('title'); ?>
    Demande envoyée
<?php $__env->stopSection(); ?>
<?php $__env->startSection('content'); ?>
    <div class="account-pages my-5 pt-sm-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6 col-xl-5">
                    <div class="card overflow-hidden">
                        <div class="card-body text-center p-5">
                            <div class="avatar-lg mx-auto mb-4">
                                <span class="avatar-title rounded-circle bg-success bg-opacity-10 text-success font-size-24">
                                    <i class="bx bx-check-circle"></i>
                                </span>
                            </div>
                            <h4 class="mb-3">Demande bien reçue</h4>
                            <p class="text-muted mb-4">
                                Votre demande d’inscription en tant que partenaire a été enregistrée avec succès.
                            </p>
                            <p class="text-muted mb-4">
                                <strong>Votre compte doit être validé par un administrateur.</strong> Vous recevrez un email dès que votre compte sera activé. Vous pourrez alors vous connecter et accéder à votre espace partenaire.
                            </p>
                            <p class="text-muted small mb-4">
                                En attendant, vous pouvez vous connecter avec votre email et mot de passe : un message vous indiquera que votre compte est en cours de validation.
                            </p>
                            <a href="<?php echo e(route('login')); ?>" class="btn btn-primary">Aller à la connexion</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master-without-nav', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\auth\partner-registration-success.blade.php ENDPATH**/ ?>