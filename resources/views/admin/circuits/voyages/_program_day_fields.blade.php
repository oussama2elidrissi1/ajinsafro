@php
    $day = $day ?? null;
@endphp
<div class="mb-3">
    <label for="title" class="form-label">Titre du jour <span class="text-danger">*</span></label>
    <input type="text" class="form-control" name="title" id="title" value="{{ old('title', $day?->title) }}" required placeholder="Ex : Arrivée à Casablanca">
</div>
<div class="mb-3">
    <label for="city" class="form-label">Ville / Étape</label>
    <input type="text" class="form-control" name="city" id="city" value="{{ old('city', $day?->city) }}" placeholder="Ex : Casablanca">
</div>
<div class="mb-3">
    <label for="day_label" class="form-label">Badge jour (Inclus / Optionnel / Libre)</label>
    <select class="form-select" name="day_label" id="day_label">
        <option value="">—</option>
        @foreach(\App\Models\TravelProgramDay::DAY_LABELS as $value => $label)
            <option value="{{ $value }}" {{ old('day_label', $day?->day_label) == $value ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>
</div>
<div class="mb-3">
    <label for="content_html" class="form-label">Contenu du jour (HTML : listes, sous-listes, texte riche)</label>
    <textarea class="form-control font-monospace" name="content_html" id="content_html" rows="10" placeholder="<ul><li>Point 1</li><li>Point 2<ul><li>Sous-point</li></ul></li></ul>">{{ old('content_html', $day?->content_html ?? $day?->description) }}</textarea>
    <small class="text-muted">Vous pouvez utiliser &lt;ul&gt;, &lt;ol&gt;, &lt;li&gt;, &lt;p&gt;, &lt;strong&gt;, etc.</small>
</div>
<div class="mb-3">
    <label for="description" class="form-label">Description (texte simple, utilisé si content_html vide)</label>
    <textarea class="form-control" name="description" id="description" rows="3" placeholder="Description courte">{{ old('description', $day?->description) }}</textarea>
</div>
<div class="mb-3">
    <label for="nights" class="form-label">Nombre de nuits</label>
    <select class="form-select" name="nights" id="nights">
        <option value="0" {{ old('nights', $day?->nights ?? 0) == 0 ? 'selected' : '' }}>0</option>
        <option value="1" {{ old('nights', $day?->nights ?? 0) == 1 ? 'selected' : '' }}>1</option>
    </select>
</div>
<div class="mb-3">
    <label for="day_type" class="form-label">Type de journée</label>
    <select class="form-select" name="day_type" id="day_type">
        @foreach(\App\Models\TravelProgramDay::DAY_TYPES as $value => $label)
            <option value="{{ $value }}" {{ old('day_type', $day?->day_type ?? 'visite') == $value ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>
</div>
<div class="mb-3">
    <label class="form-label">Repas inclus</label>
    <div class="form-check">
        <input type="checkbox" class="form-check-input" name="meal_breakfast" value="1" id="meal_breakfast" {{ old('meal_breakfast', $day?->meal_breakfast) ? 'checked' : '' }}>
        <label class="form-check-label" for="meal_breakfast">Petit-déjeuner</label>
    </div>
    <div class="form-check">
        <input type="checkbox" class="form-check-input" name="meal_lunch" value="1" id="meal_lunch" {{ old('meal_lunch', $day?->meal_lunch) ? 'checked' : '' }}>
        <label class="form-check-label" for="meal_lunch">Déjeuner</label>
    </div>
    <div class="form-check">
        <input type="checkbox" class="form-check-input" name="meal_dinner" value="1" id="meal_dinner" {{ old('meal_dinner', $day?->meal_dinner) ? 'checked' : '' }}>
        <label class="form-check-label" for="meal_dinner">Dîner</label>
    </div>
</div>
