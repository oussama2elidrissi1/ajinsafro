<?php $__env->startSection('title', 'Hôtels RateHawk'); ?>

<?php
    $cssPath = public_path('css/ratehawk-hotels.css');
    $cssV = is_file($cssPath) ? filemtime($cssPath) : '1';
?>
<?php $__env->startPush('styles'); ?>
    <link rel="stylesheet" href="<?php echo e(asset('css/ratehawk-hotels.css')); ?>?v=<?php echo e($cssV); ?>">
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
    <div class="rh-page">
        <header class="rh-hero">
            <div class="rh-inner">
                <p class="rh-kicker">RateHawk · ETG API v3</p>
                <h1 class="rh-title">Trouver un hôtel</h1>
                <p class="rh-lead">
                    Indiquez une <strong>ville</strong> : résolution automatique du <code>region_id</code> (multicomplete), puis tarifs (SERP).
                    Vous pouvez aussi forcer un <strong>ID région</strong> connu.
                </p>

                
                <form class="rh-form" method="get" action="<?php echo e(route('ratehawk.hotels.index', [], false)); ?>">
                    <input type="hidden" name="search" value="1">

                    <div class="rh-form-grid">
                        <div class="rh-field rh-field--city">
                            <label for="city">Destination (ville ou lieu)</label>
                            <div class="rh-ac-wrap">
                                <input
                                    type="text"
                                    name="city"
                                    id="city"
                                    value="<?php echo e(old('city', $city ?? '')); ?>"
                                    placeholder="ex. Marrakech, Paris, Berlin…"
                                    class="rh-input"
                                    autocomplete="off"
                                    data-autocomplete-url="<?php echo e($autocompleteUrl); ?>"
                                >
                                <div id="rh-ac-dropdown" class="rh-ac-dropdown rh-hidden" role="listbox" aria-label="Suggestions"></div>
                            </div>
                        </div>
                        <div class="rh-field">
                            <label for="region_id">ID région (optionnel)</label>
                            <input
                                type="number"
                                name="region_id"
                                id="region_id"
                                value="<?php echo e(old('region_id', $regionId)); ?>"
                                min="1"
                                placeholder="Prioritaire sur la ville"
                                class="rh-input"
                                title="Si renseigné, la recherche utilise cet ID sans multicomplete"
                            >
                        </div>
                        <div class="rh-field">
                            <label for="checkin">Arrivée</label>
                            <input type="date" name="checkin" id="checkin" value="<?php echo e(old('checkin', $checkin)); ?>" class="rh-input" required>
                        </div>
                        <div class="rh-field">
                            <label for="checkout">Départ</label>
                            <input type="date" name="checkout" id="checkout" value="<?php echo e(old('checkout', $checkout)); ?>" class="rh-input" required>
                        </div>
                        <div class="rh-field rh-field--narrow">
                            <label for="adults">Adultes</label>
                            <input type="number" name="adults" id="adults" value="<?php echo e(old('adults', $adults)); ?>" min="1" max="6" class="rh-input" required>
                        </div>
                        <div class="rh-field rh-field--action">
                            <button type="submit" class="rh-btn">Rechercher</button>
                        </div>
                    </div>
                </form>
            </div>
        </header>

        <main class="rh-main">
            <div class="rh-inner">
                <?php if($errors->any()): ?>
                    <div class="rh-alert rh-alert--error" role="alert">
                        <ul class="rh-alert-list">
                            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $err): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <li><?php echo e($err); ?></li>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if($error): ?>
                    <div class="rh-alert <?php echo e(! empty($ratehawkIpAccessDenied) ? 'rh-alert--warning' : 'rh-alert--error'); ?>" role="alert">
                        <strong class="rh-alert-title"><?php echo e(! empty($ratehawkIpAccessDenied) ? 'Accès refusé' : 'Erreur'); ?></strong>
                        <p class="rh-alert-text mb-0"><?php echo e($error); ?></p>
                    </div>
                <?php endif; ?>

                <?php if(config('app.debug')): ?>
                    <div class="rh-debug-panel">
                        <strong class="rh-debug-title">Debug (APP_DEBUG=true)</strong>
                        <ul class="rh-debug-list">
                            <li><span>Ville</span> <code><?php echo e($city !== '' ? $city : '—'); ?></code></li>
                            <li><span>region_id</span> <code><?php echo e($regionId !== null ? $regionId : '—'); ?></code></li>
                            <li><span>Hôtels affichés</span> <code><?php echo e(count($hotels ?? [])); ?></code></li>
                            <li><span>total_hotels (API)</span> <code><?php echo e($totalHotels !== null ? $totalHotels : '—'); ?></code></li>
                            <li><span>Recherche lancée</span> <code><?php echo e($searchRequested ? 'oui' : 'non'); ?></code></li>
                            <li><span>Query string</span> <code><?php echo e(request()->getQueryString() ?: '—'); ?></code></li>
                        </ul>
                        <?php if(! empty($ratehawkAccessDeniedDebug)): ?>
                            <div class="rh-debug-access-denied" role="region" aria-label="Détail refus IP RateHawk">
                                <strong class="rh-debug-access-denied__title">Refus d’accès RateHawk (<code>not_allowed_host</code>)</strong>
                                <ul class="rh-debug-list rh-debug-list--tight mt-2">
                                    <li><span>error</span> <code><?php echo e($ratehawkAccessDeniedDebug['error'] ?? $ratehawkAccessDeniedDebug['api_error'] ?? '—'); ?></code></li>
                                    <li><span>validation_error</span> <code><?php echo e($ratehawkAccessDeniedDebug['validation_error'] ?? '—'); ?></code></li>
                                    <li><span>http_status</span> <code><?php echo e(isset($ratehawkAccessDeniedDebug['http_status']) ? $ratehawkAccessDeniedDebug['http_status'] : '—'); ?></code></li>
                                    <li><span>IP (extraite)</span> <code><?php echo e($ratehawkAccessDeniedDebug['ip'] ?? '—'); ?></code></li>
                                </ul>
                            </div>
                        <?php endif; ?>
                        <?php if(! empty($apiDebug)): ?>
                            <pre class="rh-debug-pre"><?php echo json_encode($apiDebug, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE, 512) ?></pre>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <section class="rh-results" aria-label="Résultats">
                    <?php if($configured && $searchRequested && ! $error && $totalHotels !== null && count($hotels) > 0): ?>
                        <div class="rh-results-head">
                            <h2 class="rh-results-title">Résultats</h2>
                            <p class="rh-results-sub">
                                <span class="font-semibold text-slate-900"><?php echo e($totalHotels); ?></span> proposition(s)
                                <?php if(! empty($resolvedLabel ?? null)): ?>
                                    · <?php echo e($resolvedLabel); ?>

                                    <?php if(! empty($regionId)): ?>
                                        <span class="font-mono text-slate-600">#<?php echo e($regionId); ?></span>
                                    <?php endif; ?>
                                <?php elseif(! empty($regionId)): ?>
                                    · région <span class="font-mono">#<?php echo e($regionId); ?></span>
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php endif; ?>

                    <?php if($configured && $searchRequested && ! $error && $totalHotels !== null && count($hotels) === 0): ?>
                        <div class="rh-alert rh-alert--empty">
                            <p class="rh-empty-title mb-1">Aucun hôtel trouvé pour cette recherche.</p>
                            <p class="text-slate-500 mb-0 text-sm">Modifiez les dates ou la destination.</p>
                        </div>
                    <?php endif; ?>

                    <?php if(count($hotels ?? []) > 0): ?>
                        <div class="rh-grid">
                            <?php $__currentLoopData = $hotels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $hotel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <article class="rh-card">
                                    <div class="rh-card__media">
                                        <img
                                            src="<?php echo e($hotel['image'] ?? $placeholderImage); ?>"
                                            alt="<?php echo e($hotel['name']); ?>"
                                            loading="lazy"
                                            width="640"
                                            height="400"
                                            onerror="this.onerror=null;this.src='<?php echo e($placeholderImage); ?>';"
                                        >
                                        <?php if($hotel['stars'] !== null): ?>
                                            <span class="rh-badge"><?php echo e(number_format($hotel['stars'], 1)); ?> ★</span>
                                        <?php elseif($hotel['rating'] !== null): ?>
                                            <span class="rh-badge"><?php echo e(number_format($hotel['rating'], 1)); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="rh-card__body">
                                        <h3 class="rh-card__title"><?php echo e($hotel['name']); ?></h3>
                                        <?php if(! empty($hotel['address'])): ?>
                                            <p class="rh-card__line"><?php echo e($hotel['address']); ?></p>
                                        <?php endif; ?>
                                        <?php if(! empty($hotel['region_name'])): ?>
                                            <p class="rh-card__line rh-card__line--muted"><?php echo e($hotel['region_name']); ?></p>
                                        <?php endif; ?>
                                        <?php if(! empty($hotel['meal'])): ?>
                                            <p class="rh-card__meta"><?php echo e($hotel['meal']); ?></p>
                                        <?php endif; ?>
                                        <div class="rh-card__footer">
                                            <div class="rh-price">
                                                <?php if($hotel['price'] !== null): ?>
                                                    <?php echo e(number_format($hotel['price'], 0, ',', ' ')); ?>

                                                    <small><?php echo e($hotel['currency']); ?></small>
                                                <?php else: ?>
                                                    <small class="text-slate-500">Tarif sur demande</small>
                                                <?php endif; ?>
                                            </div>
                                            <div class="rh-card__actions">
                                                <a href="#" class="rh-link" onclick="return false;">Voir détails</a>
                                                <a href="#" class="rh-btn rh-btn--outline" onclick="return false;">Réserver</a>
                                            </div>
                                        </div>
                                        <?php if(($hotel['hid'] ?? 0) > 0): ?>
                                            <p class="rh-card__hid">Hôtel #<?php echo e($hotel['hid']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </article>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    <?php endif; ?>
                </section>

                <?php if(! $configured): ?>
                    <p class="rh-neutral text-center">
                        Configuration API RateHawk manquante : renseignez
                        <code>RATEHAWK_KEY_ID</code>, <code>RATEHAWK_API_KEY</code> et <code>RATEHAWK_API_BASE_URL</code> dans votre fichier <code>.env</code>.
                    </p>
                <?php elseif(! $searchRequested): ?>
                    <p class="rh-neutral text-center">Renseignez une destination et cliquez sur <strong>Rechercher</strong>.</p>
                <?php endif; ?>

                <p class="rh-footnote">ETG : multicomplete → region_id → SERP région.</p>
            </div>
        </main>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
