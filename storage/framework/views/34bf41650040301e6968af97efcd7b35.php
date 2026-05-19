<?php
    $modalAjax = $modalAjax ?? false;
    $layout = $layout ?? 'default';
?>
<div class="card border shadow-sm">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0"><i class="bx bx-hotel me-1 text-primary"></i> Hôtels pour ce départ</h5>
    </div>
    <div class="card-body">
        <div class="border rounded p-3 bg-light mb-4">
            <h6 class="small text-uppercase text-muted mb-3">Ajouter un hôtel</h6>
            <form method="post" action="<?php echo e(route('admin.circuits.voyages.departures.hotels.store', [$voyage, $departure])); ?>" class="row g-2 align-items-end ra-modal-ajax-form">
                <?php echo csrf_field(); ?>
                <?php echo $__env->make('admin.circuits.voyages.departures.partials._modal_ajax_hidden', ['modalAjax' => $modalAjax], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                <div class="col-md-4">
                    <label class="form-label small">Catalogue (optionnel)</label>
                    <select name="hotel_id" class="form-select form-select-sm">
                        <option value="">— Saisie manuelle —</option>
                        <?php $__currentLoopData = $hotelsCatalog; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $h): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($h->id); ?>"><?php echo e($h->name); ?> <?php if($h->city): ?> — <?php echo e($h->city); ?> <?php endif; ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Nom affiché</label>
                    <input type="text" name="hotel_name" class="form-control form-control-sm" placeholder="Si pas de catalogue">
                </div>
                <div class="col-md-1">
                    <label class="form-label small">Étoiles</label>
                    <input type="number" name="stars" class="form-control form-control-sm" min="0" max="5">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Adresse</label>
                    <input type="text" name="address" class="form-control form-control-sm">
                </div>
                <div class="col-md-1">
                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="new_hotel_active_<?php echo e($departure->id); ?>" checked>
                        <label class="form-check-label small" for="new_hotel_active_<?php echo e($departure->id); ?>">Actif</label>
                    </div>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-sm btn-primary">Ajouter</button>
                </div>
            </form>
        </div>

        <?php if($layout === 'accordion'): ?>
            <div class="accordion ra-hotels-accordion" id="ra-hotel-acc-<?php echo e($departure->id); ?>">
                <?php $__empty_1 = true; $__currentLoopData = $departure->departureHotels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dh): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <div class="accordion-item border rounded mb-2 overflow-hidden">
                        <h2 class="accordion-header" id="ra-hotel-h-<?php echo e($dh->id); ?>">
                            <button class="accordion-button <?php echo e($loop->first ? '' : 'collapsed'); ?>" type="button" data-bs-toggle="collapse" data-bs-target="#ra-hotel-c-<?php echo e($dh->id); ?>" aria-expanded="<?php echo e($loop->first ? 'true' : 'false'); ?>" aria-controls="ra-hotel-c-<?php echo e($dh->id); ?>">
                                <span class="fw-semibold"><?php echo e($dh->hotel_name ?: 'Hôtel #'.$dh->id); ?></span>
                                <?php if($dh->hotel_id): ?><span class="badge bg-light text-dark ms-2">Catalogue <?php echo e($dh->hotel_id); ?></span><?php endif; ?>
                            </button>
                        </h2>
                        <div id="ra-hotel-c-<?php echo e($dh->id); ?>" class="accordion-collapse collapse <?php echo e($loop->first ? 'show' : ''); ?>" aria-labelledby="ra-hotel-h-<?php echo e($dh->id); ?>">
                            <div class="accordion-body">
                                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 pb-2 border-bottom">
                                    <form method="post" action="<?php echo e(route('admin.circuits.voyages.departures.hotels.update', [$voyage, $dh])); ?>" class="d-flex flex-wrap gap-1 align-items-center ra-modal-ajax-form">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('PUT'); ?>
                                        <?php echo $__env->make('admin.circuits.voyages.departures.partials._modal_ajax_hidden', ['modalAjax' => $modalAjax], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                                        <input type="hidden" name="hotel_id" value="<?php echo e($dh->hotel_id); ?>">
                                        <input type="text" name="hotel_name" value="<?php echo e($dh->hotel_name); ?>" class="form-control form-control-sm" style="max-width:160px" placeholder="Nom">
                                        <input type="number" name="sort_order" value="<?php echo e($dh->sort_order); ?>" class="form-control form-control-sm" style="width:70px" min="0" title="Ordre">
                                        <div class="form-check form-check-inline mb-0">
                                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="act_acc<?php echo e($dh->id); ?>" <?php echo e($dh->is_active ? 'checked' : ''); ?>>
                                            <label class="form-check-label small" for="act_acc<?php echo e($dh->id); ?>">Actif</label>
                                        </div>
                                        <button type="submit" class="btn btn-sm btn-outline-primary">MAJ</button>
                                    </form>
                                    <form method="post" action="<?php echo e(route('admin.circuits.voyages.departures.hotels.destroy', [$voyage, $dh])); ?>" class="ra-modal-ajax-form ra-hotel-destroy-form" data-confirm-msg="Retirer cet hôtel du départ ?">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('DELETE'); ?>
                                        <?php echo $__env->make('admin.circuits.voyages.departures.partials._modal_ajax_hidden', ['modalAjax' => $modalAjax], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Retirer</button>
                                    </form>
                                </div>
                                <?php echo $__env->make('admin.circuits.voyages.departures.partials._rooms_table', ['departure' => $departure, 'voyage' => $voyage, 'departureHotel' => $dh, 'roomStatuses' => $roomStatuses, 'modalAjax' => $modalAjax], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <p class="text-muted mb-0">Aucun hôtel. Ajoutez-en un ci-dessus pour gérer le stock par chambre.</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <?php $__empty_1 = true; $__currentLoopData = $departure->departureHotels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dh): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div id="rooms-<?php echo e($dh->id); ?>" class="card mb-3 border">
                    <div class="card-header bg-white py-2 d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            <strong><?php echo e($dh->hotel_name ?: 'Hôtel #'.$dh->id); ?></strong>
                            <?php if($dh->hotel_id): ?><span class="badge bg-light text-dark ms-1">ID catalogue <?php echo e($dh->hotel_id); ?></span><?php endif; ?>
                        </div>
                        <div class="d-flex flex-wrap gap-1 align-items-center">
                            <form method="post" action="<?php echo e(route('admin.circuits.voyages.departures.hotels.update', [$voyage, $dh])); ?>" class="d-flex flex-wrap gap-1 align-items-center ra-modal-ajax-form">
                                <?php echo csrf_field(); ?>
                                <?php echo method_field('PUT'); ?>
                                <?php echo $__env->make('admin.circuits.voyages.departures.partials._modal_ajax_hidden', ['modalAjax' => $modalAjax], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                                <input type="hidden" name="hotel_id" value="<?php echo e($dh->hotel_id); ?>">
                                <input type="text" name="hotel_name" value="<?php echo e($dh->hotel_name); ?>" class="form-control form-control-sm" style="max-width:160px" placeholder="Nom">
                                <input type="number" name="sort_order" value="<?php echo e($dh->sort_order); ?>" class="form-control form-control-sm" style="width:70px" min="0" title="Ordre">
                                <div class="form-check form-check-inline mb-0">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="act<?php echo e($dh->id); ?>" <?php echo e($dh->is_active ? 'checked' : ''); ?>>
                                    <label class="form-check-label small" for="act<?php echo e($dh->id); ?>">Actif</label>
                                </div>
                                <button type="submit" class="btn btn-sm btn-outline-primary">MAJ</button>
                            </form>
                            <form method="post" action="<?php echo e(route('admin.circuits.voyages.departures.hotels.destroy', [$voyage, $dh])); ?>" class="ra-modal-ajax-form ra-hotel-destroy-form" data-confirm-msg="Retirer cet hôtel du départ ?">
                                <?php echo csrf_field(); ?>
                                <?php echo method_field('DELETE'); ?>
                                <?php echo $__env->make('admin.circuits.voyages.departures.partials._modal_ajax_hidden', ['modalAjax' => $modalAjax], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                                <button type="submit" class="btn btn-sm btn-outline-danger">Retirer</button>
                            </form>
                        </div>
                    </div>
                    <div class="card-body pt-2">
                        <?php echo $__env->make('admin.circuits.voyages.departures.partials._rooms_table', ['departure' => $departure, 'voyage' => $voyage, 'departureHotel' => $dh, 'roomStatuses' => $roomStatuses, 'modalAjax' => $modalAjax], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <p class="text-muted mb-0">Aucun hôtel. Ajoutez-en un ci-dessus pour gérer le stock par chambre.</p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\circuits\voyages\departures\partials\_hotels_section.blade.php ENDPATH**/ ?>