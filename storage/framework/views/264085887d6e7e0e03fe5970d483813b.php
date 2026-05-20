<div class="tab-pane" id="location" role="tabpanel" data-ve-pane-title="Destination">
<div class="card ve-pane-card destination-ux-card">
                    <div class="card-body destination-ux-body">
                        <div class="destination-ux-header">
                            <h4 class="destination-ux-title">Destination</h4>
                            <p class="destination-ux-helper">Sélectionnez une ou plusieurs locations pour ce circuit.</p>
                            <div class="destination-ux-badge-wrap">
                                <span class="badge bg-primary destination-ux-badge" id="locationCountBadge">
                                    <span id="locationCountText"><?php echo e(count($selectedLocationIds ?? [])); ?> location(s) sélectionnée(s)</span>
                                </span>
                            </div>
                        </div>

                        
                        <div class="destination-ux-chips-section">
                            <div class="destination-ux-chips-label">Sélections actuelles</div>
                            <div class="destination-ux-chips" id="locationChipsContainer"></div>
                            <button type="button" class="btn btn-sm btn-outline-secondary destination-ux-chips-clear" id="locationChipsClear" style="display: none;">Effacer tout</button>
                        </div>

                        
                        <div id="locationTreeContainer">
                            <?php echo $__env->make('admin.circuits.voyages.partials.location-country-cities', [
                                'worldCountries' => $worldCountries ?? [],
                                'countryCitiesData' => $countryCitiesData ?? [],
                                'mergedCitiesByCode' => $mergedCitiesByCode ?? [],
                                'selectedLocationIds' => $selectedLocationIds ?? []
                            ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                        </div>
                    </div>
                </div>
                <div class="card ve-pane-card">
                    <div class="card-body">
                        <h4 class="card-title mb-3">Adresse & contact</h4>
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label for="address" class="form-label">Adresse affichée (résumé)</label>
                                <input type="text" class="form-control" id="address" name="address" value="<?php echo e(old('address', $meta['address'] ?? '')); ?>">
                            </div>
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label for="contact_email" class="form-label">E-mail de contact</label>
                                    <input type="email" class="form-control" id="contact_email" name="contact_email" value="<?php echo e(old('contact_email', $meta['contact_email'] ?? '')); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Téléphone</label>
                                    <input type="text" class="form-control" id="phone" name="phone" value="<?php echo e(old('phone', $meta['phone'] ?? '')); ?>">
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label for="fax" class="form-label">Fax</label>
                                    <input type="text" class="form-control" id="fax" name="fax" value="<?php echo e(old('fax', $meta['fax'] ?? '')); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="website" class="form-label">Site web</label>
                                    <input type="text" class="form-control" id="website" name="website" value="<?php echo e(old('website', $meta['website'] ?? '')); ?>" placeholder="https://�?�">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            




<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\circuits\voyages\partials\tabs\_location.blade.php ENDPATH**/ ?>