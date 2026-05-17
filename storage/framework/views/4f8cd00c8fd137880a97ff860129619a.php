<div class="tab-pane" id="information" role="tabpanel" data-ve-pane-title="Détails">
                <div class="card ve-pane-card">
                    <div class="card-body">
                        <h4 class="card-title mb-2">Contenu du tour</h4>
                        <p class="text-muted small mb-4">Blocs affichÃ©s sur la fiche publique.</p>
                        <div class="row g-4">
                            <div class="col-12 col-xl-6 ve-rich-field mb-0">
                                <label for="tours_include" class="form-label">Ce qui est inclus</label>
                                <textarea class="form-control rich-editor" id="tours_include" name="tours_include" rows="6"><?php echo e(old('tours_include', $meta['tours_include'] ?? '')); ?></textarea>
                            </div>
                            <div class="col-12 col-xl-6 ve-rich-field mb-0">
                                <label for="tours_exclude" class="form-label">Ce qui n'est pas inclus</label>
                                <textarea class="form-control rich-editor" id="tours_exclude" name="tours_exclude" rows="6"><?php echo e(old('tours_exclude', $meta['tours_exclude'] ?? '')); ?></textarea>
                            </div>
                            <div class="col-12 col-xl-6 ve-rich-field mb-0">
                                <label for="tours_highlight" class="form-label">Points forts</label>
                                <textarea class="form-control rich-editor" id="tours_highlight" name="tours_highlight" rows="6"><?php echo e(old('tours_highlight', $meta['tours_highlight'] ?? '')); ?></textarea>
                            </div>
                            <div class="col-12 col-xl-6 ve-rich-field mb-0">
                                <label for="tours_faq" class="form-label">FAQ</label>
                                <textarea class="form-control rich-editor" id="tours_faq" name="tours_faq" rows="6"><?php echo e(old('tours_faq', $meta['tours_faq'] ?? '')); ?></textarea>
                            </div>
                            <div class="col-12">
                                <label for="tours_program_style" class="form-label">Style du programme (front)</label>
                                <select class="form-select" id="tours_program_style" name="tours_program_style" style="max-width:22rem;">
                                    <option value="">DÃ©faut</option>
                                    <option value="tab" <?php echo e(old('tours_program_style', $meta['tours_program_style'] ?? '') === 'tab' ? 'selected' : ''); ?>>Onglets</option>
                                    <option value="accordion" <?php echo e(old('tours_program_style', $meta['tours_program_style'] ?? '') === 'accordion' ? 'selected' : ''); ?>>AccordÃ©on</option>
                                    <option value="list" <?php echo e(old('tours_program_style', $meta['tours_program_style'] ?? '') === 'list' ? 'selected' : ''); ?>>Liste</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            

<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\circuits\voyages\partials\tabs\_information.blade.php ENDPATH**/ ?>