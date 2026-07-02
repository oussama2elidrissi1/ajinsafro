@php
    $paymentMethodOptions = $paymentMethodOptions ?? \App\Services\BusinessReferentialService::paymentMethods();
    $ref = $businessReferentials ?? \App\Services\BusinessReferentialService::allMerged();
    $discountScopes = $ref['discount_scopes'] ?? [];
    $discountConditions = $ref['discount_conditions'] ?? [];
    $discountTypes = $ref['discount_types'] ?? [];
    $discountRules = $discountRules ?? collect();
    $paymentMethodsRefUrl = \Illuminate\Support\Facades\Route::has('admin.settings.referentiels-metier.group')
        ? route('admin.settings.referentiels-metier.group', ['groupKey' => 'payment_methods'])
        : (\Illuminate\Support\Facades\Route::has('admin.settings.index') ? route('admin.settings.index') : '#');
    $paymentMethodUi = [
        'is_meta_payment_gateway_st_cashplus' => ['icon' => 'bx bx-store-alt', 'tone' => 'orange', 'hint' => 'Paiement CashPlus'],
        'is_meta_payment_gateway_st_wafacash' => ['icon' => 'bx bx-wallet', 'tone' => 'red', 'hint' => 'Paiement Wafacash'],
        'is_meta_payment_gateway_st_bank_transfer' => ['icon' => 'bx bx-bank', 'tone' => 'blue', 'hint' => 'Virement bancaire'],
        'is_meta_payment_gateway_st_cash_transfer' => ['icon' => 'bx bx-transfer-alt', 'tone' => 'green', 'hint' => 'Transfert cash'],
        'is_meta_payment_gateway_st_paypal' => ['icon' => 'bx bxl-paypal', 'tone' => 'navy', 'hint' => 'Paiement PayPal'],
    ];
