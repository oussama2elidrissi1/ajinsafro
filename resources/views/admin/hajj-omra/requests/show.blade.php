@extends('layouts.admin-v6')

@section('title', 'Detail demande Hajj & Omra')

@section('content')
    <x-admin.page-header
        :title="'Demande de '.$requestItem->full_name"
        subtitle="Consultez la demande, associez une offre et mettez a jour le statut."
        :breadcrumbs="[
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Demandes Hajj & Omra', 'url' => route('admin.hajj-omra.requests.index')],
            ['label' => 'Detail'],
        ]"
    />

    <x-admin.flash-messages />

    <div class="row g-4">
        <div class="col-lg-5">
            <x-admin.form-section title="Informations client">
                <div class="d-flex flex-column gap-2">
                    <div><strong>Nom :</strong> {{ $requestItem->full_name }}</div>
                    <div><strong>Telephone :</strong> {{ $requestItem->phone }}</div>
                    <div><strong>Email :</strong> {{ $requestItem->email }}</div>
                    <div><strong>Adultes :</strong> {{ $requestItem->adults }}</div>
                    <div><strong>Enfants :</strong> {{ $requestItem->children }}</div>
                    <div><strong>Type chambre :</strong> {{ $requestItem->room_type ?: 'Non precise' }}</div>
                    <div><strong>Date depart choisie :</strong> {{ $requestItem->selected_departure_date?->format('d/m/Y') ?: 'â€”' }}</div>
                    <div><strong>Message :</strong><br>{!! nl2br(e($requestItem->message ?: 'Aucun message')) !!}</div>
                </div>
            </x-admin.form-section>
        </div>

        <div class="col-lg-7">
            <x-admin.form-section title="Traitement admin">
                <form action="{{ route('admin.hajj-omra.requests.update', $requestItem) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Statut</label>
                            <select name="status" class="form-select">
                                @foreach($statusOptions as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', $requestItem->status) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Associer a une offre</label>
                            <select name="package_id" class="form-select">
                                <option value="">Ne pas modifier</option>
                                @foreach($packages as $package)
                                    <option value="{{ $package->id }}" @selected(old('package_id', $requestItem->package_id) === $package->id)>{{ $package->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes internes</label>
                            <textarea name="internal_notes" rows="8" class="form-control">{{ old('internal_notes', $requestItem->internal_notes) }}</textarea>
                        </div>
                        <div class="col-12 d-flex justify-content-end">
                            <button type="submit" class="aj-btn aj-btn-primary">
                                <i class="bx bx-save"></i>
                                <span>Mettre a jour la demande</span>
                            </button>
                        </div>
                    </div>
                </form>
            </x-admin.form-section>
        </div>
    </div>
@endsection

