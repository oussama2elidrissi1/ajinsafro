@php
    /**
     * Onglets d'ecran du module : vue d'ensemble / projets / fiche projet.
     * $current : 'overview' | 'projects' | 'detail'. $departure : requis pour l'onglet fiche.
     */
    $fcCurrent = $current ?? 'overview';
    $fcDeparture = $departure ?? null;
@endphp

<nav class="fc-tabs" aria-label="Écrans Finance &amp; contrôle">
    <a href="{{ route('admin.finance.control.dashboard') }}"
       class="fc-tab {{ $fcCurrent === 'overview' ? 'is-active' : '' }}">Vue d'ensemble</a>
    <a href="{{ route('admin.finance.control.travel-projects.index') }}"
       class="fc-tab {{ $fcCurrent === 'projects' ? 'is-active' : '' }}">Projets de voyage</a>
    @if ($fcDeparture)
        <a href="{{ route('admin.finance.control.travel-projects.show', $fcDeparture) }}"
           class="fc-tab {{ $fcCurrent === 'detail' ? 'is-active' : '' }}">Détail projet</a>
    @endif
</nav>
