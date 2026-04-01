<div class="tab-pane" id="taxonomies" role="tabpanel">
                <div class="card ve-pane-card">
                    <div class="card-body">
                        <h4 class="card-title mb-4">CatÃ©gories & Taxonomies</h4>
                        <p class="text-muted small">GÃ©rez les catÃ©gories (Type de tour, DurÃ©e, Langue). Les cases Ã  cocher assignent les catÃ©gories au voyage.</p>
                        @include('admin.circuits.voyages.partials._taxonomies_crud', [
                            'availableTaxonomies' => $availableTaxonomies ?? [],
                            'assignedTaxonomies' => $assignedTaxonomies ?? [],
                        ])

                    </div>
                </div>

            </div>

