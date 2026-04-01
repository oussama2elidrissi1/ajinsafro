<div class="tab-pane" id="voyage-extras" role="tabpanel">
                <div class="card ve-pane-card">
                    <div class="card-body">
                        <h4 class="card-title mb-2">Extras rÃ©servation</h4>
                        <p class="text-muted small mb-3">Options affichÃ©es dans le formulaire Â« Nouvelle rÃ©servation Â» (workspace) pour ce voyage. Cochables par passager, prix adulte / enfant.</p>
                        @include('admin.circuits.voyages.partials._voyage_extras', ['voyageExtras' => $voyageExtras ?? collect()])
                    </div>
                </div>
            </div>

            {{-- TAB 5: AVAILABILITY --}}

