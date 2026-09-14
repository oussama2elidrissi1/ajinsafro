@php
    $agentReservationMode = (bool) request()->attributes->get('agent_reservation_mode', false);
    $backHref = $agentReservationMode ? route('agent.catalogue') : route('admin.reservations.workspace');
    $backLabel = $agentReservationMode ? 'Retour au catalogue' : 'Retour au workspace';
@endphp

<header class="reservation-fast-header">
    <div class="reservation-fast-header__inner">
        <div class="reservation-fast-header__top">
            <div class="reservation-fast-header__identity">
                <nav class="reservation-create__breadcrumb" aria-label="Fil d'Ariane">
                    <a href="{{ $agentReservationMode ? route('agent.reservations.index') : route('admin.reservations.index') }}">Réservations</a>
                    <span class="reservation-create__breadcrumb-sep" aria-hidden="true">/</span>
                    <span class="reservation-create__breadcrumb-current">Nouvelle rapide</span>
                </nav>
                <div class="reservation-fast-header__title-row">
                    <h1 class="reservation-fast-header__title">Nouvelle réservation rapide</h1>
                </div>
            </div>
            <div class="reservation-fast-header__actions">
                <a href="{{ $backHref }}" class="reservation-create__back-link">{{ $backLabel }}</a>
            </div>
        </div>

        @include('admin.reservations.create.partials.workflow-fast')
    </div>
</header>
