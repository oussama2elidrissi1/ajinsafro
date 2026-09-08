@php
    $agentVoyageMode = (bool) ($agentVoyageMode ?? request()->routeIs('agent.voyages.*'));
    $voyageRoutePrefix = $agentVoyageMode ? 'agent.voyages' : 'admin.circuits.voyages';
    $voyageBackUrl = $agentVoyageMode ? route('agent.catalogue') : route('admin.circuits.voyages.index');
    $voyageBackLabel = $agentVoyageMode ? 'Retour au catalogue' : 'Retour catalogue';
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
    // Résolution destination : priorité meta WP address > multi_location > Laravel destination
    $veDestination = null;
    $veWpId = isset($voyage->ID) ? (int) $voyage->ID : 0;
    if ($veWpId > 0) {
        $wpAddress = null;
        $wpPost = null;
        try {
            $wpPost = \App\Models\Wp\WpPost::tours()->find($veWpId);
            if ($wpPost) {
                $wpAddress = $wpPost->getMeta('address');
            }
        } catch (\Throwable $e) {
            \Log::warning('edit_v2.blade: failed reading WP address meta', ['wp_post_id' => $veWpId, 'error' => $e->getMessage()]);
        }
        if (is_string($wpAddress) && trim($wpAddress) !== '') {
            $veDestination = trim(preg_split('/[,;|]/', $wpAddress)[0] ?? $wpAddress);
        } else {
            try {
                $multiLoc = $wpPost ? $wpPost->getMeta('multi_location') : null;
                $locNames = app(\App\Services\Wp\WpTourRepository::class)->getLocationNamesFromMultiLocation($multiLoc);
                if ($locNames !== '') {
                    $veDestination = $locNames;
                }
            } catch (\Throwable $e) {
                \Log::warning('edit_v2.blade: failed reading WP locations', ['wp_post_id' => $veWpId, 'error' => $e->getMessage()]);
            }
        }
    }
    if (! $veDestination && $laravelV) {
        $laravelDest = data_get($laravelV, 'destination');
        if ($laravelDest !== null && trim((string) $laravelDest) !== '') {
            $veDestination = trim((string) $laravelDest);
        }
    }
    $veDatesCount = isset($travelDates) && $travelDates instanceof \Illuminate\Support\Collection ? $travelDates->count() : 0;

    $headerTitle = old('title', $voyage->post_title ?? '') ?: ($isCreate ? 'Nouveau voyage' : 'Modifier le voyage');
    $postStatus = old('post_status', $voyage->post_status ?? 'draft');
    $statusLabels = ['publish' => 'Publié', 'draft' => 'Brouillon', 'pending' => 'En attente', 'private' => 'Archivé'];
    $statusCls = ['publish' => 'v2-pill-publish', 'draft' => 'v2-pill-draft', 'pending' => 'v2-pill-pending', 'private' => 'v2-pill-private'];
    $statusLabel = $statusLabels[$postStatus] ?? ucfirst($postStatus);
    $statusClass = $statusCls[$postStatus] ?? 'v2-pill-draft';

    $formId = 'edit-voyage-form';
    $formAction = $isCreate
        ? route($voyageRoutePrefix . '.store')
        : route($voyageRoutePrefix . '.update', $voyage->ID);

    $cssV2 = file_exists(public_path('css/voyage-v2.css')) ? (string) filemtime(public_path('css/voyage-v2.css')) : '1';
    $cssV3 = file_exists(public_path('css/voyage-v3.css')) ? (string) filemtime(public_path('css/voyage-v3.css')) : '1';
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
        ['id' => 's-flights', 'icon' => 'bx-paper-plane', 'label' => 'Vols', 'group' => 'Logistique', 'partial' => 'tabs._flights', 'eyebrow' => 'Logistique', 'title' => 'Vols', 'desc' => 'Compagnies, itinéraires et options de vol.'],
        ['id' => 's-hotels', 'icon' => 'bx-hotel', 'label' => 'Hôtels', 'group' => 'Logistique', 'partial' => 'tabs._hotels', 'eyebrow' => 'Logistique', 'title' => 'Hôtels', 'desc' => 'Hébergements et allocations de chambres.'],
        ['id' => 's-transfers', 'icon' => 'bx-bus', 'label' => 'Transferts', 'group' => 'Logistique', 'partial' => 'tabs._transfers', 'eyebrow' => 'Logistique', 'title' => 'Transferts', 'desc' => 'Transferts arrivée / départ.'],
        ['id' => 's-activities', 'icon' => 'bx-run', 'label' => 'Activités', 'group' => 'Logistique', 'partial' => 'tabs._activities', 'eyebrow' => 'Logistique', 'title' => 'Activités', 'desc' => 'Catalogue des activités du voyage.'],
        ['id' => 's-extras', 'icon' => 'bx-star', 'label' => 'Extras', 'group' => 'Logistique', 'partial' => 'tabs._extras', 'eyebrow' => 'Logistique', 'title' => 'Supplements & extras', 'desc' => 'Options payantes complémentaires.'],
        ['id' => 's-logistics', 'icon' => 'bx-cog', 'label' => 'Paramètres', 'group' => 'Exploitation', 'partial' => 'tabs._logistics', 'eyebrow' => 'Exploitation', 'title' => 'Paramètres avancés', 'desc' => 'Réglages techniques et logistiques.'],
    ];

    $initialStepStates = is_array($v2StepStates ?? null)
        ? $v2StepStates
        : collect($sections)->mapWithKeys(fn ($sec) => [$sec['id'] => 'incomplete'])->all();

    $saveCreateUrl = route($voyageRoutePrefix . '.v2.steps.save.create', ['step' => '__STEP__']);
    $saveUpdateTemplate = route($voyageRoutePrefix . '.v2.steps.save', ['id' => 999999, 'step' => '__STEP__']);
    $saveUpdateTemplate = str_replace('999999', '__ID__', $saveUpdateTemplate);

    $sectionsCount = count($sections);
    $completedSteps = collect($initialStepStates)->filter(fn ($state) => $state === 'complete')->count();
    $progressPercent = $sectionsCount > 0 ? (int) round(($completedSteps / $sectionsCount) * 100) : 0;
    $nextActionSection = collect($sections)->first(fn (array $sec) => ($initialStepStates[$sec['id']] ?? 'incomplete') !== 'complete') ?? ($sections[0] ?? null);
    $alertSection = collect($sections)->first(fn (array $sec) => ($initialStepStates[$sec['id']] ?? 'incomplete') === 'error')
        ?? collect($sections)->first(fn (array $sec) => ($initialStepStates[$sec['id']] ?? 'incomplete') !== 'complete');
    $frontPreviewUrl = !$isCreate && !empty($voyage->post_name) && \Illuminate\Support\Facades\Route::has('front.voyages.show')
        ? route('front.voyages.show', $voyage->post_name)
        : null;
    $publicVoyagesBaseUrl = \Illuminate\Support\Facades\Route::has('front.voyages.index')
        ? route('front.voyages.index')
        : url('/voyages');
    $journeySubtitle = $isCreate
        ? 'Structurez la fiche, les departs et la logistique avant la premiere publication.'
        : 'Pilotez le contenu, les departs et la logistique depuis un seul studio de production.';
    $v3QuickActions = array_values(array_filter([
        ['label' => 'Infos générales', 'step' => 's-general', 'icon' => 'bx-file-blank'],
        ['label' => 'MÃ©dias', 'step' => 's-media', 'icon' => 'bx-image-alt'],
        ['label' => 'DisponibilitÃ©s', 'step' => 's-availability', 'icon' => 'bx-calendar'],
        ['label' => 'Vols', 'step' => 's-flights', 'icon' => 'bx-paper-plane'],
    ], fn (array $item) => collect($sections)->contains(fn (array $sec) => $sec['id'] === $item['step'])));

    // Espace Admin v2 « Voyage - Formulaire » : champs de l'étape 1 encore vides (rappel dans la colonne de gauche).
    $vfMissing = array_values(array_filter([
        trim((string) old('title', $voyage->post_title ?? '')) === '' ? 'titre' : null,
        trim((string) old('excerpt', $voyage->post_excerpt ?? '')) === '' ? 'accroche' : null,
        trim((string) old('destination', $veDestination ?? '')) === '' ? 'destination' : null,
        trim((string) old('tour_price_by', $meta['tour_price_by'] ?? '')) === '' ? 'tarification par' : null,
        trim((string) old('min_people', $meta['min_people'] ?? '')) === '' ? 'min. personnes' : null,
    ]));
    // Étapes dont le partial rend lui-même ses colonnes (mise en page issue des maquettes).
    $vfOwnLayoutSteps = ['s-general', 's-hotels', 's-activities'];
    $vfBrandName = \App\Models\Setting::getValue('brand_name', 'Ajinsafro');
    $vfBaseDisplay = preg_replace('#^https?://#', '', rtrim((string) $publicVoyagesBaseUrl, '/')) . '/';
    $cssVf = file_exists(public_path('css/voyage-form-v2.css')) ? (string) filemtime(public_path('css/voyage-form-v2.css')) : '1';
    $jsVf = file_exists(public_path('js/voyage-form-v2.js')) ? (string) filemtime(public_path('js/voyage-form-v2.js')) : '1';
    $vfStatusClass = 'is-' . (in_array($postStatus, ['publish', 'draft', 'pending', 'private'], true) ? $postStatus : 'draft');
