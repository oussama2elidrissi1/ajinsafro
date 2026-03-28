@php
    $formId = $formId ?? 'edit-voyage-form';
    $currentStatus = $currentStatus ?? old('post_status', $voyage->post_status ?? 'draft');
    $cancelUrl = $cancelUrl ?? route('admin.circuits.voyages.index');
    $deleteFormId = $deleteFormId ?? 'delete-voyage-form';
@endphp
<div class="ve-page-header">
    <div class="ve-header-topbar">
        <ul class="ve-breadcrumb mb-0">
            <li><a href="{{ route('admin.dashboard') }}"><i class="bx bx-home-alt"></i> Admin</a></li>
            <li><a href="{{ route('admin.circuits.index') }}">Circuits</a></li>
            <li><a href="{{ route('admin.circuits.voyages.index') }}">Tours</a></li>
            <li class="active">{{ $isCreate ? 'Créer' : Str::limit($voyage->post_title ?? $voyage->name ?? '', 48) }}</li>
        </ul>
        <div class="ve-header-actions d-flex flex-wrap">
            <a href="{{ $cancelUrl }}" class="btn btn-outline-light btn-sm ve-header-btn"><i class="bx bx-x me-1"></i> Annuler</a>
            @if(!$isCreate)
                <button type="submit" form="{{ $deleteFormId }}" class="btn btn-danger btn-sm ve-header-btn ve-header-btn--danger">
                    <i class="bx bx-trash me-1"></i> Supprimer
                </button>
            @endif
            <button type="submit" form="{{ $formId }}" class="btn btn-light btn-sm ve-header-btn ve-header-btn--primary fw-semibold">
                <i class="bx bx-save me-1"></i> {{ $isCreate ? 'Créer le tour' : 'Enregistrer' }}
            </button>
        </div>
    </div>
    <div class="ve-header-grid">
        <div class="ve-header-main">
            <div class="d-flex flex-wrap align-items-start gap-3 mb-2">
                <h1 class="ve-page-title mb-0">{{ $isCreate ? 'Créer un voyage' : ($voyage->post_title ?? $voyage->name) }}</h1>
                <span class="ve-status-badge status-{{ $currentStatus }} align-self-center">
                    <span class="status-dot"></span>
                    {{ $currentStatus === 'publish' ? 'Publié' : ($currentStatus === 'draft' ? 'Brouillon' : 'En attente') }}
                </span>
            </div>
            @if($isCreate)
                <p class="ve-page-subtitle mb-2">Complétez les champs puis enregistrez. Vous pourrez ensuite enrichir prix, médias et programme.</p>
            @endif
            <div class="ve-header-meta-line">
                @if(!$isCreate)
                    <span class="ve-meta-pill"><i class="bx bx-hash"></i> WP #{{ $veWpId }}</span>
                    @if($laravelV && (int) ($laravelV->id ?? 0) > 0)
                        <span class="ve-meta-pill"><i class="bx bx-data"></i> Laravel #{{ $laravelV->id }}</span>
                    @endif
                    @if($vePriceLabel)
                        <span class="ve-meta-pill ve-meta-pill--accent"><i class="bx bx-purchase-tag"></i> {{ $vePriceLabel }}</span>
                    @endif
                    <span class="ve-meta-pill ve-meta-pill--muted"><i class="bx bx-time"></i> {{ $voyage->post_modified ? \Carbon\Carbon::parse($voyage->post_modified)->locale('fr')->translatedFormat('d M Y H:i') : '—' }}</span>
                @else
                    <span class="ve-meta-pill ve-meta-pill--muted">Nouveau tour</span>
                @endif
            </div>
        </div>
        <div class="ve-header-aside">
            @if(!$isCreate)
                @if($vePriceLabel)
                    <div class="ve-header-stat"><span class="ve-header-stat-label">Prix</span><span class="ve-header-stat-value">{{ $vePriceLabel }}</span></div>
                @endif
                @if($veDestination)
                    <div class="ve-header-stat"><span class="ve-header-stat-label">Destination</span><span class="ve-header-stat-value ve-header-stat-value--sm">{{ Str::limit($veDestination, 56) }}</span></div>
                @endif
                @if(!$vePriceLabel && !$veDestination)
                    <p class="ve-header-placeholder mb-0 small">Renseignez prix et destination dans les onglets.</p>
                @endif
            @else
                <div class="ve-header-stat ve-header-stat--hint">
                    <span class="ve-header-stat-label">Étape</span>
                    <span class="ve-header-stat-value ve-header-stat-value--sm">Création</span>
                </div>
            @endif
        </div>
    </div>
</div>
