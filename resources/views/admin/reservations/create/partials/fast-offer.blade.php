@php
    $fastDeparture = $selectedDeparture ?? null;
    $fastTour = $preselectedTour ?? null;
    $fastUnitPrice = $selectedUnitPrice ?? null;
    $fastSeats = $fastDeparture?->available_capacity;

    $fastDatesLabel = null;
    if ($fastDeparture?->start_date) {
        $fastDatesLabel = $fastDeparture->start_date->format('d/m');
        $fastDatesLabel .= $fastDeparture->end_date
            ? ' → ' . $fastDeparture->end_date->format('d/m/Y')
            : '/' . $fastDeparture->start_date->format('Y');
    }
@endphp

<section class="reservation-fast-offer">
    <div class="reservation-fast-offer__meta">
        <p class="reservation-create__eyebrow">Offre sélectionnée</p>
        <h2 class="reservation-fast-offer__title">{{ $fastTour?->name ?? 'Voyage' }}</h2>
        <div class="reservation-fast-offer__chips">
            @if ($fastDatesLabel)
                <span class="reservation-fast-offer__chip">{{ $fastDatesLabel }}</span>
            @endif
            @if ($fastUnitPrice !== null)
                <span class="reservation-fast-offer__chip">{{ number_format((float) $fastUnitPrice, 0, ',', ' ') }} DH / pers.</span>
            @endif
            @if ($fastSeats !== null)
                <span
                    class="reservation-fast-offer__chip reservation-fast-offer__seats"
                    id="fast-offer-seats"
                    data-available-capacity="{{ (int) $fastSeats }}"
                >{{ (int) $fastSeats }} place(s) restante(s)</span>
            @endif
        </div>
    </div>
    <button type="button" class="reservation-fast-offer__action" id="btn-fast-modify-offer">Modifier l'offre</button>
</section>
