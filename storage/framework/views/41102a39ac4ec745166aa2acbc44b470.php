<?php
    $selectedTourId = (int) ($preselectedTourId ?? old('tour_id'));
    $wpTitles = $wpTitles ?? collect();
?>

<section class="reservation-create__panel is-active" data-create-step="1" data-reservation-step="1">
    <div class="reservation-create__card">
        <div class="reservation-create__section-head">
            <div>
                <p class="reservation-create__eyebrow">�?tape 1</p>
                <h3 class="reservation-create__section-title">Sélection de la prestation</h3>
                <p class="reservation-create__section-subtitle">Choisissez le voyage et le départ avant de composer le dossier.</p>
            </div>
            <span class="reservation-create__pill">Réservation</span>
        </div>

        <?php if(isset($travelDateIncoherent) && $travelDateIncoherent): ?>
            <div class="reservation-create__notice reservation-create__notice--warn">
                La date de départ fournie ne correspond pas au voyage sélectionné. Elle a été ignorée.
            </div>
        <?php endif; ?>

        <div class="reservation-create__grid reservation-create__grid--two">
            <div class="reservation-create__field reservation-create__field--full">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="reservation-create__label mb-0" for="select-tour-id">Voyage / circuit <span>*</span></label>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btn-toggle-tour">Modifier</button>
                </div>
                <select class="reservation-create__input" required id="select-tour-id" disabled>
                    <option value="">Sélectionner un voyage�?�</option>
                    <?php $__currentLoopData = $voyages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $voyage): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            $label = $voyage->wp_post_id && $wpTitles->has($voyage->wp_post_id)
                                ? ($wpTitles->get($voyage->wp_post_id)->post_title ?? $voyage->name ?? $voyage->slug)
                                : ($voyage->name ?? $voyage->slug ?? 'Voyage #' . $voyage->id);
                        ?>
                        <option value="<?php echo e($voyage->id); ?>" data-price-from="<?php echo e((float) ($voyage->price_from ?? 0)); ?>" <?php echo e($selectedTourId === (int) $voyage->id ? 'selected' : ''); ?>>
                            <?php echo e($label); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <input type="hidden" name="tour_id" id="tour_id_hidden" value="<?php echo e(old('tour_id', $selectedTourId)); ?>">
            </div>
        </div>

        <div class="reservation-create__selection-card">
            <div class="reservation-create__selection-item">
                <span>Voyage sélectionné</span>
                <strong id="create-selected-trip-name">
                    <?php if($selectedTourId > 0): ?>
                        <?php echo e(optional($voyages->firstWhere('id', $selectedTourId))->name ?? 'Voyage préchargé'); ?>

                    <?php else: ?>
                        Aucune sélection
                    <?php endif; ?>
                </strong>
            </div>
            <div class="reservation-create__selection-item">
                <span>Date préchargée</span>
                <strong id="create-selected-date-name">
                    <?php echo e(isset($selectedTravelDate) && $selectedTravelDate ? $selectedTravelDate->date->translatedFormat('d M Y') : '�?"'); ?>

                </strong>
            </div>
        </div>
    </div>

    <?php echo $__env->make('admin.reservations.partials._hotel_rooms', [
        'tourHotelsWithRooms' => collect(),
        'reservation' => null,
        'hotelsRoomsUrl' => route('admin.reservations.hotels-rooms'),
        'voyageDeparturesUrl' => route('admin.reservations.voyage-departures'),
        'departureHotelsRoomsUrl' => route('admin.reservations.departure-hotels-rooms'),
        'selectedTravelDate' => $selectedTravelDate ?? null,
        'selectedDepartureId' => $selectedDepartureId ?? null,
        'compactAvailabilityOnly' => true,
    ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    <p class="reservation-create__helper" style="margin-top:1rem;">
        La répartition des chambres sera effectuée à l'étape 3 après la saisie des voyageurs.
    </p>

    <div class="reservation-create__step-errors" id="step-1-errors" hidden></div>
    <div class="reservation-create__actions">
        <span></span>
        <button type="button" class="reservation-create__button reservation-create__button--primary" data-create-next data-step-next="2">Continuer</button>
    </div>
</section>

<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\reservations\create\partials\step-prestation.blade.php ENDPATH**/ ?>