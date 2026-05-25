@php
    $formId = $formId ?? 'edit-voyage-form';
    $currentStatus = $currentStatus ?? old('post_status', $voyage->post_status ?? 'draft');
    $cancelUrl = $cancelUrl ?? route('admin.circuits.voyages.index');
    $deleteFormId = $deleteFormId ?? 'delete-voyage-form';
    $pageTitle = $isCreate ? 'Creer un voyage' : ($voyage->post_title ?? $voyage->name);
    $statusLabel = match ($currentStatus) {
        'publish' => 'Publié',
        'draft' => 'Brouillon',
        'private' => 'Archivé',
        default => 'En attente',
    };
    $pageSubtitle = $isCreate
        ? 'Complétez la fiche, les départs, le programme et les services.'
        : 'Fiche voyage ? départs, programme et options.';
    $lastUpdated = !$isCreate && $voyage->post_modified
        ? \Carbon\Carbon::parse($voyage->post_modified)->locale('fr')->translatedFormat('d M Y H:i')
        : null;

    $publicShowUrl = (!$isCreate && $laravelV && !empty($laravelV->slug))
        ? url('/voyages/' . $laravelV->slug)
        : null;
    $workflowTotalSteps = 14;
    $workflowValidatedSteps = 10;
    $workflowProgressPercent = 71;
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

    <div class="ve-header-main ve-header-main--compact">
        <div class="ve-header-main__content">
            <h1 class="ve-page-title mb-1">{{ $pageTitle }}</h1>
            <p class="ve-page-subtitle mb-0">{{ $pageSubtitle }}</p>

            <div class="ve-header-meta-line">
                @if($veDestination)
                    <span class="ve-meta-pill"><i class="bx bx-map"></i> {{ Str::limit($veDestination, 42) }}</span>
                @endif

                @if($vePriceLabel)
                    <span class="ve-meta-pill ve-meta-pill--accent"><i class="bx bx-purchase-tag"></i> {{ $vePriceLabel }}</span>
                @endif

                <span class="ve-meta-pill"><i class="bx bx-calendar"></i> {{ $veDatesCount }} départ(s)</span>

                @if($lastUpdated)
                    <span class="ve-meta-pill ve-meta-pill--muted"><i class="bx bx-time"></i> Maj {{ $lastUpdated }}</span>
                @endif
            </div>
        </div>

        <div class="ve-header-main__actions ve-header-main__actions--workflow">
            <div class="voyage-header-workflow-card" id="workflow">
                <div class="workflow-card-title">Workflow</div>
                <div class="workflow-card-value">
                    <strong>{{ $workflowValidatedSteps }}</strong> / {{ $workflowTotalSteps }} étapes validées
                </div>
                <div class="workflow-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $workflowProgressPercent }}">
                    <span class="workflow-progress-bar" style="width: {{ $workflowProgressPercent }}%"></span>
                </div>
                <div class="workflow-card-footer">
                    <span>{{ $workflowProgressPercent }}%</span>
                    <a href="#basic" data-ve-resume-workflow>Reprendre le workflow</a>
                </div>
            </div>
        </div>
    </div>

    <div class="ve-header-quickbar" aria-label="Vue rapide voyage">
        <div class="ve-quick-item">
            <span>Statut</span>
            <strong>{{ $statusLabel }}</strong>
        </div>
        <div class="ve-quick-item">
            <span>Départs</span>
            <strong>{{ $veDatesCount }}</strong>
        </div>
        <div class="ve-quick-item">
            <span>Destination</span>
            <strong>{{ $veDestination ? Str::limit($veDestination, 28) : '? définir' }}</strong>
        </div>
        <div class="ve-quick-item">
            <span>Prix</span>
            <strong>{{ $vePriceLabel ?: '? définir' }}</strong>
        </div>
    </div>
</div>

