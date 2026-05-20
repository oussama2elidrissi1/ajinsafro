

<?php $__env->startSection('title', 'Fiche Formule �?conomique'); ?>

<?php $__env->startSection('content'); ?>
    <?php if (isset($component)) { $__componentOriginalcb19cb35a534439097b02b8af91726ee = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcb19cb35a534439097b02b8af91726ee = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.page-header','data' => ['title' => $offer->title,'subtitle' => $offer->short_description ?: 'Offre économique Ajinsafro','breadcrumbs' => [
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Formule �?conomique', 'url' => route('admin.economic-offers.index')],
            ['label' => 'Fiche offre'],
        ]]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($offer->title),'subtitle' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($offer->short_description ?: 'Offre économique Ajinsafro'),'breadcrumbs' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Formule �?conomique', 'url' => route('admin.economic-offers.index')],
            ['label' => 'Fiche offre'],
        ])]); ?>
         <?php $__env->slot('actions', null, []); ?> 
            <a href="<?php echo e(route('admin.economic-offers.edit', $offer)); ?>" class="aj-btn aj-btn-primary">
                <i class="bx bx-pencil"></i>
                <span>Modifier</span>
            </a>
         <?php $__env->endSlot(); ?>
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

    <div class="row g-4">
        <div class="col-lg-4">
            <?php if (isset($component)) { $__componentOriginalba421f08b6b43aecb09f8eebe577a4f3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalba421f08b6b43aecb09f8eebe577a4f3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.form-section','data' => ['title' => 'Résumé']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.form-section'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Résumé']); ?>
                <div class="d-flex flex-column gap-3">
                    <?php if (isset($component)) { $__componentOriginalf5efe4dc5fa3be8d4c5a3e56a4ce9c7d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf5efe4dc5fa3be8d4c5a3e56a4ce9c7d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.image-thumb','data' => ['src' => $offer->main_image_url ?: $offer->fallback_image_url,'alt' => $offer->title,'size' => 'lg']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.image-thumb'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['src' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($offer->main_image_url ?: $offer->fallback_image_url),'alt' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($offer->title),'size' => 'lg']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf5efe4dc5fa3be8d4c5a3e56a4ce9c7d)): ?>
