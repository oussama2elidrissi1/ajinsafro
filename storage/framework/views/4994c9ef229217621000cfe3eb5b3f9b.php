<?php
    $opt = $option ?? null;
    $isNew = !$opt;
    $departurePlaces = $departurePlaces ?? collect();
    $showDeparturePlace = in_array($type ?? '', ['outbound', 'return'], true);
?>

<div class="flight-opt-card" data-flight-opt-index="<?php echo e($index); ?>">
    
    <input type="hidden" name="flight_options[<?php echo e($index); ?>][id]" value="<?php echo e($opt ? $opt->id : ''); ?>">
    <input type="hidden" name="flight_options[<?php echo e($index); ?>][type]" value="<?php echo e($type); ?>">
    <?php if($type === 'segment'): ?>
        <input type="hidden" name="flight_options[<?php echo e($index); ?>][day_number]" value="<?php echo e($opt ? $opt->day_number : 1); ?>">
    <?php else: ?>
        <input type="hidden" name="flight_options[<?php echo e($index); ?>][day_number]" value="<?php echo e($type === 'outbound' ? 1 : ($lastDayNumber ?? 1)); ?>">
    <?php endif; ?>

    
    <div class="flight-opt-header">
        <div class="flight-opt-route-display">

            <div style="flex: 1; display: flex; align-items: center; gap: 12px;">
                <div style="flex: 1;">
                    <input type="text" 
                           class="form-control form-control-sm flight-opt-from-city" 
                           name="flight_options[<?php echo e($index); ?>][from_city]" 
                           value="<?php echo e($opt ? $opt->from_city : ''); ?>" 
                           placeholder="From (ex: Paris)"
                           style="font-weight: 600;">
                </div>
                <div style="color: #999; font-size: 14px;">�?'</div>
                <div style="flex: 1;">
                    <input type="text" 
                           class="form-control form-control-sm flight-opt-to-city" 
                           name="flight_options[<?php echo e($index); ?>][to_city]" 
                           value="<?php echo e($opt ? $opt->to_city : ''); ?>" 
                           placeholder="To (ex: Rome)"
                           style="font-weight: 600;">
                </div>
            </div>

            <button type="button" class="btn btn-sm btn-icon btn-outline-danger flight-opt-remove ms-2" title="Supprimer ce vol" style="min-width: 40px;">
                <i class="bx bx-trash"></i>
            </button>
        </div>
    </div>

    
    <div class="flight-opt-body p-3">
        <div class="row g-3">
            
            <div class="col-md-6">
                <label class="form-label small">Compagnie aérienne</label>
                <select name="flight_options[<?php echo e($index); ?>][airline_id]" class="form-select form-select-sm">
                    <option value="">�?" Pas de compagnie �?"</option>
                    <?php $__currentLoopData = $airlines; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($a->id); ?>" <?php echo e($opt && $opt->airline_id == $a->id ? 'selected' : ''); ?>>
                            <?php echo e($a->name); ?> <?php if($a->code_iata): ?>(<?php echo e($a->code_iata); ?>)<?php endif; ?>
                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label small">Classe cabine</label>
                <select name="flight_options[<?php echo e($index); ?>][cabin]" class="form-select form-select-sm">
                    <?php $__currentLoopData = \App\Models\VoyageFlightOption::cabinOptions(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $v => $l): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($v); ?>" <?php echo e(($opt ? $opt->cabin : 'economy') == $v ? 'selected' : ''); ?>><?php echo e($l); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>

            
            <div class="col-md-4">
                <label class="form-label small">Date départ</label>
                <input type="date" class="form-control form-control-sm" 
                       name="flight_options[<?php echo e($index); ?>][departure_date]" 
                       value="<?php echo e($opt && $opt->depart_at ? $opt->depart_at->format('Y-m-d') : ''); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label small">Heure départ</label>
                <input type="time" class="form-control form-control-sm" 
                       name="flight_options[<?php echo e($index); ?>][departure_time]" 
                       value="<?php echo e($opt && $opt->depart_at ? $opt->depart_at->format('H:i') : ''); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label small">Heure arrivée</label>
                <input type="time" class="form-control form-control-sm" 
                       name="flight_options[<?php echo e($index); ?>][arrival_time]" 
                       value="<?php echo e($opt && $opt->arrive_at ? $opt->arrive_at->format('H:i') : ''); ?>">
            </div>

            
            <div class="col-md-6">
                <label class="form-label small">Bagages cabine (kg)</label>
                <input type="number" class="form-control form-control-sm" 
                       name="flight_options[<?php echo e($index); ?>][baggage_cabin_kg]" 
                       value="<?php echo e($opt ? $opt->baggage_cabin_kg : ''); ?>" 
                       min="0" placeholder="ex: 7">
            </div>
            <div class="col-md-6">
                <label class="form-label small">Bagages check-in (kg)</label>
                <input type="number" class="form-control form-control-sm" 
                       name="flight_options[<?php echo e($index); ?>][baggage_checkin_kg]" 
                       value="<?php echo e($opt ? $opt->baggage_checkin_kg : ''); ?>" 
                       min="0" placeholder="ex: 20">
            </div>

            
            <div class="col-md-6">
                <label class="form-label small">N° de vol</label>
                <input type="text" class="form-control form-control-sm" 
                       name="flight_options[<?php echo e($index); ?>][flight_number]" 
                       value="<?php echo e($opt ? $opt->flight_number : ''); ?>" 
                       placeholder="ex: AF1234">
            </div>
            <div class="col-md-6">
                <label class="form-label small">&nbsp;</label>
                <div class="form-check mt-2">
                    <input type="checkbox" class="form-check-input" 
                           name="flight_options[<?php echo e($index); ?>][is_tentative]" 
                           value="1" 
                           id="tentative-<?php echo e($index); ?>"
                           <?php echo e($opt && $opt->is_tentative ? 'checked' : ''); ?>>
                    <label class="form-check-label small" for="tentative-<?php echo e($index); ?>">
                        Tentative / Sous confirmation
                    </label>
                </div>
            </div>

            
            <?php if($showDeparturePlace): ?>
                <div class="col-12">
                    <label class="form-label small">Lieu de départ <span class="text-muted">(pour affichage client)</span></label>
                    <?php
                        $dpSorted = $departurePlaces->values();
                        $selDp = (string) old('flight_options.'.$index.'.departure_place_id', $opt ? (string) ($opt->departure_place_id ?? '') : '');
                    ?>
                    <select name="flight_options[<?php echo e($index); ?>][departure_place_id]" class="form-select form-select-sm ve-flight-departure-place-select">
                        <option value="">�?" Aucun �?"</option>
                        <?php $__currentLoopData = $dpSorted; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dpPos => $place): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php
                                $optVal = $place->id ? (string) $place->id : 'NEW_'.$dpPos;
                            ?>
                            <option value="<?php echo e($optVal); ?>" <?php if($selDp !== '' && $selDp === (string) $optVal): echo 'selected'; endif; ?>>
                                <?php echo e($place->name ?? ''); ?><?php echo e(isset($place->code) && $place->code !== '' ? ' (' . $place->code . ')' : ''); ?>

                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
            <?php endif; ?>

            
            <?php if($type === 'segment'): ?>
                <div class="col-md-4">
                    <label class="form-label small">Jour du programme</label>
                    <select name="flight_options[<?php echo e($index); ?>][day_number_edit]" class="form-select form-select-sm flight-opt-day">
                        <?php for($d = 1; $d <= ($lastDayNumber ?? 6); $d++): ?>
                            <option value="<?php echo e($d); ?>" <?php echo e($opt && $opt->day_number == $d ? 'selected' : ''); ?>>
                                Jour <?php echo e($d); ?>

                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\circuits\voyages\partials\_flight_option_card.blade.php ENDPATH**/ ?>