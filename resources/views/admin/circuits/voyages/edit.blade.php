@extends('layouts.master-ajinsafro')
@section('title')
    Modifier le voyage
@endsection
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Modifier le voyage</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.circuits.index') }}">Circuits</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.circuits.voyages.index') }}">Voyages</a></li>
                        <li class="breadcrumb-item active">{{ $voyage->name }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="mb-3">
        <a href="{{ route('admin.circuits.voyages.show', $voyage) }}" class="btn btn-soft-info waves-effect waves-light me-2">Voir la fiche brochure</a>
        <a href="{{ route('admin.circuits.voyages.index') }}" class="btn btn-secondary waves-effect">Retour à la liste</a>
    </div>

    {{-- Formulaire voyage (brochure + prix + départs règle) --}}
    <form action="{{ route('admin.circuits.voyages.update', $voyage) }}" method="POST" class="mb-4" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Informations du voyage</h4>
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label for="name" class="form-label">Nom du voyage <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $voyage->name) }}" required>
                    </div>
                    <div class="col-12 mb-3">
                        <label for="featured_image" class="form-label">Image à la une (couverture)</label>
                        @if($voyage->featured_image)
                            <div class="mb-2">
                                <img src="{{ $voyage->featured_image_url }}" alt="Image à la une" class="img-thumbnail" style="max-height: 120px;">
                                <span class="text-muted small d-block">Image actuelle. Téléversez un fichier pour remplacer.</span>
                            </div>
                        @endif
                        <input type="file" class="form-control" id="featured_image" name="featured_image" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp">
                        <small class="text-muted">Max 5 Mo. Stockage : storage/app/public/travels/featured/</small>
                    </div>
                    <div class="col-12 mb-3">
                        <label for="gallery_images" class="form-label">Galerie d’images</label>
                        @if($voyage->images->isNotEmpty())
                            <div class="d-flex flex-wrap gap-2 mb-2">
                                @foreach($voyage->images as $img)
                                    <div class="position-relative d-inline-block">
                                        <img src="{{ $img->url }}" alt="Galerie" class="img-thumbnail" style="height: 80px; width: auto;">
                                        <form action="{{ route('admin.circuits.voyages.images.destroy', [$voyage, $img]) }}" method="POST" class="d-inline position-absolute top-0 end-0" style="transform: translate(50%, -50%);" onsubmit="return confirm('Supprimer cette image ?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" title="Supprimer"><i class="bx bx-trash font-size-12"></i></button>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        <input type="file" class="form-control" id="gallery_images" name="gallery_images[]" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp" multiple>
                        <small class="text-muted">Plusieurs images possibles. Max 5 Mo par fichier. Stockage : storage/app/public/travels/gallery/</small>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label for="accroche" class="form-label">Accroche</label>
                        <textarea class="form-control" id="accroche" name="accroche" rows="2" placeholder="Phrase d'accroche">{{ old('accroche', $voyage->accroche) }}</textarea>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="destination" class="form-label">Destination</label>
                        <input type="text" class="form-control" id="destination" name="destination" value="{{ old('destination', $voyage->destination) }}" placeholder="Ex : Dubaï, Émirats Arabes Unis">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="duration_text" class="form-label">Durée (texte)</label>
                        <input type="text" class="form-control" id="duration_text" name="duration_text" value="{{ old('duration_text', $voyage->duration_text) }}" placeholder="Ex : 7 jours / 6 nuits">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="price_from" class="form-label">Prix à partir de</label>
                        <input type="number" class="form-control" id="price_from" name="price_from" value="{{ old('price_from', $voyage->price_from) }}" min="0" step="1">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="old_price" class="form-label">Valeur (ancien prix)</label>
                        <input type="number" class="form-control" id="old_price" name="old_price" value="{{ old('old_price', $voyage->old_price) }}" min="0" step="1">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="currency" class="form-label">Devise</label>
                        <select class="form-select" id="currency" name="currency">
                            <option value="MAD" {{ old('currency', $voyage->currency ?? 'MAD') === 'MAD' ? 'selected' : '' }}>MAD</option>
                            <option value="EUR" {{ old('currency', $voyage->currency) === 'EUR' ? 'selected' : '' }}>EUR</option>
                            <option value="USD" {{ old('currency', $voyage->currency) === 'USD' ? 'selected' : '' }}>USD</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="min_people" class="form-label">Minimum personnes</label>
                        <input type="number" class="form-control" id="min_people" name="min_people" value="{{ old('min_people', $voyage->min_people) }}" min="1">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="status" class="form-label">Statut</label>
                        <select class="form-select" id="status" name="status">
                            <option value="actif" {{ old('status', $voyage->status ?? 'actif') === 'actif' ? 'selected' : '' }}>Actif</option>
                            <option value="inactif" {{ old('status', $voyage->status) === 'inactif' ? 'selected' : '' }}>Inactif</option>
                        </select>
                    </div>
                    <div class="col-12 mb-3">
                        <label for="departure_policy" class="form-label">Règle départ (texte)</label>
                        <textarea class="form-control" id="departure_policy" name="departure_policy" rows="3">{{ old('departure_policy', $voyage->departure_policy) }}</textarea>
                    </div>
                    <div class="col-12 mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="4">{{ old('description', $voyage->description) }}</textarea>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary waves-effect waves-light">Enregistrer les modifications</button>
            </div>
        </div>
    </form>

    {{-- Section Départs --}}
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <h4 class="card-title mb-0">Départs</h4>
                <button type="button" class="btn btn-primary btn-sm waves-effect waves-light" data-bs-toggle="modal" data-bs-target="#modalAddDeparture">
                    <i class="bx bx-plus me-1"></i> Ajouter un départ
                </button>
            </div>
            @if($voyage->departures->isEmpty())
                <p class="text-muted mb-0 small">Aucun départ. Cliquez sur « Ajouter un départ ».</p>
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-centered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Statut</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($voyage->departures as $dep)
                                <tr>
                                    <td>{{ $dep->start_date->format('d/m/Y') }}</td>
                                    <td><span class="badge bg-{{ $dep->status === 'open' ? 'success' : ($dep->status === 'full' ? 'warning' : 'secondary') }}">{{ $dep->status_label }}</span></td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-xs btn-soft-primary btn-edit-departure" data-bs-toggle="modal" data-bs-target="#modalEditDeparture" data-id="{{ $dep->id }}" data-url="{{ route('admin.circuits.voyages.departures.update', [$voyage, $dep]) }}" data-date="{{ $dep->start_date->format('Y-m-d') }}" data-status="{{ $dep->status }}">Modifier</button>
                                        <form action="{{ route('admin.circuits.voyages.departures.destroy', [$voyage, $dep]) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer ce départ ?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-xs btn-soft-danger">Supprimer</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- Section Programme du voyage --}}
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
                <h4 class="card-title mb-0">Programme du voyage</h4>
                <button type="button" class="btn btn-primary waves-effect waves-light" data-bs-toggle="modal" data-bs-target="#modalAddDay">
                    <i class="bx bx-plus me-1"></i> Ajouter un jour
                </button>
            </div>

            @if($voyage->programDays->isEmpty())
                <p class="text-muted mb-0">Aucun jour dans le programme. Cliquez sur « Ajouter un jour ».</p>
            @else
                <div class="row">
                    @foreach($voyage->programDays as $day)
                        <div class="col-12 col-lg-6 col-xl-4 mb-3">
                            <div class="card border h-100">
                                <div class="card-body">
                                    <h5 class="card-title mt-0">Jour {{ $day->day_number }} – {{ $day->title }}</h5>
                                    @if($day->city)
                                        <p class="text-muted mb-1 small"><i class="bx bx-map-pin font-size-12 me-1"></i> {{ $day->city }}</p>
                                    @endif
                                    <p class="mb-1">
                                        @if($day->day_label)
                                            <span class="badge bg-soft-primary">{{ $day->day_label_badge }}</span>
                                        @endif
                                        <span class="badge bg-soft-info">{{ $day->day_type_label }}</span>
                                        @if($day->nights)
                                            <span class="badge bg-soft-secondary">{{ $day->nights }} nuit(s)</span>
                                        @endif
                                    </p>
                                    @if($day->hasMealBreakfast() || $day->hasMealLunch() || $day->hasMealDinner())
                                        <p class="text-muted small mb-2">Repas : @if($day->hasMealBreakfast()) Petit-déj. @endif @if($day->hasMealLunch()) Déjeuner @endif @if($day->hasMealDinner()) Dîner @endif</p>
                                    @endif
                                    @php $content = $day->content_for_display; $plain = strip_tags($content); @endphp
                                    <p class="card-text text-muted small mb-3">{{ Str::limit($plain, 80) }}</p>
                                    <div class="d-flex gap-1">
                                        <button type="button" class="btn btn-sm btn-soft-primary waves-effect waves-light btn-edit-day" data-bs-toggle="modal" data-bs-target="#modalEditDay" data-day-form-id="editDayForm{{ $day->id }}">Modifier</button>
                                        <form action="{{ route('admin.circuits.voyages.programme.destroy', [$voyage, $day]) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer ce jour ? Les jours suivants seront renumérotés.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-soft-danger waves-effect waves-light">Supprimer</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Modal Ajouter un jour --}}
    <div class="modal fade" id="modalAddDay" tabindex="-1" aria-labelledby="modalAddDayLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="{{ route('admin.circuits.voyages.programme.store', $voyage) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalAddDayLabel">Ajouter un jour</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @include('admin.circuits.voyages._program_day_fields', ['day' => null])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary waves-effect waves-light">Ajouter le jour</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Modifier un jour --}}
    <div class="modal fade" id="modalEditDay" tabindex="-1" aria-labelledby="modalEditDayLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEditDayLabel">Modifier le jour</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @foreach($voyage->programDays as $day)
                        <div id="editDayForm{{ $day->id }}" class="edit-day-form-content" style="display: none;">
                            <form action="{{ route('admin.circuits.voyages.programme.update', [$voyage, $day]) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                @include('admin.circuits.voyages._program_day_fields', ['day' => $day])
                                <div class="mt-3">
                                    <button type="submit" class="btn btn-primary waves-effect waves-light">Enregistrer</button>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                </div>
                            </form>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Ajouter un départ --}}
    <div class="modal fade" id="modalAddDeparture" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('admin.circuits.voyages.departures.store', $voyage) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Ajouter un départ</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="dep_start_date" class="form-label">Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="dep_start_date" name="start_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="dep_status" class="form-label">Statut</label>
                            <select class="form-select" id="dep_status" name="status">
                                <option value="open">Ouvert</option>
                                <option value="full">Complet</option>
                                <option value="canceled">Annulé</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary waves-effect waves-light">Ajouter</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Modifier un départ --}}
    <div class="modal fade" id="modalEditDeparture" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="formEditDeparture" action="" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="modal-header">
                        <h5 class="modal-title">Modifier le départ</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_dep_start_date" class="form-label">Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="edit_dep_start_date" name="start_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_dep_status" class="form-label">Statut</label>
                            <select class="form-select" id="edit_dep_status" name="status">
                                <option value="open">Ouvert</option>
                                <option value="full">Complet</option>
                                <option value="canceled">Annulé</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary waves-effect waves-light">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
@push('script')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
    <script>
        document.querySelectorAll('.btn-edit-day').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var formId = this.getAttribute('data-day-form-id');
                document.querySelectorAll('#modalEditDay .edit-day-form-content').forEach(function(el) { el.style.display = 'none'; });
                var target = document.getElementById(formId);
                if (target) target.style.display = 'block';
            });
        });
        document.getElementById('modalEditDay').addEventListener('hidden.bs.modal', function() {
            document.querySelectorAll('#modalEditDay .edit-day-form-content').forEach(function(el) { el.style.display = 'none'; });
        });
        document.querySelectorAll('.btn-edit-departure').forEach(function(btn) {
            btn.addEventListener('click', function() {
                document.getElementById('formEditDeparture').action = this.dataset.url;
                document.getElementById('edit_dep_start_date').value = this.dataset.date;
                document.getElementById('edit_dep_status').value = this.dataset.status;
            });
        });
    </script>
@endpush
