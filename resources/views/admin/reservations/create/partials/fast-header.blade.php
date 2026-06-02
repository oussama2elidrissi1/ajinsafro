@php
    $fastDeparture = $selectedDeparture ?? null;
    $fastTour = $preselectedTour ?? null;
    $fastUnitPrice = $selectedUnitPrice ?? null;
    $agentReservationMode = (bool) request()->attributes->get('agent_reservation_mode', false);
@endphp

<header class="reservation-fast-header">
    <div class="reservation-fast-header__top">
        <nav class="reservation-create__breadcrumb" aria-label="Breadcrumb">
            <a href="{{ $agentReservationMode ? route('agent.reservations.index') : route('admin.reservations.index') }}">Réservations</a>
            <span>/</span>
            <span>Nouvelle rapide</span>
        </nav>
        <a href="{{ $agentReservationMode ? route('agent.catalogue') : route('admin.reservations.workspace') }}" class="reservation-create__back-link">{{ $agentReservationMode ? 'Retour au catalogue' : 'Retour au workspace' }}</a>
    </div>

    <div class="reservation-fast-header__card">
        <div class="reservation-fast-header__meta">
            <p class="reservation-create__eyebrow">Offre sélectionnée</p>
            <h1 class="reservation-fast-header__title">{{ $fastTour?->name ?? 'Voyage' }}</h1>
            <p class="reservation-fast-header__subtitle">
                Départ :
                @if($fastDeparture?->start_date)
                    {{ $fastDeparture->start_date->format('d/m/Y') }}
                    @if($fastDeparture?->end_date)
                        → {{ $fastDeparture->end_date->format('d/m/Y') }}
                    @endif
                @else
                    —
                @endif
                @if($fastDeparture?->available_capacity !== null)
                    · Places restantes : {{ $fastDeparture->available_capacity }}
                @endif
                @if($fastUnitPrice !== null)
                    · Prix unitaire : {{ number_format((float) $fastUnitPrice, 2, ',', ' ') }} DH
                @endif
            </p>
        </div>
        <button type="button" class="btn btn-sm btn-outline-primary" id="btn-fast-modify-offer">Modifier l'offre</button>
    </div>
</header>
