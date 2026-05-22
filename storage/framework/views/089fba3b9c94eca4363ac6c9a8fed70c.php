<div class="tab-pane active show" id="basic" role="tabpanel" data-ve-pane-title="Fiche">
    <div class="row g-4 ve-basic-layout">
        <div class="col-lg-8">
            <div class="card ve-pane-card mb-0">
                <div class="card-body">
                    <p class="ve-section-kicker mb-2">Informations principales</p>
                    <h4 class="card-title mb-4">Fiche commerciale</h4>

                    <div class="mb-4">
                        <label for="title" class="form-label">Titre du voyage <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-lg" id="title" name="title" value="<?php echo e(old('title', $voyage->post_title)); ?>" required>
                    </div>

                    <div class="mb-4">
                        <label for="slug" class="form-label">URL du voyage</label>
                        <input type="text" class="form-control" id="slug" name="slug" value="<?php echo e(old('slug', $voyage->post_name)); ?>">
                        <div class="v3-slug-preview">
                            <span class="v3-slug-preview__text" data-v3-slug-preview><?php echo e((rtrim(route('front.voyages.index'), '/') . '/' . ltrim((string) old('slug', $voyage->post_name), '/'))); ?></span>
                            <button type="button" class="v3-slug-preview__copy" data-v3-copy-slug>Copier</button>
                        </div>
                    </div>

                    <div class="mb-4 ve-rich-field">
                        <div class="d-flex align-items-center justify-content-between gap-3 mb-2">
                            <label for="excerpt" class="form-label mb-0">Accroche courte</label>
                            <span class="v3-field-counter" data-v3-counter-for="excerpt"><?php echo e(\Illuminate\Support\Str::length(trim(strip_tags((string) old('excerpt', $voyage->post_excerpt))))); ?> / 160</span>
                        </div>
                        <textarea class="form-control" id="excerpt" name="excerpt" rows="4"><?php echo e(old('excerpt', $voyage->post_excerpt)); ?></textarea>
                    </div>

                    <div class="mb-0 ve-rich-field">
                        <label for="content" class="form-label">Presentation detaillee</label>
                        <textarea class="form-control rich-editor" id="content" name="content" rows="10"><?php echo e(old('content', $voyage->post_content)); ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="ve-basic-sidebar">
                <div class="card ve-pane-card ve-basic-side-card mb-0">
                    <div class="card-body">
                        <h5 class="ve-sidebar-title mb-3 fw-bold"><i class="bx bx-pulse text-primary"></i> Vue rapide</h5>
                        <ul class="list-unstyled small mb-0 ve-summary-list">
                            <li class="d-flex justify-content-between py-2 border-bottom">
                                <span class="text-muted">Ref. voyage</span>
                                <span class="fw-semibold font-monospace">#<?php echo e($veWpId ?: '-'); ?></span>
                            </li>
                            <li class="d-flex justify-content-between py-2 border-bottom">
                                <span class="text-muted">Departs</span>
                                <span class="fw-semibold"><?php echo e($veDatesCount); ?></span>
                            </li>
                            <?php if($vePriceLabel): ?>
                                <li class="d-flex justify-content-between py-2 border-bottom">
                                    <span class="text-muted">Prix de base</span>
                                    <span class="fw-semibold text-truncate ms-1" style="max-width:55%"><?php echo e($vePriceLabel); ?></span>
                                </li>
                            <?php endif; ?>
                            <?php if($veDestination): ?>
                                <li class="d-flex justify-content-between py-2">
                                    <span class="text-muted">Destination</span>
                                    <span class="fw-semibold text-end small" style="max-width:55%"><?php echo e(\Illuminate\Support\Str::limit($veDestination, 40)); ?></span>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>

                <div class="card ve-pane-card ve-basic-side-card mb-0">
                    <div class="card-body">
                        <h5 class="ve-sidebar-title mb-3 fw-bold"><i class="bx bx-cog text-primary"></i> Reglages</h5>

                        <div class="mb-3">
                            <label for="post_status" class="form-label">Statut</label>
                            <select class="form-select" id="post_status" name="post_status">
                                <option value="publish" <?php echo e(old('post_status', $voyage->post_status) === 'publish' ? 'selected' : ''); ?>>Publie</option>
                                <option value="draft" <?php echo e(old('post_status', $voyage->post_status) === 'draft' ? 'selected' : ''); ?>>Brouillon</option>
                                <option value="private" <?php echo e(old('post_status', $voyage->post_status) === 'private' ? 'selected' : ''); ?>>Archive</option>
                            </select>
                        </div>

                        <input type="hidden" id="duration_day" name="duration_day" value="<?php echo e(old('duration_day', $meta['duration_day'] ?? 1)); ?>">

                        <div class="mb-3">
                            <label for="min_people" class="form-label">Min. personnes</label>
                            <input type="number" class="form-control" id="min_people" name="min_people" value="<?php echo e(old('min_people', $meta['min_people'] ?? '')); ?>" min="1">
                        </div>

                        <?php $tourPriceByOpts = \App\Services\BusinessReferentialService::tourPriceByOptions(); ?>
                        <div class="mb-3">
                            <label for="tour_price_by" class="form-label">Tarification par</label>
                            <select class="form-select" id="tour_price_by" name="tour_price_by">
                                <option value="">- Selectionner -</option>
                                <?php $__currentLoopData = $tourPriceByOpts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($opt['value']); ?>" <?php if(old('tour_price_by', $meta['tour_price_by'] ?? '') === $opt['value']): echo 'selected'; endif; ?>><?php echo e($opt['label']); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>

                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" value="1" <?php echo e(old('is_featured', $meta['is_featured'] ?? '') === 'on' ? 'checked' : ''); ?>>
                            <label class="form-check-label" for="is_featured">Mettre en avant le voyage</label>
                        </div>

                        <input type="hidden" name="is_group_deal" value="0">
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" id="is_group_deal" name="is_group_deal" value="1" <?php if((bool) old('is_group_deal', (int) (data_get($laravelV ?? null, 'is_group_deal', 0))) ): echo 'checked'; endif; ?>>
                            <label class="form-check-label" for="is_group_deal">Afficher dans Group Deals</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\circuits\voyages\partials\tabs\_basic.blade.php ENDPATH**/ ?>