<?php $attributes = $__attributesOriginalf5efe4dc5fa3be8d4c5a3e56a4ce9c7d; ?>
<?php unset($__attributesOriginalf5efe4dc5fa3be8d4c5a3e56a4ce9c7d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf5efe4dc5fa3be8d4c5a3e56a4ce9c7d)): ?>
<?php $component = $__componentOriginalf5efe4dc5fa3be8d4c5a3e56a4ce9c7d; ?>
<?php unset($__componentOriginalf5efe4dc5fa3be8d4c5a3e56a4ce9c7d); ?>
<?php endif; ?>
                    <div><strong>Type :</strong> <?php echo e($offer->type_label); ?></div>
                    <div><strong>Catégorie :</strong> <?php echo e($offer->category_label); ?></div>
                    <div><strong>Statut :</strong> <?php echo e($offer->status_label); ?></div>
                    <div><strong>Disponibilité :</strong> <?php echo e($offer->availability_label); ?></div>
                    <div><strong>Ville de départ :</strong> <?php echo e($offer->departure_city ?: '�?"'); ?></div>
                    <div><strong>Destination :</strong> <?php echo e($offer->destination ?: '�?"'); ?></div>
                    <div><strong>Prix à partir de :</strong> <?php echo e($offer->price_from_value !== null ? number_format($offer->price_from_value, 0, ',', ' ') . ' ' . $offer->currency : 'Sur demande'); ?></div>
                    <div><strong>Places restantes :</strong> <?php echo e($offer->remaining_places); ?></div>
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
        <div class="col-lg-8">
            <?php if (isset($component)) { $__componentOriginalba421f08b6b43aecb09f8eebe577a4f3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalba421f08b6b43aecb09f8eebe577a4f3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.form-section','data' => ['title' => 'Présentation']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.form-section'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Présentation']); ?>
                <div class="mb-3"><strong>Description courte</strong><br><?php echo e($offer->short_description ?: '�?"'); ?></div>
                <div><strong>Description détaillée</strong><br><?php echo nl2br(e($offer->description ?: '�?"')); ?></div>
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

            <?php if (isset($component)) { $__componentOriginalba421f08b6b43aecb09f8eebe577a4f3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalba421f08b6b43aecb09f8eebe577a4f3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.form-section','data' => ['title' => 'Prix et conditions']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.form-section'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Prix et conditions']); ?>
                <div class="row g-3">
                    <div class="col-md-4"><strong>Ancien prix :</strong> <?php echo e($offer->old_price !== null ? number_format((float) $offer->old_price, 0, ',', ' ') . ' ' . $offer->currency : '�?"'); ?></div>
                    <div class="col-md-4"><strong>Type de prix :</strong> <?php echo e(\App\Models\EconomicOffer::priceTypeOptions()[$offer->price_type] ?? '�?"'); ?></div>
                    <div class="col-md-4"><strong>Acompte :</strong> <?php echo e($offer->deposit_amount !== null ? number_format((float) $offer->deposit_amount, 0, ',', ' ') . ' ' . $offer->currency : '�?"'); ?></div>
                    <div class="col-md-6"><strong>Ville d arrivée :</strong> <?php echo e($offer->arrival_city ?: '�?"'); ?></div>
                    <div class="col-md-6"><strong>Zone / adresse :</strong> <?php echo e($offer->address_zone ?: '�?"'); ?></div>
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
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <?php if (isset($component)) { $__componentOriginalba421f08b6b43aecb09f8eebe577a4f3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalba421f08b6b43aecb09f8eebe577a4f3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.form-section','data' => ['title' => 'Prix variables']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.form-section'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Prix variables']); ?>
                <?php if($offer->prices->isEmpty()): ?>
                    <p class="text-muted mb-0">Aucune ligne de prix renseignée.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead><tr><th>Libellé</th><th>Type</th><th>Prix</th><th>Stock</th></tr></thead>
                            <tbody>
                            <?php $__currentLoopData = $offer->prices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $price): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td><?php echo e($price->label); ?></td>
                                    <td><?php echo e($price->type ?: '�?"'); ?></td>
                                    <td><?php echo e(number_format((float) $price->price, 0, ',', ' ')); ?> <?php echo e($offer->currency); ?></td>
                                    <td><?php echo e($price->stock); ?></td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
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
        <div class="col-lg-6">
            <?php if (isset($component)) { $__componentOriginalba421f08b6b43aecb09f8eebe577a4f3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalba421f08b6b43aecb09f8eebe577a4f3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.form-section','data' => ['title' => 'Départs']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.form-section'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Départs']); ?>
                <?php if($offer->departures->isEmpty()): ?>
                    <p class="text-muted mb-0">Aucun départ renseigné.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead><tr><th>Départ</th><th>Retour</th><th>Statut</th><th>Places</th></tr></thead>
                            <tbody>
                            <?php $__currentLoopData = $offer->departures; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $departure): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td><?php echo e($departure->departure_date?->format('d/m/Y')); ?></td>
                                    <td><?php echo e($departure->return_date?->format('d/m/Y') ?: '�?"'); ?></td>
                                    <td><?php echo e($departure->status_label); ?></td>
                                    <td><?php echo e($departure->remaining_places); ?></td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
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

    <?php if (isset($component)) { $__componentOriginalba421f08b6b43aecb09f8eebe577a4f3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalba421f08b6b43aecb09f8eebe577a4f3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.form-section','data' => ['title' => 'Demandes reçues']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.form-section'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Demandes reçues']); ?>
        <?php if($offer->requests->isEmpty()): ?>
            <p class="text-muted mb-0">Aucune demande client liée à cette offre.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Date</th><th>Client</th><th>Statut</th><th></th></tr></thead>
                    <tbody>
                    <?php $__currentLoopData = $offer->requests->take(10); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $requestItem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td><?php echo e($requestItem->created_at?->format('d/m/Y H:i')); ?></td>
                            <td><?php echo e($requestItem->full_name); ?><br><small class="text-muted"><?php echo e($requestItem->phone); ?></small></td>
                            <td><?php echo e($requestItem->status_label); ?></td>
                            <td class="text-end">
                                <a href="<?php echo e(route('admin.economic-offers.requests.show', $requestItem)); ?>" class="aj-btn aj-btn-soft btn-sm">Voir</a>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
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
<?php $__env->stopSection(); ?>



<?php echo $__env->make('layouts.admin-v6', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\economic-offers\show.blade.php ENDPATH**/ ?>