@php
    $modalAjax = $modalAjax ?? false;
    $layout = $layout ?? 'default';
@endphp
<div class="card border shadow-sm">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0"><i class="bx bx-hotel me-1 text-primary"></i> Hôtels pour ce départ</h5>
    </div>
    <div class="card-body">
        <div class="border rounded p-3 bg-light mb-4">
            <h6 class="small text-uppercase text-muted mb-3">Ajouter un hôtel</h6>
            <form method="post" action="{{ route('admin.circuits.voyages.departures.hotels.store', [$voyage, $departure]) }}" class="row g-2 align-items-end ra-modal-ajax-form">
                @csrf
                @include('admin.circuits.voyages.departures.partials._modal_ajax_hidden', ['modalAjax' => $modalAjax])
                <div class="col-md-4">
                    <label class="form-label small">Catalogue (optionnel)</label>
                    <select name="hotel_id" class="form-select form-select-sm">
                        <option value="">? Saisie manuelle ?</option>
                        @foreach($hotelsCatalog as $h)
                            <option value="{{ $h->id }}">{{ $h->name }} @if($h->city) ? {{ $h->city }} @endif</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Nom affiché</label>
                    <input type="text" name="hotel_name" class="form-control form-control-sm" placeholder="Si pas de catalogue">
                </div>
                <div class="col-md-1">
                    <label class="form-label small">?toiles</label>
                    <input type="number" name="stars" class="form-control form-control-sm" min="0" max="5">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Adresse</label>
                    <input type="text" name="address" class="form-control form-control-sm">
                </div>
                <div class="col-md-1">
                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="new_hotel_active_{{ $departure->id }}" checked>
                        <label class="form-check-label small" for="new_hotel_active_{{ $departure->id }}">Actif</label>
                    </div>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-sm btn-primary">Ajouter</button>
                </div>
            </form>
        </div>

        @if($layout === 'accordion')
            <div class="accordion ra-hotels-accordion" id="ra-hotel-acc-{{ $departure->id }}">
                @forelse($departure->departureHotels as $dh)
                    <div class="accordion-item border rounded mb-2 overflow-hidden">
                        <h2 class="accordion-header" id="ra-hotel-h-{{ $dh->id }}">
                            <button class="accordion-button {{ $loop->first ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#ra-hotel-c-{{ $dh->id }}" aria-expanded="{{ $loop->first ? 'true' : 'false' }}" aria-controls="ra-hotel-c-{{ $dh->id }}">
                                <span class="fw-semibold">{{ $dh->hotel_name ?: 'Hôtel #'.$dh->id }}</span>
                                @if($dh->hotel_id)<span class="badge bg-light text-dark ms-2">Catalogue {{ $dh->hotel_id }}</span>@endif
                            </button>
                        </h2>
                        <div id="ra-hotel-c-{{ $dh->id }}" class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}" aria-labelledby="ra-hotel-h-{{ $dh->id }}">
                            <div class="accordion-body">
                                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 pb-2 border-bottom">
                                    <form method="post" action="{{ route('admin.circuits.voyages.departures.hotels.update', [$voyage, $dh]) }}" class="d-flex flex-wrap gap-1 align-items-center ra-modal-ajax-form">
                                        @csrf
                                        @method('PUT')
                                        @include('admin.circuits.voyages.departures.partials._modal_ajax_hidden', ['modalAjax' => $modalAjax])
                                        <input type="hidden" name="hotel_id" value="{{ $dh->hotel_id }}">
                                        <input type="text" name="hotel_name" value="{{ $dh->hotel_name }}" class="form-control form-control-sm" style="max-width:160px" placeholder="Nom">
                                        <input type="number" name="sort_order" value="{{ $dh->sort_order }}" class="form-control form-control-sm" style="width:70px" min="0" title="Ordre">
                                        <div class="form-check form-check-inline mb-0">
                                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="act_acc{{ $dh->id }}" {{ $dh->is_active ? 'checked' : '' }}>
                                            <label class="form-check-label small" for="act_acc{{ $dh->id }}">Actif</label>
                                        </div>
                                        <button type="submit" class="btn btn-sm btn-outline-primary">MAJ</button>
                                    </form>
                                    <form method="post" action="{{ route('admin.circuits.voyages.departures.hotels.destroy', [$voyage, $dh]) }}" class="ra-modal-ajax-form ra-hotel-destroy-form" data-confirm-msg="Retirer cet hôtel du départ ?">
                                        @csrf
                                        @method('DELETE')
                                        @include('admin.circuits.voyages.departures.partials._modal_ajax_hidden', ['modalAjax' => $modalAjax])
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Retirer</button>
                                    </form>
                                </div>
                                @include('admin.circuits.voyages.departures.partials._rooms_table', ['departure' => $departure, 'voyage' => $voyage, 'departureHotel' => $dh, 'roomStatuses' => $roomStatuses, 'modalAjax' => $modalAjax])
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-muted mb-0">Aucun hôtel. Ajoutez-en un ci-dessus pour gérer le stock par chambre.</p>
                @endforelse
            </div>
        @else
            @forelse($departure->departureHotels as $dh)
                <div id="rooms-{{ $dh->id }}" class="card mb-3 border">
                    <div class="card-header bg-white py-2 d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            <strong>{{ $dh->hotel_name ?: 'Hôtel #'.$dh->id }}</strong>
                            @if($dh->hotel_id)<span class="badge bg-light text-dark ms-1">ID catalogue {{ $dh->hotel_id }}</span>@endif
                        </div>
                        <div class="d-flex flex-wrap gap-1 align-items-center">
                            <form method="post" action="{{ route('admin.circuits.voyages.departures.hotels.update', [$voyage, $dh]) }}" class="d-flex flex-wrap gap-1 align-items-center ra-modal-ajax-form">
                                @csrf
                                @method('PUT')
                                @include('admin.circuits.voyages.departures.partials._modal_ajax_hidden', ['modalAjax' => $modalAjax])
                                <input type="hidden" name="hotel_id" value="{{ $dh->hotel_id }}">
                                <input type="text" name="hotel_name" value="{{ $dh->hotel_name }}" class="form-control form-control-sm" style="max-width:160px" placeholder="Nom">
                                <input type="number" name="sort_order" value="{{ $dh->sort_order }}" class="form-control form-control-sm" style="width:70px" min="0" title="Ordre">
                                <div class="form-check form-check-inline mb-0">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="act{{ $dh->id }}" {{ $dh->is_active ? 'checked' : '' }}>
                                    <label class="form-check-label small" for="act{{ $dh->id }}">Actif</label>
                                </div>
                                <button type="submit" class="btn btn-sm btn-outline-primary">MAJ</button>
                            </form>
                            <form method="post" action="{{ route('admin.circuits.voyages.departures.hotels.destroy', [$voyage, $dh]) }}" class="ra-modal-ajax-form ra-hotel-destroy-form" data-confirm-msg="Retirer cet hôtel du départ ?">
                                @csrf
                                @method('DELETE')
                                @include('admin.circuits.voyages.departures.partials._modal_ajax_hidden', ['modalAjax' => $modalAjax])
                                <button type="submit" class="btn btn-sm btn-outline-danger">Retirer</button>
                            </form>
                        </div>
                    </div>
                    <div class="card-body pt-2">
                        @include('admin.circuits.voyages.departures.partials._rooms_table', ['departure' => $departure, 'voyage' => $voyage, 'departureHotel' => $dh, 'roomStatuses' => $roomStatuses, 'modalAjax' => $modalAjax])
                    </div>
                </div>
            @empty
                <p class="text-muted mb-0">Aucun hôtel. Ajoutez-en un ci-dessus pour gérer le stock par chambre.</p>
            @endforelse
        @endif
    </div>
</div>

