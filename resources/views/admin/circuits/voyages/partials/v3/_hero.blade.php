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
        <div class="v3-header-workflow-card">
            <p class="v3-header-workflow-card__kicker">Workflow</p>
            <p class="v3-header-workflow-card__value">
                <strong>{{ $completedSteps }}</strong> / {{ $sectionsCount }} étapes validées
            </p>
            <div class="v3-header-workflow-card__progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $progressPercent }}">
                <span id="v2-progress-bar" style="width: {{ $progressPercent }}%"></span>
            </div>
            <div class="v3-header-workflow-card__footer">
                <span id="v2-progress-text">{{ $progressPercent }}%</span>
                @if(!empty($nextActionSection))
                    <a href="#{{ $nextActionSection['id'] }}">Reprendre le workflow</a>
                @endif
            </div>
            <div class="v3-header-workflow-card__links">
                <a href="{{ route('admin.circuits.voyages.index') }}">Retour catalogue</a>
                @if($frontPreviewUrl)
                    <a href="{{ $frontPreviewUrl }}" target="_blank" rel="noopener">Aperçu public</a>
                @endif
                @if(!$isCreate)
                    <a href="{{ route('admin.circuits.voyages.edit', $veWpId) }}" data-v2-classic-link>Version classique</a>
                @endif
            </div>
        </div>
    </div>
</section>
