@php
    $formulaRows = old('formulas', $package->formulas->map(fn ($formula) => [
        'id' => $formula->id, 'name_fr' => $formula->name_fr, 'name_ar' => $formula->name_ar,
        'description_fr' => $formula->description_fr, 'description_ar' => $formula->description_ar,
        'departure_id' => $formula->departure_id, 'sort_order' => $formula->sort_order,
        'is_active' => (int) $formula->is_active, 'tariff_ids' => $formula->tariffs->modelKeys(),
        'hotels' => $formula->stays->map(fn ($stay) => $stay->only(['hotel_id', 'program_day_id', 'nights_override']))->all(),
    ])->values()->all());
@endphp
<section class="mt-4 border-top pt-4" data-formula-editor>
    <style>.ho-formula-field{display:block}.ho-editor[dir=rtl]{text-align:right}.ho-editor [data-formula-editor] input[type=number]{direction:ltr;text-align:start}</style>
    <input type="hidden" name="formulas_present" value="1">
    <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-3">
        <h6 class="mb-0" data-ho-fr="Formules commerciales" data-ho-ar="الباقات التجارية">Formules commerciales</h6>
        <button type="button" class="btn btn-sm btn-outline-primary" data-formula-add data-ho-fr="+ Ajouter une formule" data-ho-ar="+ إضافة باقة">+ Ajouter une formule</button>
    </div>
    <p class="text-muted small" data-ho-fr="Sélectionnez les hébergements, tarifs et départs de cette offre. Toute modification des sources se répercute sur les formules." data-ho-ar="اختر فنادق وأسعار ومواعيد هذا العرض. تنعكس تعديلات البيانات الأصلية تلقائياً على الباقات.">Sélectionnez les hébergements, tarifs et départs de cette offre. Toute modification des sources se répercute sur les formules.</p>
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
