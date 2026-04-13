@php
    $isCreate = isset($voyage->ID) && (int) $voyage->ID === 0;
    $laravelV = $laravelVoyage ?? null;
    $veWpId = isset($voyage->ID) ? (int) $voyage->ID : 0;
    $veAdultRaw = $meta['adult_price'] ?? (method_exists($voyage, 'getMeta') ? $voyage->getMeta('adult_price') : null);
    $vePriceLabel = null;
    if ($veAdultRaw !== null && $veAdultRaw !== '') {
        $vePriceLabel = is_numeric($veAdultRaw)
            ? number_format((float) $veAdultRaw, 0, ',', ' ').' MAD'
            : trim((string) $veAdultRaw);
    } elseif ($laravelV) {
        $priceFrom = data_get($laravelV, 'price_from');
        if ($priceFrom !== null && $priceFrom !== '' && is_numeric($priceFrom) && (float) $priceFrom > 0) {
            $cur = trim((string) (data_get($laravelV, 'currency')
                ?: data_get($laravelV, 'currency_symbol')
                ?: ''));
            $vePriceLabel = number_format((float) $priceFrom, 0, ',', ' ').' '.($cur !== '' ? $cur : 'MAD');
        }
    }
    $veDestinationRaw = $laravelV ? data_get($laravelV, 'destination') : null;
    $veDestination = ($veDestinationRaw !== null && trim((string) $veDestinationRaw) !== '')
        ? trim((string) $veDestinationRaw)
        : null;
    $veDatesCount = isset($travelDates) && $travelDates instanceof \Illuminate\Support\Collection ? $travelDates->count() : 0;
@endphp
@extends('layouts.master-ajinsafro')

@section('title')
    {{ $isCreate ? 'Creer un tour WordPress' : 'Modifier le tour WordPress' }}
@endsection

@push('styles')
    <link href="{{ URL::asset('css/voyage-edit.css') }}" rel="stylesheet" type="text/css" />
@endpush

@section('content')
<div class="voyage-edit-page">
    <div class="ve-shell">
        @include('admin.circuits.voyages.partials._voyage_page_header', [
            'isCreate' => $isCreate,
            'voyage' => $voyage,
            'veWpId' => $veWpId,
            'laravelV' => $laravelV,
            'vePriceLabel' => $vePriceLabel,
            'veDestination' => $veDestination,
            'deleteFormId' => 'delete-voyage-form',
        ])
    </div>

    <div class="ve-shell">
        @include('admin.circuits.voyages.partials._voyage_form_alerts')
    </div>

    <form action="{{ $isCreate ? route('admin.circuits.voyages.store') : route('admin.circuits.voyages.update', $voyage->ID) }}" method="POST" id="edit-voyage-form" data-voyage-id="{{ $voyage->ID ?? 0 }}">
        @csrf
        @if (!$isCreate)
            @method('PUT')
        @endif
        <textarea name="programme_days_payload" id="programme-days-payload" class="d-none" aria-hidden="true"></textarea>

        <div class="ve-shell">
            <div class="ve-page-layout">
                <div class="ve-main-col">
                    <div class="ve-editor-frame">
                        @include('admin.circuits.voyages.partials._voyage_actions_bar')
                        @include('admin.circuits.voyages.partials._voyage_tabs_nav')

                        <div class="ve-editor-body">
                            <div class="tab-content ve-tab-content pt-4">
                                @include('admin.circuits.voyages.partials.tabs._basic')
                                @include('admin.circuits.voyages.partials.tabs._location')
                                @include('admin.circuits.voyages.partials.tabs._pricing')
                                @include('admin.circuits.voyages.partials.tabs._information')
                                @include('admin.circuits.voyages.partials.tabs._extras')
                                @include('admin.circuits.voyages.partials.tabs._availability')
                                @include('admin.circuits.voyages.partials.tabs._media')
                                @include('admin.circuits.voyages.partials.tabs._taxonomies')
                                @include('admin.circuits.voyages.partials.tabs._flights')
                                @include('admin.circuits.voyages.partials.tabs._hotels')
                                @include('admin.circuits.voyages.partials.tabs._transfers')
                                @include('admin.circuits.voyages.partials.tabs._activities')
                                @include('admin.circuits.voyages.partials.tabs._programme')
                            </div>

                            @if (!$isCreate)
                                <div class="card ve-pane-card ve-danger-zone-card mt-4">
                                    <div class="card-body">
                                        <p class="ve-danger-zone-title"><i class="bx bx-error-circle"></i> Suppression definitive</p>
                                        <p class="ve-danger-zone-text">Supprimez le voyage et ses donnees uniquement si ce dossier ne doit plus etre utilise.</p>
                                        <button type="submit" form="delete-voyage-form" class="btn btn-outline-danger btn-sm"
                                            onclick="return confirm('Supprimer definitivement ce tour WordPress ? Cette action est irreversible.')">
                                            <i class="bx bx-trash"></i> Supprimer ce voyage
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            @include('admin.circuits.voyages.components.DayBuilderPanel', [
                'activitiesCatalog' => $activitiesCatalog,
                'flightOptionsWithIndex' => $flightOptionsWithIndex ?? [],
                'nextFlightOptionIndex' => $nextFlightOptionIndex ?? 0,
                'lastDayNumber' => $lastDayNumber ?? (($programDays && $programDays->isNotEmpty()) ? $programDays->count() : 1),
                'airlines' => $airlines ?? collect(),
                'programDays' => $programDays ?? collect()
            ])
        </div>
    </form>

    @if (!$isCreate)
        <form id="delete-voyage-form" action="{{ route('admin.circuits.voyages.destroy', $voyage->ID) }}" method="POST" class="d-none">
            @csrf
            @method('DELETE')
        </form>
    @endif

</div>
@endsection

@push('script')
    @include('admin.circuits.voyages.partials._voyage_page_bootstrap')
    <script src="{{ URL::asset('build/libs/tinymce/tinymce.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
    <script src="{{ URL::asset('js/voyage-editor-runtime.js') }}"></script>
    <script src="{{ URL::asset('js/voyage-edit-page.js') }}"></script>
@endpush
