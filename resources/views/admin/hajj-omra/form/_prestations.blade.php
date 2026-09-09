{{-- Etape 6 : prestations incluses / non incluses, conditions et documents. --}}
@php
    $allItems = old('service_items');

    if ($allItems === null) {
        $allItems = $package->serviceItems->map(fn ($s) => [
            'id' => $s->id,
            'kind' => $s->kind,
            'label' => $s->label,
            'label_ar' => $s->label_ar,
        ])->values()->all();
    }

    // Un index global unique est conserve pour que les deux listes cohabitent
    // dans le meme tableau `service_items[]` cote serveur.
    $includedRows = [];
    $excludedRows = [];

    foreach ($allItems as $index => $item) {
        $entry = ['index' => $index] + $item;
        if (($item['kind'] ?? 'included') === 'excluded') {
            $excludedRows[] = $entry;
        } else {
            $includedRows[] = $entry;
        }
    }
@endphp

<div class="ho-panel" data-panel="prestations">
    <div class="row g-4">
        @foreach ([
            ['kind' => 'included', 'title' => 'Ce qui est inclus', 'titleAr' => 'الخدمات المشمولة', 'rows' => $includedRows, 'icon' => '✓', 'class' => 'text-success'],
            ['kind' => 'excluded', 'title' => 'Ce qui n\'est pas inclus', 'titleAr' => 'الخدمات غير المشمولة', 'rows' => $excludedRows, 'icon' => '✕', 'class' => 'text-danger'],
        ] as $block)
            <div class="col-lg-6">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="text-uppercase text-muted small mb-0">
                        {{ $block['title'] }}
                        <span class="ms-1" dir="rtl" lang="ar">{{ $block['titleAr'] }}</span>
                    </h6>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-repeat-add="service-{{ $block['kind'] }}">+ Ajouter</button>
                </div>

                <div data-repeat-list="service-{{ $block['kind'] }}">
                    @forelse ($block['rows'] as $row)
                        <div class="ho-row py-2" data-repeat-item>
                            <input type="hidden" name="service_items[{{ $row['index'] }}][id]" value="{{ $row['id'] ?? '' }}">
                            <input type="hidden" name="service_items[{{ $row['index'] }}][kind]" value="{{ $block['kind'] }}">
                            <div class="d-flex align-items-center gap-2">
                                <span class="{{ $block['class'] }}">{{ $block['icon'] }}</span>
                                <div class="flex-grow-1">
                                    <div data-lang-pane="fr">
                                        <input type="text" name="service_items[{{ $row['index'] }}][label]" class="form-control form-control-sm"
                                               value="{{ $row['label'] ?? '' }}" placeholder="Billet d'avion">
                                    </div>
                                    <div data-lang-pane="ar">
                                        <input type="text" name="service_items[{{ $row['index'] }}][label_ar]" class="form-control form-control-sm"
                                               dir="rtl" lang="ar" value="{{ $row['label_ar'] ?? '' }}" placeholder="تذكرة الطيران">
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-danger" data-repeat-remove>&times;</button>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted small" data-repeat-empty>Aucun élément.</p>
                    @endforelse
                </div>

                <template data-repeat-template="service-{{ $block['kind'] }}">
                    <div class="ho-row py-2" data-repeat-item>
                        <input type="hidden" name="service_items[__INDEX__][id]" value="">
                        <input type="hidden" name="service_items[__INDEX__][kind]" value="{{ $block['kind'] }}">
                        <div class="d-flex align-items-center gap-2">
                            <span class="{{ $block['class'] }}">{{ $block['icon'] }}</span>
                            <div class="flex-grow-1">
                                <div data-lang-pane="fr">
                                    <input type="text" name="service_items[__INDEX__][label]" class="form-control form-control-sm" placeholder="Billet d'avion">
                                </div>
                                <div data-lang-pane="ar">
                                    <input type="text" name="service_items[__INDEX__][label_ar]" class="form-control form-control-sm" dir="rtl" lang="ar" placeholder="تذكرة الطيران">
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-danger" data-repeat-remove>&times;</button>
                        </div>
                    </div>
                </template>
            </div>
        @endforeach
    </div>

    <hr class="my-4">

    <div class="row g-3">
        <div class="col-lg-6">
            @include('admin.hajj-omra.form._field', [
                'name' => 'booking_conditions_fr',
                'nameAr' => 'booking_conditions_ar',
                'label' => 'Conditions de réservation',
                'labelAr' => 'شروط الحجز',
                'value' => $package->booking_conditions,
                'valueAr' => $package->booking_conditions_ar,
                'type' => 'textarea',
                'rows' => 6,
            ])
        </div>
        <div class="col-lg-6">
            @include('admin.hajj-omra.form._field', [
                'name' => 'required_documents_fr',
                'nameAr' => 'required_documents_ar',
                'label' => 'Documents nécessaires',
                'labelAr' => 'الوثائق المطلوبة',
                'value' => $package->required_documents,
                'valueAr' => $package->required_documents_ar,
                'type' => 'textarea',
                'rows' => 6,
            ])
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-md-3">
            <label class="form-label" for="meal_plan">Pension par défaut</label>
            <select id="meal_plan" name="meal_plan" class="form-select">
                <option value="">—</option>
                @foreach ($mealPlanOptions as $key => $label)
                    <option value="{{ $key }}" @selected(old('meal_plan', $package->meal_plan) === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-9 d-flex align-items-end gap-4 flex-wrap">
            @foreach ([
                'transport_included' => 'Transport inclus',
                'visa_included' => 'Visa inclus',
                'guidance_included' => 'Encadrement inclus',
            ] as $field => $label)
                <div class="form-check">
                    <input type="hidden" name="{{ $field }}" value="0">
                    <input class="form-check-input" type="checkbox" id="{{ $field }}" name="{{ $field }}" value="1"
                           @checked(old($field, $package->{$field}))>
                    <label class="form-check-label" for="{{ $field }}">{{ $label }}</label>
                </div>
            @endforeach
        </div>
    </div>
</div>
