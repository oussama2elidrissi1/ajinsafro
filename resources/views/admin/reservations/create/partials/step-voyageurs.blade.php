@php $oldPassengers = collect(old('passengers', [])); @endphp

<section class="reservation-create__panel" data-create-step="3" hidden>
    <div class="reservation-create__card">
        <div class="reservation-create__section-head">
            <div>
                <p class="reservation-create__eyebrow">Étape 3</p>
                <h3 class="reservation-create__section-title">Voyageurs</h3>
                <p class="reservation-create__section-subtitle">Ajoutez les accompagnants et gardez une vision claire du nombre total de voyageurs.</p>
            </div>
            <div class="reservation-create__metric">
                <span>Total</span>
                <strong id="create-travelers-badge">1</strong>
            </div>
        </div>

        <div class="reservation-create__toolbar">
            <p>Le client principal compte comme voyageur principal. Ajoutez seulement les accompagnants nécessaires.</p>
            <button type="button" class="reservation-create__button reservation-create__button--ghost" id="btn-add-companion">Ajouter un accompagnant</button>
        </div>

        <div id="companions-container" class="reservation-create__companions">
            @foreach($oldPassengers as $i => $passenger)
                <div class="companion-row reservation-create__companion">
                    <div class="reservation-create__companion-head">
                        <h4 class="reservation-create__companion-title">Accompagnant #{{ $loop->iteration }}</h4>
                        <button type="button" class="btn-remove-companion reservation-create__remove" aria-label="Supprimer">×</button>
                    </div>
                    <div class="reservation-create__grid reservation-create__grid--two">
                        <div class="reservation-create__field">
                            <label class="reservation-create__label">Prénom</label>
                            <input type="text" name="passengers[{{ $i }}][first_name]" class="reservation-create__input" value="{{ $passenger['first_name'] ?? '' }}" autocomplete="given-name">
                        </div>
                        <div class="reservation-create__field">
                            <label class="reservation-create__label">Nom</label>
                            <input type="text" name="passengers[{{ $i }}][last_name]" class="reservation-create__input" value="{{ $passenger['last_name'] ?? '' }}" autocomplete="family-name">
                        </div>
                        <div class="reservation-create__field">
                            <label class="reservation-create__label">Type</label>
                            <select name="passengers[{{ $i }}][type]" class="reservation-create__input">
                                <option value="adult" {{ ($passenger['type'] ?? '') === 'adult' ? 'selected' : '' }}>Adulte</option>
                                <option value="child" {{ ($passenger['type'] ?? '') === 'child' ? 'selected' : '' }}>Enfant</option>
                                <option value="infant" {{ ($passenger['type'] ?? '') === 'infant' ? 'selected' : '' }}>Bébé</option>
                            </select>
                        </div>
                        <div class="reservation-create__field">
                            <label class="reservation-create__label">Date de naissance</label>
                            <input type="date" name="passengers[{{ $i }}][birth_date]" class="reservation-create__input" value="{{ $passenger['birth_date'] ?? '' }}">
                        </div>
                        <div class="reservation-create__field">
                            <label class="reservation-create__label">Type document</label>
                            <input type="text" name="passengers[{{ $i }}][document_type]" class="reservation-create__input" value="{{ $passenger['document_type'] ?? '' }}" placeholder="CIN, Passeport…">
                        </div>
                        <div class="reservation-create__field">
                            <label class="reservation-create__label">N° document</label>
                            <input type="text" name="passengers[{{ $i }}][document_number]" class="reservation-create__input" value="{{ $passenger['document_number'] ?? '' }}">
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <p id="create-no-companions" class="reservation-create__empty-state {{ $oldPassengers->isNotEmpty() ? 'd-none' : '' }}">Aucun accompagnant pour le moment.</p>
    </div>

    <div class="reservation-create__actions">
        <button type="button" class="reservation-create__button reservation-create__button--secondary" data-create-prev>Retour</button>
        <button type="button" class="reservation-create__button reservation-create__button--primary" data-create-next>Continuer</button>
    </div>
</section>
