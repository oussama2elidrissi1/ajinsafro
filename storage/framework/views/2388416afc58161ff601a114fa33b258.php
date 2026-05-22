

<?php $__env->startSection('title', 'Nouveau message'); ?>

<?php $__env->startSection('content'); ?>
    <div class="row mb-3">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Nouveau message</h4>
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?php echo e(route('admin.dashboard')); ?>">Admin</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo e(route('admin.reservations.index')); ?>">Réservations</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo e(route('admin.reservations.messages')); ?>">Messages</a></li>
                    <li class="breadcrumb-item active">Nouveau</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <form method="post" action="<?php echo e(route('admin.reservations.messages.store')); ?>">
                        <?php echo csrf_field(); ?>
                        <div class="mb-3">
                            <label class="form-label">Objet <span class="text-danger">*</span></label>
                            <input type="text" name="subject" class="form-control" value="<?php echo e(old('subject')); ?>" required maxlength="255" placeholder="Objet du message">
                            <?php $__errorArgs = ['subject'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="text-danger small"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Message <span class="text-danger">*</span></label>
                            <textarea name="body" class="form-control" rows="8" required placeholder="Contenu du message�?�"><?php echo e(old('body')); ?></textarea>
                            <?php $__errorArgs = ['body'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="text-danger small"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <p class="text-muted small">Le message sera envoyé au nom de votre agence.</p>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary"><i class="bx bx-send me-1"></i> Envoyer</button>
                            <a href="<?php echo e(route('admin.reservations.messages')); ?>" class="btn btn-secondary">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>



<?php echo $__env->make('layouts.admin-v6', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\reservations\messages\create.blade.php ENDPATH**/ ?>