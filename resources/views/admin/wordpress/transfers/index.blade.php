@extends('layouts.admin-v6')
@section('title', 'WordPress - Transferts')
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Catalogue transferts</h4>
                <a href="{{ route('admin.wordpress.transfers.create') }}" class="btn btn-primary">
                    <i class="bx bx-plus me-1"></i>Nouveau transfert
                </a>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="card-body">
            <form method="GET" class="row g-3 mb-4">
                <div class="col-md-5">
                    <input type="text" name="search" class="form-control" value="{{ $filters['search'] }}" placeholder="Nom, départ, arrivée, type, ville">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">Tous les statuts</option>
                        <option value="publish" @selected($filters['status'] === 'publish')>Publié</option>
                        <option value="draft" @selected($filters['status'] === 'draft')>Brouillon</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="city" class="form-select">
                        <option value="">Toutes les villes</option>
                        @foreach($cityOptions as $option)
                            <option value="{{ $option }}" @selected($filters['city'] === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-light w-100">Filtrer</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Image</th>
                            <th>Service</th>
                            <th>Ville</th>
                            <th>Trajet</th>
                            <th>Type</th>
                            <th>Prix</th>
                            <th>Statut</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transfers as $transfer)
                            @php
                                $detail = $transfer->stCar;
                                $thumb = $media->getFeaturedImageUrlVerified($transfer->ID);
                            @endphp
                            <tr>
                                <td>
                                    @if($thumb)
                                        <img src="{{ $thumb }}" alt="" class="rounded" style="width:50px;height:50px;object-fit:cover;">
                                    @else
                                        <span class="text-muted">�?"</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $transfer->post_title }}</div>
                                    <div class="text-muted small">{{ $transfer->post_name }}</div>
                                </td>
                                <td>{{ $detail->cars_address ?: '�?"' }}</td>
                                <td>
                                    {{ $transfer->getMeta('aj_transfer_from') ?: '�?"' }}
                                    �?'
                                    {{ $transfer->getMeta('aj_transfer_to') ?: '�?"' }}
                                </td>
                                <td>{{ $transfer->getMeta('aj_transfer_vehicle_type') ?: ($transfer->getMeta('aj_transfer_type') ?: '�?"') }}</td>
                                <td>
                                    @if($detail && ($detail->cars_price || $detail->min_price))
                                        {{ number_format((float) ($detail->cars_price ?: $detail->min_price), 0, ',', ' ') }} MAD
                                    @else
                                        �?"
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-{{ $transfer->post_status === 'publish' ? 'success' : 'secondary' }}">
                                        {{ $transfer->post_status === 'publish' ? 'Publié' : 'Brouillon' }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.wordpress.transfers.edit', $transfer) }}" class="btn btn-sm btn-soft-primary">Modifier</a>
                                    <form action="{{ route('admin.wordpress.transfers.destroy', $transfer) }}" method="POST" class="d-inline" onsubmit="return confirm('Déplacer ce transfert dans la corbeille ?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-soft-danger">Supprimer</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">Aucun transfert trouvé.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">{{ $transfers->links() }}</div>
        </div>
    </div>
@endsection


