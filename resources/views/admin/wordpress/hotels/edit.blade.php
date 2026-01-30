@extends('layouts.master-ajinsafro')
@section('title')
    Modifier l'hôtel (WordPress)
@endsection
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Modifier l'hôtel (WordPress)</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.wordpress.hotels.index') }}">WordPress – Hotels</a></li>
                        <li class="breadcrumb-item active">{{ $hotel->post_title }}</li>
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
        <a href="{{ route('admin.wordpress.hotels.index') }}" class="btn btn-secondary waves-effect">Retour à la liste</a>
    </div>

    <form action="{{ route('admin.wordpress.hotels.update', $hotel) }}" method="POST" enctype="multipart/form-data" id="hotel-edit-form">
        @csrf
        @method('PUT')
        <div class="row">
            <div class="col-lg-3 col-md-4">
                <div class="card">
                    <div class="card-body p-2">
                        <ul class="nav nav-pills flex-column" id="hotel-edit-tabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <a class="nav-link active" id="tab-location" data-bs-toggle="pill" href="#pane-location" role="tab">Location</a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link" id="tab-hotel-detail" data-bs-toggle="pill" href="#pane-hotel-detail" role="tab">Hotel detail</a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link" id="tab-contact" data-bs-toggle="pill" href="#pane-contact" role="tab">Contact</a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link" id="tab-price" data-bs-toggle="pill" href="#pane-price" role="tab">Price</a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link" id="tab-checkinout" data-bs-toggle="pill" href="#pane-checkinout" role="tab">Check in/out</a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link" id="tab-other" data-bs-toggle="pill" href="#pane-other" role="tab">Other options</a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link" id="tab-policy" data-bs-toggle="pill" href="#pane-policy" role="tab">Hotel policy</a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link" id="tab-inventory" data-bs-toggle="pill" href="#pane-inventory" role="tab">Inventory</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-lg-9 col-md-8">
                <div class="card">
                    <div class="card-body">
                        <div class="tab-content" id="hotel-edit-panes">
                            <div class="tab-pane fade show active" id="pane-location" role="tabpanel">
                                @include('admin.wordpress.hotels._tab_general', ['hotel' => $hotel, 'stHotel' => $stHotel, 'featuredUrl' => $featuredUrl ?? null])
                            </div>
                            <div class="tab-pane fade" id="pane-hotel-detail" role="tabpanel">
                                @include('admin.wordpress.hotels._tab_hotel_detail', [
                                    'hotelDetailMeta' => $hotelDetailMeta ?? [],
                                    'logoUrl' => $logoUrl ?? null,
                                    'galleryUrls' => $galleryUrls ?? [],
                                ])
                            </div>
                            <div class="tab-pane fade" id="pane-contact" role="tabpanel">
                                @include('admin.wordpress.hotels._tab_contact', ['meta' => $meta ?? []])
                            </div>
                            <div class="tab-pane fade" id="pane-price" role="tabpanel">
                                <p class="text-muted mb-0">Prix minimum est géré dans l’onglet Location. Cet onglet peut être étendu plus tard.</p>
                            </div>
                            <div class="tab-pane fade" id="pane-checkinout" role="tabpanel">
                                <p class="text-muted mb-0">Check in / Check out — à configurer si nécessaire.</p>
                            </div>
                            <div class="tab-pane fade" id="pane-other" role="tabpanel">
                                @include('admin.wordpress.hotels._tab_other', ['meta' => $meta ?? []])
                            </div>
                            <div class="tab-pane fade" id="pane-policy" role="tabpanel">
                                <p class="text-muted mb-0">Hotel policy — à configurer si nécessaire.</p>
                            </div>
                            <div class="tab-pane fade" id="pane-inventory" role="tabpanel">
                                <p class="text-muted mb-0">Inventory — à configurer si nécessaire.</p>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary waves-effect waves-light">Enregistrer</button>
                        <a href="{{ route('admin.wordpress.hotels.index') }}" class="btn btn-secondary waves-effect">Annuler</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
@push('script')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // External booking link visibility
        var cb = document.getElementById('_external_booking');
        var wrap = document.getElementById('external-booking-link-wrap');
        if (cb && wrap) {
            function toggle() { wrap.classList.toggle('d-none', cb.value !== '1' && !cb.checked); }
            cb.addEventListener('change', function() { wrap.classList.toggle('d-none', !this.checked); });
            toggle();
        }
        // Logo remove
        var removeBtn = document.getElementById('hotel-logo-remove');
        var removeInput = document.getElementById('hotel_logo_remove_input');
        if (removeBtn && removeInput) {
            removeBtn.addEventListener('click', function() {
                removeInput.value = '1';
                document.getElementById('hotel-logo-preview').innerHTML = '<span class="text-muted">Logo supprimé (enregistrez pour confirmer).</span>';
            });
        }
        // Gallery remove
        document.querySelectorAll('.gallery-remove').forEach(function(btn) {
            btn.addEventListener('click', function() { this.closest('.gallery-item').remove(); });
        });
    });
    </script>
@endpush
