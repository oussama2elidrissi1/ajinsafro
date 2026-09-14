<dialog id="departure-rooms-modal" class="departure-rooms-modal"
    aria-labelledby="departure-rooms-title"
    data-endpoint="{{ route('admin.circuits.voyages.departures.room-allocations.show', ['voyage' => '__voyage__', 'departure' => '__departure__']) }}">
    <form id="departure-rooms-form">
        <header class="departure-rooms-modal__header">
            <div>
                <h2 id="departure-rooms-title">Chambres du départ</h2>
                <p>Types de chambres, quantités et capacité couverte pour ce départ.</p>
            </div>
            <button type="button" data-rooms-close aria-label="Fermer">×</button>
        </header>
        <div class="departure-rooms-modal__body">
            <div class="departure-rooms-modal__summary" id="departure-rooms-summary"></div>
            <p id="departure-rooms-status" role="status" aria-live="polite"></p>
            <fieldset id="departure-rooms-fields" disabled>
                <div class="departure-rooms-modal__table-wrap">
                    <table>
                        <thead><tr><th>Type</th><th>Quantité</th><th>Cap./chambre</th><th>Suppl. (DH/pers.)</th><th>Application</th><th>Places couvertes</th><th><span class="visually-hidden">Actions</span></th></tr></thead>
                        <tbody id="departure-rooms-rows"></tbody>
                    </table>
                </div>
                <datalist id="departure-room-types">
                    @foreach (['Single', 'Double', 'Twin', 'Triple', 'Quadruple', 'Family', 'Suite'] as $type)
                        <option value="{{ $type }}"></option>
                    @endforeach
                </datalist>
                <div class="departure-rooms-modal__actions">
                    <button type="button" data-rooms-add>Ajouter un type</button>
                    <button type="button" data-rooms-default>Générer la répartition par défaut</button>
                </div>
            </fieldset>
        </div>
        <footer class="departure-rooms-modal__footer">
            <span>Les changements sont enregistrés automatiquement pour ce départ.</span>
            <button type="button" data-rooms-reload hidden>Recharger</button>
            <button type="button" data-rooms-discard hidden>Ignorer les changements non enregistrés</button>
            <button type="submit" data-rooms-save>Enregistrer et fermer</button>
        </footer>
    </form>
</dialog>