@endphp
@extends($agentVoyageMode ? 'layouts.master-ajinsafro' : 'layouts.admin-v6')

@section('title'){{ $isCreate ? 'Creer un voyage - Studio V3' : 'Modifier - ' . $headerTitle }}@endsection

@push('styles')
    @if($agentVoyageMode)
        <link href="{{ URL::asset('css/agent-dashboard.css') }}" rel="stylesheet" type="text/css" />
    @endif
    <link href="{{ URL::asset('css/voyage-edit.css?v=' . $cssEdit) }}" rel="stylesheet">
    <link href="{{ URL::asset('css/flight-options-new.css') }}" rel="stylesheet">
    <link href="{{ URL::asset('css/voyage-v2.css?v=' . $cssV2) }}" rel="stylesheet">
    <link href="{{ URL::asset('css/voyage-v3.css?v=' . $cssV3) }}" rel="stylesheet">
    <link href="{{ URL::asset('css/voyage-form-v2.css?v=' . $cssVf) }}" rel="stylesheet">
    <style>
        .voyage-edit-v2-page.workflow-collapsed .v3-workspace,
        .voyage-edit-v2-page.workflow-collapsed .v2-body,
        .voyage-edit-v2-page.workflow-collapsed .workflow-layout,
        .voyage-edit-v2-page.workflow-collapsed .voyage-editor-grid {
            grid-template-columns: 64px minmax(0, 1fr) !important;
        }

        .voyage-edit-v2-page.workflow-collapsed .v2-sidebar,
        .voyage-edit-v2-page.workflow-collapsed .workflow-sidebar {
            width: 64px !important;
            min-width: 64px !important;
            max-width: 64px !important;
            overflow: hidden !important;
        }

        .voyage-edit-v2-page.workflow-collapsed .v3-steps-card {
            padding: 10px !important;
            border-radius: 16px !important;
        }

        .voyage-edit-v2-page.workflow-collapsed .v3-steps-card__head {
            display: flex !important;
            justify-content: center !important;
            margin-bottom: 6px !important;
            padding: 0 !important;
        }
        .voyage-edit-v2-page.workflow-collapsed .v3-steps-card__title {
            display: none !important;
        }

        .voyage-edit-v2-page.workflow-collapsed .v3-card-kicker,
        .voyage-edit-v2-page.workflow-collapsed .v3-card-title,
        .voyage-edit-v2-page.workflow-collapsed .v3-card-subtitle,
        .voyage-edit-v2-page.workflow-collapsed .v2-sb-group,
        .voyage-edit-v2-page.workflow-collapsed .v3-stepper__group,
        .voyage-edit-v2-page.workflow-collapsed .v2-sb-label-wrap,
        .voyage-edit-v2-page.workflow-collapsed .v2-sb-label,
        .voyage-edit-v2-page.workflow-collapsed .v2-sb-meta,
        .voyage-edit-v2-page.workflow-collapsed .v2-sb-dot,
        .voyage-edit-v2-page.workflow-collapsed .v3-step__label,
        .voyage-edit-v2-page.workflow-collapsed .v3-step__badge,
        .voyage-edit-v2-page.workflow-collapsed .workflow-title,
        .voyage-edit-v2-page.workflow-collapsed .workflow-description,
        .voyage-edit-v2-page.workflow-collapsed .workflow-section-label,
        .voyage-edit-v2-page.workflow-collapsed .workflow-step-title,
        .voyage-edit-v2-page.workflow-collapsed .workflow-step-status,
        .voyage-edit-v2-page.workflow-collapsed .step-title,
        .voyage-edit-v2-page.workflow-collapsed .step-status {
            display: none !important;
        }

        .voyage-edit-v2-page.workflow-collapsed .v2-sb-item,
        .voyage-edit-v2-page.workflow-collapsed .workflow-step,
        .voyage-edit-v2-page.workflow-collapsed .workflow-step-item,
        .voyage-edit-v2-page.workflow-collapsed .voyage-step-item {
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            justify-content: center !important;
            width: 44px !important;
            min-height: 40px !important;
            padding: 4px !important;
            margin-left: auto !important;
            margin-right: auto !important;
            gap: 2px !important;
        }

        .voyage-edit-v2-page.workflow-collapsed .v3-step__icon {
            margin: 0 !important;
            flex: 0 0 auto !important;
            width: 22px !important;
            height: 22px !important;
            border-radius: 6px !important;
        }
        .voyage-edit-v2-page.workflow-collapsed .v3-step__icon i {
            font-size: 14px !important;
        }

        .voyage-edit-v2-page.workflow-collapsed .v2-sb-footer {
            display: block !important;
            margin-top: 6px !important;
            padding-top: 6px !important;
            border-top: 1px solid rgba(180,210,240,0.35) !important;
        }
        .voyage-edit-v2-page.workflow-collapsed .v2-sb-save {
            min-height: 32px !important;
            padding: 4px !important;
            font-size: 0 !important;
            border-radius: 8px !important;
        }
        .voyage-edit-v2-page.workflow-collapsed .v2-sb-save i {
            font-size: 16px !important;
            margin: 0 !important;
        }
        .voyage-edit-v2-page.workflow-collapsed .v2-sb-save span {
            display: none !important;
        }

        .voyage-edit-v2-page:not(.workflow-collapsed) .v3-workspace,
        .voyage-edit-v2-page:not(.workflow-collapsed) .v2-body,
        .voyage-edit-v2-page:not(.workflow-collapsed) .workflow-layout,
        .voyage-edit-v2-page:not(.workflow-collapsed) .voyage-editor-grid {
            grid-template-columns: 210px minmax(0, 1fr) !important;
        }

        .voyage-edit-v2-page:not(.workflow-collapsed) .v2-sidebar,
        .voyage-edit-v2-page:not(.workflow-collapsed) .workflow-sidebar {
            width: 210px !important;
            min-width: 210px !important;
            max-width: 210px !important;
        }

        .voyage-edit-v2-page:not(.workflow-collapsed) .v2-sb-label,
        .voyage-edit-v2-page:not(.workflow-collapsed) .workflow-step-title,
        .voyage-edit-v2-page:not(.workflow-collapsed) .step-title,
        .voyage-edit-v2-page:not(.workflow-collapsed) .workflow-step-status,
        .voyage-edit-v2-page:not(.workflow-collapsed) .step-status {
            display: block !important;
            white-space: normal !important;
            overflow: visible !important;
            text-overflow: unset !important;
        }

        .voyage-edit-v2-page:not(.workflow-collapsed) .v2-sb-meta {
            display: block !important;
        }

        .voyage-edit-v2-page .workflow-toggle-btn {
            width: 36px;
            height: 36px;
            border: 1px solid #d8e5f2;
            border-radius: 10px;
            background: #fff;
            color: #314865;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
        }

        .voyage-edit-v2-page .workflow-toggle-btn i {
            font-size: 20px;
        }

        .voyage-edit-v2-page .v3-steps-card__head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 10px;
        }

        /* Destination modals (V2 only) */
        .voyage-edit-v2-page .destination-modal-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            padding: 12px;
            border: 1px solid rgba(180, 210, 240, 0.45);
            border-radius: 14px;
            background: #f8fbff;
        }

        .voyage-edit-v2-page .destination-modal-actions .btn {
            border-radius: 12px;
            min-height: 38px;
            padding: 0 14px;
            font-weight: 600;
        }

        .voyage-edit-v2-page .destination-ux-chip--readonly {
            padding-right: 10px;
        }

        .voyage-edit-v2-page .destination-modal .modal-content {
            border-radius: 18px;
            border: 1px solid rgba(180, 210, 240, 0.6);
            box-shadow: 0 18px 44px rgba(20, 40, 70, 0.16);
        }

        .modal.destination-modal {
            z-index: 1065;
        }

        .voyage-edit-v2-page .destination-modal .modal-header {
            border-bottom: 1px solid rgba(180, 210, 240, 0.35);
            background: linear-gradient(180deg, #ffffff, #f7fbff);
        }

        .voyage-edit-v2-page .destination-modal .modal-title {
            font-weight: 600;
            letter-spacing: -0.01em;
        }

        .voyage-edit-v2-page .destination-modal .modal-body {
            padding: 18px 20px;
        }

        .voyage-edit-v2-page .destination-modal .modal-footer {
            border-top: 1px solid rgba(180, 210, 240, 0.35);
            background: #fbfdff;
        }

        .voyage-edit-v2-page .destination-modal .destination-country-list {
            max-height: 52vh;
            overflow: auto;
            border-radius: 14px;
            border: 1px solid rgba(180, 210, 240, 0.35);
            background: #f8fbff;
            padding: 12px;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
            gap: 8px;
        }

        .voyage-edit-v2-page .destination-modal .destination-cities-list-wrapper {
            max-height: 58vh;
            overflow: auto;
            border-radius: 14px;
            border: 1px solid rgba(180, 210, 240, 0.35);
            background: #f8fbff;
            padding: 12px;
        }

        .voyage-edit-v2-page .destination-modal .destination-country-option-label,
        .voyage-edit-v2-page .destination-modal .destination-city-checkbox-label,
        .voyage-edit-v2-page .destination-modal .destination-country-checkbox-label {
            min-height: 38px;
            display: flex;
            align-items: center;
            gap: 9px;
            margin: 0;
            padding: 8px 10px;
            border: 1px solid #e2ebf5;
            border-radius: 12px;
            background: #fff;
            color: #50627f;
            font-size: 13px;
            line-height: 1.25;
            cursor: pointer;
            transition: border-color .15s ease, background-color .15s ease, color .15s ease;
        }

        .voyage-edit-v2-page .destination-modal .destination-country-option-label:hover,
        .voyage-edit-v2-page .destination-modal .destination-city-checkbox-label:hover,
        .voyage-edit-v2-page .destination-modal .destination-country-checkbox-label:hover {
            border-color: #9bc9ff;
            background: #f2f8ff;
            color: #123a66;
        }

        .voyage-edit-v2-page .destination-modal input[type="checkbox"] {
            width: 16px;
            height: 16px;
            flex: 0 0 16px;
            margin: 0;
            accent-color: #0d6efd;
        }

        .voyage-edit-v2-page .destination-modal .destination-country-option-label:has(input:checked),
        .voyage-edit-v2-page .destination-modal .destination-city-checkbox-label:has(input:checked),
        .voyage-edit-v2-page .destination-modal .destination-country-checkbox-label:has(input:checked) {
            border-color: #6aaefb;
            background: #eaf4ff;
            color: #0b5ed7;
            font-weight: 600;
        }

        .voyage-edit-v2-page .destination-modal .destination-country-add-wrap,
        .voyage-edit-v2-page .destination-modal .destination-city-autocomplete-wrap {
            flex: 1 1 260px;
        }

        .voyage-edit-v2-page .destination-modal .destination-country-search,
        .voyage-edit-v2-page .destination-modal .destination-country-add-search,
        .voyage-edit-v2-page .destination-modal .destination-city-search,
        .voyage-edit-v2-page .destination-modal .destination-city-add-search {
            min-height: 40px;
            border-radius: 12px;
            border-color: #dce8f5;
            font-size: 13px;
        }

        .voyage-edit-v2-page .destination-modal .destination-country-multi-actions,
        .voyage-edit-v2-page .destination-modal .destination-cities-panel-actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
        }

        .voyage-edit-v2-page .destination-modal .destination-country-multi-actions .btn,
        .voyage-edit-v2-page .destination-modal .destination-cities-panel-actions .btn,
        .voyage-edit-v2-page .destination-modal .modal-footer .btn {
            min-height: 36px;
            border-radius: 11px;
            padding: 0 13px;
            font-size: 13px;
            font-weight: 600;
        }

        .voyage-edit-v2-page .destination-modal .destination-country-block {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
            gap: 8px;
            margin-bottom: 14px;
        }

        .voyage-edit-v2-page .destination-modal .destination-country-checkbox-label {
            grid-column: 1 / -1;
            background: #f5f0ff;
            border-color: #dac8ff;
            color: #6941c6;
            font-weight: 700;
        }

        .voyage-edit-v2-page .destination-modal .destination-city-path {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .voyage-edit-v2-page .destination-modal .destination-cities-panel-header {
            align-items: flex-start !important;
            padding-bottom: 12px;
            border-bottom: 1px solid #e8f0f8;
            margin-bottom: 12px !important;
        }

        .voyage-edit-v2-page .destination-modal .destination-cities-panel-title,
        .voyage-edit-v2-page .destination-modal .form-label {
            color: #253a57;
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        /* Destination taxonomy modals: final edit-v2 UI */
        .edit-v2-taxonomy-modal .modal-dialog {
            max-width: 1100px !important;
        }

        .edit-v2-taxonomy-modal--cities .modal-dialog {
            max-width: 1050px !important;
        }

        .edit-v2-taxonomy-modal .modal-content {
            max-height: 82vh !important;
            display: flex !important;
            overflow: hidden !important;
            border: 1px solid #e4edf7 !important;
            border-radius: 20px !important;
            background: #fff !important;
            box-shadow: 0 24px 70px rgba(15, 39, 66, .18) !important;
        }

        .edit-v2-taxonomy-modal--cities .modal-content {
            max-height: 75vh !important;
        }

        .edit-v2-taxonomy-modal .modal-header {
            flex: 0 0 auto !important;
            padding: 18px 22px !important;
            border-bottom: 1px solid #edf3f8 !important;
            background: #fff !important;
        }

        .edit-v2-taxonomy-modal .modal-title {
            color: #0f2742 !important;
            font-size: 20px !important;
            font-weight: 600 !important;
            letter-spacing: -0.015em !important;
        }

        .edit-v2-taxonomy-modal .modal-header .small {
            color: #60758d !important;
            font-size: 12px !important;
            font-weight: 400 !important;
        }

        .edit-v2-taxonomy-modal .modal-body {
            flex: 1 1 auto !important;
            min-height: 0 !important;
            padding: 0 !important;
            overflow: hidden !important;
        }

        .edit-v2-taxonomy-modal .destination-country-modal-panel,
        .edit-v2-taxonomy-modal .destination-country-multi-wrap,
        .edit-v2-taxonomy-modal .destination-cities-panel {
            height: 100% !important;
            margin: 0 !important;
        }

        .edit-v2-taxonomy-modal .taxonomy-toolbar {
            position: sticky !important;
            top: 0 !important;
            z-index: 5 !important;
            padding: 14px 18px !important;
            border-bottom: 1px solid #edf3f8 !important;
            background: #fff !important;
        }

        .edit-v2-taxonomy-modal .taxonomy-section-label {
            margin-bottom: 10px !important;
            color: #52657c !important;
            font-size: 12px !important;
            font-weight: 600 !important;
            letter-spacing: .01em !important;
        }

        .edit-v2-taxonomy-modal .taxonomy-toolbar-row {
            display: grid !important;
            grid-template-columns: minmax(260px, 1fr) minmax(220px, .7fr) auto auto !important;
            gap: 10px !important;
            align-items: center !important;
            margin: 0 !important;
        }

        .edit-v2-taxonomy-modal .taxonomy-toolbar input {
            width: 100% !important;
            height: 38px !important;
            min-height: 38px !important;
            border: 1px solid #dbe7f3 !important;
            border-radius: 12px !important;
            padding: 0 12px !important;
            color: #0f2742 !important;
            font-size: 12px !important;
            font-weight: 400 !important;
            outline: none !important;
        }

        .edit-v2-taxonomy-modal .taxonomy-toolbar input:focus {
            border-color: #0081bc !important;
            box-shadow: 0 0 0 3px rgba(0,129,188,.10) !important;
        }

        .edit-v2-taxonomy-modal .taxonomy-toolbar .btn,
        .edit-v2-taxonomy-modal .modal-footer .btn {
            height: 38px !important;
            min-height: 38px !important;
            border-radius: 12px !important;
            padding: 0 14px !important;
            font-size: 12px !important;
            font-weight: 600 !important;
            white-space: nowrap !important;
        }

        .edit-v2-taxonomy-modal .taxonomy-scroll {
            max-height: 58vh !important;
            overflow-y: auto !important;
            padding: 18px !important;
            border: 0 !important;
            border-radius: 0 !important;
            background: #f8fbff !important;
        }

        .edit-v2-taxonomy-modal--cities .taxonomy-scroll {
            max-height: 51vh !important;
        }

        .edit-v2-taxonomy-modal .taxonomy-grid {
            display: grid !important;
            grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
            gap: 10px !important;
        }

        .edit-v2-taxonomy-modal .taxonomy-option {
            display: flex !important;
            align-items: center !important;
            gap: 9px !important;
            min-height: 42px !important;
            margin: 0 !important;
            padding: 10px 12px !important;
            border: 1px solid #e4edf7 !important;
            border-radius: 12px !important;
            background: #fff !important;
            color: #243b53 !important;
            font-size: 12px !important;
            font-weight: 500 !important;
            line-height: 1.25 !important;
            cursor: pointer !important;
            transition: border-color .18s ease, background-color .18s ease, color .18s ease, box-shadow .18s ease !important;
        }

        .edit-v2-taxonomy-modal .taxonomy-option:hover {
            border-color: #0081bc !important;
            background: #f0f9ff !important;
        }

        .edit-v2-taxonomy-modal .taxonomy-option input[type="checkbox"] {
            width: 15px !important;
            height: 15px !important;
            flex: 0 0 15px !important;
            margin: 0 !important;
            accent-color: #0081bc !important;
        }

        .edit-v2-taxonomy-modal .taxonomy-option.is-selected,
        .edit-v2-taxonomy-modal .taxonomy-option:has(input:checked) {
            border-color: #0081bc !important;
            background: #eaf7ff !important;
            color: #005f91 !important;
            box-shadow: 0 8px 18px rgba(0, 129, 188, .08) !important;
        }

        .edit-v2-taxonomy-modal .destination-city-path,
        .edit-v2-taxonomy-modal .taxonomy-option span {
            min-width: 0 !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            white-space: nowrap !important;
            font-weight: inherit !important;
        }

        .edit-v2-taxonomy-modal .city-country-group {
            display: block !important;
            margin-bottom: 16px !important;
            overflow: hidden !important;
            border: 1px solid #e4edf7 !important;
            border-radius: 16px !important;
            background: #fff !important;
        }

        .edit-v2-taxonomy-modal .city-country-header {
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            gap: 12px !important;
            padding: 12px 14px !important;
            border-bottom: 1px solid #e4edf7 !important;
            background: #f7faff !important;
        }

        .edit-v2-taxonomy-modal .city-country-header strong {
            color: #0f2742 !important;
            font-size: 13px !important;
            font-weight: 600 !important;
        }

        .edit-v2-taxonomy-modal .city-country-header span {
            color: #71829a !important;
            font-size: 11px !important;
            font-weight: 500 !important;
            white-space: nowrap !important;
        }

        .edit-v2-taxonomy-modal .country-full-option {
            margin: 14px 14px 0 !important;
            background: #f5fbff !important;
            color: #005f91 !important;
        }

        .edit-v2-taxonomy-modal .city-grid {
            display: grid !important;
            grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
            gap: 10px !important;
            padding: 14px !important;
        }

        .edit-v2-taxonomy-modal .taxonomy-empty-state {
            margin: 14px !important;
            padding: 14px !important;
            border: 1px dashed #d9e6f3 !important;
            border-radius: 12px !important;
            color: #71829a !important;
            font-size: 12px !important;
            font-weight: 400 !important;
        }

        .edit-v2-taxonomy-modal .modal-footer {
            flex: 0 0 auto !important;
            display: flex !important;
            align-items: center !important;
            gap: 10px !important;
            padding: 14px 18px !important;
            border-top: 1px solid #edf3f8 !important;
            background: #f8fafc !important;
        }

        .edit-v2-taxonomy-modal .taxonomy-selection-count {
            color: #243b53 !important;
            font-size: 12px !important;
            font-weight: 500 !important;
        }

        .edit-v2-taxonomy-modal .taxonomy-footer-spacer {
            flex: 1 1 auto !important;
        }

        .edit-v2-taxonomy-modal .destination-country-autocomplete-dropdown,
        .edit-v2-taxonomy-modal .destination-city-autocomplete-dropdown {
            position: absolute !important;
            top: calc(100% + 6px) !important;
            left: 0 !important;
            right: 0 !important;
            z-index: 20 !important;
            display: none !important;
            max-height: 260px !important;
            overflow-y: auto !important;
            border: 1px solid #dbe7f3 !important;
            border-radius: 14px !important;
            background: #fff !important;
            box-shadow: 0 18px 40px rgba(15, 39, 66, .14) !important;
        }

        .edit-v2-taxonomy-modal .destination-country-autocomplete-dropdown.is-open,
        .edit-v2-taxonomy-modal .destination-city-autocomplete-dropdown.is-open {
            display: block !important;
        }

        .edit-v2-taxonomy-modal .destination-country-autocomplete-item,
        .edit-v2-taxonomy-modal .destination-city-autocomplete-item {
            padding: 10px 12px !important;
            color: #243b53 !important;
            font-size: 12px !important;
            font-weight: 500 !important;
            cursor: pointer !important;
        }

        .edit-v2-taxonomy-modal .destination-country-autocomplete-item:hover,
        .edit-v2-taxonomy-modal .destination-city-autocomplete-item:hover {
            background: #f0f9ff !important;
            color: #005f91 !important;
        }

        @media (max-width: 1366px) {
            .edit-v2-taxonomy-modal .taxonomy-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
            }

            .edit-v2-taxonomy-modal .city-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }
        }

        @media (max-width: 768px) {
            .edit-v2-taxonomy-modal .taxonomy-toolbar-row {
                grid-template-columns: 1fr !important;
            }

            .edit-v2-taxonomy-modal .taxonomy-grid,
            .edit-v2-taxonomy-modal .city-grid {
                grid-template-columns: 1fr !important;
            }
        }
    </style>
