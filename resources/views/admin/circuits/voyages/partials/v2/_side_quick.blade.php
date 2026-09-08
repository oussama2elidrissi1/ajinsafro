{{--
    Colonne de droite du studio voyage : « Vue rapide » + repères de l'étape courante.
    Paramètres : $isCreate, $veWpId, $veDatesCount, $vePriceLabel, $veDestination
                 et, pour la carte de navigation, $sec, $index, $prev, $next.
--}}
@php
    $vfSec = $sec ?? null;
    $vfIndex = isset($index) ? (int) $index : null;
    $vfPrev = $prev ?? null;
    $vfNext = $next ?? null;
@endphp
<div class="vf-col-side">
    <section class="vf-side-card vf-side-card--navy">
        <h2 class="vf-side-card__title">Vue rapide</h2>
        <p class="vf-side-card__sub">État du produit</p>
        <div class="vf-quick">
            <div class="vf-quick__row"><span class="vf-quick__label">Réf. voyage</span><span class="vf-quick__value">{{ $isCreate ? 'nouveau' : '#' . $veWpId }}</span></div>
            <div class="vf-quick__row"><span class="vf-quick__label">Départs programmés</span><span class="vf-quick__value {{ $veDatesCount > 0 ? '' : 'is-accent' }}">{{ $veDatesCount }}</span></div>
            <div class="vf-quick__row"><span class="vf-quick__label">Prix de base</span><span class="vf-quick__value">{{ $vePriceLabel ?: '—' }}</span></div>
            <div class="vf-quick__row"><span class="vf-quick__label">Destination</span><span class="vf-quick__value is-text">{{ $veDestination ?: '—' }}</span></div>
        </div>
        @if($veDatesCount === 0)
            <div class="vf-quick__note">Aucun départ programmé : le voyage reste invisible à la réservation.</div>
        @endif
    </section>

    @if($vfSec)
        <section class="vf-side-card">
            <div class="vf-kicker">Étape {{ $vfIndex !== null ? $vfIndex + 1 : '' }} · {{ mb_strtolower((string) $vfSec['group']) }}</div>
            <h3 class="vf-side-card__title" style="margin-top:9px; font-size:15px;">{{ $vfSec['title'] }}</h3>
            <p class="vf-side-card__sub" style="margin-bottom:14px;">{{ $vfSec['desc'] }}</p>
            <div class="vf-side-nav">
                @if($vfPrev)
                    <button type="button" class="vf-side-nav__link" data-v2-prev="{{ $vfPrev['id'] }}"><span>← {{ $vfPrev['label'] }}</span></button>
                @endif
                @if($vfNext)
                    <button type="button" class="vf-side-nav__link" data-v2-next="{{ $vfNext['id'] }}"><span>{{ $vfNext['label'] }} →</span></button>
                @endif
            </div>
        </section>
    @endif
</div>
