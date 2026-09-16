{{--
    Lignes de la grille tarifaire.

    Utilise aussi bien pour l'affichage groupe que pour le modele clone par le repeater :
    $entries contient des couples ['index' => int|'__INDEX__', 'row' => tableau de valeurs].
--}}
@foreach ($entries as $entry)
    @php $i = $entry['index']; $row = $entry['row']; @endphp
    <div class="ho-row" data-repeat-item>
        <input type="hidden" name="room_prices[{{ $i }}][id]" value="{{ $row['id'] ?? '' }}">
        <input type="hidden" name="room_prices[{{ $i }}][client_key]" value="{{ $row['client_key'] ?? '' }}">
        <div class="row g-2 align-items-end">
            <div class="col-6 col-lg-3">
                <label class="form-label">Hébergement / libellé</label>
                <input type="text" maxlength="120" name="room_prices[{{ $i }}][label]" class="form-control form-control-sm"
                       value="{{ $row['label'] ?? '' }}" placeholder="Ex. Swissotel 5★">
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label">Type de chambre</label>
                <select name="room_prices[{{ $i }}][room_type]" class="form-select form-select-sm">
                    <option value="">—</option>
                    @foreach ($roomTypeOptions as $key => $label)
                        <option value="{{ $key }}" @selected(($row['room_type'] ?? '') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label">Prix</label>
                <input type="number" step="0.01" min="0" name="room_prices[{{ $i }}][price]" class="form-control form-control-sm" value="{{ $row['price'] ?? '' }}">
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label">Ancien prix</label>
                <input type="number" step="0.01" min="0" name="room_prices[{{ $i }}][old_price]" class="form-control form-control-sm" value="{{ $row['old_price'] ?? '' }}">
            </div>
            <div class="col-4 col-lg-1">
                <label class="form-label">Cap.</label>
                <input type="number" min="1" max="20" name="room_prices[{{ $i }}][capacity]" class="form-control form-control-sm" value="{{ $row['capacity'] ?? '' }}">
            </div>
            <div class="col-4 col-lg-1">
                <label class="form-label">Places</label>
                <input type="number" min="0" name="room_prices[{{ $i }}][stock]" class="form-control form-control-sm" value="{{ $row['stock'] ?? 0 }}">
            </div>
            <div class="col-4 col-lg-1 d-flex align-items-center justify-content-between gap-2 pb-1">
                <label class="d-inline-flex align-items-center gap-2 form-text mb-0" style="cursor:pointer;">
                    <input type="hidden" name="room_prices[{{ $i }}][is_active]" value="0">
                    <input type="checkbox" class="form-check-input mt-0" name="room_prices[{{ $i }}][is_active]" value="1" @checked($row['is_active'] ?? 1)>
                    Actif
                </label>
                <button type="button" class="btn btn-sm btn-link text-danger p-0" data-repeat-remove title="Retirer ce tarif">Retirer</button>
            </div>
        </div>
    </div>
@endforeach
