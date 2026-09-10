@php
    /**
     * Barre de filtres du module « Finance & controle ».
     *
     * Parametres :
     *   $action            URL de soumission (GET) — page courante.
     *   $filters           filtres normalises par FinanceControlController::commonFilters().
     *   $voyages,$branches options des listes deroulantes.
     *   $advanced          true sur la liste des projets : statut, rentabilite, recherche.
     *   $departureStatuses statuts de depart (uniquement si $advanced).
     *
     * Les raccourcis de periode sont de simples liens : ils remplacent date_from/date_to
     * et conservent les autres filtres deja actifs.
     */
    $fcAdvanced = (bool) ($advanced ?? false);
    $fcToday = \Illuminate\Support\Carbon::today();

    $fcPresets = [
        'mois' => ['label' => 'Ce mois', 'from' => $fcToday->copy()->startOfMonth(), 'to' => $fcToday->copy()->endOfMonth()],
        '30j' => ['label' => '30 jours', 'from' => $fcToday->copy()->subDays(29), 'to' => $fcToday->copy()],
        'trimestre' => ['label' => 'Trimestre', 'from' => $fcToday->copy()->startOfQuarter(), 'to' => $fcToday->copy()->endOfQuarter()],
        'annee' => ['label' => 'Année', 'from' => $fcToday->copy()->startOfYear(), 'to' => $fcToday->copy()->endOfYear()],
    ];

    $fcQuery = request()->query();
    $fcActivePreset = null;
    foreach ($fcPresets as $fcKey => $fcPreset) {
        if ($filters['date_from'] === $fcPreset['from']->toDateString() && $filters['date_to'] === $fcPreset['to']->toDateString()) {
            $fcActivePreset = $fcKey;
            break;
        }
    }
    // « Personnalisé » est actif des qu'une borne est saisie sans correspondre a un raccourci.
    if ($fcActivePreset === null && ($filters['date_from'] || $filters['date_to'])) {
        $fcActivePreset = 'perso';
    }

    $fcPresetHref = fn (\Illuminate\Support\Carbon $from, \Illuminate\Support\Carbon $to) => $action.'?'.http_build_query(
        array_merge($fcQuery, ['date_from' => $from->toDateString(), 'date_to' => $to->toDateString(), 'page' => null])
    );
@endphp

<form method="GET" action="{{ $action }}" class="fc-filters">
    <div class="fc-periods">
        @foreach ($fcPresets as $fcKey => $fcPreset)
            <a href="{{ $fcPresetHref($fcPreset['from'], $fcPreset['to']) }}"
               class="fc-period {{ $fcActivePreset === $fcKey ? 'is-active' : '' }}">{{ $fcPreset['label'] }}</a>
        @endforeach
        <span class="fc-period {{ $fcActivePreset === 'perso' ? 'is-active' : '' }}">Personnalisé</span>
    </div>

    @if (($sort ?? null) !== null)
        <input type="hidden" name="sort" value="{{ $sort }}">
    @endif

    <label class="fc-field is-narrow">
        <span>Du</span>
        <input type="date" name="date_from" class="fc-input" value="{{ $filters['date_from'] }}">
    </label>
    <label class="fc-field is-narrow">
        <span>Au</span>
        <input type="date" name="date_to" class="fc-input" value="{{ $filters['date_to'] }}">
    </label>
    <label class="fc-field">
        <span>Voyage</span>
        <select name="voyage_id" class="fc-input">
            <option value="">Tous</option>
            @foreach ($voyages as $voyage)
                <option value="{{ $voyage->id }}" @selected($filters['voyage_id'] === $voyage->id)>{{ $voyage->name }}</option>
            @endforeach
        </select>
    </label>
    <label class="fc-field">
        <span>Agence</span>
        <select name="branch_id" class="fc-input">
            <option value="">Toutes</option>
            @foreach ($branches as $branch)
                <option value="{{ $branch->id }}" @selected($filters['branch_id'] === $branch->id)>{{ $branch->name }}</option>
            @endforeach
        </select>
    </label>

    @if ($fcAdvanced)
        <label class="fc-field is-narrow">
            <span>Statut départ</span>
            <select name="status" class="fc-input">
                <option value="">Tous</option>
                @foreach (array_unique($departureStatuses ?? []) as $fcStatus)
                    <option value="{{ $fcStatus }}" @selected($filters['status'] === $fcStatus)>{{ ucfirst($fcStatus) }}</option>
                @endforeach
            </select>
        </label>
        <label class="fc-field is-narrow">
            <span>Rentabilité</span>
            <select name="profitability" class="fc-input">
                <option value="">Toutes</option>
                <option value="rentable" @selected($filters['profitability'] === 'rentable')>Rentable</option>
                <option value="deficitaire" @selected($filters['profitability'] === 'deficitaire')>Déficitaire</option>
            </select>
        </label>
        <label class="fc-field">
            <span>Recherche voyage</span>
            <input type="text" name="search" class="fc-input" value="{{ $filters['search'] }}" placeholder="Nom du voyage">
        </label>
    @endif

    <div class="fc-filter-actions">
        <button type="submit" class="fc-btn-solid">Filtrer</button>
        <a href="{{ $action }}" class="fc-btn-ghost">Réinitialiser</a>
    </div>
</form>
