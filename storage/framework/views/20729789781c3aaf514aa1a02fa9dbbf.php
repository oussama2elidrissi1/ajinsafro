<?php $__env->startSection('title', html_entity_decode($voyage->name, ENT_QUOTES, 'UTF-8') . ' – AjiNsafro.ma'); ?>

<?php $__env->startPush('styles'); ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
<link rel="stylesheet" href="<?php echo e(asset('css/front-voyage-kiosk.css')); ?>">
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<?php
    $voyageName = html_entity_decode($voyage->name ?? '', ENT_QUOTES, 'UTF-8');
    $heroSlides = $heroImages ?? [];
    $heroUrls = $heroImageUrls ?? [];
    $heroSrc = $heroSlides[0]['url'] ?? $heroUrls[0] ?? $voyage->featured_image_url;
    $heroThumbs = array_slice($heroSlides, 0, 5);
    $hasMultiHero = count($heroThumbs) > 1;
    $cur = $voyage->currency_symbol;
    $hasPriceFrom = isset($priceFrom) && $priceFrom > 0;
    $hasPromo = $voyage->old_price && $voyage->old_price > ($priceFrom ?? 0);
    $hasGallery = count($heroUrls) > 1 || $voyage->images->isNotEmpty();
    $galleryImages = $heroUrls;
    $hasDepartures = isset($departures) && $departures->isNotEmpty();
    $hasPlaces = isset($departurePlaces) && $departurePlaces->isNotEmpty();
    $hasExtras = $voyage->extras->isNotEmpty();
    $hasProgram = $voyage->programDays->isNotEmpty();
    $hasHighlights = isset($highlights) && count($highlights) > 0;
?>
    <?php
        // Dynamic step indices (keep flow consistent when places/extras are absent)
        $stepDate = 1;
        $stepCity = $hasPlaces ? 2 : null;
        $stepClient = $hasPlaces ? 3 : 2;
        $stepRoom = $hasPlaces ? 4 : 3;
        $stepExtras = $hasExtras ? ($hasPlaces ? 5 : 4) : null;
        $stepSummary = $hasExtras ? ($hasPlaces ? 6 : 5) : ($hasPlaces ? 5 : 4);
    ?>


<section class="ksk-hero2" id="ksk-hero">
    <div class="ksk-container">
        <?php
            $galleryAll = $heroSlides; // full list for counts
            $main = $heroSlides[0] ?? null;
            $sideA = $heroSlides[1] ?? null;
            $sideB = $heroSlides[2] ?? null;
            $sideC = $heroSlides[3] ?? null;
            $totalPhotos = count($heroUrls);
            $extraPhotos = max(0, $totalPhotos - 4);
        ?>

        <div class="ksk-hero2__shell">
            
            <div class="ksk-hero2__left">
                <div class="ksk-hero2__badges">
                    <?php if($voyage->destination): ?>
                        <span class="ksk-chip"><i class="fas fa-map-marker-alt"></i> <?php echo e(e($voyage->destination)); ?></span>
                    <?php endif; ?>
                    <?php if($voyage->duration_text): ?>
                        <span class="ksk-chip"><i class="far fa-clock"></i> <?php echo e(e($voyage->duration_text)); ?></span>
                    <?php endif; ?>
                    <?php if($hasPromo): ?>
                        <span class="ksk-chip ksk-chip--promo"><i class="fas fa-tag"></i> -<?php echo e($voyage->discount_percent); ?>%</span>
                    <?php endif; ?>
                </div>

                <h1 class="ksk-hero2__title"><?php echo e($voyageName); ?></h1>
                <?php if($voyage->accroche): ?>
                    <p class="ksk-hero2__sub"><?php echo e(e($voyage->accroche)); ?></p>
                <?php endif; ?>

                <div class="ksk-hero2__cta">
                    <?php if($hasPriceFrom): ?>
                        <div class="ksk-hero2__priceCard">
                            <div class="ksk-hero2__priceTop">
                                <span class="ksk-hero2__priceLabel">À partir de</span>
                                <span class="ksk-hero2__priceMain"><?php echo e(number_format($priceFrom, 0, ',', ' ')); ?> <?php echo e($cur); ?></span>
                            </div>
                            <div class="ksk-hero2__priceBottom">
                                <?php if($hasPromo): ?>
                                    <s class="ksk-hero2__priceOld"><?php echo e(number_format($voyage->old_price, 0, ',', ' ')); ?> <?php echo e($cur); ?></s>
                                <?php endif; ?>
                                <span class="ksk-hero2__per">/ personne</span>
                            </div>
                        </div>
                    <?php endif; ?>
                    <button type="button" class="ksk-btn ksk-btn--hero" onclick="ksk.scrollToBuilder()">
                        <i class="fas fa-bolt"></i> Commencer la réservation
                    </button>

                    <?php if($hasGallery): ?>
                        <button type="button" class="ksk-btn ksk-btn--ghost" data-ksk-lb-open data-index="0">
                            <i class="fas fa-images"></i> Voir les photos
                            <span class="ksk-hero2__ghostCount"><?php echo e($totalPhotos); ?></span>
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            
            <div class="ksk-hero2__right" data-ksk-gallery>
                <div class="ksk-hero2__galleryCard">
                    <div class="ksk-hero2__galleryGrid">
                        <button type="button" class="ksk-hero2__gMain" data-ksk-lb-open data-index="0" aria-label="Ouvrir la photo 1">
                            <?php if($main): ?>
                                <?php
                                    $mSrc = $main['url'] ?? null;
                                    $mSrcset = $main['srcset'] ?? null;
                                ?>
                                <img src="<?php echo e($mSrc); ?>" <?php if($mSrcset): ?> srcset="<?php echo e($mSrcset); ?>" sizes="(min-width: 1280px) 720px, (min-width: 1024px) 54vw, 100vw" <?php endif; ?> alt="<?php echo e($voyageName); ?>" class="hero-image" loading="eager" decoding="async" fetchpriority="high">
                            <?php else: ?>
                                <div class="ksk-hero2__ph"></div>
                            <?php endif; ?>
                        </button>

                        <button type="button" class="ksk-hero2__gSmall ksk-hero2__gSmall--a" data-ksk-lb-open data-index="1" aria-label="Ouvrir la photo 2">
                            <?php if($sideA): ?>
                                <?php
                                    $sUrl = $sideA['url'] ?? '';
                                    $sSrcset = $sideA['srcset'] ?? null;
                                ?>
                                <img src="<?php echo e($sUrl); ?>" <?php if(!empty($sSrcset)): ?> srcset="<?php echo e($sSrcset); ?>" sizes="(min-width: 1280px) 340px, (min-width: 1024px) 22vw, 50vw" <?php endif; ?> alt="" class="hero-image" loading="lazy" decoding="async">
                            <?php endif; ?>
                        </button>

                        <button type="button" class="ksk-hero2__gSmall ksk-hero2__gSmall--b" data-ksk-lb-open data-index="2" aria-label="Ouvrir la photo 3">
                            <?php if($sideB): ?>
                                <?php
                                    $sUrl = $sideB['url'] ?? '';
                                    $sSrcset = $sideB['srcset'] ?? null;
                                ?>
                                <img src="<?php echo e($sUrl); ?>" <?php if(!empty($sSrcset)): ?> srcset="<?php echo e($sSrcset); ?>" sizes="(min-width: 1280px) 340px, (min-width: 1024px) 22vw, 50vw" <?php endif; ?> alt="" class="hero-image" loading="lazy" decoding="async">
                            <?php endif; ?>
                        </button>

                        <button type="button" class="ksk-hero2__gSmall ksk-hero2__gSmall--c" data-ksk-lb-open data-index="3" aria-label="Ouvrir la photo 4">
                            <?php if($sideC): ?>
                                <?php
                                    $sUrl = $sideC['url'] ?? '';
                                    $sSrcset = $sideC['srcset'] ?? null;
                                ?>
                                <img src="<?php echo e($sUrl); ?>" <?php if(!empty($sSrcset)): ?> srcset="<?php echo e($sSrcset); ?>" sizes="(min-width: 1280px) 340px, (min-width: 1024px) 22vw, 50vw" <?php endif; ?> alt="" class="hero-image" loading="lazy" decoding="async">
                            <?php endif; ?>
                            <?php if($extraPhotos > 0): ?>
                                <span class="ksk-hero2__more">+<?php echo e($extraPhotos); ?> photos</span>
                            <?php endif; ?>
                        </button>
                    </div>

                    <button type="button" class="ksk-hero2__viewAll" data-ksk-lb-open data-index="0">
                        <i class="fas fa-images"></i> Voir toutes les photos
                        <span class="ksk-hero2__viewAllCount"><?php echo e($totalPhotos); ?></span>
                    </button>
                </div>
            </div>

            
            <div class="ksk-hero2__mobile" id="ksk-hero2-mobile">
                <div class="ksk-hero2__mobile-track" id="ksk-hero-slider">
                    <?php $__currentLoopData = $heroThumbs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $img): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            $src = $img['url'] ?? null;
                            $srcset = $img['srcset'] ?? null;
                        ?>
                        <button type="button" class="ksk-hero2__mobile-slide" data-ksk-lb-open data-index="<?php echo e($idx); ?>">
                            <img src="<?php echo e($src); ?>" <?php if($srcset): ?> srcset="<?php echo e($srcset); ?>" sizes="100vw" <?php endif; ?> alt="<?php echo e($voyageName); ?>" class="hero-image" loading="<?php echo e($idx === 0 ? 'eager' : 'lazy'); ?>" decoding="async">
                        </button>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
                <?php if($hasMultiHero): ?>
                    <button class="ksk-hero2__nav ksk-hero2__nav--prev" id="ksk-hero-prev" aria-label="Précédent"><i class="fas fa-chevron-left"></i></button>
                    <button class="ksk-hero2__nav ksk-hero2__nav--next" id="ksk-hero-next" aria-label="Suivant"><i class="fas fa-chevron-right"></i></button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>


