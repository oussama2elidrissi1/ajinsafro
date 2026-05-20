
<div class="modal fade" id="itemModal" tabindex="-1" aria-labelledby="itemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="itemForm" method="POST" action="">
                <?php echo csrf_field(); ?>
                <div class="modal-header">
                    <h5 class="modal-title" id="itemModalLabel">Ajouter un item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="item_day_number" class="form-label">Jour principal <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="item_day_number" name="day_number" min="1" required>
                            <small class="text-muted">Jour d'affichage de l'item</small>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="item_type" class="form-label">Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="item_type" name="type" required>
                                <option value="">-- Sélectionner --</option>
                                <option value="flight">Vol</option>
                                <option value="hotel_stay">Hébergement</option>
                                <option value="transfer">Transfert</option>
                                <option value="activity">Activité</option>
                                <option value="meal">Repas</option>
                                <option value="addon">Option supplémentaire</option>
                            </select>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="item_sort_order" class="form-label">Ordre d'affichage</label>
                            <input type="number" class="form-control" id="item_sort_order" name="sort_order" min="0" value="0">
                        </div>

                        <div class="col-12 mb-3">
                            <label for="item_title" class="form-label">Titre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="item_title" name="title" required placeholder="Ex : Vol Emirates Dubai - Paris">
                        </div>

                        <div class="col-12 mb-3">
                            <label for="item_details" class="form-label">Détails</label>
                            <textarea class="form-control" id="item_details" name="details" rows="3" placeholder="Description détaillée de l'item"></textarea>
                        </div>

                        
                        <div id="multiDayFields" style="display: none;">
                            <div class="col-12 mb-3">
                                <div class="alert alert-info">
                                    <i class="bx bx-info-circle"></i> Pour un hébergement multi-jours, spécifiez le jour de début, fin et le nombre de nuits.
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="item_start_day" class="form-label">Jour de début</label>
                                    <input type="number" class="form-control" id="item_start_day" name="start_day" min="1">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="item_end_day" class="form-label">Jour de fin</label>
                                    <input type="number" class="form-control" id="item_end_day" name="end_day" min="1">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="item_nights" class="form-label">Nombre de nuits</label>
                                    <input type="number" class="form-control" id="item_nights" name="nights" min="0" value="0">
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="item_included" name="included" value="1" checked>
                                <label class="form-check-label" for="item_included">
                                    Inclus dans le package
                                </label>
                            </div>
                            <small class="text-muted">Si décoché, sera considéré comme optionnel</small>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="item_price_delta" class="form-label">Prix delta par personne</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="item_price_delta" name="price_delta_per_person" 
                                    step="0.01" value="0.00" placeholder="0.00">
                                <span class="input-group-text"><?php echo e($voyage->currency ?? 'MAD'); ?></span>
                            </div>
                            <small class="text-muted">0 si inclus. Positif pour supplément, négatif pour réduction.</small>
                        </div>

                        <div class="col-12 mb-3">
                            <label class="form-label">Options alternatives (JSON) <small class="text-muted">- Optionnel</small></label>
                            <textarea class="form-control font-monospace" name="options_json" rows="3" placeholder='{"option1": {"title": "...", "price_delta": 0}}'></textarea>
                            <small class="text-muted">Format JSON pour les alternatives (MODIFY action)</small>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Metadata (JSON) <small class="text-muted">- Optionnel</small></label>
                            <textarea class="form-control font-monospace" name="meta_json" rows="2" placeholder='{"supplier_id": 123, "time": "14:30"}'></textarea>
                            <small class="text-muted">Données supplémentaires (fournisseur, horaire, etc.)</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\circuits\voyages\partials\_item_modal.blade.php ENDPATH**/ ?>