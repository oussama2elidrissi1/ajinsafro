@extends(request()->attributes->get('agent_reservation_mode', false) ? 'layouts.master-ajinsafro' : 'layouts.admin-v6')

@section('title', 'Créer une réservation')
@section('hidePageFooter', '1')

@push('styles')
    @if(request()->attributes->get('agent_reservation_mode', false))
        <link href="{{ URL::asset('css/agent-dashboard.css') }}" rel="stylesheet" type="text/css" />
    @endif
    <link rel="stylesheet" href="{{ asset('css/reservation-create.css') }}">
    @if(request()->attributes->get('agent_reservation_mode', false))
        <style>
            .agent-portal-main .reservation-create {
                width: 100%;
                max-width: 1480px;
                padding: 0 18px 28px;
            }

            .agent-portal-main .reservation-create__content-grid {
                grid-template-columns: minmax(0, 1fr) minmax(300px, 360px);
            }

            @media (max-width: 1180px) {
                .agent-portal-main .reservation-create__content-grid {
                    grid-template-columns: 1fr;
                }
            }

            @media (max-width: 720px) {
                .agent-portal-main .reservation-create {
                    padding: 0 12px 24px;
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
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/reservation-create.js') . '?v=' . @filemtime(public_path('js/reservation-create.js')) }}"></script>
@endpush
