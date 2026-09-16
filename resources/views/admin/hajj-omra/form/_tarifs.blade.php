{{-- Etape 2 : grille tarifaire par type de chambre, regroupee par hebergement. --}}
@php
    $roomRows = old('room_prices', $package->roomPrices->map(fn ($r) => [
        'id' => $r->id,
        'room_type' => $r->room_type,
        'label' => $r->label,
        'price' => $r->price,
        'old_price' => $r->old_price,
        'capacity' => $r->capacity,
        'stock' => $r->stock,
        'is_active' => $r->is_active ? 1 : 0,
    ])->values()->all());

    // Le libelle sert de cle de regroupement : c'est lui qui distingue deux lignes
    // du meme type de chambre appartenant a deux hebergements differents.
    $roomGroups = [];
    foreach ($roomRows as $i => $row) {
        $roomGroups[trim((string) ($row['label'] ?? ''))][] = ['index' => $i, 'row' => $row];
    }
    $ungrouped = $roomGroups[''] ?? [];
    unset($roomGroups['']);

    $activeCount = collect($roomRows)->where('is_active', 1)->count();
@endphp

<div class="ho-panel" data-panel="tarifs">
    <section class="ho-card">
        <div class="ho-card__head">
            <div>
                <div class="ho-eyebrow mb-0">Grille tarifaire</div>
                <p>Un tarif par type de chambre et par hébergement. Le libellé regroupe les lignes ci-dessous et les rend reconnaissables dans les formules.</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <span class="ho-pill">{{ count($roomRows) }} tarif(s) · <b class="ho-mono">{{ $activeCount }} actif(s)</b></span>
                <button type="button" class="btn btn-sm btn-outline-primary" data-repeat-add="room">+ Ajouter un tarif</button>
            </div>
        </div>

        <div data-repeat-list="room">
            @foreach ($roomGroups as $groupLabel => $entries)
                @php
                    $prices = collect($entries)->pluck('row.price')->filter(fn ($p) => $p !== null && $p !== '')->map(fn ($p) => (float) $p);
                @endphp
                <div @class(['ho-acc', 'is-open' => $loop->first])>
                    <button type="button" class="ho-acc__head" data-acc-toggle>
                        <span class="ho-dot" style="background: var(--ho-primary);"></span>
                        <span class="ho-acc__title">{{ $groupLabel }}</span>
                        <span class="ho-acc__end">
                            <span class="form-text">{{ count($entries) }} chambre(s)</span>
                            <span class="ho-acc__from">dès <b class="ho-mono">{{ $prices->isEmpty() ? '—' : number_format($prices->min(), 0, ',', ' ') }}</b> {{ $package->currency ?: 'DH' }}</span>
                            <span class="ho-acc__chevron" aria-hidden="true">⌄</span>
                        </span>
                    </button>
                    <div class="ho-acc__body">
                        @include('admin.hajj-omra.form._tarif-rows', ['entries' => $entries])
                    </div>
                </div>
            @endforeach

            {{-- Accueille les lignes sans libelle et celles ajoutees depuis le bouton. --}}
            <div class="ho-acc is-open" data-room-loose @if (! $ungrouped) hidden @endif>
                <button type="button" class="ho-acc__head" data-acc-toggle>
                    <span class="ho-dot" style="background: var(--ho-accent);"></span>
                    <span class="ho-acc__title">Sans libellé<span class="ho-acc__sub">Nommez ces lignes pour les regrouper par hébergement.</span></span>
                    <span class="ho-acc__end"><span class="ho-acc__chevron" aria-hidden="true">⌄</span></span>
                </button>
                <div class="ho-acc__body" data-repeat-target>
                    @include('admin.hajj-omra.form._tarif-rows', ['entries' => $ungrouped])
                </div>
            </div>

            @if (! $roomRows)
                <p class="text-muted small mb-0" data-repeat-empty>Aucun tarif. Ajoutez au moins un type de chambre.</p>
            @endif
        </div>

        @error('room_prices')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
        @foreach ($errors->get('room_prices.*') as $messages)
            @foreach ($messages as $message)<div class="text-danger small mt-1">{{ $message }}</div>@endforeach
        @endforeach

        {{-- Modele de ligne clone par le JS des repeaters. __INDEX__ est remplace a l'insertion. --}}
        <template data-repeat-template="room">
            @include('admin.hajj-omra.form._tarif-rows', ['entries' => [['index' => '__INDEX__', 'row' => []]]])
        </template>
    </section>

    @include('admin.hajj-omra.form._formulas')
</div>
