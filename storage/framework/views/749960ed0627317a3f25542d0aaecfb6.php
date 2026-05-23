<?php
    $s = fn (string $path, $default = '') => old('services.'.str_replace('.', '.', $path), data_get($services ?? [], $path, $default));
    $isActive = fn (string $key) => array_key_exists($key, $services ?? []);
?>

<div class="crq-service-config <?php echo e($isActive('flights') ? 'is-active' : ''); ?>" data-service-config="flights">
    <h4>Vols</h4>
    <div class="crq-grid-3">
        <input name="services[flights][departure_city]" class="crq-input" placeholder="Ville départ" value="<?php echo e($s('flights.departure_city')); ?>">
        <input name="services[flights][destination]" class="crq-input" placeholder="Destination" value="<?php echo e($s('flights.destination')); ?>">
        <select name="services[flights][trip_type]" class="crq-select"><option value="">Type trajet</option><option value="one_way" <?php if($s('flights.trip_type') === 'one_way'): echo 'selected'; endif; ?>>Aller simple</option><option value="round_trip" <?php if($s('flights.trip_type') === 'round_trip'): echo 'selected'; endif; ?>>Aller-retour</option></select>
        <select name="services[flights][stops]" class="crq-select"><option value="">Escale</option><option value="direct" <?php if($s('flights.stops') === 'direct'): echo 'selected'; endif; ?>>Direct</option><option value="stopover_ok" <?php if($s('flights.stops') === 'stopover_ok'): echo 'selected'; endif; ?>>Escale acceptée</option></select>
        <select name="services[flights][baggage]" class="crq-select"><option value="">Bagage inclus</option><option value="yes" <?php if($s('flights.baggage') === 'yes'): echo 'selected'; endif; ?>>Oui</option><option value="no" <?php if($s('flights.baggage') === 'no'): echo 'selected'; endif; ?>>Non</option></select>
        <select name="services[flights][class]" class="crq-select"><option value="">Classe</option><option value="economy" <?php if($s('flights.class') === 'economy'): echo 'selected'; endif; ?>>Economique</option><option value="business" <?php if($s('flights.class') === 'business'): echo 'selected'; endif; ?>>Business</option></select>
        <input name="services[flights][time_preference]" class="crq-input" placeholder="Préférence horaire" value="<?php echo e($s('flights.time_preference')); ?>">
    </div>
</div>

<div class="crq-service-config <?php echo e($isActive('accommodation') ? 'is-active' : ''); ?>" data-service-config="accommodation">
    <h4>Hébergement</h4>
    <div class="crq-grid-3">
        <select name="services[accommodation][type]" class="crq-select"><option value="">Type</option><?php $__currentLoopData = ['hotel'=>'Hôtel','apartment'=>'Appartement','villa'=>'Villa','riad'=>'Riad']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $v=>$l): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($v); ?>" <?php if($s('accommodation.type') === $v): echo 'selected'; endif; ?>><?php echo e($l); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select>
        <select name="services[accommodation][category]" class="crq-select"><option value="">Catégorie</option><?php $__currentLoopData = ['2'=>'2*','3'=>'3*','4'=>'4*','5'=>'5*','luxury'=>'Luxe']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $v=>$l): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($v); ?>" <?php if($s('accommodation.category') === $v): echo 'selected'; endif; ?>><?php echo e($l); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select>
        <input type="number" min="1" name="services[accommodation][nights]" class="crq-input" placeholder="Nombre de nuits" value="<?php echo e($s('accommodation.nights')); ?>">
        <input type="number" min="1" name="services[accommodation][rooms]" class="crq-input" placeholder="Nombre de chambres" value="<?php echo e($s('accommodation.rooms')); ?>">
        <select name="services[accommodation][room_type]" class="crq-select"><option value="">Type chambre</option><?php $__currentLoopData = ['single'=>'Simple','double'=>'Double','twin'=>'Twin','triple'=>'Triple','quad'=>'Quad']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $v=>$l): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($v); ?>" <?php if($s('accommodation.room_type') === $v): echo 'selected'; endif; ?>><?php echo e($l); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select>
        <select name="services[accommodation][board]" class="crq-select"><option value="">Pension</option><?php $__currentLoopData = ['room_only'=>'Logement seul','breakfast'=>'Petit-déjeuner','half_board'=>'Demi-pension','full_board'=>'Pension complète','all_inclusive'=>'All inclusive']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $v=>$l): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($v); ?>" <?php if($s('accommodation.board') === $v): echo 'selected'; endif; ?>><?php echo e($l); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select>
    </div>
</div>

