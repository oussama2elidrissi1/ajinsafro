<?php $__env->startSection('title', 'Connexion partenaire'); ?>

<?php $__env->startSection('content'); ?>
    <div class="w-full max-w-xl">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 sm:px-8 py-6 border-b border-gray-100">
                <h1 class="text-xl sm:text-2xl font-semibold text-gray-900">Connexion</h1>
                <p class="mt-1 text-sm text-gray-600">Accédez à votre espace partenaire Ajinsafro.</p>
            </div>

            <div class="px-6 sm:px-8 py-6">
                <?php if(session('error')): ?>
                    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                        <?php echo e(session('error')); ?>

                    </div>
                <?php endif; ?>

                <form method="POST" action="<?php echo e(route('partner.login.submit')); ?>" class="space-y-4">
                    <?php echo csrf_field(); ?>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            required
                            autofocus
                            autocomplete="email"
                            value="<?php echo e(old('email')); ?>"
                            class="mt-1 block w-full rounded-lg border-gray-300 focus:border-gray-900 focus:ring-gray-900"
                            placeholder="ex: contact@votre-agence.ma"
                        />
                        <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <div class="mt-1 text-sm text-red-700"><?php echo e($message); ?></div>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">Mot de passe</label>
                        <input
                            id="password"
                            name="password"
                            type="password"
                            required
                            autocomplete="current-password"
                            class="mt-1 block w-full rounded-lg border-gray-300 focus:border-gray-900 focus:ring-gray-900"
                            placeholder="Votre mot de passe"
                        />
                        <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <div class="mt-1 text-sm text-red-700"><?php echo e($message); ?></div>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div class="flex items-center justify-between gap-4">
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="remember" class="rounded border-gray-300 text-gray-900 focus:ring-gray-900" <?php echo e(old('remember') ? 'checked' : ''); ?>>
                            Se souvenir de moi
                        </label>
                        <a class="text-sm font-medium text-gray-900 hover:underline" href="<?php echo e(rtrim((string) config('app.public_url', 'https://ajinsafro.net'), '/')); ?>/devenir-partenaire">
                            Devenir partenaire
                        </a>
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="w-full inline-flex items-center justify-center rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-900/30">
                            Se connecter
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('partner_v2.layouts.guest', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views/partner_v2/auth/login.blade.php ENDPATH**/ ?>