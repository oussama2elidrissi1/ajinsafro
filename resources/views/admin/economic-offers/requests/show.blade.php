@extends('layouts.admin-v6')

@section('title', 'Détail demande Formule économique')

@section('content')
    <x-admin.page-header
        :title="$requestItem->full_name"
        :subtitle="$requestItem->offer_title ?: 'Demande client'"
        :breadcrumbs="[
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Demandes économique', 'url' => route('admin.economic-offers.requests.index')],
            ['label' => 'Détail'],
        ]"
    />

    <x-admin.flash-messages />

    <div class="row g-4">
        <div class="col-lg-5">
            <x-admin.form-section title="Coordonnées client">
                <div class="d-flex flex-column gap-3">
                    <div><strong>Nom :</strong> {{ $requestItem->full_name }}</div>
                    <div><strong>Téléphone :</strong> {{ $requestItem->phone }}</div>
                    <div><strong>Email :</strong> {{ $requestItem->email }}</div>
                    <div><strong>Offre :</strong> {{ $requestItem->offer_title ?: optional($requestItem->offer)->title ?: 'Non associée' }}</div>
                    <div><strong>Départ :</strong> {{ $requestItem->selected_departure_date?->format('d/m/Y') ?: '?' }}</div>
                    <div><strong>Adultes / Enfants :</strong> {{ $requestItem->adults }} / {{ $requestItem->children }}</div>
                    <div><strong>Message :</strong><br>{!! nl2br(e($requestItem->message ?: '?')) !!}</div>
                </div>
            </x-admin.form-section>
        </div>
        <div class="col-lg-7">
            <x-admin.form-section title="Traitement admin">
                <form action="{{ route('admin.economic-offers.requests.update', $requestItem) }}" method="POST" class="row g-3">
                    @csrf
                    @method('PUT')

                    <div class="col-md-6">
                        <label class="form-label">Statut</label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror">
                            @foreach($statusOptions as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $requestItem->status) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Offre associée</label>
                        <select name="offer_id" class="form-select @error('offer_id') is-invalid @enderror">
                            <option value="">Non associée</option>
                            @foreach($offers as $offer)
                                <option value="{{ $offer->id }}" @selected((int) old('offer_id', $requestItem->offer_id) === (int) $offer->id)>{{ $offer->title }}</option>
                            @endforeach
                        </select>
                        @error('offer_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Agent responsable</label>
                        <input type="text" name="responsible_agent" value="{{ old('responsible_agent', $requestItem->responsible_agent) }}" class="form-control @error('responsible_agent') is-invalid @enderror">
                        @error('responsible_agent') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Source</label>
                        <input type="text" value="{{ $requestItem->source ?: 'wordpress' }}" class="form-control" disabled>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Notes internes</label>
                        <textarea name="internal_notes" rows="6" class="form-control @error('internal_notes') is-invalid @enderror">{{ old('internal_notes', $requestItem->internal_notes) }}</textarea>
                        @error('internal_notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12 d-flex justify-content-end">
                        <button type="submit" class="aj-btn aj-btn-primary">
                            <i class="bx bx-save"></i>
                            <span>Enregistrer</span>
                        </button>
                    </div>
                </form>
            </x-admin.form-section>
        </div>
    </div>
@endsection


