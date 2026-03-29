@php
    $veOld = old('voyage_extras');
    if ($veOld !== null && is_array($veOld)) {
        $veRows = $veOld;
    } elseif (isset($voyageExtras) && $voyageExtras->isNotEmpty()) {
        $veRows = $voyageExtras->map(function ($e) {
            return [
                'id' => $e->id,
                'name' => $e->name,
                'description' => $e->description,
                'price_adult' => $e->price_adult,
                'price_child' => $e->price_child,
                'is_active' => $e->is_active ? '1' : '',
                'extra_type' => $e->extra_type,
                'icon' => $e->icon ?: 'fa-plus-circle',
            ];
        })->values()->all();
    } else {
        $veRows = [];
    }
    if ($veRows === []) {
        $veRows = [['id' => '', 'name' => '', 'description' => '', 'price_adult' => '', 'price_child' => '', 'is_active' => '1', 'extra_type' => '', 'icon' => 'fa-plus-circle']];
    }
@endphp
<div class="table-responsive">
    <table class="table table-sm table-bordered align-middle" id="voyage-extras-table">
        <thead class="table-light">
            <tr>
                <th style="width:18%">Titre <span class="text-danger">*</span></th>
                <th style="width:22%">Description</th>
                <th style="width:9%">Prix adulte</th>
                <th style="width:9%">Prix enfant</th>
                <th style="width:8%">Actif</th>
                <th style="width:10%">Type</th>
                <th style="width:10%">Icône (FA)</th>
                <th style="width:6%"></th>
            </tr>
        </thead>
        <tbody id="voyage-extras-tbody">
            @foreach($veRows as $vi => $row)
                <tr class="voyage-extra-row">
                    <td>
                        <input type="hidden" name="voyage_extras[{{ $vi }}][id]" value="{{ $row['id'] ?? '' }}">
                        <input type="text" class="form-control form-control-sm" name="voyage_extras[{{ $vi }}][name]" value="{{ $row['name'] ?? '' }}" placeholder="ex. Demi-pension" maxlength="255">
                    </td>
                    <td><input type="text" class="form-control form-control-sm" name="voyage_extras[{{ $vi }}][description]" value="{{ $row['description'] ?? '' }}" placeholder="Courte description"></td>
                    <td><input type="number" step="0.01" min="0" class="form-control form-control-sm" name="voyage_extras[{{ $vi }}][price_adult]" value="{{ $row['price_adult'] ?? '' }}"></td>
                    <td><input type="number" step="0.01" min="0" class="form-control form-control-sm" name="voyage_extras[{{ $vi }}][price_child]" value="{{ $row['price_child'] ?? '' }}"></td>
                    <td class="text-center">
                        <input type="hidden" name="voyage_extras[{{ $vi }}][is_active]" value="0">
                        <input type="checkbox" class="form-check-input" name="voyage_extras[{{ $vi }}][is_active]" value="1" {{ !empty($row['is_active']) ? 'checked' : '' }}>
                    </td>
                    <td><input type="text" class="form-control form-control-sm" name="voyage_extras[{{ $vi }}][extra_type]" value="{{ $row['extra_type'] ?? '' }}" placeholder="optionnel"></td>
                    <td><input type="text" class="form-control form-control-sm" name="voyage_extras[{{ $vi }}][icon]" value="{{ $row['icon'] ?? 'fa-plus-circle' }}" placeholder="fa-utensils"></td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-danger voyage-extra-remove" title="Supprimer">&times;</button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
<button type="button" class="btn btn-sm btn-outline-primary" id="voyage-extras-add"><i class="bx bx-plus"></i> Ajouter un extra</button>

@push('scripts')
<script>
(function () {
    var tbody = document.getElementById('voyage-extras-tbody');
    var addBtn = document.getElementById('voyage-extras-add');
    if (!tbody || !addBtn) return;
    function nextIndex() {
        return tbody.querySelectorAll('tr.voyage-extra-row').length;
    }
    addBtn.addEventListener('click', function () {
        var i = nextIndex();
        var tr = document.createElement('tr');
        tr.className = 'voyage-extra-row';
        tr.innerHTML =
            '<td><input type="hidden" name="voyage_extras[' + i + '][id]" value="">' +
            '<input type="text" class="form-control form-control-sm" name="voyage_extras[' + i + '][name]" value="" placeholder="ex. Demi-pension" maxlength="255"></td>' +
            '<td><input type="text" class="form-control form-control-sm" name="voyage_extras[' + i + '][description]" value="" placeholder="Courte description"></td>' +
            '<td><input type="number" step="0.01" min="0" class="form-control form-control-sm" name="voyage_extras[' + i + '][price_adult]" value=""></td>' +
            '<td><input type="number" step="0.01" min="0" class="form-control form-control-sm" name="voyage_extras[' + i + '][price_child]" value=""></td>' +
            '<td class="text-center"><input type="hidden" name="voyage_extras[' + i + '][is_active]" value="0">' +
            '<input type="checkbox" class="form-check-input" name="voyage_extras[' + i + '][is_active]" value="1" checked></td>' +
            '<td><input type="text" class="form-control form-control-sm" name="voyage_extras[' + i + '][extra_type]" value="" placeholder="optionnel"></td>' +
            '<td><input type="text" class="form-control form-control-sm" name="voyage_extras[' + i + '][icon]" value="fa-plus-circle" placeholder="fa-utensils"></td>' +
            '<td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger voyage-extra-remove" title="Supprimer">&times;</button></td>';
        tbody.appendChild(tr);
        tr.querySelector('.voyage-extra-remove').addEventListener('click', function () { tr.remove(); });
    });
    tbody.querySelectorAll('.voyage-extra-remove').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var row = btn.closest('tr');
            if (row && tbody.querySelectorAll('tr.voyage-extra-row').length > 1) row.remove();
        });
    });
})();
</script>
@endpush