<div class="ksk-main" id="ksk-main">
    <div class="ksk-container">
        <div class="ksk-layout">

            
            <div class="ksk-builder" id="ksk-builder">

                
                <nav class="ksk-steps" id="ksk-steps-nav">
                    <button class="ksk-step is-active" data-step="1"><span class="ksk-step__num">1</span><span class="ksk-step__label">Date</span></button>
                    <?php if($hasPlaces): ?>
                        <button class="ksk-step" data-step="2"><span class="ksk-step__num">2</span><span class="ksk-step__label">Ville</span></button>
                    <?php endif; ?>
                    <button class="ksk-step" data-step="<?php echo e($stepClient); ?>"><span class="ksk-step__num"><?php echo e($stepClient); ?></span><span class="ksk-step__label">Client</span></button>
                    <button class="ksk-step" data-step="<?php echo e($stepRoom); ?>"><span class="ksk-step__num"><?php echo e($stepRoom); ?></span><span class="ksk-step__label">Chambre</span></button>
                    <?php if($hasExtras): ?>
                        <button class="ksk-step" data-step="<?php echo e($stepExtras); ?>"><span class="ksk-step__num"><?php echo e($stepExtras); ?></span><span class="ksk-step__label">Extras</span></button>
                    <?php endif; ?>
                    <button class="ksk-step" data-step="<?php echo e($stepSummary); ?>"><span class="ksk-step__num"><i class="fas fa-check"></i></span><span class="ksk-step__label">Résumé</span></button>
                </nav>

                
                <section class="ksk-panel is-active" data-panel="1" id="ksk-panel-date">
                    <div class="ksk-panel__head">
                        <h2><i class="fas fa-calendar-alt"></i> Choisissez votre date de départ</h2>
                        <p>Sélectionnez la date qui vous convient parmi les départs disponibles.</p>
                    </div>
                    <div class="ksk-dates" id="ksk-dates-grid">
                        
                    </div>
                    <div class="ksk-panel__foot">
                        <button type="button" class="ksk-btn ksk-btn--next" id="ksk-next-1" disabled>
                            Continuer <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </section>

                
                <?php if($hasPlaces): ?>
                <section class="ksk-panel" data-panel="2" id="ksk-panel-city">
                    <div class="ksk-panel__head">
                        <h2><i class="fas fa-plane-departure"></i> Ville de départ</h2>
                        <p>Choisissez votre point de départ.</p>
                    </div>
                    <div class="ksk-cities" id="ksk-cities-grid">
                        
                    </div>
                    <div id="ksk-flight-info" class="ksk-flight-info" hidden></div>
                    <div class="ksk-panel__foot">
                        <button type="button" class="ksk-btn ksk-btn--back" onclick="ksk.goStep(1)"><i class="fas fa-arrow-left"></i> Retour</button>
                        <button type="button" class="ksk-btn ksk-btn--next" id="ksk-next-2" disabled>Continuer <i class="fas fa-arrow-right"></i></button>
                    </div>
                </section>
                <?php endif; ?>

                
                <section class="ksk-panel" data-panel="<?php echo e($stepClient); ?>" id="ksk-panel-client">
                    <div class="ksk-panel__head">
                        <h2><i class="fas fa-user-check"></i> Client & voyageurs</h2>
                        <p>Identifiez le client principal et ajoutez les accompagnants si besoin.</p>
                    </div>

                    <div class="ksk-form">
                        <div class="ksk-choice-row" role="radiogroup" aria-label="Mode client">
                            <label class="ksk-choice">
                                <input type="radio" name="ksk_client_mode" value="new" checked>
                                <span>Nouveau client</span>
                            </label>
                            <label class="ksk-choice">
                                <input type="radio" name="ksk_client_mode" value="existing">
                                <span>Client existant</span>
                            </label>
                        </div>

                        <div id="ksk-existing-client" class="ksk-block" hidden>
                            <div class="ksk-field">
                                <label>Client existant</label>
                                <input type="text" id="ksk-client-search" class="ksk-input" placeholder="Rechercher par nom, téléphone, email…" autocomplete="off">
                                <div class="ksk-help">Sélectionnez un client existant. (Même logique que l’admin, adaptée au tunnel client.)</div>
                                <div class="ksk-search-results" id="ksk-client-results" hidden></div>
                                <div class="ksk-help" id="ksk-client-selected" hidden></div>
                                <button type="button" class="ksk-btn ksk-btn--ghost ksk-btn--sm" id="ksk-client-clear" hidden>Changer de client</button>
                            </div>
                        </div>

                        <div id="ksk-new-client" class="ksk-block">
                            <div class="ksk-grid ksk-grid--two">
                                <div class="ksk-field">
                                    <label>Prénom <span class="ksk-req">*</span></label>
                                    <input type="text" id="ksk-client-first" class="ksk-input" autocomplete="given-name">
                                </div>
                                <div class="ksk-field">
                                    <label>Nom <span class="ksk-req">*</span></label>
                                    <input type="text" id="ksk-client-last" class="ksk-input" autocomplete="family-name">
                                </div>
                                <div class="ksk-field">
                                    <label>Téléphone</label>
                                    <input type="text" id="ksk-client-phone" class="ksk-input" autocomplete="tel">
                                </div>
                                <div class="ksk-field">
                                    <label>Email</label>
                                    <input type="email" id="ksk-client-email" class="ksk-input" autocomplete="email">
                                </div>
                                <div class="ksk-field">
                                    <label>Type document</label>
                                    <input type="text" id="ksk-client-doc-type" class="ksk-input" placeholder="CIN, Passeport…">
                                </div>
                                <div class="ksk-field">
                                    <label>N° document</label>
                                    <input type="text" id="ksk-client-doc-num" class="ksk-input">
                                </div>
                            </div>
                        </div>

                        <div class="ksk-divider"></div>

                        <div class="ksk-travelers">
                            <div class="ksk-travelers__head">
                                <div>
                                    <h3><i class="fas fa-users"></i> Accompagnants</h3>
                                    <p>Le client principal est le voyageur principal. Ajoutez seulement les accompagnants.</p>
                                </div>
                                <button type="button" class="ksk-btn ksk-btn--ghost" id="ksk-add-companion">
                                    <i class="fas fa-user-plus"></i> Ajouter un accompagnant
                                </button>
                            </div>

                            <div id="ksk-companions"></div>
                            <p class="ksk-empty" id="ksk-no-companion">Aucun accompagnant pour le moment.</p>
                        </div>
                    </div>

                    <div class="ksk-panel__foot">
                        <button type="button" class="ksk-btn ksk-btn--back" onclick="ksk.goStep(<?php echo e($hasPlaces ? 2 : 1); ?>)"><i class="fas fa-arrow-left"></i> Retour</button>
                        <button type="button" class="ksk-btn ksk-btn--next" id="ksk-next-client" disabled>Continuer <i class="fas fa-arrow-right"></i></button>
                    </div>
                </section>

                
                <section class="ksk-panel" data-panel="<?php echo e($stepRoom); ?>" id="ksk-panel-room">
                    <div class="ksk-panel__head">
                        <h2><i class="fas fa-bed"></i> Choisissez votre chambre</h2>
                        <p>Sélectionnez le type de chambre. Le nombre de voyageurs est calculé depuis l’étape “Client & voyageurs”.</p>
                    </div>
                    <div class="ksk-pax-row" id="ksk-pax">
                        <div class="ksk-pax-item">
                            <label>Adultes</label>
                            <div class="ksk-counter ksk-counter--static">
                                <span id="ksk-pax-adults">1</span>
                            </div>
                        </div>
                        <div class="ksk-pax-item">
                            <label>Enfants</label>
                            <div class="ksk-counter ksk-counter--static">
                                <span id="ksk-pax-children">0</span>
                            </div>
                        </div>
                    </div>
                    <div class="ksk-rooms" id="ksk-rooms-grid">
                        
                    </div>
                    <div class="ksk-panel__foot">
                        <button type="button" class="ksk-btn ksk-btn--back" onclick="ksk.goStep(<?php echo e($stepClient); ?>)"><i class="fas fa-arrow-left"></i> Retour</button>
                        <button type="button" class="ksk-btn ksk-btn--next" id="ksk-next-room" disabled>Continuer <i class="fas fa-arrow-right"></i></button>
                    </div>
                </section>

                
                <?php if($hasExtras): ?>
                <section class="ksk-panel" data-panel="<?php echo e($stepExtras); ?>" id="ksk-panel-extras">
                    <div class="ksk-panel__head">
                        <h2><i class="fas fa-star"></i> Personnalisez votre séjour</h2>
                        <p>Ajoutez des options pour enrichir votre voyage.</p>
                    </div>
                    <div class="ksk-extras" id="ksk-extras-grid">
                        
                    </div>
                    <div class="ksk-panel__foot">
                        <button type="button" class="ksk-btn ksk-btn--back" onclick="ksk.goStep(<?php echo e($stepRoom); ?>)"><i class="fas fa-arrow-left"></i> Retour</button>
                        <button type="button" class="ksk-btn ksk-btn--next" id="ksk-next-extras">Continuer <i class="fas fa-arrow-right"></i></button>
                    </div>
                </section>
                <?php endif; ?>

                
                <section class="ksk-panel" data-panel="<?php echo e($stepSummary); ?>" id="ksk-panel-summary">
                    <div class="ksk-panel__head">
                        <h2><i class="fas fa-clipboard-check"></i> Récapitulatif de votre réservation</h2>
                        <p>Vérifiez vos choix avant de confirmer.</p>
                    </div>
                    <div class="ksk-summary-detail" id="ksk-summary-detail">
                        
                    </div>
                    <div class="ksk-panel__foot ksk-panel__foot--final">
                        <button type="button" class="ksk-btn ksk-btn--back" onclick="ksk.goStep(<?php echo e($hasExtras ? $stepExtras : $stepRoom); ?>)"><i class="fas fa-arrow-left"></i> Modifier</button>
                        <a href="#" class="ksk-btn ksk-btn--reserve" id="ksk-reserve-btn">
                            <i class="fas fa-bolt"></i> Réserver maintenant
                        </a>
                    </div>
                </section>

            </div>

            
            <aside class="ksk-sidebar" id="ksk-sidebar">
                <div class="ksk-sidebar__sticky">
                    <div class="ksk-cart">
                        <div class="ksk-cart__header">
                            <h3><i class="fas fa-shopping-bag"></i> Votre sélection</h3>
                        </div>
                        <div class="ksk-cart__body" id="ksk-cart-body">
                            <p class="ksk-cart__empty">Commencez par choisir une date de départ.</p>
                        </div>
                        <div class="ksk-cart__total">
                            <span>Total estimé</span>
                            <strong id="ksk-cart-total">— <?php echo e($cur); ?></strong>
                        </div>
                        <a href="#" class="ksk-btn ksk-btn--reserve ksk-btn--block" id="ksk-cart-reserve" style="display:none">
                            <i class="fas fa-bolt"></i> Réserver
                        </a>
                    </div>
                    
                    <div class="ksk-trip-card">
                        <?php if($heroSrc): ?>
                            <img src="<?php echo e($heroSrc); ?>" alt="" class="ksk-trip-card__img">
                        <?php endif; ?>
                        <div class="ksk-trip-card__body">
                            <h4><?php echo e($voyageName); ?></h4>
                            <?php if($voyage->destination): ?><p><i class="fas fa-map-marker-alt"></i> <?php echo e(e($voyage->destination)); ?></p><?php endif; ?>
                            <?php if($voyage->duration_text): ?><p><i class="far fa-clock"></i> <?php echo e(e($voyage->duration_text)); ?></p><?php endif; ?>
                        </div>
                    </div>
                    <a href="https://wa.me/212660683464?text=<?php echo e(rawurlencode('Bonjour, je suis intéressé(e) par : '.$voyageName)); ?>" target="_blank" rel="noopener" class="ksk-btn ksk-btn--whatsapp ksk-btn--block">
                        <i class="fab fa-whatsapp"></i> Besoin d'aide ?
                    </a>
                </div>
            </aside>

        </div>
        
    </div>
