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
    $statusLabels = ['publish' => 'Publie', 'draft' => 'Brouillon', 'pending' => 'En attente', 'private' => 'Archive'];
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
        ['id' => 's-general', 'icon' => 'bx-file-blank', 'label' => 'Infos generales', 'group' => 'Fiche produit', 'partial' => 'tabs._basic', 'eyebrow' => 'Fiche produit', 'title' => 'Informations generales', 'desc' => 'Titre, publication et presentation publique du voyage.'],
        ['id' => 's-pricing', 'icon' => 'bx-euro', 'label' => 'Tarifs & capacite', 'group' => 'Fiche produit', 'partial' => 'tabs._pricing', 'eyebrow' => 'Fiche produit', 'title' => 'Tarifs & capacite', 'desc' => 'Prix publics et parametres commerciaux.'],
        ['id' => 's-location', 'icon' => 'bx-map-pin', 'label' => 'Destination', 'group' => 'Fiche produit', 'partial' => 'tabs._location', 'eyebrow' => 'Fiche produit', 'title' => 'Destination', 'desc' => 'Localisations geographiques et informations de contact.'],
        ['id' => 's-media', 'icon' => 'bx-image-alt', 'label' => 'Medias', 'group' => 'Fiche produit', 'partial' => 'tabs._media', 'eyebrow' => 'Fiche produit', 'title' => 'Medias', 'desc' => 'Hero, image a la une et galeries.'],
        ['id' => 's-programme', 'icon' => 'bx-calendar-check', 'label' => 'Programme', 'group' => 'Contenu', 'partial' => 'tabs._programme', 'eyebrow' => 'Contenu', 'title' => 'Programme du circuit', 'desc' => 'Detail jour par jour du voyage.'],
        ['id' => 's-information', 'icon' => 'bx-list-ul', 'label' => 'Inclus / Exclus', 'group' => 'Contenu', 'partial' => 'tabs._information', 'eyebrow' => 'Contenu', 'title' => 'Inclus / Exclus / FAQ', 'desc' => 'Informations commerciales et pratiques.'],
        ['id' => 's-taxonomies', 'icon' => 'bx-tag', 'label' => 'Categories', 'group' => 'Contenu', 'partial' => 'tabs._taxonomies', 'eyebrow' => 'Contenu', 'title' => 'Categories & tags', 'desc' => 'Classement pour catalogue et SEO.'],
        ['id' => 's-availability', 'icon' => 'bx-calendar', 'label' => 'Disponibilites', 'group' => 'Exploitation', 'partial' => 'tabs._availability', 'eyebrow' => 'Exploitation', 'title' => 'Disponibilites', 'desc' => 'Dates, stock et parametres de reservation.'],
        ['id' => 's-flights', 'icon' => 'bx-paper-plane', 'label' => 'Vols', 'group' => 'Logistique', 'partial' => 'tabs._flights', 'eyebrow' => 'Logistique', 'title' => 'Vols', 'desc' => 'Compagnies, itineraires et options de vol.'],
        ['id' => 's-hotels', 'icon' => 'bx-hotel', 'label' => 'Hotels', 'group' => 'Logistique', 'partial' => 'tabs._hotels', 'eyebrow' => 'Logistique', 'title' => 'Hotels', 'desc' => 'Hebergements et allocations de chambres.'],
        ['id' => 's-transfers', 'icon' => 'bx-bus', 'label' => 'Transferts', 'group' => 'Logistique', 'partial' => 'tabs._transfers', 'eyebrow' => 'Logistique', 'title' => 'Transferts', 'desc' => 'Transferts arrivee / depart.'],
        ['id' => 's-activities', 'icon' => 'bx-run', 'label' => 'Activites', 'group' => 'Logistique', 'partial' => 'tabs._activities', 'eyebrow' => 'Logistique', 'title' => 'Activites', 'desc' => 'Catalogue des activites du voyage.'],
        ['id' => 's-extras', 'icon' => 'bx-star', 'label' => 'Extras', 'group' => 'Logistique', 'partial' => 'tabs._extras', 'eyebrow' => 'Logistique', 'title' => 'Supplements & extras', 'desc' => 'Options payantes complementaires.'],
        ['id' => 's-logistics', 'icon' => 'bx-cog', 'label' => 'Parametres', 'group' => 'Exploitation', 'partial' => 'tabs._logistics', 'eyebrow' => 'Exploitation', 'title' => 'Parametres avances', 'desc' => 'Reglages techniques et logistiques.'],
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
        ['label' => 'Infos generales', 'step' => 's-general', 'icon' => 'bx-file-blank'],
        ['label' => 'Medias', 'step' => 's-media', 'icon' => 'bx-image-alt'],
        ['label' => 'Disponibilites', 'step' => 's-availability', 'icon' => 'bx-calendar'],
        ['label' => 'Vols', 'step' => 's-flights', 'icon' => 'bx-paper-plane'],
    ], fn (array $item) => collect($sections)->contains(fn (array $sec) => $sec['id'] === $item['step'])));
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
<div class="v2-page voyage-edit-page voyage-edit-v2-page voyage-studio-v3" data-v2-initial-id="{{ $veWpId }}" data-v2-save-create-url="{{ $saveCreateUrl }}" data-v2-save-update-template="{{ $saveUpdateTemplate }}" data-v2-is-create="{{ $isCreate ? '1' : '0' }}" data-v3-public-base-url="{{ $publicVoyagesBaseUrl }}">
    <div class="v3-shell">
        @include('admin.circuits.voyages.partials.v3._hero', [
            'isCreate' => $isCreate,
            'headerTitle' => $headerTitle,
            'statusClass' => $statusClass,
            'statusLabel' => $statusLabel,
            'veWpId' => $veWpId,
            'heroImageUrl' => $heroImageUrl ?? null,
            'veDatesCount' => $veDatesCount,
            'vePriceLabel' => $vePriceLabel,
            'veDestination' => $veDestination,
            'journeySubtitle' => $journeySubtitle,
            'frontPreviewUrl' => $frontPreviewUrl,
            'nextActionSection' => $nextActionSection,
            'completedSteps' => $completedSteps,
            'sectionsCount' => $sectionsCount,
            'progressPercent' => $progressPercent,
        ])

        <div class="v2-body v3-workspace">
            @include('admin.circuits.voyages.partials.v3._steps', [
                'sections' => $sections,
                'initialStepStates' => $initialStepStates,
                'isCreate' => $isCreate,
            ])

            <main class="v2-main v3-main" id="v2-main">
                <div class="v3-editor-stack">
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
                        <input type="hidden" name="current_step" value="s-general">
                        <input type="hidden" name="redirect_step" value="s-general">
                        <input type="hidden" name="v2_save_mode" value="manual">
                        <input type="hidden" name="voyage_id" value="{{ $veWpId }}">
                        <textarea name="programme_days_payload" id="programme-days-payload" class="d-none" aria-hidden="true"></textarea>

                        @foreach($sections as $index => $sec)
                            @php $groupSlug = \Illuminate\Support\Str::slug($sec['group']); @endphp
                            <section class="v2-panel v3-panel{{ $index === 0 ? ' active' : '' }}" id="{{ $sec['id'] }}">
                                <div class="v2-section-hdr v3-panel-head">
                                    <div>
                                        <p class="v2-section-eyebrow">{{ $sec['eyebrow'] }}</p>
                                        <h1 class="v2-section-title">{{ $sec['title'] }}</h1>
                                        <p class="v2-section-desc">{{ $sec['desc'] }}</p>
                                    </div>
                                    <div class="v3-panel-head__meta">
                                        <span class="v3-step-chip">Etape {{ $index + 1 }} / {{ $sectionsCount }}</span>
                                        <span class="v3-step-chip v3-step-chip--group v3-step-chip--{{ $groupSlug }}">{{ $sec['group'] }}</span>
                                    </div>
                                </div>

                                <div class="v2-card v3-editor-card">
                                    @if($sec['id'] === 's-general')
                                        <div class="v3-editor-tabs" aria-hidden="true">
                                            <span class="v3-editor-tab is-active"><i class="bx bx-list-ul"></i>Fiche commerciale</span>
                                            <span class="v3-editor-tab"><i class="bx bx-link"></i>SEO & URL</span>
                                            <span class="v3-editor-tab"><i class="bx bx-detail"></i>Presentation</span>
                                            <span class="v3-editor-tab"><i class="bx bx-cog"></i>Reglages</span>
                                        </div>
                                    @endif
                                    <div class="v2-card-hdr">
                                        <div class="v2-card-icon"><i class="bx {{ $sec['icon'] }}"></i></div>
                                        <div class="v2-card-hdr-text"><p class="v2-card-hdr-title">{{ $sec['title'] }}</p></div>
                                    </div>
                                    <div class="v2-card-body">@include('admin.circuits.voyages.partials.' . $sec['partial'])</div>
                                </div>

                                @if($sec['id'] === 's-logistics' && !$isCreate)
                                    <div class="v2-danger-zone">
                                        <div>
                                            <p class="v2-danger-title"><i class="bx bx-error-circle me-1"></i>Suppression definitive</p>
                                            <p class="v2-danger-desc">Action irreversible.</p>
                                        </div>
                                        <button type="submit" form="v2-delete-form" class="v2-btn v2-btn-danger-ghost" onclick="return confirm('Supprimer definitivement ce voyage ?')"><i class="bx bx-trash"></i> Supprimer</button>
                                    </div>
                                @endif

                                @php
                                    $prev = $sections[$index - 1]['id'] ?? null;
                                    $next = $sections[$index + 1]['id'] ?? null;
                                    $prevLabel = $sections[$index - 1]['label'] ?? null;
                                    $nextLabel = $sections[$index + 1]['label'] ?? null;
                                @endphp
                                @include('admin.circuits.voyages.partials.v2._footer', compact('prev', 'next', 'prevLabel', 'nextLabel', 'formId'))
                            </section>
                        @endforeach
                    </form>
                </div>
            </main>

        </div>
    </div>

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
@endpush
