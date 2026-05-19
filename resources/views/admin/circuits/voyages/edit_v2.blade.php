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
        ? route('admin.circuits.voyages.store')
        : route('admin.circuits.voyages.update', $voyage->ID);

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
        ['id' => 's-flights', 'icon' => 'bx-trip', 'label' => 'Vols', 'group' => 'Logistique', 'partial' => 'tabs._flights', 'eyebrow' => 'Logistique', 'title' => 'Vols', 'desc' => 'Compagnies, itineraires et options de vol.'],
        ['id' => 's-hotels', 'icon' => 'bx-hotel', 'label' => 'Hotels', 'group' => 'Logistique', 'partial' => 'tabs._hotels', 'eyebrow' => 'Logistique', 'title' => 'Hotels', 'desc' => 'Hebergements et allocations de chambres.'],
        ['id' => 's-transfers', 'icon' => 'bx-bus', 'label' => 'Transferts', 'group' => 'Logistique', 'partial' => 'tabs._transfers', 'eyebrow' => 'Logistique', 'title' => 'Transferts', 'desc' => 'Transferts arrivee / depart.'],
        ['id' => 's-activities', 'icon' => 'bx-run', 'label' => 'Activites', 'group' => 'Logistique', 'partial' => 'tabs._activities', 'eyebrow' => 'Logistique', 'title' => 'Activites', 'desc' => 'Catalogue des activites du voyage.'],
        ['id' => 's-extras', 'icon' => 'bx-star', 'label' => 'Extras', 'group' => 'Logistique', 'partial' => 'tabs._extras', 'eyebrow' => 'Logistique', 'title' => 'Supplements & extras', 'desc' => 'Options payantes complementaires.'],
        ['id' => 's-logistics', 'icon' => 'bx-cog', 'label' => 'Parametres', 'group' => 'Exploitation', 'partial' => 'tabs._logistics', 'eyebrow' => 'Exploitation', 'title' => 'Parametres avances', 'desc' => 'Reglages techniques et logistiques.'],
    ];

    $initialStepStates = is_array($v2StepStates ?? null)
        ? $v2StepStates
        : collect($sections)->mapWithKeys(fn ($sec) => [$sec['id'] => 'incomplete'])->all();

    $saveCreateUrl = route('admin.circuits.voyages.v2.steps.save.create', ['step' => '__STEP__']);
    $saveUpdateTemplate = route('admin.circuits.voyages.v2.steps.save', ['id' => 999999, 'step' => '__STEP__']);
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
        ['label' => 'Vols', 'step' => 's-flights', 'icon' => 'bx-trip'],
    ], fn (array $item) => collect($sections)->contains(fn (array $sec) => $sec['id'] === $item['step'])));
@endphp
@extends('layouts.admin-v2')

@section('title'){{ $isCreate ? 'Creer un voyage - Studio V3' : 'Modifier - ' . $headerTitle }}@endsection

@push('styles')
    <link href="{{ URL::asset('css/voyage-edit.css?v=' . $cssEdit) }}" rel="stylesheet">
    <link href="{{ URL::asset('css/flight-options-new.css') }}" rel="stylesheet">
    <link href="{{ URL::asset('css/voyage-v2.css?v=' . $cssV2) }}" rel="stylesheet">
    <link href="{{ URL::asset('css/voyage-v3.css?v=' . $cssV3) }}" rel="stylesheet">
@endpush

@section('content')
<div class="v2-page voyage-edit-page voyage-studio-v3" data-v2-initial-id="{{ $veWpId }}" data-v2-save-create-url="{{ $saveCreateUrl }}" data-v2-save-update-template="{{ $saveUpdateTemplate }}" data-v2-is-create="{{ $isCreate ? '1' : '0' }}" data-v3-public-base-url="{{ $publicVoyagesBaseUrl }}">
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

    @if(!$isCreate)
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
