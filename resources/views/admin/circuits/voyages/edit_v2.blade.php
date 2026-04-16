@php
    $isCreate = isset($voyage->ID) && (int) $voyage->ID === 0;
    $veWpId = isset($voyage->ID) ? (int) $voyage->ID : 0;
    $laravelV = $laravelVoyage ?? null;
    $veAdultRaw = $meta['adult_price'] ?? (method_exists($voyage, 'getMeta') ? $voyage->getMeta('adult_price') : null);
    $vePriceLabel = null;
    if ($veAdultRaw !== null && $veAdultRaw !== '') {
        $vePriceLabel = is_numeric($veAdultRaw)
            ? number_format((float) $veAdultRaw, 0, ',', ' ') . ' MAD'
            : trim((string) $veAdultRaw);
    } elseif ($laravelV) {
        $priceFrom = data_get($laravelV, 'price_from');
        if ($priceFrom !== null && $priceFrom !== '' && is_numeric($priceFrom) && (float) $priceFrom > 0) {
            $cur = trim((string) (data_get($laravelV, 'currency')
                ?: data_get($laravelV, 'currency_symbol')
                ?: ''));
            $vePriceLabel = number_format((float) $priceFrom, 0, ',', ' ') . ' ' . ($cur !== '' ? $cur : 'MAD');
        }
    }
    $veDestinationRaw = $laravelV ? data_get($laravelV, 'destination') : null;
    $veDestination = ($veDestinationRaw !== null && trim((string) $veDestinationRaw) !== '')
        ? trim((string) $veDestinationRaw)
        : null;
    $veDatesCount = isset($travelDates) && $travelDates instanceof \Illuminate\Support\Collection ? $travelDates->count() : 0;

    $headerTitle = old('title', $voyage->post_title ?? '') ?: ($isCreate ? 'Nouveau voyage' : 'Modifier le voyage');
    $postStatus = old('post_status', $voyage->post_status ?? 'draft');
    $statusLabels = ['publish' => 'Publié', 'draft' => 'Brouillon', 'pending' => 'En attente', 'private' => 'Archivé'];
    $statusCls = ['publish' => 'v2-pill-publish', 'draft' => 'v2-pill-draft', 'pending' => 'v2-pill-pending', 'private' => 'v2-pill-private'];
    $statusLabel = $statusLabels[$postStatus] ?? ucfirst($postStatus);
    $statusClass = $statusCls[$postStatus] ?? 'v2-pill-draft';

    $formId = 'edit-voyage-form';
    $formAction = $isCreate
        ? route('admin.circuits.voyages.store')
        : route('admin.circuits.voyages.update', $voyage->ID);

    $cssV2 = file_exists(public_path('css/voyage-v2.css')) ? (string) filemtime(public_path('css/voyage-v2.css')) : '1';
    $cssEdit = file_exists(public_path('css/voyage-edit.css')) ? (string) filemtime(public_path('css/voyage-edit.css')) : '1';
    $jsEdit = file_exists(public_path('js/voyage-edit-page.js')) ? (string) filemtime(public_path('js/voyage-edit-page.js')) : '1';
    $jsV2 = file_exists(public_path('js/voyage-v2.js')) ? (string) filemtime(public_path('js/voyage-v2.js')) : '1';

    $sections = [
        ['id' => 's-general', 'icon' => 'bx-file-blank', 'label' => 'Infos générales', 'group' => 'Fiche produit', 'partial' => 'tabs._basic', 'eyebrow' => 'Fiche produit', 'title' => 'Informations générales', 'desc' => 'Titre, publication et présentation publique du voyage.'],
        ['id' => 's-pricing', 'icon' => 'bx-euro', 'label' => 'Tarifs & capacité', 'group' => 'Fiche produit', 'partial' => 'tabs._pricing', 'eyebrow' => 'Fiche produit', 'title' => 'Tarifs & capacité', 'desc' => 'Prix publics et paramètres commerciaux.'],
        ['id' => 's-location', 'icon' => 'bx-map-pin', 'label' => 'Destination', 'group' => 'Fiche produit', 'partial' => 'tabs._location', 'eyebrow' => 'Fiche produit', 'title' => 'Destination', 'desc' => 'Localisations géographiques et informations de contact.'],
        ['id' => 's-media', 'icon' => 'bx-image-alt', 'label' => 'Médias', 'group' => 'Fiche produit', 'partial' => 'tabs._media', 'eyebrow' => 'Fiche produit', 'title' => 'Médias', 'desc' => 'Hero, image à la une et galeries.'],
        ['id' => 's-programme', 'icon' => 'bx-calendar-check', 'label' => 'Programme', 'group' => 'Contenu', 'partial' => 'tabs._programme', 'eyebrow' => 'Contenu', 'title' => 'Programme du circuit', 'desc' => 'Détail jour par jour du voyage.'],
        ['id' => 's-information', 'icon' => 'bx-list-ul', 'label' => 'Inclus / Exclus', 'group' => 'Contenu', 'partial' => 'tabs._information', 'eyebrow' => 'Contenu', 'title' => 'Inclus / Exclus / FAQ', 'desc' => 'Informations commerciales et pratiques.'],
        ['id' => 's-taxonomies', 'icon' => 'bx-tag', 'label' => 'Catégories', 'group' => 'Contenu', 'partial' => 'tabs._taxonomies', 'eyebrow' => 'Contenu', 'title' => 'Catégories & tags', 'desc' => 'Classement pour catalogue et SEO.'],
        ['id' => 's-availability', 'icon' => 'bx-calendar', 'label' => 'Disponibilités', 'group' => 'Exploitation', 'partial' => 'tabs._availability', 'eyebrow' => 'Exploitation', 'title' => 'Disponibilités', 'desc' => 'Dates, stock et paramètres de réservation.'],
        ['id' => 's-flights', 'icon' => 'bx-trip', 'label' => 'Vols', 'group' => 'Logistique', 'partial' => 'tabs._flights', 'eyebrow' => 'Logistique', 'title' => 'Vols', 'desc' => 'Compagnies, itinéraires et options de vol.'],
        ['id' => 's-hotels', 'icon' => 'bx-hotel', 'label' => 'Hôtels', 'group' => 'Logistique', 'partial' => 'tabs._hotels', 'eyebrow' => 'Logistique', 'title' => 'Hôtels', 'desc' => 'Hébergements et allocations de chambres.'],
        ['id' => 's-transfers', 'icon' => 'bx-bus', 'label' => 'Transferts', 'group' => 'Logistique', 'partial' => 'tabs._transfers', 'eyebrow' => 'Logistique', 'title' => 'Transferts', 'desc' => 'Transferts arrivée / départ.'],
        ['id' => 's-activities', 'icon' => 'bx-run', 'label' => 'Activités', 'group' => 'Logistique', 'partial' => 'tabs._activities', 'eyebrow' => 'Logistique', 'title' => 'Activités', 'desc' => 'Catalogue des activités du voyage.'],
        ['id' => 's-extras', 'icon' => 'bx-star', 'label' => 'Extras', 'group' => 'Logistique', 'partial' => 'tabs._extras', 'eyebrow' => 'Logistique', 'title' => 'Suppléments & extras', 'desc' => 'Options payantes complémentaires.'],
        ['id' => 's-logistics', 'icon' => 'bx-cog', 'label' => 'Paramètres', 'group' => 'Exploitation', 'partial' => 'tabs._logistics', 'eyebrow' => 'Exploitation', 'title' => 'Paramètres avancés', 'desc' => 'Réglages techniques et logistiques.'],
    ];

    $initialStepStates = is_array($v2StepStates ?? null)
        ? $v2StepStates
        : collect($sections)->mapWithKeys(fn ($sec) => [$sec['id'] => 'incomplete'])->all();

    $saveCreateUrl = route('admin.circuits.voyages.v2.steps.save.create', ['step' => '__STEP__']);
    $saveUpdateTemplate = route('admin.circuits.voyages.v2.steps.save', ['id' => 999999, 'step' => '__STEP__']);
    $saveUpdateTemplate = str_replace('999999', '__ID__', $saveUpdateTemplate);
