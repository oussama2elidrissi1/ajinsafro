

<?php $__env->startSection('title', 'Detail demande Hajj & Omra'); ?>

<?php $__env->startSection('content'); ?>
    <?php if (isset($component)) { $__componentOriginalcb19cb35a534439097b02b8af91726ee = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcb19cb35a534439097b02b8af91726ee = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.page-header','data' => ['title' => 'Demande de '.$requestItem->full_name,'subtitle' => 'Consultez la demande, associez une offre et mettez a jour le statut.','breadcrumbs' => [
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Demandes Hajj & Omra', 'url' => route('admin.hajj-omra.requests.index')],
            ['label' => 'Detail'],
        ]]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('Demande de '.$requestItem->full_name),'subtitle' => 'Consultez la demande, associez une offre et mettez a jour le statut.','breadcrumbs' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Demandes Hajj & Omra', 'url' => route('admin.hajj-omra.requests.index')],
            ['label' => 'Detail'],
        ])]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalcb19cb35a534439097b02b8af91726ee)): ?>
<?php $attributes = $__attributesOriginalcb19cb35a534439097b02b8af91726ee; ?>
<?php unset($__attributesOriginalcb19cb35a534439097b02b8af91726ee); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalcb19cb35a534439097b02b8af91726ee)): ?>
<?php $component = $__componentOriginalcb19cb35a534439097b02b8af91726ee; ?>
<?php unset($__componentOriginalcb19cb35a534439097b02b8af91726ee); ?>
<?php endif; ?>

    <?php if (isset($component)) { $__componentOriginaldb1b157d84f8f63332f3508c9e385c0a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginaldb1b157d84f8f63332f3508c9e385c0a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.flash-messages','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.flash-messages'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginaldb1b157d84f8f63332f3508c9e385c0a)): ?>
<?php $attributes = $__attributesOriginaldb1b157d84f8f63332f3508c9e385c0a; ?>
<?php unset($__attributesOriginaldb1b157d84f8f63332f3508c9e385c0a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginaldb1b157d84f8f63332f3508c9e385c0a)): ?>
<?php $component = $__componentOriginaldb1b157d84f8f63332f3508c9e385c0a; ?>
<?php unset($__componentOriginaldb1b157d84f8f63332f3508c9e385c0a); ?>
<?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-5">
            <?php if (isset($component)) { $__componentOriginalba421f08b6b43aecb09f8eebe577a4f3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalba421f08b6b43aecb09f8eebe577a4f3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.form-section','data' => ['title' => 'Informations client']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.form-section'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Informations client']); ?>
                <div class="d-flex flex-column gap-2">
                    <div><strong>Nom :</strong> <?php echo e($requestItem->full_name); ?></div>
                    <div><strong>Telephone :</strong> <?php echo e($requestItem->phone); ?></div>
                    <div><strong>Email :</strong> <?php echo e($requestItem->email); ?></div>
                    <div><strong>Adultes :</strong> <?php echo e($requestItem->adults); ?></div>
                    <div><strong>Enfants :</strong> <?php echo e($requestItem->children); ?></div>
                    <div><strong>Type chambre :</strong> <?php echo e($requestItem->room_type ?: 'Non precise'); ?></div>
                    <div><strong>Date depart choisie :</strong> <?php echo e($requestItem->selected_departure_date?->format('d/m/Y') ?: '�?"'); ?></div>
                    <div><strong>Message :</strong><br><?php echo nl2br(e($requestItem->message ?: 'Aucun message')); ?></div>
                </div>
             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalba421f08b6b43aecb09f8eebe577a4f3)): ?>
<?php $attributes = $__attributesOriginalba421f08b6b43aecb09f8eebe577a4f3; ?>
<?php unset($__attributesOriginalba421f08b6b43aecb09f8eebe577a4f3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalba421f08b6b43aecb09f8eebe577a4f3)): ?>
<?php $component = $__componentOriginalba421f08b6b43aecb09f8eebe577a4f3; ?>
<?php unset($__componentOriginalba421f08b6b43aecb09f8eebe577a4f3); ?>
<?php endif; ?>
        </div>

        <div class="col-lg-7">
            <?php if (isset($component)) { $__componentOriginalba421f08b6b43aecb09f8eebe577a4f3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalba421f08b6b43aecb09f8eebe577a4f3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.form-section','data' => ['title' => 'Traitement admin']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.form-section'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Traitement admin']); ?>
                <form action="<?php echo e(route('admin.hajj-omra.requests.update', $requestItem)); ?>" method="POST">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PUT'); ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Statut</label>
                            <select name="status" class="form-select">
                                <?php $__currentLoopData = $statusOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($value); ?>" <?php if(old('status', $requestItem->status) === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Associer a une offre</label>
                            <select name="package_id" class="form-select">
                                <option value="">Ne pas modifier</option>
                                <?php $__currentLoopData = $packages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $package): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($package->id); ?>" <?php if(old('package_id', $requestItem->package_id) === $package->id): echo 'selected'; endif; ?>><?php echo e($package->title); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes internes</label>
                            <textarea name="internal_notes" rows="8" class="form-control"><?php echo e(old('internal_notes', $requestItem->internal_notes)); ?></textarea>
                        </div>
                        <div class="col-12 d-flex justify-content-end">
                            <button type="submit" class="aj-btn aj-btn-primary">
                                <i class="bx bx-save"></i>
                                <span>Mettre a jour la demande</span>
                            </button>
                        </div>
                    </div>
                </form>
             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalba421f08b6b43aecb09f8eebe577a4f3)): ?>
<?php $attributes = $__attributesOriginalba421f08b6b43aecb09f8eebe577a4f3; ?>
<?php unset($__attributesOriginalba421f08b6b43aecb09f8eebe577a4f3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalba421f08b6b43aecb09f8eebe577a4f3)): ?>
<?php $component = $__componentOriginalba421f08b6b43aecb09f8eebe577a4f3; ?>
<?php unset($__componentOriginalba421f08b6b43aecb09f8eebe577a4f3); ?>
<?php endif; ?>
        </div>
    </div>
<?php $__env->stopSection(); ?>



<?php echo $__env->make('layouts.admin-v6', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\hajj-omra\requests\show.blade.php ENDPATH**/ ?>