</div>


<section class="ksk-details" id="ksk-details">
    <div class="ksk-container">
        <h2 class="ksk-details__title"><i class="fas fa-info-circle"></i> Détails du voyage</h2>

        <?php if($hasHighlights): ?>
        <div class="ksk-highlights">
            <?php $__currentLoopData = $highlights; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $hl): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <span class="ksk-highlight"><i class="fas fa-check-circle"></i> <?php echo e(e($hl)); ?></span>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <?php endif; ?>

        <?php if(!empty(trim((string)$voyage->description))): ?>
        <div class="ksk-prose"><?php echo $voyage->description; ?></div>
        <?php endif; ?>

        <?php if($hasProgram): ?>
        <h3 class="ksk-details__sub"><i class="fas fa-route"></i> Programme jour par jour</h3>
        <div class="ksk-program">
            <?php $__currentLoopData = $voyage->programDays; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $day): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <details class="ksk-day" <?php echo e($loop->first ? 'open' : ''); ?>>
                    <summary>
                        <span class="ksk-day__num">J<?php echo e($day->day_number); ?></span>
                        <span class="ksk-day__title"><?php echo e(e($day->title ?: 'Jour '.$day->day_number)); ?></span>
                        <?php if($day->city): ?><span class="ksk-day__city"><i class="fas fa-map-pin"></i> <?php echo e(e($day->city)); ?></span><?php endif; ?>
                    </summary>
                    <div class="ksk-day__body">
                        <?php if($day->content_html): ?><?php echo $day->content_html; ?><?php elseif($day->description): ?><?php echo nl2br(e($day->description)); ?><?php endif; ?>
                        <?php $meals = $day->meals_array ?? ['breakfast' => false, 'lunch' => false, 'dinner' => false]; ?>
                        <?php if(($meals['breakfast'] ?? false) || ($meals['lunch'] ?? false) || ($meals['dinner'] ?? false)): ?>
                            <div class="ksk-day__meals">
                                <?php if(($meals['breakfast'] ?? false)): ?><span><i class="fas fa-coffee"></i> Petit-déj</span><?php endif; ?>
                                <?php if(($meals['lunch'] ?? false)): ?><span><i class="fas fa-utensils"></i> Déjeuner</span><?php endif; ?>
                                <?php if(($meals['dinner'] ?? false)): ?><span><i class="fas fa-moon"></i> Dîner</span><?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </details>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <?php endif; ?>
    </div>
</section>


<?php if($hasGallery && count($galleryImages) > 1): ?>
<section class="ksk-gallery-section">
    <div class="ksk-container">
        <h2 class="ksk-details__title"><i class="fas fa-images"></i> Galerie photos</h2>
        <div class="ksk-gallery">
            <?php $__currentLoopData = array_slice($galleryImages, 0, 8); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $imgUrl): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a href="<?php echo e($imgUrl); ?>" class="ksk-gallery__item" data-ksk-lb data-index="<?php echo e($idx); ?>">
                    <img src="<?php echo e($imgUrl); ?>" alt="Photo <?php echo e($idx+1); ?>" loading="lazy">
                </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
</section>
<?php endif; ?>


<div class="ksk-mobile-bar" id="ksk-mobile-bar">
    <div class="ksk-mobile-bar__price">
        <span id="ksk-mobile-total">—</span> <?php echo e($cur); ?>

    </div>
    <button type="button" class="ksk-btn ksk-btn--reserve ksk-btn--sm" onclick="ksk.scrollToBuilder()">
        <i class="fas fa-bolt"></i> Réserver
    </button>
</div>


<?php if($hasGallery && count($galleryImages) > 1): ?>
<div id="ksk-lightbox" class="ksk-lightbox" hidden>
    <button class="ksk-lightbox__close" aria-label="Fermer">&times;</button>
    <button class="ksk-lightbox__prev"><i class="fas fa-chevron-left"></i></button>
    <button class="ksk-lightbox__next"><i class="fas fa-chevron-right"></i></button>
    <img class="ksk-lightbox__img" src="" alt="">
    <span class="ksk-lightbox__counter"></span>
