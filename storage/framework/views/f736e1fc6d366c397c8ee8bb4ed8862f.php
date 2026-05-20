

<?php $__env->startSection('title', 'Finaliser la réservation - ' . $voyage->name); ?>

<?php $__env->startSection('content'); ?>
<div class="container my-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h3 class="mb-2"><?php echo e($voyage->name); ?></h3>
                            <p class="text-muted mb-0">
                                <i class="bx bx-map me-1"></i> <?php echo e($voyage->destination); ?>

                                <span class="mx-2">|</span>
                                <i class="bx bx-time me-1"></i> <?php echo e($voyage->duration_text); ?>

                            </p>
                        </div>
                        <?php if($voyage->featured_image_url): ?>
                            <img src="<?php echo e($voyage->featured_image_url); ?>" alt="<?php echo e($voyage->name); ?>" 
                                class="rounded" style="height: 80px; width: auto; object-fit: cover;">
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            
            <div class="alert alert-warning d-flex align-items-center mb-4" role="alert">
                <i class="bx bx-time-five font-size-24 me-3"></i>
                <div class="flex-grow-1">
                    <strong>Prix bloqué pour</strong>
                    <div class="countdown font-size-18 fw-bold text-dark" id="priceCountdown" data-remaining="<?php echo e($remainingSeconds); ?>">
                        <span id="minutes">--</span>:<span id="seconds">--</span>
                    </div>
                </div>
            </div>

            
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bx bx-group me-2"></i>Voyageurs</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="text-center p-3 border rounded">
                                <div class="font-size-24 text-primary mb-2"><?php echo e($session->pax_adults); ?></div>
                                <div class="text-muted">Adultes</div>
                            </div>
                        </div>
                        <?php if($session->pax_children > 0): ?>
                            <div class="col-md-4">
                                <div class="text-center p-3 border rounded">
                                    <div class="font-size-24 text-info mb-2"><?php echo e($session->pax_children); ?></div>
                                    <div class="text-muted">Enfants</div>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if($session->pax_infants > 0): ?>
                            <div class="col-md-4">
                                <div class="text-center p-3 border rounded">
                                    <div class="font-size-24 text-success mb-2"><?php echo e($session->pax_infants); ?></div>
                                    <div class="text-muted">Bébés</div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bx bx-calendar me-2"></i>Programme</h5>
                </div>
                <div class="card-body">
                    <div class="accordion" id="accordionProgram">
                        <?php $__currentLoopData = $days; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $day): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingDay<?php echo e($day['day_number']); ?>">
                                    <button class="accordion-button <?php echo e($index > 0 ? 'collapsed' : ''); ?>" type="button" 
                                        data-bs-toggle="collapse" data-bs-target="#collapseDay<?php echo e($day['day_number']); ?>" 
                                        aria-expanded="<?php echo e($index === 0 ? 'true' : 'false'); ?>">
                                        <strong>Jour <?php echo e($day['day_number']); ?> : <?php echo e($day['title']); ?></strong>
                                        <?php if($day['city']): ?>
                                            <span class="badge bg-soft-primary text-primary ms-2"><?php echo e($day['city']); ?></span>
                                        <?php endif; ?>
                                    </button>
                                </h2>
                                <div id="collapseDay<?php echo e($day['day_number']); ?>" 
                                    class="accordion-collapse collapse <?php echo e($index === 0 ? 'show' : ''); ?>" 
                                    data-bs-parent="#accordionProgram">
                                    <div class="accordion-body">
                                        <?php if($day['description']): ?>
                                            <p class="text-muted mb-3"><?php echo e($day['description']); ?></p>
                                        <?php endif; ?>
                                        
                                        <?php if(!empty($day['items'])): ?>
                                            <h6 class="mb-2">Inclus :</h6>
                                            <ul class="list-unstyled">
                                                <?php $__currentLoopData = $day['items']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <?php if($item['selected'] ?? false): ?>
                                                        <li class="mb-2">
                                                            <i class="bx bx-check-circle text-success me-2"></i>
                                                            <strong><?php echo e($item['title']); ?></strong>
                                                            <?php if(!$item['included']): ?>
                                                                <span class="badge bg-soft-warning text-warning">Option</span>
                                                            <?php endif; ?>
                                                        </li>
                                                    <?php endif; ?>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </ul>
                                        <?php endif; ?>

                                        <?php if($day['meals']['breakfast'] || $day['meals']['lunch'] || $day['meals']['dinner']): ?>
                                            <div class="mt-3">
                                                <small class="text-muted">
                                                    <i class="bx bx-restaurant me-1"></i>
                                                    <?php if($day['meals']['breakfast']): ?> Petit-déjeuner <?php endif; ?>
                                                    <?php if($day['meals']['lunch']): ?> Déjeuner <?php endif; ?>
                                                    <?php if($day['meals']['dinner']): ?> Dîner <?php endif; ?>
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
            </div>

            
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bx bx-receipt me-2"></i>Détail du prix</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless mb-0">
                        <tbody>
                            <?php $__currentLoopData = $breakdown; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td><?php echo e($item['label']); ?></td>
                                    <td class="text-end">
                                        <strong class="<?php echo e($key === 'total_group' ? 'font-size-18 text-primary' : ''); ?>">
                                            <?php echo e($item['formatted']); ?>

                                        </strong>
                                    </td>
                                </tr>
                                <?php if($key === 'total_per_person'): ?>
                                    <tr>
                                        <td colspan="2"><hr></td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
            </div>

            
            <div class="card shadow-sm">
                <div class="card-body">
                    <form action="<?php echo e(route('booking.checkout.process', $checkoutToken->token)); ?>" method="POST">
                        <?php echo csrf_field(); ?>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="acceptTerms" required>
                            <label class="form-check-label" for="acceptTerms">
                                J'accepte les <a href="#" target="_blank">conditions générales de vente</a>
                            </label>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg waves-effect waves-light" id="btnConfirm">
                                <i class="bx bx-check-circle me-2"></i>
                                Confirmer la réservation
                            </button>
                            <a href="<?php echo e(route('front.voyages.show', $voyage->slug)); ?>" class="btn btn-outline-secondary">
                                <i class="bx bx-arrow-back me-2"></i>
                                Retour au voyage
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $__env->startPush('scripts'); ?>
<script>
    // Countdown timer
    let remainingSeconds = parseInt(document.getElementById('priceCountdown').dataset.remaining);
    
    function updateCountdown() {
        if (remainingSeconds <= 0) {
            // Price lock expired
            document.getElementById('btnConfirm').disabled = true;
            document.querySelector('.alert-warning').classList.remove('alert-warning');
            document.querySelector('.alert').classList.add('alert-danger');
            document.querySelector('.alert strong').textContent = 'Le délai de réservation a expiré !';
            document.getElementById('priceCountdown').innerHTML = '<span class="text-danger">EXPIRÉ</span>';
            return;
        }
        
        const minutes = Math.floor(remainingSeconds / 60);
        const seconds = remainingSeconds % 60;
        
        document.getElementById('minutes').textContent = String(minutes).padStart(2, '0');
        document.getElementById('seconds').textContent = String(seconds).padStart(2, '0');
        
        remainingSeconds--;
        setTimeout(updateCountdown, 1000);
    }
    
    updateCountdown();
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.front', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\booking\checkout.blade.php ENDPATH**/ ?>