@php
    $allVoyageThemes = $allVoyageThemes ?? collect();
    $laravelV = $laravelVoyage ?? null;
    $selectedThemeIds = $laravelV ? $laravelV->themes->pluck('id')->map(fn ($id) => (int) $id)->all() : [];
@endphp
<div class="card ve-pane-card mb-4">
    <div class="card-body">
        <h5 class="card-title mb-2"><i class="bx bx-purchase-tag text-primary"></i> Thèmes du voyage</h5>
        <p class="text-muted small mb-3">Sélectionnez un ou plusieurs thèmes. Ils servent au filtre « Thème du voyage » sur le catalogue (site public) et sont synchronisés vers WordPress.</p>
        <label for="voyage_theme_ids" class="form-label">Thèmes (multi-sélection)</label>
        <select
            name="voyage_theme_ids[]"
            id="voyage_theme_ids"
            class="form-select"
            multiple
            size="{{ min(12, max(6, $allVoyageThemes->count())) }}"
            aria-describedby="voyage_theme_ids_help"
        >
            @foreach ($allVoyageThemes as $th)
                <option value="{{ (int) $th->id }}" @selected(in_array((int) $th->id, $selectedThemeIds, true))>
                    {{ $th->name }}
                </option>
            @endforeach
        </select>
        <p id="voyage_theme_ids_help" class="form-text text-muted mb-0">Maintenez Ctrl (Windows) ou ⌘ (Mac) pour sélectionner plusieurs entrées.</p>
    </div>
</div>