(function () {
    var input = document.getElementById('city');
    var dd = document.getElementById('rh-ac-dropdown');
    var url = input && input.getAttribute('data-autocomplete-url');
    if (!input || !dd || !url) return;

    var t = null;

    function hide() {
        dd.classList.add('rh-hidden');
        dd.innerHTML = '';
    }

    function render(list) {
        dd.innerHTML = '';
        if (!list.length) { hide(); return; }
        list.forEach(function (s) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'rh-ac-item';
            btn.setAttribute('role', 'option');
            var meta = [s.type, s.country_code].filter(Boolean).join(' · ');
            btn.innerHTML = '<span class="rh-ac-item__title">' + escapeHtml(s.label) + '</span>' +
                (meta ? '<span class="rh-ac-item__meta">' + escapeHtml(meta) + '</span>' : '') +
                '<span class="rh-ac-item__id">#' + s.region_id + '</span>';
            btn.addEventListener('click', function () {
                input.value = s.label.split(' — ')[0].trim();
                var rid = document.getElementById('region_id');
                if (rid) rid.value = String(s.region_id);
                hide();
            });
            dd.appendChild(btn);
        });
        dd.classList.remove('rh-hidden');
    }

    function escapeHtml(str) {
        var d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }

    input.addEventListener('input', function () {
        var q = input.value.trim();
        clearTimeout(t);
        if (q.length < 2) { hide(); return; }
        t = setTimeout(function () {
            fetch(url + '?q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    render(data.suggestions || []);
                })
                .catch(function () { hide(); });
        }, 280);
    });

    document.addEventListener('click', function (e) {
        if (!input.contains(e.target) && !dd.contains(e.target)) hide();
    });
})();
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.front', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\ratehawk\hotels.blade.php ENDPATH**/ ?>