@endpush

@section('content')
<div class="v2-page vf-page voyage-edit-page voyage-edit-v2-page" id="v2-main"
     data-v2-initial-id="{{ $veWpId }}"
     data-v2-save-create-url="{{ $saveCreateUrl }}"
     data-v2-save-update-template="{{ $saveUpdateTemplate }}"
     data-v2-is-create="{{ $isCreate ? '1' : '0' }}"
     data-v3-public-base-url="{{ $publicVoyagesBaseUrl }}"
     data-vf-brand="{{ $vfBrandName }}"
     data-vf-base-display="{{ $vfBaseDisplay }}">

    {{-- ═══ Sous-en-tête collant : titre, workflow, étapes ═══ --}}
    <div class="vf-subhead">
        <div class="vf-subhead__inner">
            <div class="vf-subhead__row">
                <div class="vf-subhead__title">
                    <div class="vf-subhead__meta">
                        <a href="{{ $voyageBackUrl }}" class="vf-back">← Catalogue voyages</a>
                        <span class="vf-status {{ $vfStatusClass }}" id="v2-live-status">{{ $statusLabel }}</span>
                        <span class="vf-ref" id="v2-live-subtitle">{{ $isCreate ? 'Brouillon à créer au premier enregistrement' : 'ID #' . $veWpId }}</span>
                    </div>
                    <h1 class="vf-h1" id="v2-live-title">{{ $headerTitle }}</h1>
                </div>
                <div class="vf-workflow">
                    <div class="vf-workflow__head">
                        <span class="vf-kicker">Workflow</span>
                        <span class="vf-workflow__count" id="v2-progress-text">{{ $completedSteps }} / {{ $sectionsCount }} étapes validées</span>
                    </div>
                    <div class="vf-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $progressPercent }}"><span id="v2-progress-bar" style="width:{{ $progressPercent }}%"></span></div>
                    <div class="vf-workflow__actions">
                        @if($frontPreviewUrl)
                            <a href="{{ $frontPreviewUrl }}" target="_blank" rel="noopener" class="vf-btn vf-btn--outline">Aperçu public</a>
                        @else
                            <span class="vf-btn vf-btn--outline" style="opacity:.55; cursor:default" title="Disponible après le premier enregistrement">Aperçu public</span>
                        @endif
                        <button type="button" class="vf-btn vf-btn--accent" data-v2-save><span>{{ $isCreate ? 'Créer le voyage' : 'Enregistrer' }}</span></button>
                    </div>
                </div>
            </div>

            <div class="vf-steps" role="list" aria-label="Étapes du voyage">
                @foreach($sections as $i => $sec)
                    @php $stepState = $initialStepStates[$sec['id']] ?? 'incomplete'; @endphp
                    <button type="button" class="vf-step state-{{ $stepState }}{{ $i === 0 ? ' active' : '' }}" data-v2-nav="{{ $sec['id'] }}" data-v2-step-state="{{ $stepState }}" title="{{ $sec['label'] }}" role="listitem">
                        <span class="vf-step__dot"><span class="vf-step__num">{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</span><i class="bx bx-check" aria-hidden="true"></i></span>
                        <span class="vf-step__label">{{ $sec['label'] }}</span>
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ═══ Messages ═══ --}}
    <div class="vf-alerts">
        @if(session('success'))
            <div class="vf-alert vf-alert--ok"><i class="bx bx-check-circle"></i><div>{{ session('success') }}</div></div>
        @endif
        @if($errors->any())
            <div class="vf-alert vf-alert--err"><i class="bx bx-error-circle"></i><div><strong>Corrections requises :</strong><ul>@foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul></div></div>
        @endif
        <div class="vf-alert vf-alert--err d-none" id="v2-step-errors"></div>
    </div>

    {{-- ═══ Grille : navigation de l'étape | contenu | colonne droite ═══ --}}
    <div class="vf-grid">
        <aside class="vf-aside" aria-label="Navigation dans l'étape">
            @foreach($sections as $i => $sec)
                <div class="vf-aside-block" data-vf-for="{{ $sec['id'] }}">
                    <div class="vf-aside__kicker">Étape {{ $i + 1 }} · {{ $sec['id'] === 's-general' ? 'sections' : $sec['group'] }}</div>
                    @if($sec['id'] === 's-general')
                        <a href="#sec-fiche" class="vf-aside-link is-active" data-vf-section="sec-fiche">Fiche commerciale<span class="vf-aside-link__meta {{ in_array('titre', $vfMissing, true) ? 'is-warn' : 'is-ok' }}">{{ in_array('titre', $vfMissing, true) ? 'titre' : '✓' }}</span></a>
                        <a href="#sec-seo" class="vf-aside-link" data-vf-section="sec-seo">SEO &amp; URL<span class="vf-aside-link__meta {{ trim((string) old('slug', $voyage->post_name ?? '')) !== '' ? 'is-ok' : '' }}">{{ trim((string) old('slug', $voyage->post_name ?? '')) !== '' ? '✓' : 'auto' }}</span></a>
                        <a href="#sec-presentation" class="vf-aside-link" data-vf-section="sec-presentation">Présentation<span class="vf-aside-link__meta {{ trim(strip_tags((string) old('content', $voyage->post_content ?? ''))) !== '' ? 'is-ok' : '' }}">{{ trim(strip_tags((string) old('content', $voyage->post_content ?? ''))) !== '' ? '✓' : 'vide' }}</span></a>
                        <a href="#sec-medias" class="vf-aside-link" data-vf-section="sec-medias">Médias<span class="vf-aside-link__meta {{ !empty($heroImageUrl) ? 'is-ok' : '' }}">{{ !empty($heroImageUrl) ? '✓' : '0' }}</span></a>
                        <a href="#sec-reglages" class="vf-aside-link" data-vf-section="sec-reglages">Réglages<span class="vf-aside-link__meta {{ count(array_intersect($vfMissing, ['tarification par', 'min. personnes'])) > 0 ? 'is-warn' : 'is-ok' }}">{{ count(array_intersect($vfMissing, ['tarification par', 'min. personnes'])) > 0 ? count(array_intersect($vfMissing, ['tarification par', 'min. personnes'])) : '✓' }}</span></a>
                        @if($vfMissing !== [])
                            <div class="vf-aside-callout">
                                <div class="vf-aside-callout__title">{{ count($vfMissing) }} champ{{ count($vfMissing) > 1 ? 's' : '' }} à compléter</div>
                                <div class="vf-aside-callout__text">{{ \Illuminate\Support\Str::ucfirst(implode(', ', $vfMissing)) }}</div>
                            </div>
                        @else
                            <div class="vf-aside-callout is-ok">
                                <div class="vf-aside-callout__title">Fiche complète</div>
                                <div class="vf-aside-callout__text">Passez aux tarifs et à la capacité.</div>
                            </div>
                        @endif
                    @else
                        <div class="vf-aside-card">
                            <div class="vf-aside-card__title">{{ $sec['title'] }}</div>
                            <div class="vf-aside-card__text">{{ $sec['desc'] }}</div>
                        </div>
                        @if(isset($sections[$i - 1]))
                            <button type="button" class="vf-aside-link" data-v2-prev="{{ $sections[$i - 1]['id'] }}">← {{ $sections[$i - 1]['label'] }}</button>
                        @endif
                        @if(isset($sections[$i + 1]))
                            <button type="button" class="vf-aside-link" data-v2-next="{{ $sections[$i + 1]['id'] }}">{{ $sections[$i + 1]['label'] }} →</button>
                        @endif
                    @endif
                </div>
            @endforeach
        </aside>

        <form id="{{ $formId }}" class="vf-form" action="{{ $formAction }}" method="POST" data-voyage-id="{{ $veWpId }}" data-v2-current-step="s-general" novalidate>
            @csrf
            @if(!$isCreate) @method('PUT') @endif
            <input type="hidden" name="current_step" value="s-general">
            <input type="hidden" name="redirect_step" value="s-general">
            <input type="hidden" name="v2_save_mode" value="manual">
            <input type="hidden" name="voyage_id" value="{{ $veWpId }}">
            <textarea name="programme_days_payload" id="programme-days-payload" class="d-none" aria-hidden="true"></textarea>

            @foreach($sections as $index => $sec)
                @php
                    $prev = $sections[$index - 1] ?? null;
                    $next = $sections[$index + 1] ?? null;
                @endphp
                <section class="v2-panel vf-panel{{ $index === 0 ? ' active' : '' }}" id="{{ $sec['id'] }}">
                    @if(in_array($sec['id'], $vfOwnLayoutSteps, true))
                        {{-- Étapes dessinées : le partial rend lui-même ses deux colonnes. --}}
                        @include('admin.circuits.voyages.partials.' . $sec['partial'])
                    @else
                        <div class="vf-col-main">
                            <section class="vf-card">
                                <div class="vf-card__head">
                                    <span class="vf-card__num">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                                    <div style="min-width:0">
                                        <h2 class="vf-card__title">{{ $sec['title'] }}</h2>
                                        <p class="vf-card__desc">{{ $sec['desc'] }}</p>
                                    </div>
                                </div>
                                <div class="vf-card__body vf-card__body--legacy">@include('admin.circuits.voyages.partials.' . $sec['partial'])</div>
                            </section>
                            @if($sec['id'] === 's-logistics' && !$isCreate && !$agentVoyageMode)
                                <div class="vf-danger">
                                    <div>
                                        <div class="vf-danger__title">Suppression définitive</div>
                                        <div class="vf-danger__desc">Action irréversible : le voyage et sa fiche publique sont supprimés.</div>
                                    </div>
                                    <button type="submit" form="v2-delete-form" class="vf-btn vf-btn--danger" onclick="return confirm('Supprimer définitivement ce voyage ?')">Supprimer le voyage</button>
                                </div>
                            @endif
                        </div>
                        @include('admin.circuits.voyages.partials.v2._side_quick')
                    @endif

                    {{-- Barre d'actions fixe de l'étape --}}
                    <div class="vf-bottombar">
                        <div class="vf-bottombar__inner">
                            <span class="vf-bottombar__status" data-state="idle"><span data-vf-save-help>Modifiez un champ pour activer la sauvegarde d'étape.</span> · <b data-vf-save-state>Prêt</b></span>
                            <div class="vf-bottombar__actions">
                                @if($prev)
                                    <button type="button" class="vf-btn vf-btn--ghost" data-v2-prev="{{ $prev['id'] }}">← {{ $prev['label'] }}</button>
                                @endif
                                <button type="button" class="vf-btn vf-btn--accent" data-v2-save><span>Enregistrer cette étape</span></button>
                                @if($next)
                                    <button type="button" class="vf-btn vf-btn--primary" data-v2-next="{{ $next['id'] }}">{{ $next['label'] }} →</button>
                                @endif
                            </div>
                        </div>
                    </div>
                </section>
            @endforeach
        </form>
    </div>

    {{-- Bloc d'état alimenté par voyage-v2.js, recopié dans les barres d'actions par voyage-form-v2.js --}}
    <div id="v2-save-card" data-state="idle" hidden><span id="v2-save-state">Prêt</span><span id="v2-save-help">Modifiez un champ pour activer la sauvegarde d'étape.</span></div>

    @if(!$isCreate && !$agentVoyageMode)
        <form id="v2-delete-form" action="{{ route('admin.circuits.voyages.destroy', $voyage->ID) }}" method="POST" class="d-none">@csrf @method('DELETE')</form>
    @endif
</div>
@endsection

@push('scripts')
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
    <script src="{{ URL::asset('js/voyage-form-v2.js?v=' . $jsVf) }}"></script>
@endpush
