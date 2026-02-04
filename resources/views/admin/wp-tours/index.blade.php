@extends('layouts.master')

@section('title') Tours WordPress @endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Admin @endslot
@slot('title') Tours WordPress (TravelerWP) @endslot
@endcomponent

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">Liste des Tours WordPress</h4>
                <a href="{{ route('admin.wordpress.tours.create') }}" class="btn btn-primary">
                    <i class="mdi mdi-plus me-1"></i> Créer un tour
                </a>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <div class="alert alert-info">
                    <i class="mdi mdi-information me-2"></i>
                    <strong>CRUD Direct WordPress</strong> - Modifications immédiatement visibles sur <a href="https://ajinsafro.net" target="_blank">ajinsafro.net</a>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th width="80">ID</th>
                                <th>Titre</th>
                                <th>Slug</th>
                                <th>Destination</th>
                                <th width="100">Durée</th>
                                <th width="120">Prix Adulte</th>
                                <th width="100">Status</th>
                                <th width="150">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tours as $tour)
                                <tr>
                                    <td><strong>{{ $tour->ID }}</strong></td>
                                    <td>
                                        <a href="{{ route('admin.wordpress.tours.edit', $tour->ID) }}">
                                            {{ $tour->post_title }}
                                        </a>
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $tour->post_name }}</small>
                                    </td>
                                    <td>{{ $tour->address ?? '-' }}</td>
                                    <td>{{ $tour->duration_day ?? '-' }}</td>
                                    <td>
                                        @if($tour->adult_price)
                                            <strong>{{ number_format($tour->adult_price, 0, ',', ' ') }} MAD</strong>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if($tour->post_status === 'publish')
                                            <span class="badge bg-success">Publié</span>
                                        @elseif($tour->post_status === 'draft')
                                            <span class="badge bg-secondary">Brouillon</span>
                                        @else
                                            <span class="badge bg-warning">{{ $tour->post_status }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('admin.wordpress.tours.edit', $tour->ID) }}" 
                                               class="btn btn-sm btn-primary" 
                                               title="Éditer">
                                                <i class="mdi mdi-pencil"></i>
                                            </a>
                                            <a href="https://ajinsafro.net/tours/{{ $tour->post_name }}" 
                                               target="_blank" 
                                               class="btn btn-sm btn-info" 
                                               title="Voir sur WordPress">
                                                <i class="mdi mdi-eye"></i>
                                            </a>
                                            <form action="{{ route('admin.wordpress.tours.destroy', $tour->ID) }}" 
                                                  method="POST" 
                                                  onsubmit="return confirm('Supprimer ce tour ?');"
                                                  style="display:inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" title="Supprimer">
                                                    <i class="mdi mdi-delete"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        Aucun tour trouvé
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-center mt-3">
                    {{ $tours->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
