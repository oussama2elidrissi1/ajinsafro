<div class="tab-pane" id="price" role="tabpanel">
                <div class="card ve-pane-card">
                    <div class="card-body">
                        <h4 class="card-title mb-4">ParamÃ¨tres de prix</h4>
                        
                        <div class="row">
                            <div class="col-lg-4">
                                <div class="mb-3">
                                    <label for="min_price" class="form-label">Prix minimum (MAD)</label>
                                    <input type="number" class="form-control" id="min_price" name="min_price" value="{{ old('min_price', $meta['min_price'] ?? '') }}" step="0.01" min="0">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="base_price" class="form-label">Prix de base (MAD)</label>
                                    <input type="number" class="form-control" id="base_price" name="base_price" value="{{ old('base_price', $meta['base_price'] ?? '') }}" step="0.01" min="0">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="sale_price" class="form-label">Prix soldÃ© (MAD)</label>
                                    <input type="number" class="form-control" id="sale_price" name="sale_price" value="{{ old('sale_price', $meta['sale_price'] ?? '') }}" step="0.01" min="0">
                                </div>
                            </div>
                            
                            <div class="col-lg-4">
                                <div class="mb-3">
                                    <label for="adult_price" class="form-label">Prix Adulte (MAD)</label>
                                    <input type="number" class="form-control" id="adult_price" name="adult_price" value="{{ old('adult_price', $meta['adult_price'] ?? '') }}" step="0.01" min="0">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="child_price" class="form-label">Prix Enfant (MAD)</label>
                                    <input type="number" class="form-control" id="child_price" name="child_price" value="{{ old('child_price', $meta['child_price'] ?? '') }}" step="0.01" min="0">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="infant_price" class="form-label">Prix BÃ©bÃ© (MAD)</label>
                                    <input type="number" class="form-control" id="infant_price" name="infant_price" value="{{ old('infant_price', $meta['infant_price'] ?? '') }}" step="0.01" min="0">
                                </div>
                                <div class="mb-3">
                                    <label for="commission_adulte" class="form-label">Commission Adulte (MAD)</label>
                                    <input type="number" class="form-control" id="commission_adulte" name="commission_adulte" value="{{ old('commission_adulte', $meta['commission_adulte'] ?? '') }}" step="0.01" min="0" placeholder="0">
                                </div>
                                <div class="mb-3">
                                    <label for="commission_enfant" class="form-label">Commission Enfant (MAD)</label>
                                    <input type="number" class="form-control" id="commission_enfant" name="commission_enfant" value="{{ old('commission_enfant', $meta['commission_enfant'] ?? '') }}" step="0.01" min="0" placeholder="0">
                                </div>
                            </div>
                            
                            <div class="col-lg-4">
                                <div class="mb-3">
                                    <label for="discount" class="form-label">RÃ©duction</label>
                                    <input type="text" class="form-control" id="discount" name="discount" value="{{ old('discount', $meta['discount'] ?? '') }}">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="discount_type" class="form-label">Type de rÃ©duction</label>
                                    <select class="form-select" id="discount_type" name="discount_type">
                                        <option value="">Aucune</option>
                                        <option value="percent" {{ old('discount_type', $meta['discount_type'] ?? '') === 'percent' ? 'selected' : '' }}>Pourcentage</option>
                                        <option value="fixed" {{ old('discount_type', $meta['discount_type'] ?? '') === 'fixed' ? 'selected' : '' }}>Montant fixe</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="discount_by_people_type" class="form-label">RÃ©duction selon type personne</label>
                                    <input type="text" class="form-control" id="discount_by_people_type" name="discount_by_people_type" value="{{ old('discount_by_people_type', $meta['discount_by_people_type'] ?? '') }}" placeholder="adult,child,infant">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="calculator_discount_by_people_type" class="form-label">Calculateur rÃ©duction</label>
                                    <input type="text" class="form-control" id="calculator_discount_by_people_type" name="calculator_discount_by_people_type" value="{{ old('calculator_discount_by_people_type', $meta['calculator_discount_by_people_type'] ?? '') }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

                <div class="card mt-3">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Moyens de paiement</h4>
                        <div class="row">
                            <div class="col-lg-4">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="is_meta_payment_gateway_st_paypal" name="is_meta_payment_gateway_st_paypal" value="1" {{ old('is_meta_payment_gateway_st_paypal', $meta['is_meta_payment_gateway_st_paypal'] ?? '') === 'on' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_meta_payment_gateway_st_paypal">
                                        <i class="bx bxl-paypal"></i> PayPal
                                    </label>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="is_meta_payment_gateway_st_onepay" name="is_meta_payment_gateway_st_onepay" value="1" {{ old('is_meta_payment_gateway_st_onepay', $meta['is_meta_payment_gateway_st_onepay'] ?? '') === 'on' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_meta_payment_gateway_st_onepay">
                                        OnePay
                                    </label>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="is_meta_payment_gateway_st_onepay_atm" name="is_meta_payment_gateway_st_onepay_atm" value="1" {{ old('is_meta_payment_gateway_st_onepay_atm', $meta['is_meta_payment_gateway_st_onepay_atm'] ?? '') === 'on' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_meta_payment_gateway_st_onepay_atm">
                                        OnePay ATM
                                    </label>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="is_meta_payment_gateway_st_payu" name="is_meta_payment_gateway_st_payu" value="1" {{ old('is_meta_payment_gateway_st_payu', $meta['is_meta_payment_gateway_st_payu'] ?? '') === 'on' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_meta_payment_gateway_st_payu">
                                        PayU
                                    </label>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="is_meta_payment_gateway_st_payulatam" name="is_meta_payment_gateway_st_payulatam" value="1" {{ old('is_meta_payment_gateway_st_payulatam', $meta['is_meta_payment_gateway_st_payulatam'] ?? '') === 'on' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_meta_payment_gateway_st_payulatam">
                                        PayU Latam
                                    </label>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="is_meta_payment_gateway_st_payumoney" name="is_meta_payment_gateway_st_payumoney" value="1" {{ old('is_meta_payment_gateway_st_payumoney', $meta['is_meta_payment_gateway_st_payumoney'] ?? '') === 'on' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_meta_payment_gateway_st_payumoney">
                                        PayUmoney
                                    </label>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="is_meta_payment_gateway_st_razor" name="is_meta_payment_gateway_st_razor" value="1" {{ old('is_meta_payment_gateway_st_razor', $meta['is_meta_payment_gateway_st_razor'] ?? '') === 'on' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_meta_payment_gateway_st_razor">
                                        <i class="bx bx-credit-card"></i> Razorpay
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- TAB 4: INFORMATION --}}

