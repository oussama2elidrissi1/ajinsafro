<aside class="v2-rail v3-rail" id="v2-rail">
    <div class="v2-rail-card v3-rail-card">
        <p class="v2-rail-kicker">Progression</p>
        <h3 class="v2-rail-title">Workflow</h3>
        <div class="v3-progress-big">{{ $completedSteps }} <span>/ {{ $sectionsCount }} etapes validees</span></div>
        <div class="v2-progress" role="progressbar" aria-valuenow="{{ $progressPercent }}" aria-valuemin="0" aria-valuemax="100">
            <span id="v2-progress-bar" style="width: {{ $progressPercent }}%"></span>
        </div>
        <div class="v3-progress-text-row">
            <p class="v2-progress-text" id="v2-progress-text">{{ $completedSteps }} / {{ $sectionsCount }} etapes validees</p>
            <strong>{{ $progressPercent }}%</strong>
        </div>
        @if(!empty($nextActionSection))
            <a href="#{{ $nextActionSection['id'] }}" class="v3-inline-link">Reprendre le workflow <i class="bx bx-right-arrow-alt"></i></a>
        @endif
    </div>

    <div class="v2-rail-card v3-rail-card" id="v2-save-card" data-state="idle">
        <p class="v2-rail-kicker">Enregistrement</p>
        <h3 class="v2-rail-title" id="v2-save-state">Pret</h3>
        <p class="v2-save-help" id="v2-save-help">Sauvegarde automatique a la navigation entre etapes.</p>
        <div class="v2-rail-actions">
            <button type="button" class="v2-btn v2-btn-primary v2-btn-full" data-v2-save>
                <i class="bx bx-save"></i>
                <span>{{ $isCreate ? 'Creer le brouillon' : 'Enregistrer maintenant' }}</span>
            </button>
            <button type="button" class="v2-btn v2-btn-ghost v2-btn-full" data-v2-save>
                <i class="bx bx-cloud-upload"></i>
                <span>Enregistrer l etape</span>
            </button>
        </div>
    </div>

    <div class="v2-rail-card v3-rail-card">
        <p class="v2-rail-kicker">Actions rapides</p>
        <h3 class="v2-rail-title">Raccourcis d edition</h3>
        <div class="v3-quick-list">
            @foreach($v3QuickActions as $action)
                <a href="#{{ $action['step'] }}" class="v3-quick-item">
                    <span><i class="bx {{ $action['icon'] }}"></i>{{ $action['label'] }}</span>
                    <i class="bx bx-chevron-right"></i>
                </a>
            @endforeach
        </div>
    </div>

    <div class="v2-rail-card v3-rail-card">
        <p class="v2-rail-kicker">Resume</p>
        <h3 class="v2-rail-title" id="v2-rail-id">{{ $isCreate ? 'Nouveau voyage' : ('ID #' . $veWpId) }}</h3>
        <ul class="v2-rail-list">
            <li><span>Statut</span><strong id="v2-rail-status">{{ $statusLabel }}</strong></li>
            <li><span>Destination</span><strong id="v2-rail-destination">{{ $veDestination ?: '-' }}</strong></li>
            <li><span>Departs programmes</span><strong>{{ $veDatesCount }}</strong></li>
            <li><span>Prix de base</span><strong>{{ $vePriceLabel ?: 'A definir' }}</strong></li>
        </ul>
    </div>

    @if(!empty($alertSection))
        <div class="v2-rail-card v3-rail-card">
            <div class="v3-alert-head">
                <h3 class="v2-rail-title mb-0">Alertes</h3>
                <span class="v3-alert-count">1</span>
            </div>
            <div class="v3-alert-box">
                <div class="v3-alert-icon"><i class="bx bx-error"></i></div>
                <div>
                    <div class="v3-alert-title">Completer l etape {{ $alertSection['label'] }}</div>
                    <div class="v3-alert-desc">Cette etape demande encore une verification avant publication.</div>
                    <a href="#{{ $alertSection['id'] }}" class="v3-inline-link">Aller a l etape <i class="bx bx-right-arrow-alt"></i></a>
                </div>
            </div>
        </div>
    @endif
</aside>
