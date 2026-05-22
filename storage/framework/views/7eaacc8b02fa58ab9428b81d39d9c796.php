<?php $__env->startSection('title', 'Hôtels — RapidAPI Booking'); ?>

<?php
    $cssPath = public_path('css/rapidapi-hotels.css');
    $cssV = is_file($cssPath) ? filemtime($cssPath) : '1';
?>
<?php $__env->startPush('styles'); ?>
    <link rel="stylesheet" href="<?php echo e(asset('css/rapidapi-hotels.css')); ?>?v=<?php echo e($cssV); ?>">
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
    <div class="rap-page">
        <header class="rap-hero">
            <div class="rap-inner">
                <p class="rap-kicker">RapidAPI · Booking COM</p>
                <h1 class="rap-title">Rechercher un hôtel</h1>
                <p class="rap-lead">
                    Saisissez une <strong>ville</strong> : résolution de la destination puis liste d’hébergements (test d’intégration).
                </p>

                <form class="rap-form" method="get" action="<?php echo e(route('rapidapi.hotels.index', [], false)); ?>">
                    <input type="hidden" name="search" value="1">
                    <div class="rap-form-grid">
                        <div class="rap-field rap-field--city">
                            <label for="rap-city">Ville</label>
                            <input type="text" name="city" id="rap-city" value="<?php echo e(old('city', $city ?? '')); ?>"
                                class="rap-input" placeholder="ex. Paris, Marrakech, Berlin…" required autocomplete="off">
                        </div>
                        <div class="rap-field">
                            <label for="rap-checkin">Arrivée</label>
                            <input type="date" name="checkin" id="rap-checkin" value="<?php echo e(old('checkin', $checkin)); ?>" class="rap-input" required>
                        </div>
                        <div class="rap-field">
                            <label for="rap-checkout">Départ</label>
                            <input type="date" name="checkout" id="rap-checkout" value="<?php echo e(old('checkout', $checkout)); ?>" class="rap-input" required>
                        </div>
                        <div class="rap-field rap-field--narrow">
                            <label for="rap-adults">Adultes</label>
                            <input type="number" name="adults" id="rap-adults" value="<?php echo e(old('adults', $adults)); ?>" min="1" max="6" class="rap-input" required>
                        </div>
                        <div class="rap-field rap-field--action">
                            <button type="submit" class="rap-btn">Rechercher</button>
                        </div>
                    </div>
                </form>
            </div>
        </header>

        <main class="rap-main">
            <div class="rap-inner">
                <?php if($errors->any()): ?>
                    <div class="rap-alert rap-alert--error" role="alert">
                        <ul class="mb-0 pl-4"><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $err): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($err); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></ul>
                    </div>
                <?php endif; ?>

                <?php if(! $configured): ?>
                    <p class="rap-neutral">Ajoutez <code>RAPIDAPI_KEY</code> et vérifiez <code>RAPIDAPI_HOST</code> / <code>RAPIDAPI_BASE_URL</code> dans <code>.env</code>.</p>
                <?php elseif($error): ?>
                    <div class="rap-alert rap-alert--error" role="alert"><?php echo e($error); ?></div>
                <?php elseif($configured && empty($searchRequested)): ?>
                    <p class="rap-neutral">Indiquez une ville et cliquez sur <strong>Rechercher</strong> pour tester le flux RapidAPI.</p>
                <?php endif; ?>

                <?php if($configured && $searchRequested && $destinationLabel && ! $error): ?>
                    <p class="rap-meta">
                        Destination : <strong><?php echo e($destinationLabel); ?></strong>
                        <?php if($destId !== null): ?>
                            <span class="rap-meta-id">· dest_id <code><?php echo e($destId); ?></code></span>
                        <?php endif; ?>
                        <?php if($destType): ?>
                            <span class="rap-meta-id">· <?php echo e($destType); ?></span>
                        <?php endif; ?>
                    </p>
                <?php endif; ?>

                <?php if(config('app.debug') && ! empty($apiDebug)): ?>
                    <details class="rap-debug-wrap">
                        <summary class="rap-debug-summary">Debug API (développement)</summary>
                        <pre class="rap-debug"><?php echo json_encode($apiDebug, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE, 512) ?></pre>
                    </details>
                <?php endif; ?>

                <?php if(count($hotels ?? []) > 0): ?>
                    <div class="rap-grid">
                        <?php $__currentLoopData = $hotels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $hotel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php
                                $hotelPk = $hotel['id'] ?? data_get($hotel, 'raw.hotel_id');
                                $hotelDetailUrl = filled($hotelPk)
                                    ? route('rapidapi.hotels.show', [
                                        'hotelId' => $hotelPk,
                                        'city' => $city ?? request('city'),
                                        'checkin' => $checkin ?? request('checkin'),
                                        'checkout' => $checkout ?? request('checkout'),
                                        'adults' => $adults ?? request('adults'),
                                        'search' => 1,
                                    ], false)
                                    : '#';
                            ?>
                            <?php if(filled($hotelPk)): ?>
                                <a href="<?php echo e($hotelDetailUrl); ?>" class="rap-card rap-card--link">
                            <?php else: ?>
                                <div class="rap-card">
                            <?php endif; ?>
                                <div class="rap-card__media">
                                    <img
                                        src="<?php echo e($hotel['image']); ?>"
                                        alt="<?php echo e($hotel['name']); ?>"
                                        loading="lazy"
                                        decoding="async"
                                        onerror="this.onerror=null;this.src='/images/hotel-placeholder.svg';"
                                    >
                                    <?php if(($hotel['rating'] ?? null) !== null): ?>
                                        <div class="rap-score-pill" title="<?php echo e($hotel['rating_label']); ?>">
                                            <span class="rap-score-pill__value"><?php echo e(number_format($hotel['rating'], 1)); ?></span>
                                            <?php if($hotel['rating_label'] !== ''): ?>
                                                <span class="rap-score-pill__word"><?php echo e($hotel['rating_label']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="rap-card__body">
                                    <h2 class="rap-card__title"><?php echo e($hotel['name']); ?></h2>
                                    <?php if(($hotel['stars'] ?? null) !== null && (int) $hotel['stars'] > 0): ?>
                                        <div class="rap-stars" aria-label="<?php echo e((int) $hotel['stars']); ?> étoiles sur 5">
                                            <?php for($i = 0; $i < (int) $hotel['stars']; $i++): ?>
                                                <span class="rap-stars__icon" aria-hidden="true">★</span>
                                            <?php endfor; ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if($hotel['city'] !== ''): ?>
                                        <p class="rap-card__sub rap-card__city"><?php echo e($hotel['city']); ?></p>
                                    <?php elseif($hotel['address'] !== ''): ?>
                                        <p class="rap-card__sub rap-card__city"><?php echo e($hotel['address']); ?></p>
                                    <?php endif; ?>
                                    <?php if($hotel['rating_label'] !== '' && ($hotel['rating'] ?? null) === null): ?>
                                        <p class="rap-card__rating-label"><?php echo e($hotel['rating_label']); ?></p>
                                    <?php endif; ?>
                                    <div class="rap-card__foot">
                                        <div class="rap-card__price-block">
                                            <?php if($hotel['price'] !== null): ?>
                                                <span class="rap-price-from">à partir de</span>
                                                <span class="rap-price"><?php echo e(number_format($hotel['price'], 0, ',', ' ')); ?> <small><?php echo e($hotel['currency']); ?></small></span>
                                            <?php else: ?>
                                                <span class="rap-price rap-price--muted">Prix sur demande</span>
                                            <?php endif; ?>
                                        </div>
                                        <span class="rap-soon">Voir la fiche</span>
                                    </div>
                                </div>
                            <?php if(filled($hotelPk)): ?>
                                </a>
                            <?php else: ?>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.front', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\rapidapi\hotels.blade.php ENDPATH**/ ?>