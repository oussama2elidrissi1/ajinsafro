<div class="tab-pane active show" id="basic" role="tabpanel">
                <div class="row g-4 ve-basic-layout">
                    <div class="col-lg-8">
                        <div class="card ve-pane-card mb-0">
                            <div class="card-body">
                        <h4 class="card-title mb-2">Informations principales</h4>
                        <p class="text-muted small mb-4">Publication et capacitÃ©s : voir la colonne de droite.</p>
                        <div class="mb-4">
                            <label for="title" class="form-label">Titre du tour <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-lg" id="title" name="title" value="{{ old('title', $voyage->post_title) }}" required>
                        </div>
                        <div class="mb-4">
                            <label for="slug" class="form-label">Slug (URL)</label>
                            <input type="text" class="form-control" id="slug" name="slug" value="{{ old('slug', $voyage->post_name) }}">
                        </div>
                        <div class="mb-4 ve-rich-field">
                            <label for="content" class="form-label">Description complÃ¨te</label>
                            <textarea class="form-control rich-editor" id="content" name="content" rows="10">{{ old('content', $voyage->post_content) }}</textarea>
                        </div>
                        <div class="mb-0 ve-rich-field">
                            <label for="excerpt" class="form-label">Extrait / Accroche</label>
                            <textarea class="form-control rich-editor" id="excerpt" name="excerpt" rows="3">{{ old('excerpt', $voyage->post_excerpt) }}</textarea>
                        </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="ve-basic-sidebar">
                            <div class="card ve-pane-card ve-basic-side-card mb-0">
                                <div class="card-body">
                                    <h5 class="ve-sidebar-title mb-3 fw-bold"><i class="bx bx-pulse text-primary"></i> Resume</h5>
                                    <ul class="list-unstyled small mb-0 ve-summary-list">
                                        <li class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted">WordPress</span><span class="fw-semibold font-monospace">#{{ $veWpId ?: '-' }}</span></li>
                                        @if($laravelV && (int) data_get($laravelV, 'id', 0) > 0)<li class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted">Laravel</span><span class="fw-semibold font-monospace">#{{ data_get($laravelV, 'id') }}</span></li>@endif
                                        <li class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted">Dates depart</span><span class="fw-semibold">{{ $veDatesCount }}</span></li>
                                        @if($vePriceLabel)<li class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted">Prix</span><span class="fw-semibold text-truncate ms-1" style="max-width:55%">{{ $vePriceLabel }}</span></li>@endif
                                        @if($veDestination)<li class="d-flex justify-content-between py-2"><span class="text-muted">Destination</span><span class="fw-semibold text-end small" style="max-width:55%">{{ Str::limit($veDestination, 40) }}</span></li>@endif
                                    </ul>
                                </div>
                            </div>
                            <div class="card ve-pane-card ve-basic-side-card mb-0">
                                <div class="card-body">
                                    <h5 class="ve-sidebar-title mb-3 fw-bold"><i class="bx bx-cog text-primary"></i> Parametres generaux</h5>
                                    <div class="mb-3">
                                        <label for="post_status" class="form-label">Statut</label>
                                        <select class="form-select" id="post_status" name="post_status">
                                            <option value="publish" {{ old('post_status', $voyage->post_status) === 'publish' ? 'selected' : '' }}>Publie</option>
                                            <option value="draft" {{ old('post_status', $voyage->post_status) === 'draft' ? 'selected' : '' }}>Brouillon</option>
                                            <option value="pending" {{ old('post_status', $voyage->post_status) === 'pending' ? 'selected' : '' }}>En attente</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="duration_day" class="form-label">Duree (jours)</label>
                                        <input type="number" class="form-control" id="duration_day" name="duration_day" value="{{ old('duration_day', $meta['duration_day'] ?? '') }}" min="1" readonly>
                                    </div>
                                    <div class="mb-3">
                                        <label for="min_people" class="form-label">Min. personnes</label>
                                        <input type="number" class="form-control" id="min_people" name="min_people" value="{{ old('min_people', $meta['min_people'] ?? '') }}" min="1">
                                    </div>
                                    <div class="mb-3">
                                        <label for="max_people" class="form-label">Max. personnes</label>
                                        <input type="number" class="form-control bg-light" id="max_people" name="max_people" value="{{ old('max_people', $totalPlacesVoyage ?? $meta['max_people'] ?? 0) }}" min="0" readonly>
                                        <small class="text-muted">Via chambres (Hotels)</small>
                                    </div>
                                    <div class="mb-3">
                                        <label for="places_display" class="form-label">Places</label>
                                        <input type="number" class="form-control bg-light" id="places_display" value="{{ old('max_people', $totalPlacesVoyage ?? $meta['places'] ?? $meta['max_people'] ?? 0) }}" min="0" readonly>
                                    </div>
                                    <div class="mb-3">
                                        <label for="tour_price_by" class="form-label">Tarification par</label>
                                        <select class="form-select" id="tour_price_by" name="tour_price_by">
                                            <option value="">-- Selectionner --</option>
                                            <option value="person" {{ old('tour_price_by', $meta['tour_price_by'] ?? '') === 'person' ? 'selected' : '' }}>Par personne</option>
                                            <option value="group" {{ old('tour_price_by', $meta['tour_price_by'] ?? '') === 'group' ? 'selected' : '' }}>Par groupe</option>
                                            <option value="fixed" {{ old('tour_price_by', $meta['tour_price_by'] ?? '') === 'fixed' ? 'selected' : '' }}>Prix fixe</option>
                                        </select>
                                    </div>
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" value="1" {{ old('is_featured', $meta['is_featured'] ?? '') === 'on' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_featured">Tour a la une</label>
                                    </div>
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="hide_adult_in_booking_form" name="hide_adult_in_booking_form" value="1" {{ old('hide_adult_in_booking_form', $meta['hide_adult_in_booking_form'] ?? '') === 'on' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="hide_adult_in_booking_form">Masquer champ adulte</label>
                                    </div>
                                    <div class="mb-0">
                                        <label for="st_tour_external_booking" class="form-label">Lien reservation externe</label>
                                        <input type="text" class="form-control" id="st_tour_external_booking" name="st_tour_external_booking" value="{{ old('st_tour_external_booking', $meta['st_tour_external_booking'] ?? '') }}" placeholder="https://...">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