</div>
<?php endif; ?>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
(function(){
    'use strict';

    var VOYAGE_ID = <?php echo e((int)$voyage->id); ?>;
    var CURRENCY = <?php echo json_encode($cur, 15, 512) ?>;
    var DEPARTURES = <?php echo json_encode($departuresJson, 15, 512) ?>;
    var PLACES = <?php echo json_encode($placesJson, 15, 512) ?>;
    var EXTRAS = <?php echo json_encode($extrasJson, 15, 512) ?>;
    var FLIGHTS_BY_PLACE = <?php echo json_encode($flightsByPlace ?? (object)[], 15, 512) ?>;
    var HAS_PLACES = PLACES.length > 0;
    var HAS_EXTRAS = EXTRAS.length > 0;
    var BASE_PRICE = <?php echo e((float)($priceFrom ?? 0)); ?>;

    var STEP_DATE = 1;
    var STEP_CITY = HAS_PLACES ? 2 : null;
    var STEP_CLIENT = HAS_PLACES ? 3 : 2;
    var STEP_ROOM = HAS_PLACES ? 4 : 3;
    var STEP_EXTRAS = HAS_EXTRAS ? (HAS_PLACES ? 5 : 4) : null;
    var STEP_SUMMARY = HAS_EXTRAS ? (HAS_PLACES ? 6 : 5) : (HAS_PLACES ? 5 : 4);

    var state = {
        step: 1,
        departureIdx: null,
        placeIdx: null,
        roomIdx: null,
        paxAdults: 1,
        paxChildren: 0,
        // extras selection: { [extraId:number]: { [travelerId:string]: true } }
        extras: {},
        clientMode: 'new', // new|existing
        clientExternalId: null,
        client: {
            first_name: '',
            last_name: '',
            phone: '',
            email: '',
            document_type: '',
            document_number: ''
        },
        passengers: [] // companions only (principal is the client)
    };

    function esc(s) {
        var d = document.createElement('div'); d.textContent = s; return d.innerHTML;
    }
    function fmt(n) {
        return n.toLocaleString('fr-FR', {maximumFractionDigits:0});
    }

    function recalcPaxFromPassengers() {
        var adults = 1; // principal
        var children = 0;
        (state.passengers || []).forEach(function(p){
            if(!p) return;
            var hasName = String(p.first_name||'').trim() !== '' || String(p.last_name||'').trim() !== '';
            if(!hasName) return;
            var t = String(p.type||'adult');
            if(t === 'child') children++;
            else if(t !== 'infant') adults++;
        });
        state.paxAdults = Math.max(1, adults);
        state.paxChildren = Math.max(0, children);
        var a = document.getElementById('ksk-pax-adults'); if(a) a.textContent = String(state.paxAdults);
        var c = document.getElementById('ksk-pax-children'); if(c) c.textContent = String(state.paxChildren);
    }

    function travelerRows() {
        var principalLabel = 'Client principal';
        if (state.clientMode === 'new') {
            var nm = [String(state.client.first_name||'').trim(), String(state.client.last_name||'').trim()].filter(Boolean).join(' ');
            if (nm) principalLabel = nm;
        }
        var rows = [{
            id: 'titulaire',
            label: principalLabel,
            type: 'adult',
            typeLabel: 'Adulte'
        }];

        (state.passengers || []).forEach(function(p, idx){
            var hasName = String(p.first_name||'').trim() !== '' || String(p.last_name||'').trim() !== '';
            var label = [String(p.first_name||'').trim(), String(p.last_name||'').trim()].filter(Boolean).join(' ');
            if (!label) label = 'Accompagnant #' + (idx + 1);
            var type = String(p.type || 'adult');
            rows.push({
                id: 'comp_' + idx,
                label: label,
                type: type,
                typeLabel: type === 'child' ? 'Enfant' : (type === 'infant' ? 'Bébé' : 'Adulte'),
                active: hasName, // match admin: only counts when has name
            });
        });

        return rows;
    }

    function extraUnitPrice(extra, travelerType) {
        if (!extra) return 0;
        if (travelerType === 'child') return Number(extra.price_child || 0);
        if (travelerType === 'infant') return 0;
        return Number(extra.price_adult || 0);
    }

    function extrasSelectionLines() {
        var travelers = travelerRows();
        var lines = [];
        Object.keys(state.extras || {}).forEach(function(extraIdStr){
            var extraId = parseInt(extraIdStr, 10);
            var extra = EXTRAS.find(function(e){ return e.id === extraId; });
            if (!extra) return;
            var perTrav = state.extras[extraIdStr] || {};
            Object.keys(perTrav).forEach(function(tid){
                if (!perTrav[tid]) return;
                var traveler = travelers.find(function(t){ return t.id === tid; });
                var tType = traveler ? traveler.type : 'adult';
                var unit = extraUnitPrice(extra, tType);
                lines.push({
                    voyage_extra_id: extraId,
                    extra_name: extra.name,
                    traveler_id: tid,
                    traveler_label: traveler ? traveler.label : tid,
                    traveler_type: tType,
                    unit_price: unit,
                    total_price: unit,
                    extra_type: extra.extra_type || '',
                });
            });
        });
        return lines;
    }

    function extrasTotal() {
        return extrasSelectionLines().reduce(function(sum, ln){ return sum + Number(ln.total_price || 0); }, 0);
    }

    function validateClientStep() {
        var ok = true;
        if (state.clientMode === 'existing') {
            ok = !!state.clientExternalId;
        } else {
            ok = String(state.client.first_name || '').trim() !== '' && String(state.client.last_name || '').trim() !== '';
        }
        var depOk = state.departureIdx !== null;
        if (HAS_PLACES) depOk = depOk && state.placeIdx !== null;
        ok = ok && depOk;

        var btn = document.getElementById('ksk-next-client');
        if (btn) btn.disabled = !ok;
        return ok;
    }

    function renderCompanions() {
        var container = document.getElementById('ksk-companions');
        var empty = document.getElementById('ksk-no-companion');
        if(!container || !empty) return;
        container.innerHTML = '';
        (state.passengers || []).forEach(function(p, idx){
            var wrap = document.createElement('div');
            wrap.className = 'ksk-companion';
            wrap.innerHTML = '' +
                '<div class="ksk-companion__head">' +
                '  <strong>Accompagnant #' + (idx+1) + '</strong>' +
                '  <button type="button" class="ksk-icon-btn" data-remove="'+idx+'" aria-label="Supprimer">×</button>' +
                '</div>' +
                '<div class="ksk-grid ksk-grid--two">' +
                '  <div class="ksk-field"><label>Prénom</label><input class="ksk-input" data-k="first_name" data-i="'+idx+'" autocomplete="given-name" value="'+esc(String(p.first_name||''))+'"></div>' +
                '  <div class="ksk-field"><label>Nom</label><input class="ksk-input" data-k="last_name" data-i="'+idx+'" autocomplete="family-name" value="'+esc(String(p.last_name||''))+'"></div>' +
                '  <div class="ksk-field"><label>Type</label>' +
                '    <select class="ksk-input" data-k="type" data-i="'+idx+'">' +
                '      <option value="adult" '+(String(p.type||'adult')==='adult'?'selected':'')+'>Adulte</option>' +
                '      <option value="child" '+(String(p.type||'adult')==='child'?'selected':'')+'>Enfant</option>' +
                '      <option value="infant" '+(String(p.type||'adult')==='infant'?'selected':'')+'>Bébé</option>' +
                '    </select>' +
                '  </div>' +
                '  <div class="ksk-field"><label>Date de naissance</label><input type="date" class="ksk-input" data-k="birth_date" data-i="'+idx+'" value="'+esc(String(p.birth_date||''))+'"></div>' +
                '  <div class="ksk-field"><label>Type document</label><input class="ksk-input" data-k="document_type" data-i="'+idx+'" value="'+esc(String(p.document_type||''))+'" placeholder="CIN, Passeport…"></div>' +
                '  <div class="ksk-field"><label>N° document</label><input class="ksk-input" data-k="document_number" data-i="'+idx+'" value="'+esc(String(p.document_number||''))+'"></div>' +
                '</div>';
            container.appendChild(wrap);
        });
        empty.style.display = (state.passengers || []).length ? 'none' : '';

        container.querySelectorAll('[data-remove]').forEach(function(btn){
            btn.addEventListener('click', function(){
                var i = parseInt(btn.getAttribute('data-remove'), 10);
                state.passengers.splice(i, 1);
                renderCompanions();
                recalcPaxFromPassengers();
                validateClientStep();
                updateCart();
            });
        });
        container.querySelectorAll('[data-k][data-i]').forEach(function(inp){
            inp.addEventListener('input', function(){
                var i = parseInt(inp.getAttribute('data-i'), 10);
                var k = inp.getAttribute('data-k');
                if(!state.passengers[i]) state.passengers[i] = {};
                state.passengers[i][k] = inp.value;
                recalcPaxFromPassengers();
                validateClientStep();
                updateCart();
            });
        });
    }

    /* ═══ STEP NAVIGATION ═══ */
    function resolveStep(n) {
        // Normalize to existing steps depending on HAS_PLACES/HAS_EXTRAS
        if (!HAS_PLACES) {
            if (n === 2) n = STEP_CLIENT;
        }
        if (!HAS_EXTRAS && n === STEP_EXTRAS) n = STEP_SUMMARY;
        return n;
    }
    function goStep(n) {
        n = resolveStep(n);
        state.step = n;
        document.querySelectorAll('.ksk-panel').forEach(function(p){
            p.classList.toggle('is-active', parseInt(p.dataset.panel) === n);
        });
        document.querySelectorAll('.ksk-step').forEach(function(s){
            var sn = parseInt(s.dataset.step);
            s.classList.toggle('is-active', sn === n);
            s.classList.toggle('is-done', sn < n);
        });
        document.getElementById('ksk-builder').scrollIntoView({behavior:'smooth', block:'start'});
        if (n === STEP_ROOM) renderRooms();
        if (STEP_EXTRAS !== null && n === STEP_EXTRAS) renderExtras();
        if (n === STEP_SUMMARY) renderSummary();
        updateCart();
    }

    /* ═══ STEP 1: DATES ═══ */
    function renderDates() {
        var g = document.getElementById('ksk-dates-grid'); if(!g) return;
        var h = '';
        DEPARTURES.forEach(function(d, i){
            var disabled = d.status === 'full' || d.status === 'closed' || d.status === 'canceled' || d.status === 'cancelled';
            var statusLabel = {open:'Disponible', limited:'Dernières places', full:'Complet', closed:'Fermé', canceled:'Fermé', cancelled:'Fermé', draft:'Bientôt'}[d.status] || d.status;
            var statusClass = {open:'ok', limited:'warn', full:'full', closed:'full', canceled:'full', cancelled:'full'}[d.status] || 'ok';
            var price = d.sale_price > 0 ? d.sale_price : (d.base_price > 0 ? d.base_price : BASE_PRICE);
            h += '<button type="button" class="ksk-date-card' + (disabled ? ' is-disabled' : '') + '" data-dep="'+i+'"' + (disabled ? ' disabled' : '') + '>';
            h += '<div class="ksk-date-card__date">' + esc(d.start_label) + '</div>';
            if(d.end_label) h += '<div class="ksk-date-card__range">→ ' + esc(d.end_label) + '</div>';
            h += '<span class="ksk-status ksk-status--'+statusClass+'">' + esc(statusLabel) + '</span>';
            if(!disabled && d.available_capacity > 0) h += '<div class="ksk-date-card__seats">' + d.available_capacity + ' place(s)</div>';
            if(price > 0) h += '<div class="ksk-date-card__price">' + fmt(price) + ' ' + CURRENCY + '</div>';
            h += '</button>';
        });
        if(h === '') h = '<p class="ksk-empty">Aucun départ disponible actuellement.</p>';
        g.innerHTML = h;
        g.querySelectorAll('.ksk-date-card:not(.is-disabled)').forEach(function(c){
            c.addEventListener('click', function(){
                state.departureIdx = parseInt(c.dataset.dep);
                state.roomIdx = null;
                g.querySelectorAll('.ksk-date-card').forEach(function(x){ x.classList.remove('is-selected'); });
                c.classList.add('is-selected');
                var btn = document.getElementById('ksk-next-1'); if(btn) btn.disabled = false;
                updateCart();
                validateClientStep();
            });
        });
    }

    /* ═══ STEP 2: CITIES ═══ */
    function renderCities() {
        var g = document.getElementById('ksk-cities-grid'); if(!g) return;
        var h = '';
        PLACES.forEach(function(p, i){
            h += '<button type="button" class="ksk-city-card" data-place="'+i+'">';
            h += '<i class="fas fa-plane-departure ksk-city-card__icon"></i>';
            h += '<div class="ksk-city-card__name">' + esc(p.name) + '</div>';
            if(p.code) h += '<div class="ksk-city-card__code">' + esc(p.code) + '</div>';
            if(p.price > 0) h += '<div class="ksk-city-card__sup">+' + fmt(p.price) + ' ' + CURRENCY + '</div>';
            h += '</button>';
        });
        g.innerHTML = h;
        g.querySelectorAll('.ksk-city-card').forEach(function(c){
            c.addEventListener('click', function(){
                state.placeIdx = parseInt(c.dataset.place);
                g.querySelectorAll('.ksk-city-card').forEach(function(x){ x.classList.remove('is-selected'); });
                c.classList.add('is-selected');
                var btn = document.getElementById('ksk-next-2'); if(btn) btn.disabled = false;
                showFlightInfo();
                updateCart();
                validateClientStep();
            });
        });
    }
    function showFlightInfo() {
        var el = document.getElementById('ksk-flight-info'); if(!el) return;
        var place = PLACES[state.placeIdx]; if(!place) { el.hidden = true; return; }
        var flights = FLIGHTS_BY_PLACE[String(place.id)] || [];
        if(flights.length === 0) { el.hidden = true; return; }
        var h = '<div class="ksk-flight-cards">';
        flights.forEach(function(f){
            var typeLabel = f.type === 'outbound' ? 'Aller' : (f.type === 'return' ? 'Retour' : 'Interne');
            h += '<div class="ksk-flight-mini">';
            h += '<span class="ksk-flight-mini__type">' + typeLabel + '</span>';
            h += '<span>' + esc(f.from_city||'—') + ' → ' + esc(f.to_city||'—') + '</span>';
            if(f.depart_at) h += '<span><i class="far fa-clock"></i> ' + f.depart_at + '</span>';
            if(f.airline) h += '<span><i class="fas fa-building"></i> ' + esc(f.airline) + '</span>';
            h += '</div>';
        });
        h += '</div>';
        el.innerHTML = h; el.hidden = false;
    }

    /* ═══ STEP 3: ROOMS ═══ */
    function renderRooms() {
        var g = document.getElementById('ksk-rooms-grid'); if(!g) return;
        var dep = DEPARTURES[state.departureIdx];
        var rooms = dep ? (dep.rooms || []) : [];
        var h = '';
        if(rooms.length === 0) {
            state.roomIdx = null;
            h = '<div class="ksk-empty">Aucun type de chambre n’est configuré pour ce départ.</div>';
            var btn = document.getElementById('ksk-next-room'); if(btn) btn.disabled = true;
        } else {
            rooms.forEach(function(r, i){
                var availableRooms = Number(r.available_rooms || 0);
                var cap = Math.max(1, Number(r.capacity_per_room || 0));
                var disabled = r.status === 'full' || r.status === 'closed' || r.status === 'inactive' || availableRooms <= 0;
                var statusLabel = disabled ? 'Complet' : (r.status === 'limited' ? 'Dernières dispo' : 'Disponible');
                var hotelLabel = r.hotel_name ? r.hotel_name : 'Sans hôtel';
                var coveredPlaces = Number(r.reserved_places || 0);
                var effectiveCapacity = Number(r.effective_capacity || (availableRooms * cap));
                h += '<button type="button" class="ksk-room-card' + (disabled ? ' is-disabled' : '') + '" data-room="'+i+'"' + (disabled?' disabled':'') + '>';
                h += '<div class="ksk-room-card__icon"><i class="fas fa-bed"></i></div>';
                h += '<div class="ksk-room-card__name">' + esc(r.room_type) + '</div>';
                h += '<div class="ksk-room-card__meta">' + esc(hotelLabel) + ' · ' + cap + ' pers./chambre</div>';
                h += '<div class="ksk-room-card__meta">Quantité dispo: <strong>' + availableRooms + '</strong> · Capacité effective: <strong>' + effectiveCapacity + '</strong> · Places couvertes: <strong>' + coveredPlaces + '</strong></div>';
                h += '<span class="ksk-status ksk-status--' + (disabled?'full':'ok') + '">' + statusLabel + '</span>';
                h += '<div class="ksk-room-card__price">' + (r.supplement > 0 ? '+'+fmt(r.supplement)+' '+CURRENCY : 'Inclus') + '</div>';
                h += '</button>';
            });
        }
        g.innerHTML = h;
        g.querySelectorAll('.ksk-room-card:not(.is-disabled)').forEach(function(c){
            c.addEventListener('click', function(){
                state.roomIdx = parseInt(c.dataset.room);
                g.querySelectorAll('.ksk-room-card').forEach(function(x){ x.classList.remove('is-selected'); });
                c.classList.add('is-selected');
                enforcePaxForSelectedRoom();
                var btn = document.getElementById('ksk-next-room'); if(btn) btn.disabled = false;
                updateCart();
            });
        });
    }

    /* ═══ STEP 4: EXTRAS ═══ */
    function renderExtras() {
        var g = document.getElementById('ksk-extras-grid'); if(!g) return;
        var travelers = travelerRows();
        var principal = travelers[0];
        var activeCompanions = travelers.slice(1).filter(function(t){ return t.active; });
        var travelerList = [principal].concat(activeCompanions);

        if (travelerList.length === 0) {
            g.innerHTML = '<div class="ksk-empty">Aucun voyageur disponible.</div>';
            return;
        }

        var h = '';
        EXTRAS.forEach(function(e){
            var icon = e.icon || 'fa-plus-circle';
            var base = Number(e.price_adult || 0);
            var selectedCount = 0;
            var selectedTotal = 0;
            var perTrav = state.extras[String(e.id)] || {};
            travelerList.forEach(function(t){
                if(perTrav[t.id]) {
                    selectedCount++;
                    selectedTotal += extraUnitPrice(e, t.type);
                }
            });

            h += '<details class="ksk-extra2" data-extra="'+e.id+'"' + (selectedCount>0?' open':'') + '>';
            h += '  <summary class="ksk-extra2__head">';
            h += '    <div class="ksk-extra2__title"><i class="fas '+esc(icon)+'"></i> '+esc(e.name)+'</div>';
            h += '    <div class="ksk-extra2__meta">';
            h += '      <span class="ksk-extra2__unit">'+(base>0? (fmt(base)+' '+CURRENCY+'/pers') : 'Gratuit')+'</span>';
            h += '      <span class="ksk-extra2__total">'+(selectedCount>0 ? (fmt(selectedTotal)+' '+CURRENCY) : '—')+'</span>';
            h += '    </div>';
            h += '  </summary>';
            if (e.description) h += '  <div class="ksk-extra2__desc">'+esc(e.description)+'</div>';
            h += '  <div class="ksk-extra2__trav">';
            travelerList.forEach(function(t){
                var unit = extraUnitPrice(e, t.type);
                var checked = !!(perTrav[t.id]);
                h += '    <label class="ksk-extra2__row">';
                h += '      <span class="ksk-extra2__who">';
                h += '        <input type="checkbox" class="ksk-extra2__cb" data-extra="'+e.id+'" data-trav="'+esc(t.id)+'" '+(checked?'checked':'')+'>';
                h += '        <span>'+esc(t.label)+'</span><small>'+esc(t.typeLabel)+'</small>';
                h += '      </span>';
                h += '      <span class="ksk-extra2__price">'+(unit>0? (fmt(unit)+' '+CURRENCY) : '0 '+CURRENCY)+'</span>';
                h += '    </label>';
            });
            h += '  </div>';
            h += '</details>';
        });
        g.innerHTML = h;

        g.querySelectorAll('.ksk-extra2__cb').forEach(function(cb){
            cb.addEventListener('change', function(){
                var extraId = String(cb.getAttribute('data-extra') || '');
                var travId = String(cb.getAttribute('data-trav') || '');
                if (!extraId || !travId) return;
                if (!state.extras[extraId]) state.extras[extraId] = {};
                if (cb.checked) state.extras[extraId][travId] = true;
                else delete state.extras[extraId][travId];

                // Cleanup empties
                if (state.extras[extraId] && Object.keys(state.extras[extraId]).length === 0) delete state.extras[extraId];

                renderExtras();
                updateCart();
            });
        });
    }

    /* ═══ PRICING ═══ */
    function calcTotal() {
        var dep = DEPARTURES[state.departureIdx];
        if(!dep) return 0;
        var perPerson = dep.sale_price > 0 ? dep.sale_price : (dep.base_price > 0 ? dep.base_price : BASE_PRICE);
        if(state.roomIdx !== null && state.roomIdx >= 0 && dep.rooms[state.roomIdx]) {
            var sup = dep.rooms[state.roomIdx].supplement || 0;
            if (isHalfDoubleSelection()) sup = sup / 2;
            perPerson += sup;
        }
        var place = PLACES[state.placeIdx];
        if(place) perPerson += place.price || 0;
        var totalPax = state.paxAdults + state.paxChildren;
        var total = perPerson * (totalPax > 0 ? totalPax : 1);
        total += extrasTotal();
        return total;
    }

    /* ═══ CART / SIDEBAR ═══ */
    function updateCart() {
        var body = document.getElementById('ksk-cart-body');
        var totalEl = document.getElementById('ksk-cart-total');
        var mobileTotal = document.getElementById('ksk-mobile-total');
        var reserveBtn = document.getElementById('ksk-cart-reserve');
        var dep = DEPARTURES[state.departureIdx];
        if(!dep) {
            body.innerHTML = '<p class="ksk-cart__empty">Commencez par choisir une date de départ.</p>';
            totalEl.textContent = '— ' + CURRENCY;
            if(mobileTotal) mobileTotal.textContent = '—';
            if(reserveBtn) reserveBtn.style.display = 'none';
            return;
        }
        var h = '';
        h += '<div class="ksk-cart__line"><span><i class="fas fa-calendar"></i> Départ</span><strong>' + esc(dep.start_label) + '</strong></div>';
        var place = PLACES[state.placeIdx];
        if(place) h += '<div class="ksk-cart__line"><span><i class="fas fa-plane"></i> Ville</span><strong>' + esc(place.name) + '</strong></div>';
        if(state.roomIdx !== null) {
            if(state.roomIdx >= 0 && dep.rooms[state.roomIdx]) {
                var roomLabel = dep.rooms[state.roomIdx].room_type;
                if (isHalfDoubleSelection()) roomLabel = roomLabel + ' (partagée)';
                h += '<div class="ksk-cart__line"><span><i class="fas fa-bed"></i> Chambre</span><strong>' + esc(roomLabel) + '</strong></div>';
            }
        }
        h += '<div class="ksk-cart__line"><span><i class="fas fa-users"></i> Voyageurs</span><strong>' + state.paxAdults + ' ad.' + (state.paxChildren > 0 ? ' + '+state.paxChildren+' enf.' : '') + '</strong></div>';
        var extraNames = [];
        var extraLines = extrasSelectionLines();
        if(extraLines.length) {
            var grouped = {};
            extraLines.forEach(function(ln){
                var k = String(ln.voyage_extra_id);
                if(!grouped[k]) grouped[k] = { name: ln.extra_name, count: 0, total: 0 };
                grouped[k].count += 1;
                grouped[k].total += Number(ln.total_price || 0);
            });
            Object.keys(grouped).forEach(function(k){
                var it = grouped[k];
                extraNames.push(it.name + ' (' + it.count + ' pers) → ' + fmt(it.total) + ' ' + CURRENCY);
            });
            h += '<div class="ksk-cart__line"><span><i class="fas fa-star"></i> Extras</span><strong>' + esc(extraNames.join(', ')) + '</strong></div>';
        }
        body.innerHTML = h;
        var total = calcTotal();
        totalEl.textContent = fmt(total) + ' ' + CURRENCY;
        if(mobileTotal) mobileTotal.textContent = fmt(total);
        if(reserveBtn) {
            reserveBtn.style.display = state.step >= 3 ? '' : 'none';
            reserveBtn.href = buildReserveUrl();
        }
    }

    /* ═══ SUMMARY ═══ */
    function renderSummary() {
        var el = document.getElementById('ksk-summary-detail'); if(!el) return;
        var dep = DEPARTURES[state.departureIdx] || {};
        var place = PLACES[state.placeIdx];
        var h = '<div class="ksk-summary-grid">';
        h += sumRow('Date de départ', dep.start_label || '—', 'fa-calendar');
        if(dep.end_label) h += sumRow('Retour', dep.end_label, 'fa-calendar-check');
        if(place) h += sumRow('Ville de départ', place.name, 'fa-plane-departure');
        var roomLabel = '—';
        if(state.roomIdx !== null && state.roomIdx >= 0 && dep.rooms && dep.rooms[state.roomIdx]) {
            roomLabel = dep.rooms[state.roomIdx].room_type;
            if (isHalfDoubleSelection()) roomLabel = roomLabel + ' (partagée)';
        }
        h += sumRow('Chambre', roomLabel, 'fa-bed');
        h += sumRow('Voyageurs', state.paxAdults + ' adulte(s)' + (state.paxChildren > 0 ? ', '+state.paxChildren+' enfant(s)' : ''), 'fa-users');
        var extraLines = extrasSelectionLines();
        if(extraLines.length) {
            var grouped = {};
            extraLines.forEach(function(ln){
                var k = String(ln.voyage_extra_id);
                if(!grouped[k]) grouped[k] = { name: ln.extra_name, count: 0, total: 0 };
                grouped[k].count += 1;
                grouped[k].total += Number(ln.total_price || 0);
            });
            var labels = Object.keys(grouped).map(function(k){
                var it = grouped[k];
                return it.name + ' (' + it.count + ' pers) → ' + fmt(it.total) + ' ' + CURRENCY;
            });
            h += sumRow('Extras', labels.join(', '), 'fa-star');
        }
        h += '</div>';
        h += '<div class="ksk-summary-total"><span>Total estimé</span><strong>' + fmt(calcTotal()) + ' ' + CURRENCY + '</strong></div>';
        el.innerHTML = h;
        var btn = document.getElementById('ksk-reserve-btn');
        if(btn) btn.href = '#';
    }
    function sumRow(label, value, icon) {
        return '<div class="ksk-sum-row"><span class="ksk-sum-row__label"><i class="fas '+icon+'"></i> '+esc(label)+'</span><span class="ksk-sum-row__value">'+esc(value)+'</span></div>';
    }

    /* ═══ RESERVE URL ═══ */
    function buildReserveUrl() {
        var dep = DEPARTURES[state.departureIdx];
        if(!dep) return '#';
        var params = 'voyage_id=' + VOYAGE_ID + '&travel_date_id=' + dep.wp_travel_date_id;
        if(state.paxAdults) params += '&adults=' + state.paxAdults;
        if(state.paxChildren) params += '&children=' + state.paxChildren;
        if(state.roomIdx !== null && state.roomIdx >= 0 && dep.rooms && dep.rooms[state.roomIdx] && dep.rooms[state.roomIdx].id) {
            params += '&departure_hotel_room_id=' + dep.rooms[state.roomIdx].id;
        }
        // Legacy link kept for compatibility; primary flow is now POST to /voyages/{slug}/reserve
        return '/admin/reservations/create?' + params;
    }

    async function submitReservation() {
        var dep = DEPARTURES[state.departureIdx];
        if(!dep) return;

        var payload = {
            departure_id: dep.id,
            travel_date_id: dep.wp_travel_date_id,
            departure_hotel_room_id: null,
            client_mode: state.clientMode,
            client_external_id: state.clientExternalId,
            client_first_name: state.client.first_name,
            client_last_name: state.client.last_name,
            client_phone: state.client.phone,
            client_email: state.client.email,
            client_document_type: state.client.document_type,
            client_document_number: state.client.document_number,
            passengers: state.passengers || [],
            extras_json: JSON.stringify(extrasSelectionLines().map(function(ln){
                return {
                    voyage_extra_id: ln.voyage_extra_id,
                    name: ln.extra_name + ' (' + ln.traveler_label + ')',
                    price: ln.unit_price,
                    quantity: 1,
                    pax: ln.traveler_id,
                    traveler_type: ln.traveler_type,
                    extra_type: ln.extra_type || '',
                };
            }))
        };

        if(state.roomIdx !== null && state.roomIdx >= 0 && dep.rooms && dep.rooms[state.roomIdx] && dep.rooms[state.roomIdx].id) {
            payload.departure_hotel_room_id = dep.rooms[state.roomIdx].id;
        }

        var btn = document.getElementById('ksk-reserve-btn');
        if(btn) { btn.classList.add('is-loading'); btn.setAttribute('aria-disabled','true'); }

        try {
            var res = await fetch(window.location.pathname + '/reserve', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': <?php echo json_encode(csrf_token(), 15, 512) ?>
                },
                body: JSON.stringify(payload)
            });
            var json = await res.json();
            if(!res.ok || !json.ok) {
                throw new Error((json && json.message) ? json.message : 'Erreur lors de la réservation.');
            }
            alert('Réservation créée (ID ' + json.reservation_id + '). Statut: ' + json.status);
        } catch (e) {
            alert(e && e.message ? e.message : 'Erreur lors de la réservation.');
        } finally {
            if(btn) { btn.classList.remove('is-loading'); btn.removeAttribute('aria-disabled'); }
        }
    }

    function selectedRoomCap() {
        var dep = DEPARTURES[state.departureIdx];
        if(!dep || state.roomIdx === null || state.roomIdx < 0 || !dep.rooms || !dep.rooms[state.roomIdx]) return null;
        var cap = parseInt(dep.rooms[state.roomIdx].capacity_per_room || '0', 10) || 0;
        return cap > 0 ? cap : null;
    }

    function isHalfDoubleSelection() {
        var dep = DEPARTURES[state.departureIdx];
        if(!dep || state.roomIdx === null || state.roomIdx < 0 || !dep.rooms || !dep.rooms[state.roomIdx]) return false;
        var r = dep.rooms[state.roomIdx];
        var cap = parseInt(r.capacity_per_room || '0', 10) || 0;
        var rt = String(r.room_type || '');
        return state.paxAdults === 1 && state.paxChildren === 0 && cap === 2 && rt.toLowerCase() === 'double';
    }

    function enforcePaxForSelectedRoom() {
        var cap = selectedRoomCap();
        if(!cap) return;
        var total = state.paxAdults + state.paxChildren;
        if(total <= cap) return;

        // Keep at least 1 adult, clamp total pax to room capacity (1 room selected).
        var newTotal = cap;
        var adults = Math.max(1, Math.min(state.paxAdults, newTotal));
        var remaining = newTotal - adults;
        var children = Math.max(0, Math.min(state.paxChildren, remaining));

        state.paxAdults = adults;
        state.paxChildren = children;
        document.getElementById('ksk-pax-adults').textContent = state.paxAdults;
        document.getElementById('ksk-pax-children').textContent = state.paxChildren;
    }

    // PAX is derived from Client/Voyageurs step.

    /* ═══ NEXT BUTTONS ═══ */
    document.querySelectorAll('.ksk-btn--next').forEach(function(btn){
        btn.addEventListener('click', function(){
            var panel = btn.closest('.ksk-panel');
            var cur = parseInt(panel.dataset.panel);
            var next = cur + 1;
            if(!HAS_PLACES && STEP_CITY === null && next === 2) next = STEP_CLIENT;
            if(!HAS_EXTRAS && next === STEP_EXTRAS) next = STEP_SUMMARY;
            goStep(next);
        });
    });
    document.querySelectorAll('.ksk-step').forEach(function(s){
        s.addEventListener('click', function(){
            var n = parseInt(s.dataset.step);
            if(n <= state.step) goStep(n);
        });
    });

    /* ═══ CLIENT / TRAVELERS STEP BINDINGS ═══ */
    (function bindClientStep(){
        var modeRadios = document.querySelectorAll('input[name="ksk_client_mode"]');
        var blockExisting = document.getElementById('ksk-existing-client');
        var blockNew = document.getElementById('ksk-new-client');
        var btnNext = document.getElementById('ksk-next-client');
        var btnAdd = document.getElementById('ksk-add-companion');

        function syncModeUI() {
            var mode = state.clientMode;
            if (blockExisting) blockExisting.hidden = (mode !== 'existing');
            if (blockNew) blockNew.hidden = (mode === 'existing');
            validateClientStep();
        }

        modeRadios.forEach(function(r){
            r.addEventListener('change', function(){
                state.clientMode = r.value === 'existing' ? 'existing' : 'new';
                state.clientExternalId = null;
                syncModeUI();
                updateCart();
            });
        });

        function bindField(id, key) {
            var el = document.getElementById(id);
            if (!el) return;
            el.addEventListener('input', function(){
                state.client[key] = el.value;
                validateClientStep();
                updateCart();
            });
        }
        bindField('ksk-client-first', 'first_name');
        bindField('ksk-client-last', 'last_name');
        bindField('ksk-client-phone', 'phone');
        bindField('ksk-client-email', 'email');
        bindField('ksk-client-doc-type', 'document_type');
        bindField('ksk-client-doc-num', 'document_number');

        // Existing client search (AJAX autocomplete)
        (function bindExistingClientSearch(){
            var input = document.getElementById('ksk-client-search');
            var results = document.getElementById('ksk-client-results');
            var selected = document.getElementById('ksk-client-selected');
            var btnClear = document.getElementById('ksk-client-clear');
            if (!input || !results) return;

            var endpoint = <?php echo json_encode(\Illuminate\Support\Facades\Route::has('admin.customers.clients.search') ? route('admin.customers.clients.search') : null, 15, 512) ?>;
            var timer = null;
            var lastQ = '';
            var inflight = 0;
            if (!endpoint) {
                var existingModeRadio = document.querySelector('input[name="ksk_client_mode"][value="existing"]');
                if (existingModeRadio) {
                    existingModeRadio.checked = false;
                    existingModeRadio.disabled = true;
                    var existingModeLabel = existingModeRadio.closest('label');
                    if (existingModeLabel) existingModeLabel.style.display = 'none';
                }
                state.clientMode = 'new';
                syncModeUI();
                return;
            }

            function setLoading(q) {
                if (!results) return;
                results.hidden = false;
                results.innerHTML = '<div class="ksk-search-item"><strong>Recherche…</strong><div class="small text-muted">'+esc(q)+'</div></div>';
            }
            function setEmpty(q) {
                results.hidden = false;
                results.innerHTML = '<div class="ksk-search-item"><strong>Aucun client trouvé</strong><div class="small text-muted">'+esc(q)+'</div></div>';
            }
            function clearSelection() {
                state.clientExternalId = null;
                input.value = '';
                input.disabled = false;
                if (selected) { selected.hidden = true; selected.innerHTML = ''; }
                if (btnClear) btnClear.hidden = true;
                results.hidden = true;
                results.innerHTML = '';
                validateClientStep();
                updateCart();
                input.focus();
            }
            function applySelection(item) {
                state.clientExternalId = item.id;
                var label = (item.client_code ? ('['+item.client_code+'] ') : '') + (item.full_name || '');
                if (item.phone) label += ' · ' + item.phone;
                if (item.email) label += ' · ' + item.email;

                input.value = label;
                input.disabled = true;
                results.hidden = true;
                results.innerHTML = '';

                if (selected) {
                    var meta = [];
                    if (item.city) meta.push(item.city);
                    if (item.document) meta.push(item.document);
                    selected.hidden = false;
                    selected.innerHTML = '<strong>Client sélectionné :</strong> ' + esc(label) + (meta.length ? ('<div class="small text-muted">'+esc(meta.join(' · '))+'</div>') : '');
                }
                if (btnClear) btnClear.hidden = false;

                validateClientStep();
                updateCart();
            }

            async function runSearch(q) {
                var myFlight = ++inflight;
                try {
                    setLoading(q);
                    var url = endpoint + '?q=' + encodeURIComponent(q);
                    var res = await fetch(url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
                    if (myFlight !== inflight) return; // stale

                    if (res.status === 401 || res.status === 403) {
                        results.hidden = false;
                        results.innerHTML = '<div class="ksk-search-item"><strong>Accès non autorisé</strong><div class="small text-muted">Connectez-vous à l’admin pour rechercher un client existant.</div></div>';
                        return;
                    }
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    var data = await res.json();
                    var items = (data && data.items) ? data.items : [];
                    if (!items.length) { setEmpty(q); return; }

                    results.hidden = false;
                    results.innerHTML = items.map(function(it){
                        var top = (it.full_name || '—');
                        var sub = [];
                        if (it.client_code) sub.push('Code: ' + it.client_code);
                        if (it.phone) sub.push('Tél: ' + it.phone);
                        if (it.email) sub.push(it.email);
                        if (it.document) sub.push('Doc: ' + it.document);
                        return '' +
                            '<button type="button" class="ksk-search-item" data-id="'+it.id+'">' +
                            '  <strong>'+esc(top)+'</strong>' +
                            '  <div class="small text-muted">'+esc(sub.filter(Boolean).join(' · '))+'</div>' +
                            '</button>';
                    }).join('');

                    results.querySelectorAll('[data-id]').forEach(function(btn){
                        btn.addEventListener('click', function(){
                            var id = parseInt(btn.getAttribute('data-id'), 10);
                            var it = items.find(function(x){ return x.id === id; });
                            if (it) applySelection(it);
                        });
                    });
                } catch (e) {
                    results.hidden = false;
                    results.innerHTML = '<div class="ksk-search-item"><strong>Erreur de recherche</strong><div class="small text-muted">Veuillez réessayer.</div></div>';
                }
            }

            input.addEventListener('input', function(){
                if (state.clientMode !== 'existing') return;
                state.clientExternalId = null;
                if (selected) selected.hidden = true;
                if (btnClear) btnClear.hidden = true;
                input.disabled = false;

                var q = String(input.value || '').trim();
                lastQ = q;
                if (timer) window.clearTimeout(timer);
                if (q.length < 2) {
                    results.hidden = true;
                    results.innerHTML = '';
                    validateClientStep();
                    return;
                }
                timer = window.setTimeout(function(){ runSearch(q); }, 220);
                validateClientStep();
            });

            input.addEventListener('keydown', function(e){
                if (e.key === 'Escape') { results.hidden = true; results.innerHTML = ''; }
            });
            document.addEventListener('click', function(e){
                if (!results || results.hidden) return;
                if (e.target === input || results.contains(e.target)) return;
                results.hidden = true;
            });
            if (btnClear) btnClear.addEventListener('click', function(){ clearSelection(); });

            // When switching mode, reset UI appropriately
            var modeRadios2 = document.querySelectorAll('input[name="ksk_client_mode"]');
            modeRadios2.forEach(function(r){
                r.addEventListener('change', function(){
                    if (r.value === 'existing') {
                        clearSelection();
                    } else {
                        // hide existing search UI remnants
                        results.hidden = true;
                        if (selected) selected.hidden = true;
                        if (btnClear) btnClear.hidden = true;
                    }
                });
            });
        })();

        if (btnAdd) {
            btnAdd.addEventListener('click', function(){
                state.passengers.push({ first_name:'', last_name:'', type:'adult', birth_date:'', document_type:'', document_number:'' });
                renderCompanions();
                recalcPaxFromPassengers();
                validateClientStep();
                updateCart();
            });
        }

        if (btnNext) {
            btnNext.addEventListener('click', function(){
                if (!validateClientStep()) return;
                recalcPaxFromPassengers();
                goStep(STEP_ROOM);
            });
        }

        renderCompanions();
        recalcPaxFromPassengers();
        syncModeUI();
    })();

    /* ═══ FINAL RESERVE BUTTON ═══ */
    (function bindReserve(){
        var btn = document.getElementById('ksk-reserve-btn');
        var cartBtn = document.getElementById('ksk-cart-reserve');
        function onClick(e){ e && e.preventDefault && e.preventDefault(); submitReservation(); }
        if (btn) btn.addEventListener('click', onClick);
        if (cartBtn) cartBtn.addEventListener('click', onClick);
    })();

    /* ═══ MOBILE BAR ═══ */
    var mobileBar = document.getElementById('ksk-mobile-bar');
    var heroEl = document.getElementById('ksk-hero');
    if(mobileBar && heroEl) {
        var obs = new IntersectionObserver(function(entries){ mobileBar.classList.toggle('is-visible', !entries[0].isIntersecting); }, {threshold:0});
        obs.observe(heroEl);
    }

    /* ═══ LIGHTBOX ═══ */
    var lb = document.getElementById('ksk-lightbox');
    if(lb) {
        var imgs = <?php echo json_encode(array_values($galleryImages), 15, 512) ?>;
        var cur_lb = 0;
        var lbImg = lb.querySelector('.ksk-lightbox__img');
        var lbCnt = lb.querySelector('.ksk-lightbox__counter');
        function showLb(i){ cur_lb=((i%imgs.length)+imgs.length)%imgs.length; lbImg.src=imgs[cur_lb]; lbCnt.textContent=(cur_lb+1)+'/'+imgs.length; }
        window.__kskShowLb = showLb;
        window.__kskLb = lb;
        document.querySelectorAll('[data-ksk-lb]').forEach(function(a){ a.addEventListener('click',function(e){ e.preventDefault(); showLb(parseInt(a.dataset.index||'0')); lb.hidden=false; document.body.style.overflow='hidden'; }); });
        lb.querySelector('.ksk-lightbox__close').addEventListener('click',function(){ lb.hidden=true; document.body.style.overflow=''; });
        lb.querySelector('.ksk-lightbox__prev').addEventListener('click',function(){ showLb(cur_lb-1); });
        lb.querySelector('.ksk-lightbox__next').addEventListener('click',function(){ showLb(cur_lb+1); });
        lb.addEventListener('click',function(e){ if(e.target===lb){lb.hidden=true;document.body.style.overflow='';} });
        document.addEventListener('keydown',function(e){ if(lb.hidden)return; if(e.key==='Escape'){lb.hidden=true;document.body.style.overflow='';} if(e.key==='ArrowLeft')showLb(cur_lb-1); if(e.key==='ArrowRight')showLb(cur_lb+1); });
    }

    /* ═══ PUBLIC API ═══ */
    window.ksk = {
        goStep: goStep,
        scrollToBuilder: function(){ document.getElementById('ksk-builder').scrollIntoView({behavior:'smooth',block:'start'}); }
    };

    /* ═══ HERO (Booking-style) ═══ */
    (function(){
        // Mobile slider (simple, no auto)
        var slides = document.querySelectorAll('.ksk-hero2__mobile-slide');
        if(slides.length > 1) {
            var cur = 0;
            var total = slides.length;
            function show(n) {
                cur = ((n % total) + total) % total;
                slides.forEach(function(s, i){ s.classList.toggle('is-active', i === cur); });
            }
            function next(){ show(cur + 1); }
            function prev(){ show(cur - 1); }
            show(0);

            var prevBtn = document.getElementById('ksk-hero-prev');
            var nextBtn = document.getElementById('ksk-hero-next');
            if(prevBtn) prevBtn.addEventListener('click', function(e){ e.preventDefault(); prev(); });
            if(nextBtn) nextBtn.addEventListener('click', function(e){ e.preventDefault(); next(); });
        }

        // Hook hero tiles to existing lightbox
        document.querySelectorAll('[data-ksk-lb-open]').forEach(function(btn){
            btn.addEventListener('click', function(){
                var _lb = window.__kskLb || lb;
                var _show = window.__kskShowLb || (typeof showLb === 'function' ? showLb : null);
                if(!_lb || typeof _show !== 'function') return;
                var i = parseInt(btn.dataset.index || '0');
                _show(i);
                _lb.hidden = false;
                document.body.style.overflow = 'hidden';
            });
        });
    })();

    /* ═══ INIT ═══ */
    renderDates();
    if(HAS_PLACES) renderCities();
    updateCart();

})();
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.front', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\front\voyages\show.blade.php ENDPATH**/ ?>