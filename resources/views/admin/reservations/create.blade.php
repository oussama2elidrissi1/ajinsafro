@extends(request()->attributes->get('agent_reservation_mode', false) ? 'layouts.master-ajinsafro' : 'layouts.admin-v6')

@section('title', 'Créer une réservation')
@section('hidePageFooter', '1')

@push('styles')
    @if(request()->attributes->get('agent_reservation_mode', false))
        <link href="{{ URL::asset('css/agent-dashboard.css') }}" rel="stylesheet" type="text/css" />
    @endif
    <link rel="stylesheet" href="{{ asset('css/reservation-create.css') . '?v=' . @filemtime(public_path('css/reservation-create.css')) }}">
    @if(request()->attributes->get('agent_reservation_mode', false))
        <style>
            .agent-portal-main .reservation-create {
                width: 100% !important;
                max-width: 1540px !important;
                padding: 0 22px 32px !important;
            }

            .agent-portal-main .reservation-create__workflow {
                max-width: 1360px !important;
                margin: 0 auto 22px !important;
            }

            .agent-portal-main .reservation-create__content-grid {
                display: grid !important;
                grid-template-columns: minmax(0, 1fr) !important;
                gap: 28px !important;
                align-items: start !important;
                max-width: 1180px !important;
                margin: 0 auto !important;
            }

            .agent-portal-main .reservation-fast-header {
                max-width: 1360px !important;
                margin: 0 auto 22px !important;
            }

            .agent-portal-main .reservation-fast-header__top {
                margin-bottom: 12px !important;
            }

            .agent-portal-main .reservation-fast-header__card,
            .agent-portal-main .reservation-create__steps-card--workflow,
            .agent-portal-main .reservation-fast-summary,
            .agent-portal-main .reservation-fast-card {
                border: 1px solid #d9e7f2 !important;
                border-radius: 18px !important;
                background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%) !important;
                box-shadow: 0 14px 36px rgba(14, 58, 90, .08) !important;
            }

            .agent-portal-main .reservation-fast-header__card {
                padding: 22px 24px !important;
                border-top: 4px solid #ff7a1a !important;
            }

            .agent-portal-main .reservation-fast-header__title {
                font-size: 1.28rem !important;
                line-height: 1.18 !important;
                letter-spacing: 0 !important;
                color: #111827 !important;
            }

            .agent-portal-main .reservation-fast-header__subtitle {
                color: #48627c !important;
                font-weight: 600 !important;
            }

            .agent-portal-main #btn-fast-modify-offer {
                border-radius: 10px !important;
                border-color: #0083c4 !important;
                color: #0074ad !important;
                background: #ffffff !important;
                font-weight: 800 !important;
                line-height: 1.1 !important;
                min-width: 112px !important;
            }

            .agent-portal-main .reservation-create__steps-card--workflow {
                padding: 18px 20px !important;
                border-top: 4px solid #0083c4 !important;
            }

            .agent-portal-main .reservation-create__steps-card--workflow .reservation-create__sidebar-title {
                margin-bottom: 12px !important;
                color: #111827 !important;
                font-size: 1rem !important;
            }

            .agent-portal-main .reservation-create__steps--chevrons {
                display: grid !important;
                grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
                gap: 12px !important;
                overflow: visible !important;
            }

            .agent-portal-main .reservation-create__steps--chevrons .reservation-create__step,
            .agent-portal-main .reservation-create__steps--chevrons .reservation-create__step + .reservation-create__step {
                position: relative !important;
                min-width: 0 !important;
                width: 100% !important;
                min-height: 48px !important;
                margin: 0 !important;
                padding: 10px 14px !important;
                border-radius: 12px !important;
                border: 1px solid #d9e4ef !important;
                background: #eef4fa !important;
                color: #20324d !important;
                box-shadow: none !important;
                overflow: hidden !important;
            }

            .agent-portal-main .reservation-create__steps--chevrons .reservation-create__step::before,
            .agent-portal-main .reservation-create__steps--chevrons .reservation-create__step::after {
                content: none !important;
                display: none !important;
                border: 0 !important;
            }

            .agent-portal-main .reservation-create__steps--chevrons .reservation-create__step.is-active {
                border-color: #005b89 !important;
                background: linear-gradient(135deg, #063d5d 0%, #0078bd 100%) !important;
                color: #ffffff !important;
                box-shadow: 0 10px 24px rgba(0, 120, 189, .24) !important;
            }

            .agent-portal-main .reservation-create__steps--chevrons .reservation-create__step.is-complete {
                border-color: #89d7ad !important;
                background: #eafaf1 !important;
                color: #0f5132 !important;
            }

            .agent-portal-main .reservation-create__steps--chevrons .reservation-create__step-index {
                width: 30px !important;
                height: 30px !important;
                min-width: 30px !important;
                border-radius: 999px !important;
                font-size: .9rem !important;
                font-weight: 900 !important;
            }

            .agent-portal-main .reservation-create__steps--chevrons .reservation-create__step:not(.is-active):not(.is-complete) .reservation-create__step-index {
                background: #d8e4ef !important;
                color: #0e3a5a !important;
            }

            .agent-portal-main .reservation-create__steps--chevrons .reservation-create__step.is-active .reservation-create__step-index {
                background: #ffffff !important;
                color: #005b89 !important;
            }

            .agent-portal-main .reservation-create__steps--chevrons .reservation-create__step-label {
                white-space: normal !important;
                overflow-wrap: anywhere !important;
                line-height: 1.15 !important;
                font-weight: 800 !important;
            }

            .agent-portal-main .reservation-create__main,
            .agent-portal-main .reservation-create__summary,
            .agent-portal-main .reservation-create__summary-card {
                min-width: 0 !important;
            }

            .agent-portal-main .reservation-create__summary {
                order: -1 !important;
                grid-column: 1 !important;
                width: 100% !important;
                max-width: none !important;
            }

            .agent-portal-main .reservation-fast-summary {
                display: grid !important;
                grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
                gap: 0 !important;
                width: 100% !important;
                min-width: 0 !important;
                padding: 18px 20px !important;
                border-top: 4px solid #12b76a !important;
            }

            .agent-portal-main .reservation-fast-summary__item {
                display: grid !important;
                grid-template-columns: 1fr !important;
                gap: 4px !important;
                padding: 10px 14px !important;
                border-bottom: 0 !important;
                border-left: 1px solid #edf2f7 !important;
            }

            .agent-portal-main .reservation-fast-summary__item span {
                color: #5b708b !important;
                font-weight: 700 !important;
            }

            .agent-portal-main .reservation-fast-summary__item:first-of-type {
                border-left: 0 !important;
                grid-column: span 2 !important;
            }

            .agent-portal-main .reservation-fast-summary__item strong {
                font-size: .9rem !important;
                line-height: 1.35 !important;
                overflow-wrap: anywhere !important;
                word-break: normal !important;
                text-align: left !important;
                color: #101828 !important;
            }

            .agent-portal-main .reservation-fast-summary__item--total,
            .agent-portal-main .reservation-fast-summary__item--remaining {
                border-top: 0 !important;
                margin-top: 0 !important;
            }

            .agent-portal-main .reservation-fast-summary__item--total strong {
                color: #005b89 !important;
                font-size: 1rem !important;
            }

            .agent-portal-main .reservation-fast-summary__item--remaining strong {
                color: #c2410c !important;
                font-size: 1rem !important;
            }

            .agent-portal-main .reservation-fast-tabs {
                background: #eaf1f7 !important;
                border-color: #d5e2ee !important;
                border-radius: 12px !important;
            }

            .agent-portal-main .reservation-fast-tab {
                border-radius: 10px !important;
            }

            .agent-portal-main .reservation-fast-tab.is-active,
            .agent-portal-main .reservation-fast-tab:hover {
                background: #ffffff !important;
                color: #0078bd !important;
                box-shadow: 0 8px 18px rgba(14, 58, 90, .10) !important;
            }

            .agent-portal-main .reservation-create__input {
                border-radius: 10px !important;
                border-color: #cfe0ee !important;
                background: #fbfdff !important;
            }

            .agent-portal-main .reservation-create__input:focus {
                border-color: #0083c4 !important;
                box-shadow: 0 0 0 4px rgba(0, 131, 196, .12) !important;
            }

            @media (max-width: 1180px) {
                .agent-portal-main .reservation-create__content-grid {
                    grid-template-columns: 1fr !important;
                }

                .agent-portal-main .reservation-create__summary {
                    grid-column: 1 !important;
                    max-width: none !important;
                }

                .agent-portal-main .reservation-fast-summary {
                    grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                }

                .agent-portal-main .reservation-fast-summary__item:nth-of-type(odd) {
                    border-left: 0 !important;
                }

                .agent-portal-main .reservation-fast-summary__item:first-of-type {
                    grid-column: 1 / -1 !important;
                }

                .agent-portal-main .reservation-create__steps--chevrons {
                    grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                }
            }

            @media (max-width: 720px) {
                .agent-portal-main .reservation-create {
                    padding: 0 12px 24px;
                }

                .agent-portal-main .reservation-fast-header__card,
                .agent-portal-main .reservation-fast-header__top {
                    flex-direction: column;
                    align-items: stretch;
                }

                .agent-portal-main .reservation-fast-summary {
                    grid-template-columns: 1fr !important;
                }

                .agent-portal-main .reservation-fast-summary__item {
                    border-left: 0 !important;
                    border-bottom: 1px solid #edf2f7 !important;
                }

                .agent-portal-main .reservation-create__steps--chevrons {
                    grid-template-columns: 1fr !important;
                }
            }
        </style>
    @endif
@endpush

@section('content')
    @php($agentReservationMode = (bool) request()->attributes->get('agent_reservation_mode', false))
    <div class="reservation-create {{ ($fastCreateMode ?? false) ? 'reservation-create--fast' : '' }}" data-fast-create="{{ ($fastCreateMode ?? false) ? '1' : '0' }}">
        @if ($fastCreateMode ?? false)
            @include('admin.reservations.create.partials.fast-header')
        @else
            <header class="reservation-create__header">
                <nav class="reservation-create__breadcrumb" aria-label="Breadcrumb">
                    <a href="{{ $agentReservationMode ? route('agent.reservations.index') : route('admin.reservations.index') }}">Réservations</a>
                    <span>/</span>
                    <span>Nouvelle</span>
                </nav>
                <div class="reservation-create__header-main">
                    <div>
                        <h1 class="reservation-create__title">Créer une réservation</h1>
                        <p class="reservation-create__subtitle">Tunnel dédié pour ouvrir un dossier de réservation sans confusion avec le workspace.</p>
                    </div>
                    <a href="{{ $agentReservationMode ? route('agent.catalogue') : route('admin.reservations.workspace') }}" class="reservation-create__back-link">{{ $agentReservationMode ? 'Retour au catalogue' : 'Retour au workspace' }}</a>
                </div>
            </header>
        @endif

        @if ($errors->any())
            <div class="reservation-create__alert reservation-create__alert--error">
                <strong>Le dossier contient des erreurs.</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="post" action="{{ $agentReservationMode ? route('agent.reservations.store') : route('admin.reservations.store') }}" enctype="multipart/form-data" id="reservation-create-form">
            @csrf
            <input type="hidden" name="extras_json" id="reservation-create-extras-json" value="[]">
            <input type="hidden" name="travelers_json" id="reservation-travelers-json" value="[]">
            <input type="hidden" name="room_allocations_json" id="reservation-room-allocations-json" value="{{ old('room_allocations_json', '[]') }}">
            <input type="hidden" name="accommodation_mode" id="reservation-accommodation-mode" value="rooms">
            <input type="hidden" name="total_base" id="reservation-total-base-input" value="{{ old('total_base', 0) }}">
            <input type="hidden" name="room_supplement_total" id="reservation-room-supplement-total-input" value="{{ old('room_supplement_total', 0) }}">
            <input type="hidden" name="extras_total" id="reservation-extras-total-input" value="{{ old('extras_total', 0) }}">
            <input type="hidden" name="total_amount" id="reservation-total-amount-input" value="{{ old('total_amount', 0) }}">

            <div class="reservation-create__workflow">
                @include('admin.reservations.create.partials.' . (($fastCreateMode ?? false) ? 'workflow-fast' : 'workflow'))
            </div>

            <div class="reservation-create__content-grid">
                <main class="reservation-create__main">
                    @if ($fastCreateMode ?? false)
                        @include('admin.reservations.create.partials.step-fast-1')
                        @include('admin.reservations.create.partials.step-fast-2')
                        @include('admin.reservations.create.partials.step-fast-3')
                        @include('admin.reservations.create.partials.step-fast-4')
                    @else
                        @include('admin.reservations.create.partials.step-prestation')
                        @include('admin.reservations.create.partials.step-client')
                        @include('admin.reservations.create.partials.step-voyageurs')
                        @include('admin.reservations.create.partials.step-extras')
                        @include('admin.reservations.create.partials.step-payment')
                        @include('admin.reservations.create.partials.step-dossier')
                    @endif
                </main>

                <aside class="reservation-create__summary">
                    @include('admin.reservations.create.partials.' . (($fastCreateMode ?? false) ? 'summary-fast' : 'summary'))
                </aside>
            </div>
        </form>

        <script type="application/json" id="reservation-create-extras-map">{!! json_encode($extrasByVoyage ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) !!}</script>
        <script type="application/json" id="reservation-create-wp-voyage-map">{!! json_encode(($voyages ?? collect())->filter(fn ($v) => (int) ($v->wp_post_id ?? 0) > 0)->mapWithKeys(fn ($v) => [(string) $v->wp_post_id => (int) $v->id])->all(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) !!}</script>
        <script>
            window.RESERVATION_CREATE_ENDPOINTS = {
                clientQuickStore: @json($agentReservationMode ? route('agent.customers.clients.quick-store') : route('admin.customers.clients.quick-store')),
                clientSearch: @json($agentReservationMode ? route('agent.customers.clients.search') : route('admin.customers.clients.search')),
                extras: @json($agentReservationMode ? route('agent.reservations.extras') : route('admin.reservations.extras')),
                voyageDepartures: @json($agentReservationMode ? route('agent.reservations.voyage-departures') : route('admin.reservations.voyage-departures')),
                departureHotelsRooms: @json($agentReservationMode ? route('agent.reservations.departure-hotels-rooms') : route('admin.reservations.departure-hotels-rooms'))
            };
        </script>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/reservation-create.js') . '?v=' . @filemtime(public_path('js/reservation-create.js')) }}"></script>
@endpush
