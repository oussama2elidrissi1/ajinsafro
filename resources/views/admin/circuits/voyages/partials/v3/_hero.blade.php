<section class="v3-hero-card">
    <div class="v3-hero-thumb{{ !empty($heroImageUrl) ? ' has-image' : '' }}" @if(!empty($heroImageUrl)) style="background-image:url('{{ $heroImageUrl }}')" @endif>
        @if(empty($heroImageUrl))
            <span class="v3-hero-thumb-badge">Voyage Studio</span>
        @endif
    </div>

    <div class="v3-hero-main">
        <span class="v3-eyebrow">{{ $isCreate ? 'Nouveau voyage' : 'Voyage' }}</span>
        <div class="v3-hero-title-row">
            <h1 class="v3-hero-title" id="v2-live-title">{{ $headerTitle }}</h1>
            <span class="v3-status-pill {{ $statusClass }}" id="v2-live-status">{{ $statusLabel }}</span>
        </div>
        <div class="v3-hero-subtitle">{{ $journeySubtitle }}</div>
        <div class="v3-hero-id" id="v2-live-subtitle">{{ $isCreate ? 'Brouillon a creer au premier enregistrement' : ('ID #' . $veWpId) }}</div>

        <div class="v3-stat-row">
            <div class="v3-mini-stat">
                <div class="v3-mini-stat__icon"><i class="bx bx-calendar"></i></div>
                <div>
                    <small>Departs programmes</small>
                    <strong>{{ $veDatesCount }}</strong>
                </div>
            </div>
            <div class="v3-mini-stat">
                <div class="v3-mini-stat__icon"><i class="bx bx-wallet"></i></div>
                <div>
                    <small>Prix de base</small>
                    <strong>{{ $vePriceLabel ?: 'A definir' }}</strong>
                </div>
            </div>
            <div class="v3-mini-stat">
                <div class="v3-mini-stat__icon"><i class="bx bx-map"></i></div>
                <div>
                    <small>Destination</small>
                    <strong>{{ $veDestination ?: 'A renseigner' }}</strong>
                </div>
            </div>
        </div>
    </div>

    <div class="v3-hero-actions">
        <a href="{{ route('admin.circuits.voyages.index') }}" class="v3-btn v3-btn-muted">
            <i class="bx bx-arrow-back"></i>
            <span>Retour catalogue</span>
        </a>

        @if($frontPreviewUrl)
            <a href="{{ $frontPreviewUrl }}" class="v3-btn v3-btn-muted" target="_blank" rel="noopener">
                <i class="bx bx-show-alt"></i>
                <span>Apercu public</span>
            </a>
        @else
            <button type="button" class="v3-btn v3-btn-muted" disabled>
                <i class="bx bx-show-alt"></i>
                <span>Apercu public</span>
            </button>
        @endif

        @if(!$isCreate)
            <a href="{{ route('admin.circuits.voyages.edit', $veWpId) }}" class="v3-btn v3-btn-muted" data-v2-classic-link>
                <i class="bx bx-transfer-alt"></i>
                <span>Version classique</span>
            </a>
        @endif

        <button type="button" class="v3-btn v3-btn-primary" data-v2-save>
            <i class="bx bx-save"></i>
            <span>{{ $isCreate ? 'Creer le brouillon' : 'Enregistrer maintenant' }}</span>
        </button>

        @if(!empty($nextActionSection))
            <a href="#{{ $nextActionSection['id'] }}" class="v3-btn v3-btn-ghost">
                <span>Continuer le workflow</span>
                <i class="bx bx-chevron-right"></i>
            </a>
        @endif
    </div>
</section>
