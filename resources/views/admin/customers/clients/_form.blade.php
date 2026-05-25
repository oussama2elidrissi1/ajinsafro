@php
    $isEdit = isset($client) && $client->exists;
    $sources = ['website' => 'Site web', 'whatsapp' => 'WhatsApp', 'phone' => 'Téléphone', 'facebook' => 'Facebook', 'instagram' => 'Instagram', 'referral' => 'Parrainage', 'walkin' => 'Walk-in', 'admin' => 'Admin'];
@endphp

{{-- A. Informations générales --}}
<div class="card mb-3">
    <div class="card-header bg-light">
        <h5 class="mb-0">Informations générales</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Type de client <span class="text-danger">*</span></label>
                <select name="client_type" class="form-select" required>
                    <option value="individual" {{ old('client_type', $client->client_type ?? 'individual') === 'individual' ? 'selected' : '' }}>Particulier</option>
                    <option value="company" {{ old('client_type', $client->client_type ?? '') === 'company' ? 'selected' : '' }}>Société</option>
                    <option value="agency" {{ old('client_type', $client->client_type ?? '') === 'agency' ? 'selected' : '' }}>Agence</option>
                </select>
                @error('client_type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Statut <span class="text-danger">*</span></label>
                <select name="status" class="form-select" required>
                    <option value="active" {{ old('status', $client->status ?? 'active') === 'active' ? 'selected' : '' }}>Actif</option>
                    <option value="inactive" {{ old('status', $client->status ?? '') === 'inactive' ? 'selected' : '' }}>Inactif</option>
                    <option value="blocked" {{ old('status', $client->status ?? '') === 'blocked' ? 'selected' : '' }}>Bloqué</option>
                    <option value="vip" {{ old('status', $client->status ?? '') === 'vip' ? 'selected' : '' }}>VIP</option>
                </select>
                @error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Source</label>
                <select name="source" class="form-select">
                    <option value="">?</option>
                    @foreach($sources as $k => $v)
                        <option value="{{ $k }}" {{ old('source', $client->source ?? '') === $k ? 'selected' : '' }}>{{ $v }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Assigné à</label>
                <select name="assigned_to" class="form-select">
                    <option value="">?</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}" {{ old('assigned_to', $client->assigned_to ?? '') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
</div>

{{-- B. Identité --}}
<div class="card mb-3">
    <div class="card-header bg-light">
        <h5 class="mb-0">Identité</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Prénom <span class="text-danger">*</span></label>
                <input type="text" name="first_name" class="form-control @error('first_name') is-invalid @enderror" value="{{ old('first_name', $client->first_name ?? '') }}" required>
                @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">Nom <span class="text-danger">*</span></label>
                <input type="text" name="last_name" class="form-control @error('last_name') is-invalid @enderror" value="{{ old('last_name', $client->last_name ?? '') }}" required>
                @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">Nom complet</label>
                <input type="text" class="form-control bg-light" value="{{ old('full_name', $client->full_name ?? '') }}" readonly placeholder="Rempli automatiquement">
            </div>
            <div class="col-md-4">
                <label class="form-label">Genre</label>
                <select name="gender" class="form-select">
                    <option value="">?</option>
                    <option value="male" {{ old('gender', $client->gender ?? '') === 'male' ? 'selected' : '' }}>Homme</option>
                    <option value="female" {{ old('gender', $client->gender ?? '') === 'female' ? 'selected' : '' }}>Femme</option>
                    <option value="other" {{ old('gender', $client->gender ?? '') === 'other' ? 'selected' : '' }}>Autre</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Date de naissance</label>
                <input type="date" name="date_of_birth" class="form-control" value="{{ old('date_of_birth', $client->date_of_birth?->format('Y-m-d') ?? '') }}">
                @error('date_of_birth')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">Nationalité</label>
                <input type="text" name="nationality" class="form-control" value="{{ old('nationality', $client->nationality ?? '') }}" placeholder="ex. Maroc">
            </div>
            <div class="col-md-6">
                <label class="form-label">Langue préférée</label>
                <select name="preferred_language" class="form-select">
                    <option value="fr" {{ old('preferred_language', $client->preferred_language ?? 'fr') === 'fr' ? 'selected' : '' }}>Français</option>
                    <option value="en" {{ old('preferred_language', $client->preferred_language ?? '') === 'en' ? 'selected' : '' }}>English</option>
                    <option value="ar" {{ old('preferred_language', $client->preferred_language ?? '') === 'ar' ? 'selected' : '' }}>ا?"عرب?Sة</option>
                </select>
            </div>
        </div>
    </div>
</div>

{{-- C. Contact --}}
<div class="card mb-3">
    <div class="card-header bg-light">
        <h5 class="mb-0">Contact</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $client->email ?? '') }}">
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Téléphone</label>
                <input type="text" name="phone" class="form-control" value="{{ old('phone', $client->phone ?? '') }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Téléphone secondaire</label>
                <input type="text" name="phone_alt" class="form-control" value="{{ old('phone_alt', $client->phone_alt ?? '') }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">WhatsApp</label>
                <input type="text" name="whatsapp_number" class="form-control" value="{{ old('whatsapp_number', $client->whatsapp_number ?? '') }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Site web</label>
                <input type="url" name="website" class="form-control" value="{{ old('website', $client->website ?? '') }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Préférence de contact</label>
                <select name="contact_method_preference" class="form-select">
                    <option value="">?</option>
                    <option value="phone" {{ old('contact_method_preference', $client->contact_method_preference ?? '') === 'phone' ? 'selected' : '' }}>Téléphone</option>
                    <option value="email" {{ old('contact_method_preference', $client->contact_method_preference ?? '') === 'email' ? 'selected' : '' }}>Email</option>
                    <option value="whatsapp" {{ old('contact_method_preference', $client->contact_method_preference ?? '') === 'whatsapp' ? 'selected' : '' }}>WhatsApp</option>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label">Adresse ligne 1</label>
                <input type="text" name="address_line_1" class="form-control" value="{{ old('address_line_1', $client->address_line_1 ?? '') }}">
            </div>
            <div class="col-12">
                <label class="form-label">Adresse ligne 2</label>
                <input type="text" name="address_line_2" class="form-control" value="{{ old('address_line_2', $client->address_line_2 ?? '') }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Ville</label>
                <input type="text" name="city" class="form-control" value="{{ old('city', $client->city ?? '') }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Pays de résidence</label>
                <input type="text" name="country_of_residence" class="form-control" value="{{ old('country_of_residence', $client->country_of_residence ?? '') }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Code postal</label>
                <input type="text" name="postal_code" class="form-control" value="{{ old('postal_code', $client->postal_code ?? '') }}">
            </div>
        </div>
    </div>
