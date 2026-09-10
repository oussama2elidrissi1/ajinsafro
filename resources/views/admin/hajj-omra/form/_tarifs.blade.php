{{-- Etape 2 : grille tarifaire par type de chambre. --}}
@php
    $roomRows = old('room_prices', $package->roomPrices->map(fn ($r) => [
        'id' => $r->id,
        'room_type' => $r->room_type,
        'price' => $r->price,
        'old_price' => $r->old_price,
        'capacity' => $r->capacity,
        'stock' => $r->stock,
        'is_active' => $r->is_active ? 1 : 0,
    ])->values()->all());
@endphp

<div class="ho-panel" data-panel="tarifs">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h6 class="text-uppercase text-muted small mb-1">Tarifs &amp; chambres</h6>
            <p class="text-muted small mb-0">Liez ces tarifs aux formules ci-dessous. Le prix actif le plus bas devient le « à partir de ».</p>
        </div>
        <button type="button" class="btn btn-sm btn-outline-primary" data-repeat-add="room">+ Ajouter un tarif</button>
    </div>

    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width:22%">Type de chambre</th>
                    <th style="width:15%">Prix</th>
                    <th style="width:15%">Ancien prix</th>
                    <th style="width:12%">Capacité</th>
                    <th style="width:12%">Places</th>
                    <th style="width:10%">Actif</th>
                    <th style="width:8%" class="text-end">Action</th>
                </tr>
            </thead>
            <tbody data-repeat-list="room">
                @forelse ($roomRows as $i => $row)
                    <tr class="ho-repeat-item" data-repeat-item>
                        <td>
                            <input type="hidden" name="room_prices[{{ $i }}][id]" value="{{ $row['id'] ?? '' }}">
<input type="hidden" name="room_prices[{{ $i }}][client_key]" value="{{ $row['client_key'] ?? '' }}">
                            <select name="room_prices[{{ $i }}][room_type]" class="form-select form-select-sm">
                                <option value="">—</option>
                                @foreach ($roomTypeOptions as $key => $label)
                                    <option value="{{ $key }}" @selected(($row['room_type'] ?? '') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td><input type="number" step="0.01" min="0" name="room_prices[{{ $i }}][price]" class="form-control form-control-sm" value="{{ $row['price'] ?? '' }}"></td>
                        <td><input type="number" step="0.01" min="0" name="room_prices[{{ $i }}][old_price]" class="form-control form-control-sm" value="{{ $row['old_price'] ?? '' }}"></td>
                        <td><input type="number" min="1" max="20" name="room_prices[{{ $i }}][capacity]" class="form-control form-control-sm" value="{{ $row['capacity'] ?? '' }}"></td>
                        <td><input type="number" min="0" name="room_prices[{{ $i }}][stock]" class="form-control form-control-sm" value="{{ $row['stock'] ?? 0 }}"></td>
                        <td>
                            <input type="hidden" name="room_prices[{{ $i }}][is_active]" value="0">
                            <input type="checkbox" class="form-check-input" name="room_prices[{{ $i }}][is_active]" value="1" @checked(($row['is_active'] ?? 1))>
                        </td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-outline-danger" data-repeat-remove>Retirer</button>
                        </td>
                    </tr>
                @empty
                    <tr data-repeat-empty><td colspan="7" class="text-center text-muted py-4">Aucun tarif. Ajoutez au moins un type de chambre.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @error('room_prices')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
    @foreach ($errors->get('room_prices.*') as $messages)
        @foreach ($messages as $message)<div class="text-danger small mt-1">{{ $message }}</div>@endforeach
    @endforeach

    {{-- Modele de ligne clone par le JS des repeaters. __INDEX__ est remplace a l'insertion. --}}
    <template data-repeat-template="room">
        <tr class="ho-repeat-item" data-repeat-item>
            <td>
                <input type="hidden" name="room_prices[__INDEX__][id]" value="">
<input type="hidden" name="room_prices[__INDEX__][client_key]" value="">
                <select name="room_prices[__INDEX__][room_type]" class="form-select form-select-sm">
                    <option value="">—</option>
                    @foreach ($roomTypeOptions as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </td>
            <td><input type="number" step="0.01" min="0" name="room_prices[__INDEX__][price]" class="form-control form-control-sm"></td>
            <td><input type="number" step="0.01" min="0" name="room_prices[__INDEX__][old_price]" class="form-control form-control-sm"></td>
            <td><input type="number" min="1" max="20" name="room_prices[__INDEX__][capacity]" class="form-control form-control-sm"></td>
            <td><input type="number" min="0" name="room_prices[__INDEX__][stock]" class="form-control form-control-sm" value="0"></td>
            <td>
                <input type="hidden" name="room_prices[__INDEX__][is_active]" value="0">
                <input type="checkbox" class="form-check-input" name="room_prices[__INDEX__][is_active]" value="1" checked>
            </td>
            <td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger" data-repeat-remove>Retirer</button></td>
        </tr>
    </template>
    @include('admin.hajj-omra.form._formulas')
</div>
