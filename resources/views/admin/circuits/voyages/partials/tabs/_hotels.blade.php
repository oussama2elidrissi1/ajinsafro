{{--
    Étape 10 · Hôtels — design « Voyage - Hôtels et Activités » (Espace Admin v2).
    Deux cartes : les séjours hôteliers, puis la répartition des chambres par départ.
    Le bouton d'ajout porte l'id attendu par le script de _tour_hotels_section et doit
    donc rester présent dans le DOM avant l'inclusion de ce partial.
--}}
@php
    $lastDayNumber = ($programDays && $programDays->isNotEmpty())
        ? $programDays->count()
        : max(1, (int) ($meta['duration_day'] ?? 1));

    // Couverture initiale du séjour (recalculée côté client à chaque changement de date).
    $vfStays = collect($tourHotels ?? [])
        ->map(function ($hotel) use ($lastDayNumber) {
            $in = (int) ($hotel->check_in_day ?? $hotel->day_number ?? 1);
            $out = (int) ($hotel->check_out_day ?? $hotel->day_number ?? $in);
            $in = max(1, min($in, $lastDayNumber));
            $out = max($in, min($out, $lastDayNumber));
            return ['in' => $in, 'out' => $out, 'nights' => max(0, $out - $in)];
        })
        ->values();
    $vfNightsTotal = max(0, $lastDayNumber - 1);
    $vfNightsCovered = 0;
    if ($vfStays->isNotEmpty()) {
        $covered = [];
        foreach ($vfStays as $stay) {
            for ($d = $stay['in']; $d < $stay['out']; $d++) { $covered[$d] = true; }
        }
        $vfNightsCovered = count($covered);
    }
    $vfCoverageOk = $vfNightsTotal > 0 && $vfNightsCovered >= $vfNightsTotal;
@endphp

<div class="vf-col-main">

    <section class="vf-card">
        <div class="vf-card__head">
            <span class="vf-card__num">{{ str_pad((string) (($index ?? 9) + 1), 2, '0', STR_PAD_LEFT) }}</span>
            <div style="min-width:0">
                <h2 class="vf-card__title">Hôtels</h2>
                {{-- #tour-hotels-period : cible du script de _tour_hotels_section, qui y écrit la période
                     couverte et, dans la même passe, rafraîchit l'en-tête de chaque séjour. --}}
                <p class="vf-card__desc">Hébergements et allocation des chambres · <span id="tour-hotels-period">J1 → J{{ $lastDayNumber }}</span></p>
            </div>
            <button type="button" class="vf-btn vf-btn--primary vf-card__action" id="tour-add-hotel">+ Ajouter un séjour</button>
        </div>

        <div class="vf-coverage" data-vf-coverage data-vf-days="{{ $lastDayNumber }}">
            <div class="vf-coverage__head">
                <span class="vf-kicker">Couverture du séjour</span>
                <span class="vf-coverage__label {{ $vfCoverageOk ? 'is-ok' : 'is-warn' }}" data-vf-coverage-label>{{ $vfNightsCovered }} nuit{{ $vfNightsCovered !== 1 ? 's' : '' }} couverte{{ $vfNightsCovered !== 1 ? 's' : '' }} sur {{ $vfNightsTotal }}</span>
            </div>
            <div class="vf-coverage__bar" data-vf-coverage-bar>
                @forelse($vfStays as $vfStayIndex => $vfStay)
                    <span class="vf-coverage__seg vf-coverage__seg--{{ $vfStayIndex % 3 }}" style="flex:{{ max(1, $vfStay['nights']) }}">J{{ $vfStay['in'] }}→J{{ $vfStay['out'] }}</span>
                @empty
                    <span class="vf-coverage__seg is-empty" style="flex:1">aucun séjour configuré</span>
                @endforelse
            </div>
        </div>

        <div class="vf-card__body--legacy vf-hotels" style="margin-top:16px;">
            <div id="tour-hotels-anchor">
                @include('admin.circuits.voyages.partials._tour_hotels_section')
            </div>
        </div>
    </section>

    <section class="vf-card">
        <div class="vf-card__head">
            <span class="vf-card__num"><i class="bx bx-bed" aria-hidden="true"></i></span>
            <div style="min-width:0">
                <h2 class="vf-card__title">Répartition des chambres par départ</h2>
                <p class="vf-card__desc">Types de chambres, quantités et capacité couverte pour chaque départ programmé.</p>
            </div>
        </div>
        <div class="vf-card__body--legacy vf-allocations" style="margin-top:16px;">
            @include('admin.circuits.voyages.partials._departure_room_allocations')
        </div>
    </section>
</div>

@include('admin.circuits.voyages.partials.v2._side_quick')
