@php
    $formulaRows = old('formulas', $package->formulas->map(fn ($formula) => [
        'id' => $formula->id, 'name_fr' => $formula->name_fr, 'name_ar' => $formula->name_ar,
        'description_fr' => $formula->description_fr, 'description_ar' => $formula->description_ar,
        'departure_id' => $formula->departure_id, 'sort_order' => $formula->sort_order,
        'is_active' => (int) $formula->is_active, 'tariff_ids' => $formula->tariffs->modelKeys(),
        'hotels' => $formula->stays->map(fn ($stay) => $stay->only(['hotel_id', 'program_day_id', 'nights_override']))->all(),
    ])->values()->all());
@endphp
<section class="ho-card" data-formula-editor>
    <style>.ho-formula-field{display:block}.ho-editor[dir=rtl]{text-align:right}.ho-editor [data-formula-editor] input[type=number]{direction:ltr;text-align:start}</style>
    <input type="hidden" name="formulas_present" value="1">
    <div class="ho-card__head">
        <div>
            <div class="ho-eyebrow mb-0" data-ho-fr="Formules commerciales" data-ho-ar="الباقات التجارية">Formules commerciales</div>
            <p data-ho-fr="Ce que voit le client : une formule = un départ + ses hébergements + les tarifs retenus." data-ho-ar="ما يراه العميل: الباقة = موعد + إقاماتها + الأسعار المختارة.">Ce que voit le client : une formule = un départ + ses hébergements + les tarifs retenus.</p>
        </div>
        <button type="button" class="btn btn-sm btn-outline-primary" data-formula-add data-ho-fr="+ Ajouter une formule" data-ho-ar="+ إضافة باقة">+ Ajouter une formule</button>
    </div>
    <div data-formula-list></div>
    @foreach ($errors->get('formulas.*') as $messages)
        @foreach ($messages as $message)<p class="text-danger small" role="alert">{{ $message }}</p>@endforeach
    @endforeach
    @if ($package->exists)
        <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.hajj-omra.preview', $package) }}#formules" target="_blank" rel="noopener" data-formula-preview data-ho-fr="Prévisualiser le tableau client enregistré" data-ho-ar="معاينة جدول العميل المحفوظ">Prévisualiser le tableau client enregistré</a>
    @endif
    <p class="form-text" data-ho-fr="Enregistrez le brouillon pour prévisualiser vos dernières modifications avant publication." data-ho-ar="احفظ المسودة لمعاينة آخر التعديلات قبل النشر.">Enregistrez le brouillon pour prévisualiser vos dernières modifications avant publication.</p>
    <script type="application/json" data-formula-initial>@json($formulaRows)</script>
</section>