@endphp
<div class="tab-pane" id="price" role="tabpanel" data-ve-pane-title="Tarifs">
    <div class="card ve-pane-card mb-3">
        <div class="card-body">
            <p class="text-uppercase text-muted small fw-bold mb-2">Prix de base</p>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label" for="min_price">Prix minimum (MAD)</label>
                    <input type="number" class="form-control" id="min_price" name="min_price" value="{{ old('min_price', $meta['min_price'] ?? '') }}" step="0.01" min="0">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="base_price">Prix de base (MAD)</label>
                    <input type="number" class="form-control" id="base_price" name="base_price" value="{{ old('base_price', $meta['base_price'] ?? '') }}" step="0.01" min="0">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="sale_price">Prix soldé (MAD)</label>
                    <input type="number" class="form-control" id="sale_price" name="sale_price" value="{{ old('sale_price', $meta['sale_price'] ?? '') }}" step="0.01" min="0">
                </div>
            </div>
        </div>
    </div>

    <div class="card ve-pane-card mb-3">
        <div class="card-body">
            <p class="text-uppercase text-muted small fw-bold mb-2">Tarifs par profil</p>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label" for="adult_price">Prix adulte (MAD)</label>
                    <input type="number" class="form-control" id="adult_price" name="adult_price" value="{{ old('adult_price', $meta['adult_price'] ?? '') }}" step="0.01" min="0">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="child_price">Prix enfant (MAD)</label>
                    <input type="number" class="form-control" id="child_price" name="child_price" value="{{ old('child_price', $meta['child_price'] ?? '') }}" step="0.01" min="0">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="infant_price">Prix bébé (MAD)</label>
                    <input type="number" class="form-control" id="infant_price" name="infant_price" value="{{ old('infant_price', $meta['infant_price'] ?? '') }}" step="0.01" min="0">
                </div>
            </div>
        </div>
    </div>

    <div class="card ve-pane-card mb-3" id="ve-discount-rules-block">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div>
                    <p class="text-uppercase text-muted small fw-bold mb-0">Réductions</p>
                    <p class="text-muted small mb-0">Règles métier (priorité croissante : la plus forte priorité peut être appliquée en premier selon votre moteur de calcul).</p>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary" id="ve-add-discount-rule">Ajouter une règle</button>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0" id="ve-discount-rules-table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Portée</th>
                            <th>Condition</th>
                            <th class="text-end">Valeur</th>
                            <th class="text-center">Priorité</th>
                            <th class="text-center">Actif</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($discountRules as $i => $rule)
                            <tr class="ve-discount-rule-row">
                                <td>
                                    <select class="form-select form-select-sm" name="discount_rules[{{ $i }}][reduction_type]">
                                        @foreach($discountTypes as $dt)
                                            <option value="{{ $dt['value'] }}" @selected(($rule->reduction_type ?? '') === $dt['value'])>{{ $dt['label'] }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <select class="form-select form-select-sm" name="discount_rules[{{ $i }}][scope]">
                                        @foreach($discountScopes as $sc)
                                            <option value="{{ $sc['value'] }}" @selected(($rule->scope ?? '') === $sc['value'])>{{ $sc['label'] }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <select class="form-select form-select-sm" name="discount_rules[{{ $i }}][condition_type]">
                                        @foreach($discountConditions as $c)
                                            <option value="{{ $c['value'] }}" @selected(($rule->condition_type ?? '') === $c['value'])>{{ $c['label'] }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td style="max-width:110px">
                                    <input type="number" step="0.01" class="form-control form-control-sm text-end" name="discount_rules[{{ $i }}][value]" value="{{ old('discount_rules.'.$i.'.value', $rule->value) }}">
                                </td>
                                <td style="max-width:90px">
                                    <input type="number" class="form-control form-control-sm text-center" name="discount_rules[{{ $i }}][priority]" value="{{ old('discount_rules.'.$i.'.priority', $rule->priority ?? 100) }}">
                                </td>
                                <td class="text-center">
                                    <input type="hidden" name="discount_rules[{{ $i }}][is_active]" value="0">
                                    <input type="checkbox" class="form-check-input" name="discount_rules[{{ $i }}][is_active]" value="1" @checked(old('discount_rules.'.$i.'.is_active', $rule->is_active ?? true))>
                                </td>
                                <td><button type="button" class="btn btn-sm btn-outline-danger ve-remove-discount-rule">?</button></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card ve-pane-card mb-3">
        <div class="card-body">
            <p class="text-uppercase text-muted small fw-bold mb-2">Commissions</p>
            <div class="row g-3 align-items-end">
                @php
                    $adultCommissionType = old('commission_adulte_type', $meta['commission_adulte_type'] ?? 'fixed');
                    $childCommissionType = old('commission_enfant_type', $meta['commission_enfant_type'] ?? 'fixed');
                @endphp
                <div class="col-md-5">
                    <label class="form-label" for="commission_adulte">Commission adulte</label>
                    <div class="input-group">
                        <input type="number" class="form-control" id="commission_adulte" name="commission_adulte" value="{{ old('commission_adulte', $meta['commission_adulte'] ?? '') }}" step="0.01" min="0" placeholder="0">
                        <div class="input-group-text p-0 border-0">
                            <div class="btn-group" role="group" aria-label="Type commission adulte">
                                <input type="radio" class="btn-check" name="commission_adulte_type" id="commission_adulte_type_percentage" value="percentage" autocomplete="off" {{ $adultCommissionType === 'percentage' ? 'checked' : '' }}>
                                <label class="btn btn-outline-secondary" for="commission_adulte_type_percentage" style="border-radius:0;">%</label>
                                <input type="radio" class="btn-check" name="commission_adulte_type" id="commission_adulte_type_fixed" value="fixed" autocomplete="off" {{ $adultCommissionType === 'fixed' ? 'checked' : '' }}>
                                <label class="btn btn-outline-secondary" for="commission_adulte_type_fixed">MAD</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-5">
                    <label class="form-label" for="commission_enfant">Commission enfant</label>
                    <div class="input-group">
                        <input type="number" class="form-control" id="commission_enfant" name="commission_enfant" value="{{ old('commission_enfant', $meta['commission_enfant'] ?? '') }}" step="0.01" min="0" placeholder="0">
                        <div class="input-group-text p-0 border-0">
                            <div class="btn-group" role="group" aria-label="Type commission enfant">
                                <input type="radio" class="btn-check" name="commission_enfant_type" id="commission_enfant_type_percentage" value="percentage" autocomplete="off" {{ $childCommissionType === 'percentage' ? 'checked' : '' }}>
                                <label class="btn btn-outline-secondary" for="commission_enfant_type_percentage" style="border-radius:0;">%</label>
                                <input type="radio" class="btn-check" name="commission_enfant_type" id="commission_enfant_type_fixed" value="fixed" autocomplete="off" {{ $childCommissionType === 'fixed' ? 'checked' : '' }}>
                                <label class="btn btn-outline-secondary" for="commission_enfant_type_fixed">MAD</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card ve-pane-card mb-3">
        <div class="card-body">
            <p class="text-uppercase text-muted small fw-bold mb-2">Moyens de paiement</p>
            <p class="text-muted small mb-3">Liste métier utilisée pour ce voyage: CashPlus, Wafacash, virement bancaire, transfert cash et PayPal.</p>
            <div class="ve-payment-method-grid">
                @foreach($paymentMethodOptions as $pm)
                    @php
                        $mk = $pm['meta_key'];
                        $paymentUi = $paymentMethodUi[$mk] ?? ['icon' => 'bx bx-credit-card', 'tone' => 'slate', 'hint' => 'Moyen de paiement'];
                        $isChecked = old($mk, $meta[$mk] ?? '') === 'on';
                    @endphp
                    <label class="ve-payment-method-card ve-payment-method-card--{{ $paymentUi['tone'] }}" for="{{ $mk }}">
                        <input type="hidden" name="{{ $mk }}" value="0">
                        <input class="ve-payment-method-card__input" type="checkbox" id="{{ $mk }}" name="{{ $mk }}" value="1" @checked($isChecked)>
                        <span class="ve-payment-method-card__check" aria-hidden="true">
                            <i class="bx bx-check"></i>
                        </span>
                        <span class="ve-payment-method-card__icon" aria-hidden="true">
                            <i class="{{ $paymentUi['icon'] }}"></i>
                        </span>
                        <span class="ve-payment-method-card__body">
                            <span class="ve-payment-method-card__title">{{ $pm['label'] }}</span>
                            <span class="ve-payment-method-card__hint">{{ $paymentUi['hint'] }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const tbody = document.querySelector('#ve-discount-rules-table tbody');
    const addBtn = document.getElementById('ve-add-discount-rule');
    if (!tbody || !addBtn) return;
    const scopes = @json($discountScopes);
    const conds = @json($discountConditions);
    const types = @json($discountTypes);
    let idx = tbody.querySelectorAll('tr').length;
    function addRow() {
        const tr = document.createElement('tr');
        tr.className = 've-discount-rule-row';
        tr.innerHTML = '<td><select class="form-select form-select-sm" name="discount_rules['+idx+'][reduction_type]">' +
            types.map(o => '<option value="'+o.value+'">'+o.label+'</option>').join('') + '</select></td>' +
            '<td><select class="form-select form-select-sm" name="discount_rules['+idx+'][scope]">' +
            scopes.map(o => '<option value="'+o.value+'">'+o.label+'</option>').join('') + '</select></td>' +
            '<td><select class="form-select form-select-sm" name="discount_rules['+idx+'][condition_type]">' +
            conds.map(o => '<option value="'+o.value+'">'+o.label+'</option>').join('') + '</select></td>' +
            '<td><input type="number" step="0.01" class="form-control form-control-sm text-end" name="discount_rules['+idx+'][value]" value="0"></td>' +
            '<td><input type="number" class="form-control form-control-sm text-center" name="discount_rules['+idx+'][priority]" value="100"></td>' +
            '<td class="text-center"><input type="hidden" name="discount_rules['+idx+'][is_active]" value="0">' +
            '<input type="checkbox" class="form-check-input" name="discount_rules['+idx+'][is_active]" value="1" checked></td>' +
            '<td><button type="button" class="btn btn-sm btn-outline-danger ve-remove-discount-rule">?</button></td>';
        tbody.appendChild(tr);
        idx++;
    }
    addBtn.addEventListener('click', addRow);
    tbody.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('ve-remove-discount-rule')) {
            const row = e.target.closest('tr');
            if (row) row.remove();
        }
    });
})();
</script>
