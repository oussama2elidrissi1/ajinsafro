
<?php $__env->startSection('title'); ?>
    Fiche voyage â€“ <?php echo e($voyage->name); ?>

<?php $__env->stopSection(); ?>
<?php $__env->startSection('content'); ?>
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18"><?php echo e($voyage->name); ?></h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="<?php echo e(route('admin.dashboard')); ?>">Admin</a></li>
                        <li class="breadcrumb-item"><a href="<?php echo e(route('admin.circuits.voyages.index')); ?>">Voyages</a></li>
                        <li class="breadcrumb-item active">Fiche</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-12">
            <a href="<?php echo e(route('admin.circuits.voyages.edit', $voyage)); ?>" class="btn btn-primary waves-effect waves-light me-2">Modifier le voyage</a>
            <a href="<?php echo e(route('admin.circuits.voyages.index')); ?>" class="btn btn-secondary waves-effect">Retour Ã  la liste</a>
        </div>
    </div>

    
    <div class="card mb-4">
        <div class="card-body">
            <h1 class="h3 mb-2"><?php echo e($voyage->name); ?></h1>
            <?php if($voyage->accroche): ?>
                <p class="text-muted lead mb-2"><?php echo e($voyage->accroche); ?></p>
            <?php endif; ?>
            <?php if($voyage->destination): ?>
                <p class="mb-1"><strong>Destination :</strong> <?php echo e($voyage->destination); ?></p>
            <?php endif; ?>
            <?php if($voyage->duration_text): ?>
                <p class="mb-1"><strong>DurÃ©e :</strong> <?php echo e($voyage->duration_text); ?></p>
            <?php endif; ?>
            <?php if(!empty($meta['min_people'])): ?>
                <p class="mb-1"><strong>Minimum personnes :</strong> <?php echo e($meta['min_people']); ?></p>
            <?php endif; ?>
            <?php if(isset($meta['max_people']) && $meta['max_people'] !== '' && $meta['max_people'] !== null): ?>
                <p class="mb-1"><strong>Nombre de personnes :</strong> <?php echo e($meta['max_people']); ?></p>
            <?php endif; ?>
            <?php if(isset($meta['places']) && $meta['places'] !== '' && $meta['places'] !== null): ?>
                <p class="mb-0"><strong>Places :</strong> <?php echo e($meta['places']); ?></p>
            <?php elseif(isset($meta['max_people']) && $meta['max_people'] !== '' && $meta['max_people'] !== null): ?>
                <p class="mb-0"><strong>Places :</strong> <?php echo e($meta['max_people']); ?></p>
            <?php endif; ?>
        </div>
    </div>

    
    <?php if($voyage->price_from !== null || $voyage->old_price !== null): ?>
        <div class="card mb-4">
            <div class="card-body">
                <h4 class="card-title mb-3">Prix & Promotion</h4>
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <p class="mb-1 font-size-18 fw-medium">Ã€ partir de <?php echo e(number_format($voyage->price_from ?? 0, 0, ',', ' ')); ?> <?php echo e($voyage->currency_symbol); ?></p>
                        <?php if($voyage->old_price && $voyage->old_price > ($voyage->price_from ?? 0)): ?>
                            <p class="text-muted mb-0">Valeur : <?php echo e(number_format($voyage->old_price, 0, ',', ' ')); ?> <?php echo e($voyage->currency_symbol); ?></p>
                        <?php endif; ?>
                    </div>
                    <?php if($voyage->discount_percent !== null && $voyage->discount_percent > 0): ?>
                        <div class="col-md-6 text-md-end">
                            <span class="badge bg-danger font-size-14 me-2">Remise : <?php echo e($voyage->discount_percent); ?> %</span>
                            <span class="badge bg-success font-size-14">Ã‰conomie : <?php echo e(number_format($voyage->discount_amount, 0, ',', ' ')); ?> <?php echo e($voyage->currency_symbol); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    
    <?php if(optional($voyage->departures)->isNotEmpty()): ?>
        <div class="card mb-4">
            <div class="card-body">
                <h4 class="card-title mb-3">DÃ©parts</h4>
                <?php if($voyage->departure_policy): ?>
                    <p class="text-muted small mb-3"><?php echo e($voyage->departure_policy); ?></p>
                <?php endif; ?>
                <div class="table-responsive">
                    <table class="table table-sm table-centered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $voyage->departures; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dep): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td><?php echo e($dep->start_date->format('d/m/Y')); ?></td>
                                    <td><span class="badge bg-<?php echo e($dep->status === 'open' ? 'success' : ($dep->status === 'full' ? 'warning' : 'secondary')); ?>"><?php echo e($dep->status_label); ?></span></td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

    
    <?php if(isset($programDays) && $programDays->isNotEmpty()): ?>
        <div class="card mb-4">
            <div class="card-body">
                <h4 class="card-title mb-4">Programme du circuit</h4>
                <div class="accordion" id="programme-accordion">
                    <?php $__currentLoopData = $programDays; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $entry): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            $day = $entry['day'];
                            $activities = $entry['activities'];
                            $mode = $day->mode ?? 'program';
                            $includedActivities = $activities->where('is_included', 1);
                            $dayTitleDisplay = !empty($day->day_title) ? $day->day_title : 'Jour ' . $day->day_number;
                        ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button <?php echo e($idx > 0 ? 'collapsed' : ''); ?>" type="button" data-bs-toggle="collapse" data-bs-target="#programme-day-<?php echo e($idx); ?>" aria-expanded="<?php echo e($idx === 0 ? 'true' : 'false'); ?>" aria-controls="programme-day-<?php echo e($idx); ?>">
                                    <span class="fw-semibold">JOUR <?php echo e($day->day_number); ?></span>
                                    <?php if(!empty($dayTitleDisplay)): ?>
                                        <span class="ms-2 text-muted">â€“ <?php echo e($dayTitleDisplay); ?></span>
                                    <?php endif; ?>
                                    <?php if($mode === 'free'): ?>
                                        <span class="badge bg-secondary ms-2">Jour libre</span>
                                    <?php endif; ?>
                                </button>
                            </h2>
                            <div id="programme-day-<?php echo e($idx); ?>" class="accordion-collapse collapse <?php echo e($idx === 0 ? 'show' : ''); ?>" data-bs-parent="#programme-accordion">
                                <div class="accordion-body">
                                    <?php if(!empty($day->notes)): ?>
                                        <div class="text-muted small mb-3"><?php echo nl2br(e($day->notes)); ?></div>
                                    <?php endif; ?>
                                    <?php if($includedActivities->isNotEmpty()): ?>
                                        <ul class="list-unstyled mb-0">
                                            <?php $__currentLoopData = $includedActivities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $da): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <li class="mb-2">
                                                    <span class="fw-medium"><?php echo e($da->custom_title ?: (optional($da->activity)->title ?? 'ActivitÃ©')); ?></span>
                                                    <?php if($da->is_mandatory): ?>
                                                        <span class="badge bg-primary ms-1">Obligatoire</span>
                                                    <?php endif; ?>
                                                    <?php if($da->custom_description || optional($da->activity)->description): ?>
                                                        <div class="text-muted small mt-1"><?php echo nl2br(e($da->custom_description ?: optional($da->activity)->description)); ?></div>
                                                    <?php endif; ?>
                                                </li>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </ul>
                                    <?php else: ?>
                                        <p class="text-muted small mb-0">Aucune activitÃ©</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if($voyage->description): ?>
        <div class="card mb-4">
            <div class="card-body">
                <h4 class="card-title mb-3">Description</h4>
                <div class="text-muted"><?php echo nl2br(e($voyage->description)); ?></div>
            </div>
        </div>
    <?php endif; ?>
<?php $__env->stopSection(); ?>
<?php $__env->startPush('scripts'); ?>
    <script src="<?php echo e(URL::asset('build/js/app.js')); ?>"></script>
<?php $__env->stopPush(); ?>
<?php $__env->startPush('styles'); ?>
<style>
.program-html ul { padding-left: 1.25rem; }
.program-html ol { padding-left: 1.25rem; }
.program-html li { margin-bottom: 0.25rem; }
</style>
<?php $__env->stopPush(); ?>


<?php echo $__env->make('layouts.admin-v6', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\circuits\voyages\show.blade.php ENDPATH**/ ?>