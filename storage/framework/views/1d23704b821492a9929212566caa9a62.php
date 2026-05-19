<?php $__env->startSection('title', data_get($detail, 'name', 'Hôtel').' — RapidAPI'); ?>

<?php
    $cssPath = public_path('css/rapidapi-hotels.css');
    $cssV = is_file($cssPath) ? filemtime($cssPath) : '1';
?>
<?php $__env->startPush('styles'); ?>
    <link rel="stylesheet" href="<?php echo e(asset('css/rapidapi-hotels.css')); ?>?v=<?php echo e($cssV); ?>">
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
    <div class="rap-page rap-page--detail">
        <header class="rap-detail-hero">
            <div class="rap-inner">
                <p class="rap-kicker"><a href="<?php echo e($backUrl); ?>" class="rap-back-link">← Retour aux résultats</a></p>
                <?php if($detail): ?>
                    <h1 class="rap-title"><?php echo e($detail['name']); ?></h1>
                    <?php if(($detail['stars'] ?? null) !== null && (int) $detail['stars'] > 0): ?>
                        <div class="rap-stars rap-stars--large" aria-label="<?php echo e((int) $detail['stars']); ?> étoiles">
                            <?php for($i = 0; $i < (int) $detail['stars']; $i++): ?>
                                <span class="rap-stars__icon" aria-hidden="true">★</span>
                            <?php endfor; ?>
                        </div>
                    <?php endif; ?>
                    <p class="rap-detail-meta">
                        <?php if($detail['city'] !== ''): ?>
                            <span><?php echo e($detail['city']); ?></span>
                        <?php endif; ?>
                        <?php if($detail['address'] !== ''): ?>
                            <span class="rap-detail-meta__sep">·</span>
                            <span><?php echo e($detail['address']); ?></span>
                        <?php endif; ?>
                    </p>
                    <?php if(($detail['rating'] ?? null) !== null): ?>
                        <div class="rap-score-pill rap-score-pill--inline" title="<?php echo e($detail['rating_label']); ?>">
                            <span class="rap-score-pill__value"><?php echo e(number_format($detail['rating'], 1)); ?></span>
                            <?php if($detail['rating_label'] !== ''): ?>
                                <span class="rap-score-pill__word"><?php echo e($detail['rating_label']); ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <h1 class="rap-title">Hôtel</h1>
                <?php endif; ?>
            </div>
        </header>

        <main class="rap-main rap-main--detail">
            <div class="rap-inner">
                <?php if(! $configured): ?>
                    <p class="rap-neutral"><?php echo e($error); ?></p>
                <?php elseif($error): ?>
                    <div class="rap-alert rap-alert--error" role="alert"><?php echo e($error); ?></div>
                <?php elseif($detail): ?>
                    <?php
                        $allPhotos    = ! empty($detail['photos']) ? $detail['photos'] : [];
                        $heroSrc      = ! empty($detail['hero_image']) ? $detail['hero_image'] : (! empty($allPhotos) ? $allPhotos[0] : '/images/hotel-placeholder.svg');
                        $hasManyPhotos = count($allPhotos) > 1;
                        $thumbPhotos  = array_slice($allPhotos, 1, 4);
                        $extraCount   = max(0, count($allPhotos) - 5);
                    ?>

                    <?php if($hasManyPhotos): ?>
                        <div class="hotel-gallery-grid">
                            <div class="hotel-gallery-main">
                                <img src="<?php echo e($heroSrc); ?>"
                                     alt="<?php echo e($detail['name']); ?>"
                                     class="hotel-gallery-img"
                                     loading="eager"
                                     decoding="async"
                                     onerror="this.onerror=null;this.src='/images/hotel-placeholder.svg';">
                            </div>
                            <div class="hotel-gallery-thumbs">
                                <?php $__currentLoopData = $thumbPhotos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $src): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php $isLast = ($i === count($thumbPhotos) - 1); ?>
                                    <div class="hotel-gallery-thumb<?php echo e(($isLast && $extraCount > 0) ? ' hotel-gallery-thumb--more' : ''); ?>">
                                        <img src="<?php echo e($src); ?>"
                                             alt=""
                                             class="hotel-gallery-img"
                                             loading="lazy"
                                             onerror="this.onerror=null;this.src='/images/hotel-placeholder.svg';">
                                        <?php if($isLast && $extraCount > 0): ?>
                                            <span class="hotel-gallery-more">+<?php echo e($extraCount); ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="hotel-gallery--single">
                            <img src="<?php echo e($heroSrc); ?>"
                                 alt="<?php echo e($detail['name']); ?>"
                                 class="hotel-gallery-img"
                                 loading="eager"
                                 decoding="async"
                                 onerror="this.onerror=null;this.src='/images/hotel-placeholder.svg';">
                        </div>
                    <?php endif; ?>

                    <?php if(($detail['price'] ?? null) !== null): ?>
                        <section class="rap-detail-section rap-detail-section--price">
                            <p class="rap-detail-price">
                                <span class="rap-detail-price-from">à partir de</span>
                                <span class="rap-detail-price-value"><?php echo e(number_format($detail['price'], 0, ',', ' ')); ?> <small><?php echo e($detail['currency'] ?? 'EUR'); ?></small></span>
                            </p>
                        </section>
                    <?php endif; ?>

                    <?php if(! empty($detail['description'])): ?>
                        <section class="rap-detail-section">
                            <h2 class="rap-detail-h2">Présentation</h2>
                            <div class="rap-detail-desc"><?php echo nl2br(e($detail['description'])); ?></div>
                        </section>
                    <?php endif; ?>

                    <?php if(! empty($detail['facilities'])): ?>
                        <section class="rap-detail-section">
                            <h2 class="rap-detail-h2">Équipements</h2>
                            <ul class="rap-facilities">
                                <?php $__currentLoopData = array_slice($detail['facilities'], 0, 40); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $f): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <li><?php echo e($f); ?></li>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </ul>
                        </section>
                    <?php endif; ?>

                    <?php if(! empty($detail['booking_url'])): ?>
                        <p class="rap-detail-cta">
                            <a href="<?php echo e($detail['booking_url']); ?>" class="rap-btn rap-btn--outline" target="_blank" rel="noopener noreferrer">Voir sur Booking.com</a>
                        </p>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if(! empty($apiDebug)): ?>
                    <details class="rap-debug-wrap" <?php if(! empty($error)): ?> open <?php endif; ?>>
                        <summary class="rap-debug-summary"><?php echo e(config('app.debug') ? 'Debug API (développement)' : 'Détails technique (réponse API)'); ?></summary>
                        <pre class="rap-debug"><?php echo json_encode($apiDebug, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE, 512) ?></pre>
                    </details>
                <?php endif; ?>
            </div>
        </main>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.front', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\rapidapi\hotel-show.blade.php ENDPATH**/ ?>