<div class="crq-service-config <?php echo e($isActive('transfers') ? 'is-active' : ''); ?>" data-service-config="transfers">
    <h4>Transferts</h4>
    <div class="crq-grid-3">
        <input name="services[transfers][from]" class="crq-input" placeholder="De" value="<?php echo e($s('transfers.from')); ?>">
        <input name="services[transfers][to]" class="crq-input" placeholder="À" value="<?php echo e($s('transfers.to')); ?>">
        <select name="services[transfers][trip_type]" class="crq-select"><option value="">Trajet</option><option value="one_way" <?php if($s('transfers.trip_type') === 'one_way'): echo 'selected'; endif; ?>>Aller simple</option><option value="round_trip" <?php if($s('transfers.trip_type') === 'round_trip'): echo 'selected'; endif; ?>>Aller-retour</option></select>
        <select name="services[transfers][vehicle_type]" class="crq-select"><option value="">Véhicule</option><option value="standard" <?php if($s('transfers.vehicle_type') === 'standard'): echo 'selected'; endif; ?>>Standard</option><option value="van" <?php if($s('transfers.vehicle_type') === 'van'): echo 'selected'; endif; ?>>Van</option><option value="luxury" <?php if($s('transfers.vehicle_type') === 'luxury'): echo 'selected'; endif; ?>>Luxe</option></select>
        <textarea name="services[transfers][routes_note]" class="crq-textarea" placeholder="Plusieurs trajets / remarques"><?php echo e($s('transfers.routes_note')); ?></textarea>
    </div>
</div>

<div class="crq-service-config <?php echo e($isActive('excursions') ? 'is-active' : ''); ?>" data-service-config="excursions">
    <h4>Excursions</h4>
    <div class="crq-grid-3">
        <input name="services[excursions][themes]" class="crq-input" placeholder="Culturel, aventure, détente..." value="<?php echo e($s('excursions.themes')); ?>">
        <input name="services[excursions][region]" class="crq-input" placeholder="Ville / région" value="<?php echo e($s('excursions.region')); ?>">
        <input type="number" min="1" name="services[excursions][days]" class="crq-input" placeholder="Nombre de jours" value="<?php echo e($s('excursions.days')); ?>">
        <select name="services[excursions][guide]" class="crq-select"><option value="">Guide demandé</option><option value="yes" <?php if($s('excursions.guide') === 'yes'): echo 'selected'; endif; ?>>Oui</option><option value="no" <?php if($s('excursions.guide') === 'no'): echo 'selected'; endif; ?>>Non</option></select>
    </div>
</div>

<div class="crq-service-config <?php echo e($isActive('omra') ? 'is-active' : ''); ?>" data-service-config="omra">
    <h4>Omra</h4>
    <div class="crq-grid-3">
        <input name="services[omra][duration]" class="crq-input" placeholder="Durée" value="<?php echo e($s('omra.duration')); ?>">
        <input type="number" min="0" name="services[omra][budget_per_person]" class="crq-input" placeholder="Budget / personne" value="<?php echo e($s('omra.budget_per_person')); ?>">
        <select name="services[omra][room_type]" class="crq-select"><option value="">Chambre</option><?php $__currentLoopData = ['double'=>'Double','triple'=>'Triple','quad'=>'Quad','quintuple'=>'Quintuple']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $v=>$l): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($v); ?>" <?php if($s('omra.room_type') === $v): echo 'selected'; endif; ?>><?php echo e($l); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select>
        <input name="services[omra][board]" class="crq-input" placeholder="Pension" value="<?php echo e($s('omra.board')); ?>">
        <input name="services[omra][haram_proximity]" class="crq-input" placeholder="Proximité Haram Makkah / Médine" value="<?php echo e($s('omra.haram_proximity')); ?>">
        <select name="services[omra][under_1km]" class="crq-select"><option value="">Moins de 1km</option><option value="yes" <?php if($s('omra.under_1km') === 'yes'): echo 'selected'; endif; ?>>Oui</option><option value="no" <?php if($s('omra.under_1km') === 'no'): echo 'selected'; endif; ?>>Non</option></select>
    </div>
</div>

<div class="crq-service-config <?php echo e($isActive('visa') ? 'is-active' : ''); ?>" data-service-config="visa">
    <h4>Visa</h4>
    <div class="crq-grid-3">
        <input name="services[visa][country]" class="crq-input" placeholder="Pays" value="<?php echo e($s('visa.country')); ?>">
        <input type="number" min="1" name="services[visa][people]" class="crq-input" placeholder="Nombre de personnes" value="<?php echo e($s('visa.people')); ?>">
        <input name="services[visa][type]" class="crq-input" placeholder="Type visa" value="<?php echo e($s('visa.type')); ?>">
        <input type="date" name="services[visa][desired_date]" class="crq-input" value="<?php echo e($s('visa.desired_date')); ?>">
    </div>
</div>

<div class="crq-service-config <?php echo e($isActive('insurance') ? 'is-active' : ''); ?>" data-service-config="insurance">
    <h4>Assurance</h4>
    <div class="crq-grid-3">
        <input type="number" min="1" name="services[insurance][people]" class="crq-input" placeholder="Nombre de personnes" value="<?php echo e($s('insurance.people')); ?>">
        <input name="services[insurance][duration]" class="crq-input" placeholder="Durée" value="<?php echo e($s('insurance.duration')); ?>">
        <input name="services[insurance][coverage]" class="crq-input" placeholder="Type couverture" value="<?php echo e($s('insurance.coverage')); ?>">
    </div>
</div>

<div class="crq-service-config <?php echo e($isActive('other') ? 'is-active' : ''); ?>" data-service-config="other">
    <h4>Autre</h4>
    <textarea name="services[other][description]" class="crq-textarea" placeholder="Description libre"><?php echo e($s('other.description')); ?></textarea>
</div>
<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\reservations\custom-requests\service-configs.blade.php ENDPATH**/ ?>