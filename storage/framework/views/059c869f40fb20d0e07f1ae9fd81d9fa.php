<?php $__env->startSection('title', 'Créer une réservation (V2)'); ?>
<?php $__env->startSection('hidePageFooter', '1'); ?>

<?php $__env->startPush('styles'); ?>
    <link rel="stylesheet" href="<?php echo e(asset('css/reservation-create-v2.css')); ?>">
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="reservation-create-v2">
    <header class="v2-header">
        <div class="v2-header__top">
            <nav class="v2-breadcrumb" aria-label="Breadcrumb">
                <a href="<?php echo e(route('admin.reservations.workspace')); ?>">Workspace</a>
                <i class="bx bx-chevron-right"></i>
                <span>Nouvelle réservation</span>
            </nav>
            <div class="v2-header__actions">
                <a href="<?php echo e(route('admin.reservations.workspace')); ?>" class="v2-btn v2-btn--ghost">
                    <i class="bx bx-arrow-back"></i> Retour
                </a>
                <button type="button" class="v2-btn v2-btn--outline" id="v2-btn-draft">
                    <i class="bx bx-save"></i> Enregistrer brouillon
                </button>
            </div>
        </div>
        <div class="v2-header__main">
            <h1 class="v2-header__title">Créer une réservation</h1>
            <p class="v2-header__subtitle">Nouvelle expérience simplifiée — 4 étapes pour un dossier complet.</p>
        </div>
    </header>

    <?php if($errors->any()): ?>
        <div class="v2-alert v2-alert--error">
            <strong>Le dossier contient des erreurs.</strong>
            <ul>
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li><?php echo e($error); ?></li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="<?php echo e(route('admin.reservations.store')); ?>" enctype="multipart/form-data" id="v2-reservation-form">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="extras_json" id="v2-extras-json" value="[]">
        <input type="hidden" name="travelers_json" id="v2-travelers-json" value="[]">
        <input type="hidden" name="room_allocations_json" id="v2-room-allocations-json" value="<?php echo e(old('room_allocations_json', '[]')); ?>">
        <input type="hidden" name="accommodation_mode" id="v2-accommodation-mode" value="rooms">
        <input type="hidden" name="total_base" id="v2-total-base" value="<?php echo e(old('total_base', 0)); ?>">
        <input type="hidden" name="room_supplement_total" id="v2-room-supplement-total" value="<?php echo e(old('room_supplement_total', 0)); ?>">
        <input type="hidden" name="extras_total" id="v2-extras-total" value="<?php echo e(old('extras_total', 0)); ?>">
        <input type="hidden" name="total_amount" id="v2-total-amount" value="<?php echo e(old('total_amount', 0)); ?>">

        <div class="v2-layout">
            <main class="v2-main">
                
                <div class="v2-stepper" role="tablist" aria-label="Étapes de création">
                    <button type="button" class="v2-stepper__step is-active" data-v2-step-nav="1">
                        <span class="v2-stepper__badge">1</span>
                        <span class="v2-stepper__label">Prestation</span>
                    </button>
                    <button type="button" class="v2-stepper__step" data-v2-step-nav="2">
                        <span class="v2-stepper__badge">2</span>
                        <span class="v2-stepper__label">Voyageurs</span>
                    </button>
                    <button type="button" class="v2-stepper__step" data-v2-step-nav="3">
                        <span class="v2-stepper__badge">3</span>
                        <span class="v2-stepper__label">Hébergement</span>
                    </button>
                    <button type="button" class="v2-stepper__step" data-v2-step-nav="4">
                        <span class="v2-stepper__badge">4</span>
                        <span class="v2-stepper__label">Paiement</span>
                    </button>
                </div>

                
                <section class="v2-panel is-active" data-v2-step="1">
                    <div class="v2-card">
                        <div class="v2-card__head">
                            <div>
                                <p class="v2-eyebrow">Étape 1 — Prestation & départ</p>
                                <h2 class="v2-card__title">Choisissez le voyage et le départ</h2>
                            </div>
                        </div>

                        <?php if(isset($travelDateIncoherent) && $travelDateIncoherent): ?>
                            <div class="v2-alert v2-alert--warn">La date de départ fournie ne correspond pas au voyage sélectionné. Elle a été ignorée.</div>
                        <?php endif; ?>

                        <div class="v2-field v2-field--full">
                            <label class="v2-label" for="v2-select-tour">Voyage / circuit <span class="v2-required">*</span></label>
                            <div class="v2-search-select">
                                <i class="bx bx-search"></i>
                                <input type="text" id="v2-tour-search" class="v2-input v2-input--search" placeholder="Rechercher un voyage…" autocomplete="off">
                            </div>
                            <select class="v2-input" required id="v2-select-tour" size="6">
                                <option value="" disabled <?php echo e(!$preselectedTourId ? 'selected' : ''); ?>>Sélectionner un voyage…</option>
                                <?php $__currentLoopData = $voyages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $voyage): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php
                                        $label = $voyage->wp_post_id && $wpTitles->has($voyage->wp_post_id)
                                            ? ($wpTitles->get($voyage->wp_post_id)->post_title ?? $voyage->name ?? $voyage->slug)
                                            : ($voyage->name ?? $voyage->slug ?? 'Voyage #' . $voyage->id);
                                    ?>
                                    <option value="<?php echo e($voyage->id); ?>" data-price-from="<?php echo e((float) ($voyage->price_from ?? 0)); ?>" <?php echo e($preselectedTourId === (int) $voyage->id ? 'selected' : ''); ?>>
                                        <?php echo e($label); ?>

                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <input type="hidden" name="tour_id" id="v2-tour-id-hidden" value="<?php echo e(old('tour_id', $preselectedTourId)); ?>">
                        </div>

                        <div class="v2-field v2-field--full" id="v2-departure-block" hidden>
                            <label class="v2-label">Départ disponible <span class="v2-required">*</span></label>
                            <div id="v2-departure-list" class="v2-departure-list">
                                <p class="v2-placeholder">Sélectionnez d’abord un voyage pour voir les départs.</p>
                            </div>
                            <input type="hidden" name="departure_id" id="v2-departure-id-hidden" value="<?php echo e(old('departure_id')); ?>">
                            <input type="hidden" name="travel_date_id" id="v2-travel-date-id-hidden" value="<?php echo e(old('travel_date_id', $travelDateId)); ?>">
                        </div>

                        
                        <div id="v2-departure-summary" class="v2-departure-summary" hidden>
                            <div class="v2-departure-summary__grid">
                                <div class="v2-departure-summary__item">
                                    <span class="v2-departure-summary__label">Départ</span>
                                    <strong class="v2-departure-summary__value" id="v2-summary-dates">—</strong>
                                </div>
                                <div class="v2-departure-summary__item">
                                    <span class="v2-departure-summary__label">Places restantes</span>
                                    <strong class="v2-departure-summary__value" id="v2-summary-capacity">—</strong>
                                </div>
                                <div class="v2-departure-summary__item">
                                    <span class="v2-departure-summary__label">Prix unitaire</span>
                                    <strong class="v2-departure-summary__value" id="v2-summary-unit-price">—</strong>
                                </div>
                                <div class="v2-departure-summary__item">
                                    <span class="v2-departure-summary__label">Statut</span>
                                    <strong class="v2-departure-summary__value" id="v2-summary-status">—</strong>
                                </div>
                            </div>
                        </div>

                        <input type="hidden" name="base_price" id="v2-base-price-hidden" value="<?php echo e(old('base_price', $selectedUnitPrice !== null ? number_format((float) $selectedUnitPrice, 2, '.', '') : '')); ?>">
                    </div>

                    <div class="v2-actions">
                        <span></span>
                        <button type="button" class="v2-btn v2-btn--primary" data-v2-next="2">Continuer <i class="bx bx-right-arrow-alt"></i></button>
                    </div>
                </section>

                
                <section class="v2-panel" data-v2-step="2" hidden>
                    <div class="v2-card">
                        <div class="v2-card__head">
                            <div>
                                <p class="v2-eyebrow">Étape 2 — Voyageurs</p>
                                <h2 class="v2-card__title">Client principal & accompagnants</h2>
                            </div>
                            <div class="v2-badge" id="v2-travelers-badge">1 voyageur</div>
                        </div>

                        <div class="v2-segmented">
                            <label class="v2-segmented__item">
                                <input type="radio" name="client_mode" value="new" id="v2-client-mode-new" <?php echo e(old('client_mode', 'new') === 'new' ? 'checked' : ''); ?>>
                                <span>Nouveau client</span>
                            </label>
                            <label class="v2-segmented__item">
                                <input type="radio" name="client_mode" value="existing" id="v2-client-mode-existing" <?php echo e(old('client_mode') === 'existing' ? 'checked' : ''); ?>>
                                <span>Client existant</span>
                            </label>
                        </div>

                        
                        <div id="v2-existing-client-block" <?php echo e(old('client_mode') !== 'existing' ? 'hidden' : ''); ?>>
                            <div class="v2-field v2-field--full" style="position:relative;z-index:20;">
                                <label class="v2-label" for="v2-client-search">Rechercher un client <span class="v2-required">*</span></label>
                                <input type="hidden" name="client_external_id" id="v2-client-external-id" value="">
                                <input type="search" id="v2-client-search" class="v2-input" placeholder="Nom, téléphone, email ou CIN…" autocomplete="off">
                                <div id="v2-client-search-results" class="v2-search-results" hidden></div>
                                <div id="v2-client-search-selected" class="v2-search-selected" hidden>
                                    <span id="v2-client-search-selected-label"></span>
                                    <button type="button" class="v2-search-selected__clear" id="v2-client-search-clear">&times;</button>
                                </div>
                                <a href="<?php echo e(route('admin.customers.clients.create')); ?>" target="_blank" rel="noopener" class="v2-link">Créer un nouveau client</a>
                            </div>
                        </div>

                        
                        <div id="v2-new-client-block" <?php echo e(old('client_mode') === 'existing' ? 'hidden' : ''); ?>>
                            <div class="v2-grid v2-grid--2">
                                <div class="v2-field">
                                    <label class="v2-label" for="v2-client-first-name">Prénom <span class="v2-required">*</span></label>
                                    <input type="text" name="client_first_name" id="v2-client-first-name" class="v2-input" value="<?php echo e(old('client_first_name')); ?>" autocomplete="given-name">
                                </div>
                                <div class="v2-field">
                                    <label class="v2-label" for="v2-client-last-name">Nom <span class="v2-required">*</span></label>
                                    <input type="text" name="client_last_name" id="v2-client-last-name" class="v2-input" value="<?php echo e(old('client_last_name')); ?>" autocomplete="family-name">
                                </div>
                                <div class="v2-field">
                                    <label class="v2-label" for="v2-client-phone">Téléphone <span class="v2-required">*</span></label>
                                    <input type="text" name="client_phone" id="v2-client-phone" class="v2-input" value="<?php echo e(old('client_phone')); ?>" autocomplete="tel">
                                </div>
                                <div class="v2-field">
                                    <label class="v2-label" for="v2-client-email">Email</label>
                                    <input type="email" name="client_email" id="v2-client-email" class="v2-input" value="<?php echo e(old('client_email')); ?>" autocomplete="email">
                                </div>
                                <div class="v2-field">
                                    <label class="v2-label" for="v2-client-document-type">Type de document</label>
                                    <select name="client_document_type" id="v2-client-document-type" class="v2-input">
                                        <option value="">Sélectionner…</option>
                                        <option value="cin" <?php echo e(old('client_document_type') === 'cin' ? 'selected' : ''); ?>>CIN</option>
                                        <option value="passport" <?php echo e(old('client_document_type') === 'passport' ? 'selected' : ''); ?>>Passeport</option>
                                    </select>
                                </div>
                                <div class="v2-field">
                                    <label class="v2-label" for="v2-client-document-number">Numéro document</label>
                                    <input type="text" name="client_document_number" id="v2-client-document-number" class="v2-input" value="<?php echo e(old('client_document_number')); ?>">
                                </div>
                                <div class="v2-field">
                                    <label class="v2-label" for="v2-client-nationality">Nationalité</label>
                                    <input type="text" name="client_nationality" id="v2-client-nationality" class="v2-input" value="<?php echo e(old('client_nationality')); ?>">
                                </div>
                                <div class="v2-field">
                                    <label class="v2-label" for="v2-client-gender">Sexe</label>
                                    <select name="client_gender" id="v2-client-gender" class="v2-input">
                                        <option value="">Sélectionner…</option>
                                        <option value="male" <?php echo e(old('client_gender') === 'male' ? 'selected' : ''); ?>>Homme</option>
                                        <option value="female" <?php echo e(old('client_gender') === 'female' ? 'selected' : ''); ?>>Femme</option>
                                    </select>
                                </div>
                                <div class="v2-field">
                                    <label class="v2-label" for="v2-client-birth-date">Date naissance</label>
                                    <input type="date" name="client_birth_date" id="v2-client-birth-date" class="v2-input" value="<?php echo e(old('client_birth_date')); ?>">
                                </div>
                                <div class="v2-field">
                                    <label class="v2-label" for="v2-client-traveler-type">Type voyageur</label>
                                    <select name="client_traveler_type" id="v2-client-traveler-type" class="v2-input">
                                        <option value="adult" <?php echo e(old('client_traveler_type', 'adult') === 'adult' ? 'selected' : ''); ?>>Adulte</option>
                                        <option value="child" <?php echo e(old('client_traveler_type') === 'child' ? 'selected' : ''); ?>>Enfant</option>
                                        <option value="infant" <?php echo e(old('client_traveler_type') === 'infant' ? 'selected' : ''); ?>>Bébé</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="v2-divider"></div>

                        <div class="v2-toolbar">
                            <h3 class="v2-mini-title">Accompagnants</h3>
                            <button type="button" class="v2-btn v2-btn--ghost v2-btn--sm" id="v2-btn-add-companion"><i class="bx bx-plus"></i> Ajouter</button>
                        </div>

                        <div id="v2-companions-container" class="v2-companions"></div>
                        <p id="v2-no-companions" class="v2-empty">Aucun accompagnant pour le moment.</p>
                    </div>

                    <div class="v2-actions">
                        <button type="button" class="v2-btn v2-btn--secondary" data-v2-prev="1"><i class="bx bx-left-arrow-alt"></i> Précédent</button>
                        <button type="button" class="v2-btn v2-btn--primary" data-v2-next="3">Continuer <i class="bx bx-right-arrow-alt"></i></button>
                    </div>
                </section>

                
                <section class="v2-panel" data-v2-step="3" hidden>
                    <div class="v2-card">
                        <div class="v2-card__head">
                            <div>
                                <p class="v2-eyebrow">Étape 3 — Hébergement & options</p>
                                <h2 class="v2-card__title">Chambres, répartition & extras</h2>
                            </div>
                            <span class="v2-pill" id="v2-rooming-pill">Chambres en attente</span>
                        </div>

                        <div class="v2-rooming-layout">
                            <div class="v2-rooming-col">
                                <h3 class="v2-mini-title">Voyageurs à affecter</h3>
                                <div id="v2-rooming-unassigned" class="v2-traveler-pool"></div>
                            </div>
                            <div class="v2-rooming-col">
                                <h3 class="v2-mini-title">Chambres disponibles</h3>
                                <div id="v2-rooming-available" class="v2-available-rooms">
                                    <p class="v2-placeholder">Sélectionnez un départ à l’étape 1.</p>
                                </div>
                            </div>
                        </div>

                        <div class="v2-rooming-actions">
                            <button type="button" class="v2-btn v2-btn--primary v2-btn--sm" id="v2-btn-auto-rooming">Répartition auto</button>
                            <button type="button" class="v2-btn v2-btn--ghost v2-btn--sm" id="v2-btn-add-room">Ajouter chambre</button>
                            <button type="button" class="v2-btn v2-btn--secondary v2-btn--sm" id="v2-btn-reset-rooming">Réinitialiser</button>
                        </div>

                        <div id="v2-rooming-board" class="v2-rooming-board"></div>

                        <div class="v2-divider"></div>

                        <h3 class="v2-mini-title">Extras optionnels</h3>
                        <div id="v2-extras-container" class="v2-extras-grid"></div>
                        <div id="v2-extras-empty" class="v2-placeholder" hidden>
                            <strong>Aucun extra configuré</strong>
                            <p>Ce voyage ne contient pas encore d’extras actifs.</p>
                        </div>

                        <div class="v2-divider"></div>

                        <div class="v2-pricing-preview">
                            <div class="v2-pricing-preview__row">
                                <span>Sous-total base</span>
                                <strong id="v2-preview-base">0 DH</strong>
                            </div>
                            <div class="v2-pricing-preview__row">
                                <span>Suppléments chambre</span>
                                <strong id="v2-preview-room-supp">0 DH</strong>
                            </div>
                            <div class="v2-pricing-preview__row">
                                <span>Extras</span>
                                <strong id="v2-preview-extras">0 DH</strong>
                            </div>
                            <div class="v2-pricing-preview__row v2-pricing-preview__row--total">
                                <span>Total avant remise</span>
                                <strong id="v2-preview-subtotal">0 DH</strong>
                            </div>
                        </div>
                    </div>

                    <div class="v2-actions">
                        <button type="button" class="v2-btn v2-btn--secondary" data-v2-prev="2"><i class="bx bx-left-arrow-alt"></i> Précédent</button>
                        <button type="button" class="v2-btn v2-btn--primary" data-v2-next="4">Continuer <i class="bx bx-right-arrow-alt"></i></button>
                    </div>
                </section>

                
                <section class="v2-panel" data-v2-step="4" hidden>
                    <div class="v2-card">
                        <div class="v2-card__head">
                            <div>
                                <p class="v2-eyebrow">Étape 4 — Paiement & validation</p>
                                <h2 class="v2-card__title">Finalisation du dossier</h2>
                            </div>
                        </div>

                        <div class="v2-grid v2-grid--2">
                            <div class="v2-field">
                                <label class="v2-label" for="v2-discount-type-select">Type de remise</label>
                                <select name="discount_type" id="v2-discount-type-select" class="v2-input">
                                    <option value="">Aucune</option>
                                    <option value="fixed" <?php echo e(old('discount_type') === 'fixed' ? 'selected' : ''); ?>>Montant fixe</option>
                                    <option value="percentage" <?php echo e(old('discount_type') === 'percentage' ? 'selected' : ''); ?>>Pourcentage</option>
                                </select>
                            </div>
                            <div class="v2-field">
                                <label class="v2-label" for="v2-discount-input">Valeur remise</label>
                                <input type="number" name="discount_value" id="v2-discount-input" class="v2-input" value="<?php echo e(old('discount_value', 0)); ?>" min="0" step="0.01">
                            </div>
                        </div>

                        <div class="v2-divider"></div>

                        <div class="v2-grid v2-grid--2">
                            <div class="v2-field">
                                <label class="v2-label" for="v2-payment-date">Date paiement</label>
                                <input type="date" name="payment_date" id="v2-payment-date" class="v2-input" value="<?php echo e(old('payment_date', now()->toDateString())); ?>">
                            </div>
                            <div class="v2-field">
                                <label class="v2-label" for="v2-payment-type">Mode de paiement</label>
                                <select name="payment_type" id="v2-payment-type" class="v2-input">
                                    <option value="">Sélectionner…</option>
                                    <option value="Espèces" <?php echo e(old('payment_type') === 'Espèces' ? 'selected' : ''); ?>>Espèces</option>
                                    <option value="Virement bancaire" <?php echo e(old('payment_type') === 'Virement bancaire' ? 'selected' : ''); ?>>Virement bancaire</option>
                                    <option value="Carte bancaire" <?php echo e(old('payment_type') === 'Carte bancaire' ? 'selected' : ''); ?>>Carte bancaire</option>
                                    <option value="Chèque" <?php echo e(old('payment_type') === 'Chèque' ? 'selected' : ''); ?>>Chèque</option>
                                    <option value="TPE" <?php echo e(old('payment_type') === 'TPE' ? 'selected' : ''); ?>>TPE</option>
                                    <option value="Autre" <?php echo e(old('payment_type') === 'Autre' ? 'selected' : ''); ?>>Autre</option>
                                </select>
                            </div>
                            <div class="v2-field">
                                <label class="v2-label" for="v2-payment-amount">Montant payé</label>
                                <input type="number" name="payment_amount" id="v2-payment-amount" class="v2-input" value="<?php echo e(old('payment_amount', 0)); ?>" min="0" step="0.01">
                            </div>
                            <div class="v2-field">
                                <label class="v2-label" for="v2-payment-reference">Référence</label>
                                <input type="text" name="payment_reference" id="v2-payment-reference" class="v2-input" value="<?php echo e(old('payment_reference')); ?>" placeholder="Reçu, transaction…">
                            </div>
                            <div class="v2-field v2-field--full">
                                <label class="v2-label" for="v2-payment-note">Note interne</label>
                                <textarea name="payment_note" id="v2-payment-note" class="v2-input v2-input--textarea" rows="3" placeholder="Détail du règlement, échéance…"><?php echo e(old('payment_note')); ?></textarea>
                            </div>
                            <div class="v2-field v2-field--full">
                                <label class="v2-label" for="v2-payment-receipt">Justificatif</label>
                                <input type="file" name="payment_receipt" id="v2-payment-receipt" class="v2-input v2-input--file" accept="image/*,.pdf">
                            </div>
                        </div>

                        <div class="v2-divider"></div>

                        <div class="v2-field v2-field--full">
                            <label class="v2-label" for="v2-dossier-documents">Documents du dossier</label>
                            <input type="file" name="dossier_documents[]" id="v2-dossier-documents" class="v2-input v2-input--file" accept="image/*,.pdf" multiple>
                        </div>

                        <div class="v2-toggle-row">
                            <input type="hidden" name="visa_ok" value="0">
                            <label class="v2-toggle">
                                <input type="checkbox" name="visa_ok" id="v2-visa-ok" value="1" <?php echo e(old('visa_ok', true) ? 'checked' : ''); ?>>
                                <span class="v2-toggle__slider"></span>
                                <span>Visa OK, pas d'assistance nécessaire</span>
                            </label>
                        </div>

                        <div id="v2-visa-block" class="v2-visa-block" <?php echo e(old('visa_ok', true) ? 'hidden' : ''); ?>>
                            <div class="v2-grid v2-grid--2">
                                <div class="v2-field">
                                    <label class="v2-label" for="v2-visa-status">Statut visa</label>
                                    <select name="visa_status" id="v2-visa-status" class="v2-input">
                                        <option value="">Sélectionner…</option>
                                        <option value="not_required" <?php echo e(old('visa_status') === 'not_required' ? 'selected' : ''); ?>>Non requis</option>
                                        <option value="pending" <?php echo e(old('visa_status') === 'pending' ? 'selected' : ''); ?>>En attente</option>
                                        <option value="approved" <?php echo e(old('visa_status') === 'approved' ? 'selected' : ''); ?>>Approuvé</option>
                                        <option value="rejected" <?php echo e(old('visa_status') === 'rejected' ? 'selected' : ''); ?>>Refusé</option>
                                    </select>
                                </div>
                                <div class="v2-field">
                                    <label class="v2-label" for="v2-visa-document">Document visa</label>
                                    <input type="file" name="visa_document" id="v2-visa-document" class="v2-input v2-input--file" accept="image/*,.pdf">
                                </div>
                                <div class="v2-field v2-field--full">
                                    <label class="v2-label" for="v2-visa-notes">Notes visa</label>
                                    <textarea name="visa_notes" id="v2-visa-notes" class="v2-input v2-input--textarea" rows="3" placeholder="Suivi visa, pièces manquantes…"><?php echo e(old('visa_notes')); ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="v2-actions v2-actions--final">
                        <button type="button" class="v2-btn v2-btn--secondary" data-v2-prev="3"><i class="bx bx-left-arrow-alt"></i> Précédent</button>
                        <div class="v2-actions__group">
                            <a href="<?php echo e(route('admin.reservations.workspace')); ?>" class="v2-btn v2-btn--ghost">Annuler</a>
                            <button type="submit" class="v2-btn v2-btn--primary v2-btn--lg">
                                <span>Confirmer la réservation</span>
                                <i class="bx bx-check"></i>
                            </button>
                        </div>
                    </div>
                </section>
            </main>

            
            <aside class="v2-sidebar">
                <div class="v2-sidebar__card">
                    <h3 class="v2-sidebar__title">Résumé dossier</h3>
                    <div class="v2-sidebar__progress">
                        <div class="v2-sidebar__progress-bar" id="v2-progress-bar" style="width: 25%"></div>
                    </div>
                    <p class="v2-sidebar__progress-label" id="v2-progress-label">Étape 1 sur 4</p>

                    <div class="v2-sidebar__section">
                        <div class="v2-sidebar__item">
                            <span>Prestation</span>
                            <strong id="v2-sidebar-trip">Aucune sélection</strong>
                        </div>
                        <div class="v2-sidebar__item">
                            <span>Départ</span>
                            <strong id="v2-sidebar-departure">—</strong>
                        </div>
                        <div class="v2-sidebar__item">
                            <span>Voyageurs</span>
                            <strong id="v2-sidebar-travelers">1</strong>
                        </div>
                        <div class="v2-sidebar__item">
                            <span>Chambres</span>
                            <strong id="v2-sidebar-rooms">—</strong>
                        </div>
                    </div>

                    <div class="v2-sidebar__divider"></div>

                    <div class="v2-sidebar__section">
                        <div class="v2-sidebar__item">
                            <span>Prix unitaire</span>
                            <strong id="v2-sidebar-unit-price">—</strong>
                        </div>
                        <div class="v2-sidebar__item">
                            <span>Réduction</span>
                            <strong id="v2-sidebar-discount">Aucune</strong>
                        </div>
                        <div class="v2-sidebar__item">
                            <span>Extras</span>
                            <strong id="v2-sidebar-extras">0 DH</strong>
                        </div>
                    </div>

                    <div class="v2-sidebar__divider"></div>

                    <div class="v2-sidebar__section v2-sidebar__section--total">
                        <div class="v2-sidebar__item v2-sidebar__item--total">
                            <span>Total provisoire</span>
                            <strong id="v2-sidebar-total">—</strong>
                        </div>
                        <div class="v2-sidebar__item">
                            <span>Payé</span>
                            <strong id="v2-sidebar-paid">0 DH</strong>
                        </div>
                        <div class="v2-sidebar__item">
                            <span>Reste</span>
                            <strong id="v2-sidebar-remaining">0 DH</strong>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </form>

    <script type="application/json" id="v2-extras-map"><?php echo json_encode($extrasByVoyage ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE); ?></script>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
    <script src="<?php echo e(asset('js/reservation-create-v2.js') . '?v=' . @filemtime(public_path('js/reservation-create-v2.js'))); ?>"></script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.admin-v6', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\reservations\create-v2.blade.php ENDPATH**/ ?>