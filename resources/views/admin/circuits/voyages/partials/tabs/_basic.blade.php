<div class="tab-pane active show" id="basic" role="tabpanel" data-ve-pane-title="Fiche">
    <div class="row g-4 ve-basic-layout">
        <div class="col-lg-8">
            <div class="card ve-pane-card mb-0">
                <div class="card-body">
                    <p class="ve-section-kicker mb-2">Informations principales</p>
                    <h4 class="card-title mb-4">Fiche commerciale</h4>

                    <div class="mb-4">
                        <label for="title" class="form-label">Titre du voyage <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-lg" id="title" name="title" value="{{ old('title', $voyage->post_title) }}" required>
                    </div>

                    <div class="mb-4">
                        <label for="slug" class="form-label">URL du voyage</label>
                        <input type="text" class="form-control" id="slug" name="slug" value="{{ old('slug', $voyage->post_name) }}">
                    </div>

                    <div class="mb-4 ve-rich-field">
                        <label for="content" class="form-label">Présentation détaillée</label>
                        <textarea class="form-control rich-editor" id="content" name="content" rows="10">{{ old('content', $voyage->post_content) }}</textarea>
                    </div>

                    <div class="mb-0 ve-rich-field">
                        <label for="excerpt" class="form-label">Accroche courte</label>
                        <textarea class="form-control rich-editor" id="excerpt" name="excerpt" rows="3">{{ old('excerpt', $voyage->post_excerpt) }}</textarea>
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
                                <span class="text-muted">Réf. voyage</span>
                                <span class="fw-semibold font-monospace">#{{ $veWpId ?: '-' }}</span>
                            </li>
                            <li class="d-flex justify-content-between py-2 border-bottom">
                                <span class="text-muted">Départs</span>
                                <span class="fw-semibold">{{ $veDatesCount }}</span>
                            </li>
                            @if($vePriceLabel)
                                <li class="d-flex justify-content-between py-2 border-bottom">
                                    <span class="text-muted">Prix de base</span>
                                    <span class="fw-semibold text-truncate ms-1" style="max-width:55%">{{ $vePriceLabel }}</span>
                                </li>
                            @endif
                            @if($veDestination)
                                <li class="d-flex justify-content-between py-2">
                                    <span class="text-muted">Destination</span>
                                    <span class="fw-semibold text-end small" style="max-width:55%">{{ Str::limit($veDestination, 40) }}</span>
                                </li>
                            @endif
                        </ul>
                    </div>
                </div>

                <div class="card ve-pane-card ve-basic-side-card mb-0">
                    <div class="card-body">
                        <h5 class="ve-sidebar-title mb-3 fw-bold"><i class="bx bx-cog text-primary"></i> Réglages</h5>

                        <div class="mb-3">
                            <label for="post_status" class="form-label">Statut</label>
                            <select class="form-select" id="post_status" name="post_status">
                                <option value="publish" {{ old('post_status', $voyage->post_status) === 'publish' ? 'selected' : '' }}>Publié</option>
                                <option value="draft" {{ old('post_status', $voyage->post_status) === 'draft' ? 'selected' : '' }}>Brouillon</option>
                                <option value="private" {{ old('post_status', $voyage->post_status) === 'private' ? 'selected' : '' }}>Archivé</option>
                            </select>
                        </div>

                        <input type="hidden" id="duration_day" name="duration_day" value="{{ old('duration_day', $meta['duration_day'] ?? 1) }}">

                        <div class="mb-3">
                            <label for="min_people" class="form-label">Min. personnes</label>
                            <input type="number" class="form-control" id="min_people" name="min_people" value="{{ old('min_people', $meta['min_people'] ?? '') }}" min="1">
                        </div>

                        @php $tourPriceByOpts = \App\Services\BusinessReferentialService::tourPriceByOptions(); @endphp
                        <div class="mb-3">
                            <label for="tour_price_by" class="form-label">Tarification par</label>
                            <select class="form-select" id="tour_price_by" name="tour_price_by">
                                <option value="">— Sélectionner —</option>
                                @foreach($tourPriceByOpts as $opt)
                                    <option value="{{ $opt['value'] }}" @selected(old('tour_price_by', $meta['tour_price_by'] ?? '') === $opt['value'])>{{ $opt['label'] }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" value="1" {{ old('is_featured', $meta['is_featured'] ?? '') === 'on' ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_featured">Mettre en avant le voyage</label>
                        </div>

                        <input type="hidden" name="is_group_deal" value="0">
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" id="is_group_deal" name="is_group_deal" value="1" @checked((bool) old('is_group_deal', (int) (data_get($laravelV ?? null, 'is_group_deal', 0))) )>
                            <label class="form-check-label" for="is_group_deal">Afficher dans Group Deals</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
