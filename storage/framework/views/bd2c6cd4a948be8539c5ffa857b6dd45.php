
<?php $__env->startSection('title', 'DÃ©tail hÃ´tel'); ?>

<?php $__env->startSection('content'); ?>
    <div class="row mb-3">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18"><?php echo e($hotel->name); ?></h4>
                <div>
                    <a href="<?php echo e(route('admin.hotels.edit', $hotel)); ?>" class="btn btn-outline-primary btn-sm">Modifier</a>
                    <a href="<?php echo e(route('admin.hotels.index')); ?>" class="btn btn-outline-secondary btn-sm">Retour</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-light d-flex align-items-center">
                    <h5 class="mb-0">Informations gÃ©nÃ©rales</h5>
                    <span class="badge bg-<?php echo e($hotel->is_active ? 'success' : 'secondary'); ?> ms-auto">
                        <?php echo e($hotel->is_active ? 'Actif' : 'Inactif'); ?>

                    </span>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-3 align-items-start">
                        <?php if($hotel->main_image_path): ?>
                            <img src="<?php echo e(asset('storage/'.$hotel->main_image_path)); ?>" alt=""
                                 class="rounded flex-shrink-0" style="width:180px;height:120px;object-fit:cover;">
                        <?php endif; ?>
                        <div class="flex-grow-1 min-w-0">
                            <h4 class="mb-2"><?php echo e($hotel->name); ?></h4>
                            <table class="table table-sm table-borderless mb-0 text-muted small">
                                <tbody>
                                    <?php if($hotel->address): ?>
                                        <tr><td class="text-nowrap pe-2 fw-medium text-dark">Adresse</td><td><?php echo e($hotel->address); ?></td></tr>
                                    <?php endif; ?>
                                    <tr><td class="text-nowrap pe-2 fw-medium text-dark">Ville</td><td><?php echo e($hotel->city ?? 'â€”'); ?></td></tr>
                                    <tr><td class="text-nowrap pe-2 fw-medium text-dark">Pays</td><td><?php echo e($hotel->country ?? 'â€”'); ?></td></tr>
                                    <?php if($hotel->latitude && $hotel->longitude): ?>
                                        <tr><td class="text-nowrap pe-2 fw-medium text-dark">CoordonnÃ©es</td><td><?php echo e($hotel->latitude); ?>, <?php echo e($hotel->longitude); ?></td></tr>
                                    <?php endif; ?>
                                    <tr><td class="text-nowrap pe-2 fw-medium text-dark">Note</td><td>
                                        <?php if($hotel->rating_average > 0): ?>
                                            <span class="badge bg-warning text-dark"><?php echo e($hotel->rating_average); ?>/5</span>
                                            <span class="ms-1">(<?php echo e($hotel->reviews_count); ?> avis)</span>
                                        <?php else: ?>
                                            Aucune note
                                        <?php endif; ?>
                                    </td></tr>
                                    <tr><td class="text-nowrap pe-2 fw-medium text-dark">Galerie</td><td><?php echo e($hotel->images->count()); ?> image(s)</td></tr>
                                    <tr><td class="text-nowrap pe-2 fw-medium text-dark">Types de chambres</td><td><?php echo e($hotel->roomTypes->count()); ?> type(s)</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php if($hotel->description): ?>
                        <hr>
                        <p class="mb-0"><?php echo e($hotel->description); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <?php if($hotel->images->isNotEmpty()): ?>
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Galerie</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            <?php $__currentLoopData = $hotel->images; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $img): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="col-md-3 col-4">
                                    <img src="<?php echo e(asset('storage/'.$img->file_path)); ?>" alt="" class="rounded w-100"
                                         style="height:90px;object-fit:cover;">
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if($hotel->amenities->isNotEmpty()): ?>
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Ã‰quipements</h5>
                    </div>
                    <div class="card-body">
                        <?php $__currentLoopData = $hotel->amenities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $amenity): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <span class="badge bg-light text-dark border me-1 mb-1">
                                <?php if($amenity->icon): ?><i class="<?php echo e($amenity->icon); ?> me-1"></i><?php endif; ?>
                                <?php echo e($amenity->label); ?>

                            </span>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-4">
            <?php if($hotel->reviews->isNotEmpty()): ?>
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Derniers avis</h5>
                    </div>
                    <div class="card-body">
                        <?php $__currentLoopData = $hotel->reviews; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $review): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="mb-2">
                                <span class="badge bg-warning text-dark"><?php echo e($review->rating); ?>/5</span>
                                <span class="text-muted small ms-1"><?php echo e($review->author_name ?? 'Client'); ?></span>
                                <?php if($review->comment): ?>
                                    <div class="small mt-1"><?php echo e($review->comment); ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    
    <?php if($hotel->roomTypes->isNotEmpty()): ?>
        <div class="row mt-2">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Types de chambres â€“ dÃ©tail</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Type</th>
                                        <th>Code</th>
                                        <th>CapacitÃ©</th>
                                        <th>QuantitÃ©</th>
                                        <th>Prix</th>
                                        <th>Description</th>
                                        <th>Options chambre</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $__currentLoopData = $hotel->roomTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <tr>
                                            <td><strong><?php echo e($rt->name); ?></strong></td>
                                            <td><code class="small"><?php echo e($rt->code ?? 'â€”'); ?></code></td>
                                            <td><?php echo e($rt->capacity_adults); ?> adulte(s) / <?php echo e($rt->capacity_children); ?> enfant(s)</td>
                                            <td><?php echo e($rt->quantity); ?></td>
                                            <td>
                                                <?php if($rt->base_price !== null): ?>
                                                    <?php echo e(number_format((float) $rt->base_price, 0, ',', ' ')); ?> <?php echo e($rt->currency ?? 'MAD'); ?>

                                                <?php else: ?>
                                                    <span class="text-muted">â€”</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="small text-muted" style="max-width:200px;">
                                                <?php echo e($rt->description ? \Str::limit($rt->description, 60) : 'â€”'); ?>

                                            </td>
                                            <td class="small">
                                                <?php if(is_array($rt->amenities) && count($rt->amenities) > 0): ?>
                                                    <?php $__currentLoopData = $rt->amenities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <span class="badge bg-light text-dark border me-1"><?php echo e(is_array($opt) ? ($opt['label'] ?? $opt['name'] ?? json_encode($opt)) : $opt); ?></span>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">â€”</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.admin-v6', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\hotels\show.blade.php ENDPATH**/ ?>