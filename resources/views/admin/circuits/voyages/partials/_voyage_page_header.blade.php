@php
    $formId = $formId ?? 'edit-voyage-form';
    $currentStatus = $currentStatus ?? old('post_status', $voyage->post_status ?? 'draft');
    $cancelUrl = $cancelUrl ?? route('admin.circuits.voyages.index');
    $deleteFormId = $deleteFormId ?? 'delete-voyage-form';
    $pageTitle = $isCreate ? 'Creer un voyage' : ($voyage->post_title ?? $voyage->name);
    $statusLabel = $currentStatus === 'publish' ? 'Publie' : ($currentStatus === 'draft' ? 'Brouillon' : 'En attente');
    $pageSubtitle = $isCreate
        ? 'Renseignez la fiche, puis completez les departs, le programme et les options de vente.'
        : 'Mettez a jour les informations commerciales, les departs, le programme et les services depuis une seule page.';
    $lastUpdated = !$isCreate && $voyage->post_modified
        ? \Carbon\Carbon::parse($voyage->post_modified)->locale('fr')->translatedFormat('d M Y H:i')
        : null;

    $publicShowUrl = (!$isCreate && $laravelV && !empty($laravelV->slug))
        ? url('/voyages/' . $laravelV->slug)
        : null;
@endphp

<div class="ve-page-header">
    <div class="ve-header-topbar">
        <ul class="ve-breadcrumb mb-0">
            <li><a href="{{ route('admin.dashboard') }}"><i class="bx bx-home-alt"></i> Admin</a></li>
            <li><a href="{{ route('admin.circuits.index') }}">Circuits</a></li>
            <li><a href="{{ route('admin.circuits.voyages.index') }}">Voyages</a></li>
            <li class="active">{{ $isCreate ? 'Creation' : 'Edition' }}</li>
        </ul>

        <div class="ve-header-topbar__meta">
            <span class="ve-meta-pill ve-meta-pill--soft">{{ $isCreate ? 'Nouveau dossier' : 'Edition en cours' }}</span>
            @if(!$isCreate && $veWpId)
                <span class="ve-meta-pill ve-meta-pill--muted">Ref. #{{ $veWpId }}</span>
            @endif
        </div>
    </div>

    <div class="ve-header-grid">
        <div class="ve-header-main">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
                <div>
                    <h1 class="ve-page-title mb-2">{{ $pageTitle }}</h1>
                    <p class="ve-page-subtitle mb-0">{{ $pageSubtitle }}</p>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2">
                    @if($publicShowUrl)
                        <a href="{{ $publicShowUrl }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-primary">
                            <i class="bx bx-link-external"></i> Voir la page client
                        </a>
                    @endif

                    <span class="ve-status-badge status-{{ $currentStatus }} align-self-center">
                        <span class="status-dot"></span>
                        {{ $statusLabel }}
                    </span>
                </div>
            </div>

            <div class="ve-header-meta-line">
                @if($veDestination)
                    <span class="ve-meta-pill"><i class="bx bx-map"></i> {{ Str::limit($veDestination, 42) }}</span>
                @endif

                @if($vePriceLabel)
                    <span class="ve-meta-pill ve-meta-pill--accent"><i class="bx bx-purchase-tag"></i> Prix de base {{ $vePriceLabel }}</span>
                @endif

                <span class="ve-meta-pill"><i class="bx bx-calendar"></i> {{ $veDatesCount }} depart(s)</span>

                @if($lastUpdated)
                    <span class="ve-meta-pill ve-meta-pill--muted"><i class="bx bx-time"></i> Maj {{ $lastUpdated }}</span>
                @endif
            </div>
        </div>

        <div class="ve-header-aside">
            <div class="ve-header-panel">
                <p class="ve-header-panel__eyebrow mb-3">Vue rapide</p>

                <div class="ve-header-panel__grid">
                    <div class="ve-header-panel__item">
                        <span>Statut</span>
                        <strong>{{ $statusLabel }}</strong>
                    </div>

                    <div class="ve-header-panel__item">
                        <span>Depart(s)</span>
                        <strong>{{ $veDatesCount }}</strong>
                    </div>

                    <div class="ve-header-panel__item">
                        <span>Destination</span>
                        <strong>{{ $veDestination ? Str::limit($veDestination, 28) : 'A definir' }}</strong>
                    </div>

                    <div class="ve-header-panel__item">
                        <span>Prix</span>
                        <strong>{{ $vePriceLabel ?: 'A definir' }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
