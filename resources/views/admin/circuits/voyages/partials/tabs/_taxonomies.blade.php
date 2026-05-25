<div class="tab-pane" id="taxonomies" role="tabpanel" data-ve-pane-title="Classement">
                @include('admin.circuits.voyages.partials._voyage_laravel_themes')
                <div class="card ve-pane-card">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Catégories & Taxonomies</h4>
                        <p class="text-muted small">Gérez les catégories (Type de tour, Durée, Langue). Les cases à cocher assignent les catégories au voyage.</p>
                        @include('admin.circuits.voyages.partials._taxonomies_crud', [
                            'availableTaxonomies' => $availableTaxonomies ?? [],
                            'assignedTaxonomies' => $assignedTaxonomies ?? [],
                        ])

                    </div>
                </div>

            </div>