</div>

{{-- D. Documents --}}
<div class="card mb-3">
    <div class="card-header bg-light">
        <h5 class="mb-0">Documents</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">N° CIN / Pièce d'identité</label>
                <input type="text" name="national_id_number" class="form-control" value="{{ old('national_id_number', $client->national_id_number ?? '') }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">N° Passeport</label>
                <input type="text" name="passport_number" class="form-control @error('passport_number') is-invalid @enderror" value="{{ old('passport_number', $client->passport_number ?? '') }}">
                @error('passport_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Pays d'émission passeport</label>
                <input type="text" name="passport_issue_country" class="form-control" value="{{ old('passport_issue_country', $client->passport_issue_country ?? '') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Date émission</label>
                <input type="date" name="passport_issue_date" class="form-control" value="{{ old('passport_issue_date', $client->passport_issue_date?->format('Y-m-d') ?? '') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Date expiration</label>
                <input type="date" name="passport_expiry_date" class="form-control" value="{{ old('passport_expiry_date', $client->passport_expiry_date?->format('Y-m-d') ?? '') }}">
                @error('passport_expiry_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label d-block">Visa requis</label>
                <div class="form-check form-check-inline">
                    <input type="hidden" name="visa_required" value="0">
                    <input type="checkbox" name="visa_required" class="form-check-input" value="1" {{ old('visa_required', $client->visa_required ?? false) ? 'checked' : '' }}>
                    <label class="form-check-label">Oui</label>
                </div>
            </div>
            <div class="col-md-4">
                <label class="form-label">Statut visa</label>
                <select name="visa_status" class="form-select">
                    <option value="not_required" {{ old('visa_status', $client->visa_status ?? 'not_required') === 'not_required' ? 'selected' : '' }}>Non requis</option>
                    <option value="pending" {{ old('visa_status', $client->visa_status ?? '') === 'pending' ? 'selected' : '' }}>En attente</option>
                    <option value="approved" {{ old('visa_status', $client->visa_status ?? '') === 'approved' ? 'selected' : '' }}>Approuvé</option>
                    <option value="rejected" {{ old('visa_status', $client->visa_status ?? '') === 'rejected' ? 'selected' : '' }}>Refusé</option>
                </select>
            </div>
        </div>
    </div>
</div>

{{-- E. Voyage --}}
<div class="card mb-3">
    <div class="card-header bg-light">
        <h5 class="mb-0">Préférences voyage</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Catégorie voyageur</label>
                <select name="traveler_category" class="form-select">
                    <option value="">?</option>
                    <option value="solo" {{ old('traveler_category', $client->traveler_category ?? '') === 'solo' ? 'selected' : '' }}>Solo</option>
                    <option value="couple" {{ old('traveler_category', $client->traveler_category ?? '') === 'couple' ? 'selected' : '' }}>Couple</option>
                    <option value="family" {{ old('traveler_category', $client->traveler_category ?? '') === 'family' ? 'selected' : '' }}>Famille</option>
                    <option value="group" {{ old('traveler_category', $client->traveler_category ?? '') === 'group' ? 'selected' : '' }}>Groupe</option>
                    <option value="business" {{ old('traveler_category', $client->traveler_category ?? '') === 'business' ? 'selected' : '' }}>Business</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Ville de départ préférée</label>
                <input type="text" name="preferred_departure_city" class="form-control" value="{{ old('preferred_departure_city', $client->preferred_departure_city ?? '') }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Destination préférée</label>
                <input type="text" name="preferred_destination" class="form-control" value="{{ old('preferred_destination', $client->preferred_destination ?? '') }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Mois de voyage préféré</label>
                <input type="text" name="preferred_travel_month" class="form-control" value="{{ old('preferred_travel_month', $client->preferred_travel_month ?? '') }}" placeholder="ex. Juillet">
            </div>
            <div class="col-md-4">
                <label class="form-label">Budget min (DH)</label>
                <input type="number" name="budget_min" class="form-control" step="0.01" min="0" value="{{ old('budget_min', $client->budget_min ?? '') }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Budget max (DH)</label>
                <input type="number" name="budget_max" class="form-control" step="0.01" min="0" value="{{ old('budget_max', $client->budget_max ?? '') }}">
                @error('budget_max')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
                <label class="form-label">Demandes spéciales</label>
                <textarea name="special_requests" class="form-control" rows="2">{{ old('special_requests', $client->special_requests ?? '') }}</textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label">Notes médicales</label>
                <textarea name="medical_notes" class="form-control" rows="2">{{ old('medical_notes', $client->medical_notes ?? '') }}</textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label">Régime alimentaire</label>
                <textarea name="dietary_requirements" class="form-control" rows="2">{{ old('dietary_requirements', $client->dietary_requirements ?? '') }}</textarea>
            </div>
        </div>
    </div>
</div>

{{-- F. Urgence --}}
<div class="card mb-3">
    <div class="card-header bg-light">
        <h5 class="mb-0">Contact d'urgence</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Nom</label>
                <input type="text" name="emergency_contact_name" class="form-control" value="{{ old('emergency_contact_name', $client->emergency_contact_name ?? '') }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Téléphone</label>
                <input type="text" name="emergency_contact_phone" class="form-control" value="{{ old('emergency_contact_phone', $client->emergency_contact_phone ?? '') }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Lien</label>
                <input type="text" name="emergency_contact_relation" class="form-control" value="{{ old('emergency_contact_relation', $client->emergency_contact_relation ?? '') }}" placeholder="ex. Conjoint, Parent">
            </div>
        </div>
    </div>
</div>

{{-- G. Société / Facturation --}}
<div class="card mb-3">
    <div class="card-header bg-light">
        <h5 class="mb-0">Société & Facturation</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Raison sociale</label>
                <input type="text" name="company_name" class="form-control" value="{{ old('company_name', $client->company_name ?? '') }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">N° registre / IF</label>
                <input type="text" name="company_registration_number" class="form-control" value="{{ old('company_registration_number', $client->company_registration_number ?? '') }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">N° TVA / Taxe</label>
                <input type="text" name="tax_number" class="form-control" value="{{ old('tax_number', $client->tax_number ?? '') }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Contact société</label>
                <input type="text" name="company_contact_person" class="form-control" value="{{ old('company_contact_person', $client->company_contact_person ?? '') }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Nom facturation</label>
                <input type="text" name="billing_name" class="form-control" value="{{ old('billing_name', $client->billing_name ?? '') }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Email facturation</label>
                <input type="email" name="billing_email" class="form-control" value="{{ old('billing_email', $client->billing_email ?? '') }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Téléphone facturation</label>
                <input type="text" name="billing_phone" class="form-control" value="{{ old('billing_phone', $client->billing_phone ?? '') }}">
            </div>
            <div class="col-12">
                <label class="form-label">Adresse facturation</label>
                <textarea name="billing_address" class="form-control" rows="2">{{ old('billing_address', $client->billing_address ?? '') }}</textarea>
            </div>
            <div class="col-md-4">
                <label class="form-label">Ville</label>
                <input type="text" name="billing_city" class="form-control" value="{{ old('billing_city', $client->billing_city ?? '') }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Pays</label>
                <input type="text" name="billing_country" class="form-control" value="{{ old('billing_country', $client->billing_country ?? '') }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Code postal</label>
                <input type="text" name="billing_postal_code" class="form-control" value="{{ old('billing_postal_code', $client->billing_postal_code ?? '') }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Conditions de paiement</label>
                <input type="text" name="payment_terms" class="form-control" value="{{ old('payment_terms', $client->payment_terms ?? '') }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Plafond crédit (DH)</label>
                <input type="number" name="credit_limit" class="form-control" step="0.01" min="0" value="{{ old('credit_limit', $client->credit_limit ?? '') }}">
            </div>
        </div>
    </div>
</div>

{{-- H. Relation client / interne --}}
<div class="card mb-3">
    <div class="card-header bg-light">
        <h5 class="mb-0">Relation client & interne</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <div class="form-check">
                    <input type="hidden" name="newsletter_opt_in" value="0">
                    <input type="checkbox" name="newsletter_opt_in" class="form-check-input" value="1" {{ old('newsletter_opt_in', $client->newsletter_opt_in ?? false) ? 'checked' : '' }}>
                    <label class="form-check-label">Newsletter</label>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-check">
                    <input type="hidden" name="sms_opt_in" value="0">
                    <input type="checkbox" name="sms_opt_in" class="form-check-input" value="1" {{ old('sms_opt_in', $client->sms_opt_in ?? false) ? 'checked' : '' }}>
                    <label class="form-check-label">SMS</label>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-check">
                    <input type="hidden" name="whatsapp_opt_in" value="0">
                    <input type="checkbox" name="whatsapp_opt_in" class="form-check-input" value="1" {{ old('whatsapp_opt_in', $client->whatsapp_opt_in ?? false) ? 'checked' : '' }}>
                    <label class="form-check-label">WhatsApp</label>
                </div>
            </div>
            <div class="col-md-4">
                <label class="form-label">Points fidélité</label>
                <input type="number" name="loyalty_points" class="form-control" min="0" value="{{ old('loyalty_points', $client->loyalty_points ?? 0) }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Dernier contact</label>
                <input type="datetime-local" name="last_contacted_at" class="form-control" value="{{ old('last_contacted_at', $client->last_contacted_at ? $client->last_contacted_at->format('Y-m-d\TH:i') : '') }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Prochain suivi</label>
                <input type="datetime-local" name="next_follow_up_at" class="form-control" value="{{ old('next_follow_up_at', $client->next_follow_up_at ? $client->next_follow_up_at->format('Y-m-d\TH:i') : '') }}">
            </div>
            <div class="col-12">
                <label class="form-label">Notes internes</label>
                <textarea name="internal_notes" class="form-control" rows="4">{{ old('internal_notes', $client->internal_notes ?? '') }}</textarea>
            </div>
            <div class="col-12">
                <label class="form-label">Raison blacklist (si bloqué)</label>
                <textarea name="blacklist_reason" class="form-control" rows="2">{{ old('blacklist_reason', $client->blacklist_reason ?? '') }}</textarea>
            </div>
        </div>
    </div>
</div>