@endphp
@extends('layouts.master-ajinsafro')

@section('title'){{ $isCreate ? 'Créer un voyage — V2' : 'Modifier — ' . $headerTitle }}@endsection

@push('styles')
    <link href="{{ URL::asset('css/voyage-edit.css?v=' . $cssEdit) }}" rel="stylesheet">
    <link href="{{ URL::asset('css/flight-options-new.css') }}" rel="stylesheet">
    <link href="{{ URL::asset('css/voyage-v2.css?v=' . $cssV2) }}" rel="stylesheet">
@endpush

@section('content')
<div class="v2-page voyage-edit-page" data-v2-initial-id="{{ $veWpId }}" data-v2-save-create-url="{{ $saveCreateUrl }}" data-v2-save-update-template="{{ $saveUpdateTemplate }}" data-v2-is-create="{{ $isCreate ? '1' : '0' }}">
    <header class="v2-header">
        <div class="v2-hdr-left">
            <a href="{{ route('admin.circuits.voyages.index') }}" class="v2-hdr-back" title="Retour"><i class="bx bx-arrow-back"></i></a>
            <div class="v2-hdr-info">
                <p class="v2-hdr-kicker">Opérations Voyages</p>
                <span class="v2-hdr-title" id="v2-live-title">{{ $headerTitle }}</span>
                <span class="v2-hdr-subtitle" id="v2-live-subtitle">{{ $isCreate ? 'Brouillon à créer au premier enregistrement' : ('ID #' . $veWpId) }}</span>
            </div>
        </div>
        <div class="v2-hdr-right">
            <span class="v2-pill {{ $statusClass }}" id="v2-live-status">{{ $statusLabel }}</span>
            @if(!$isCreate)
                <a href="{{ route('admin.circuits.voyages.edit', $veWpId) }}" class="v2-btn v2-btn-ghost" data-v2-classic-link><i class="bx bx-transfer-alt"></i><span>Version classique</span></a>
            @endif
            <button type="button" class="v2-btn v2-btn-primary" data-v2-save><i class="bx bx-save"></i><span>{{ $isCreate ? 'Créer le brouillon' : 'Enregistrer l’étape' }}</span></button>
        </div>
    </header>

    <div class="v2-body">
        <aside class="v2-sidebar" id="v2-sidebar">
            @php $lastGroup = null; @endphp
            @foreach($sections as $i => $sec)
                @if($sec['group'] !== $lastGroup)
                    <div class="v2-sb-group">{{ $sec['group'] }}</div>
                    @php $lastGroup = $sec['group']; @endphp
                @endif
                @php
                    $stepState = $initialStepStates[$sec['id']] ?? 'incomplete';
                    $stateLabel = $stepState === 'complete' ? 'Validée' : 'À compléter';
                @endphp
                <button type="button" class="v2-sb-item{{ $i === 0 ? ' active' : '' }}" data-v2-nav="{{ $sec['id'] }}" data-v2-step-state="{{ $stepState }}">
                    <span class="v2-sb-index">{{ $i + 1 }}</span>
                    <span class="v2-sb-icon"><i class="bx {{ $sec['icon'] }}"></i></span>
                    <span class="v2-sb-label-wrap"><span class="v2-sb-label">{{ $sec['label'] }}</span><span class="v2-sb-meta" data-v2-step-meta>{{ $stateLabel }}</span></span>
                    <span class="v2-sb-dot"></span>
                </button>
            @endforeach
            <div class="v2-sb-footer"><button type="button" class="v2-sb-save" data-v2-save><i class="bx bx-save"></i>{{ $isCreate ? 'Créer le voyage' : 'Enregistrer l’étape courante' }}</button></div>
        </aside>

        <main class="v2-main" id="v2-main">
            @if(session('success'))
                <div class="v2-alert v2-alert-ok mb-4"><i class="bx bx-check-circle"></i><div>{{ session('success') }}</div></div>
            @endif
            @if($errors->any())
                <div class="v2-alert v2-alert-err mb-4"><i class="bx bx-error-circle"></i><div><strong>Corrections requises :</strong><ul class="mb-0 mt-1 ps-3">@foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul></div></div>
            @endif
            <div class="v2-alert v2-alert-err d-none mb-4" id="v2-step-errors"></div>

            <form id="{{ $formId }}" action="{{ $formAction }}" method="POST" data-voyage-id="{{ $veWpId }}" data-v2-current-step="s-general" novalidate>
                @csrf
                @if(!$isCreate) @method('PUT') @endif
                <textarea name="programme_days_payload" id="programme-days-payload" class="d-none" aria-hidden="true"></textarea>

                @foreach($sections as $index => $sec)
                    <div class="v2-panel{{ $index === 0 ? ' active' : '' }}" id="{{ $sec['id'] }}">
                        <div class="v2-section-hdr">
                            <p class="v2-section-eyebrow">{{ $sec['eyebrow'] }}</p>
                            <h1 class="v2-section-title">{{ $sec['title'] }}</h1>
                            <p class="v2-section-desc">{{ $sec['desc'] }}</p>
                        </div>

                        <div class="v2-card">
                            <div class="v2-card-hdr">
                                <div class="v2-card-icon"><i class="bx {{ $sec['icon'] }}"></i></div>
                                <div class="v2-card-hdr-text"><p class="v2-card-hdr-title">{{ $sec['title'] }}</p></div>
                            </div>
                            <div class="v2-card-body">@include('admin.circuits.voyages.partials.' . $sec['partial'])</div>
                        </div>

                        @if($sec['id'] === 's-logistics' && !$isCreate)
                            <div class="v2-danger-zone">
                                <div><p class="v2-danger-title"><i class="bx bx-error-circle me-1"></i>Suppression définitive</p><p class="v2-danger-desc">Action irréversible.</p></div>
                                <button type="submit" form="v2-delete-form" class="v2-btn v2-btn-danger-ghost" onclick="return confirm('Supprimer définitivement ce voyage ?')"><i class="bx bx-trash"></i> Supprimer</button>
                            </div>
                        @endif

                        @php
                            $prev = $sections[$index - 1]['id'] ?? null;
                            $next = $sections[$index + 1]['id'] ?? null;
                            $prevLabel = $sections[$index - 1]['label'] ?? null;
                            $nextLabel = $sections[$index + 1]['label'] ?? null;
                        @endphp
                        @include('admin.circuits.voyages.partials.v2._footer', compact('prev', 'next', 'prevLabel', 'nextLabel', 'formId'))
                    </div>
                @endforeach
            </form>
        </main>

        <aside class="v2-rail" id="v2-rail">
            <div class="v2-rail-card"><p class="v2-rail-kicker">Progression</p><h3 class="v2-rail-title">Workflow</h3><div class="v2-progress" role="progressbar"><span id="v2-progress-bar"></span></div><p class="v2-progress-text" id="v2-progress-text">0 / {{ count($sections) }} étapes validées</p></div>
            <div class="v2-rail-card" id="v2-save-card" data-state="idle"><p class="v2-rail-kicker">Enregistrement</p><h3 class="v2-rail-title" id="v2-save-state">Prêt</h3><p class="v2-save-help" id="v2-save-help">Sauvegarde automatique à la navigation.</p><div class="v2-rail-actions"><button type="button" class="v2-btn v2-btn-primary v2-btn-full" data-v2-save><i class="bx bx-save"></i><span>{{ $isCreate ? 'Créer le brouillon' : 'Enregistrer maintenant' }}</span></button><button type="button" class="v2-btn v2-btn-ghost v2-btn-full" data-v2-save><i class="bx bx-cloud-upload"></i><span>Enregistrer l'étape</span></button></div></div>
            <div class="v2-rail-card"><p class="v2-rail-kicker">Résumé</p><h3 class="v2-rail-title" id="v2-rail-id">{{ $isCreate ? 'Nouveau voyage' : ('ID #' . $veWpId) }}</h3><ul class="v2-rail-list"><li><span>Statut</span><strong id="v2-rail-status">{{ $statusLabel }}</strong></li><li><span>Destination</span><strong id="v2-rail-destination">{{ $laravelV?->destination ?: '—' }}</strong></li></ul></div>
        </aside>
    </div>

    @if(!$isCreate)
        <form id="v2-delete-form" action="{{ route('admin.circuits.voyages.destroy', $voyage->ID) }}" method="POST" class="d-none">@csrf @method('DELETE')</form>
    @endif
</div>
@endsection

@push('script')
    @include('admin.circuits.voyages.partials._voyage_page_bootstrap')
    <script src="{{ URL::asset('build/libs/tinymce/tinymce.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
    <script src="{{ URL::asset('js/voyage-editor-runtime.js') }}"></script>
    <script src="{{ URL::asset('js/voyage-edit-page.js?v=' . $jsEdit) }}"></script>
    <script src="{{ URL::asset('js/flight-options-fix.js') }}"></script>
    <script src="{{ URL::asset('js/flight-options-manager.js') }}"></script>
    <script>
        window.VOYAGE_V2_CONFIG = {
            initialStepStates: @json($initialStepStates),
            sectionIds: @json(collect($sections)->pluck('id')->values()->all())
        };
    </script>
    <script src="{{ URL::asset('js/voyage-v2.js?v=' . $jsV2) }}"></script>
@endpush
