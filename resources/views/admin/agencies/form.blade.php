@extends('layouts.admin-v6')

@section('title', $isEdit ? 'Modifier point de vente' : 'Creer point de vente')

@section('content')
    <x-admin.page-header
        :title="$isEdit ? 'Modifier point de vente' : 'Creer un point de vente'"
        subtitle="Structure, coordonnees, commission, responsable et parametres metier du point de vente."
        :breadcrumbs="[
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Points de vente', 'url' => route('admin.agencies.index')],
            ['label' => $isEdit ? 'Modifier' : 'CrÃ©er'],
        ]"
    />

    <x-admin.flash-messages />

    <div class="card">
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data" action="{{ $isEdit ? route('admin.agencies.update', $agency) : route('admin.agencies.store') }}">
                @csrf
                @if($isEdit)
                    @method('PUT')
                @endif

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nom du point de vente</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $agency->name) }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Code</label>
                        <input type="text" name="code" class="form-control" value="{{ old('code', $agency->code) }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Structure</label>
                        <select name="type" class="form-select">
                            <option value="{{ \App\Models\Branch::TYPE_BRANCH }}" @selected(old('type', $agency->type) === \App\Models\Branch::TYPE_BRANCH)>Point de vente</option>
                            <option value="{{ \App\Models\Branch::TYPE_HEAD_OFFICE }}" @selected(old('type', $agency->type) === \App\Models\Branch::TYPE_HEAD_OFFICE)>SiÃ¨ge</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Type de point de vente</label>
                        <select name="agency_type" class="form-select">
                            @foreach($agencyTypeLabels as $key => $label)
                                <option value="{{ $key }}" @selected(old('agency_type', $agency->agency_type) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Statut</label>
                        <select name="status" class="form-select">
                            @foreach($statusLabels as $key => $label)
                                <option value="{{ $key }}" @selected(old('status', $agency->status) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Manager</label>
                        <select name="manager_user_id" class="form-select">
                            <option value="">Aucun</option>
                            @foreach($managerOptions as $manager)
                                <option value="{{ $manager->id }}" @selected((int) old('manager_user_id', $agency->manager_user_id) === (int) $manager->id)>
                                    {{ $manager->name }}{{ $manager->email ? ' Â· ' . $manager->email : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Ville</label>
                        <input type="text" name="city" class="form-control" value="{{ old('city', $agency->city) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Pays</label>
                        <input type="text" name="country" class="form-control" value="{{ old('country', $agency->country) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Devise</label>
                        <input type="text" name="currency" class="form-control" value="{{ old('currency', $agency->currency ?: 'MAD') }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Adresse</label>
                        <input type="text" name="address" class="form-control" value="{{ old('address', $agency->address) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">TÃ©lÃ©phone</label>
                        <input type="text" name="phone" class="form-control" value="{{ old('phone', $agency->phone) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="{{ old('email', $agency->email) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Commission par defaut (%)</label>
                        <input type="number" step="0.01" min="0" name="default_commission_rate" class="form-control" value="{{ old('default_commission_rate', $agency->default_commission_rate) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Type commission par defaut</label>
                        <select name="default_commission_type" class="form-select">
                            <option value="">Selectionner</option>
                            @foreach(\App\Models\Branch::commissionTypeLabels() as $key => $label)
                                <option value="{{ $key }}" @selected(old('default_commission_type', $agency->default_commission_type) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Valeur commission par defaut</label>
                        <input type="number" step="0.01" min="0" name="default_commission_value" class="form-control" value="{{ old('default_commission_value', $agency->default_commission_value) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Objectif mensuel CA</label>
                        <input type="number" step="0.01" min="0" name="monthly_revenue_target" class="form-control" value="{{ old('monthly_revenue_target', $agency->monthly_revenue_target) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Objectif mensuel reservations</label>
                        <input type="number" min="0" name="monthly_reservations_target" class="form-control" value="{{ old('monthly_reservations_target', $agency->monthly_reservations_target) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Logo</label>
                        <input type="file" name="logo" class="form-control" accept="image/*">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Documents administratifs</label>
                        <input type="file" name="documents[]" class="form-control" multiple>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Horaires</label>
                        <textarea name="business_hours" class="form-control" rows="4">{{ old('business_hours', $agency->business_hours) }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Notes internes</label>
                        <textarea name="internal_notes" class="form-control" rows="4">{{ old('internal_notes', $agency->internal_notes) }}</textarea>
                    </div>
                </div>

                @if($isEdit && !empty($agency->documents))
                    <div class="mt-4">
                        <label class="form-label d-block">Documents existants</label>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($agency->documents as $document)
                                <a href="{{ asset('storage/' . $document['path']) }}" target="_blank" class="aj-btn aj-btn-soft">
                                    <i class="bx bx-file"></i>
                                    <span>{{ $document['name'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="d-flex flex-wrap gap-2 mt-4">
                    <button type="submit" class="aj-btn aj-btn-primary">{{ $isEdit ? 'Mettre a jour' : 'Creer' }}</button>
                    <a href="{{ route('admin.agencies.index') }}" class="aj-btn aj-btn-soft">Annuler</a>
                </div>
            </form>
        </div>
    </div>
@endsection

