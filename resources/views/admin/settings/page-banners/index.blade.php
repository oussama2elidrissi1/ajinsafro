@extends('layouts.admin-v6')
@section('title') Bannières des pages @endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Bannières des pages</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.settings.index') }}">Paramètres</a></li>
                        <li class="breadcrumb-item active">Bannières des pages</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <x-admin.flash-messages />

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-body d-flex flex-wrap align-items-start gap-4">
            {{-- Base flex explicite : sans elle, le texte prend toute la ligne et
                 rejette les recommandations en dessous. --}}
            <div style="flex:1 1 360px;min-width:0;">
                <div class="fw-semibold mb-1">Une bannière image par page du catalogue</div>
                <p class="text-muted small mb-0">
                    Chaque bannière remplace le bandeau bleu en haut de sa page, affichée entière et sans recadrage.
                    Tant qu’une page n’a pas d’image active, elle garde son bandeau par défaut.
                </p>
            </div>
            <div class="p-3 rounded-3 bg-light" style="flex:0 1 380px;">
                <div class="fw-semibold mb-2">Recommandations</div>
                <ul class="text-muted small mb-0 ps-3">
                    <li>Dimensions recommandées : <strong>2073 × 758 px</strong> (ratio conservé, aucun recadrage).</li>
                    <li>Formats acceptés : JPG, PNG, WebP. Poids maximum : <strong>{{ $maxUploadMb }} Mo</strong>.</li>
                    <li>Le formulaire de recherche chevauche le bas de l’image : évitez d’y placer du texte important.</li>
                </ul>
            </div>
        </div>
    </div>

    @foreach ($banners as $item)
        @php
            $key = $item['key'];
            $banner = $item['banner'];
            $imageUrl = $item['imageUrl'];
            $fileInputId = 'banner-file-' . $key;
        @endphp

        <div class="card mb-4" id="banner-{{ $key }}">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h5 class="card-title mb-0" style="font-weight:800;letter-spacing:-0.02em;">{{ $item['label'] }}</h5>
                    <p class="card-title-desc mb-0 mt-1" style="color:var(--ajp-muted);font-size:.85rem;font-weight:500;">
                        <a href="{{ $publicBase . $item['path'] }}" target="_blank" rel="noopener">{{ $publicBase . $item['path'] }}</a>
                    </p>
                </div>
                @if ($imageUrl && $banner->is_active)
                    <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                @elseif ($imageUrl)
                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Image prête, inactive</span>
                @else
                    <span class="badge bg-light text-muted border">Bandeau par défaut</span>
                @endif
            </div>

            <div class="card-body">
                <form action="{{ route('admin.settings.page-banners.update', $key) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="row g-4 align-items-start">
                        <div class="col-lg-6">
                            @if ($imageUrl)
                                <div class="border rounded-3 p-2 bg-light">
                                    <img src="{{ $imageUrl }}" alt="{{ $banner->alt_text }}"
                                         style="display:block;width:100%;height:auto;border-radius:10px;">
                                </div>
                                <div class="d-flex flex-wrap align-items-center gap-2 mt-3">
                                    <label class="btn btn-outline-primary btn-sm mb-0" for="{{ $fileInputId }}">Remplacer</label>
                                    <button type="submit" class="btn btn-outline-danger btn-sm" form="banner-delete-{{ $key }}"
                                            onclick="return confirm('Supprimer l’image de « {{ $item['label'] }} » ? La page reprendra son bandeau par défaut.');">
                                        Supprimer
                                    </button>
                                    <span class="text-muted small" data-banner-file-name="{{ $key }}">Aucun nouveau fichier choisi</span>
                                </div>
                            @else
                                <div class="border border-2 rounded-3 p-4 text-center bg-light">
                                    <p class="mb-1 fw-semibold">Aucune image pour le moment</p>
                                    <p class="text-muted small mb-3">La page affiche son bandeau bleu par défaut.</p>
                                    <label class="btn btn-primary btn-sm mb-0" for="{{ $fileInputId }}">Choisir une image</label>
                                    <div class="text-muted small mt-2" data-banner-file-name="{{ $key }}">Aucun fichier choisi</div>
                                </div>
                            @endif

                            <input type="file" class="d-none" id="{{ $fileInputId }}" name="image_file"
                                   accept=".jpg,.jpeg,.png,.webp" data-banner-file-input="{{ $key }}">
                        </div>

                        <div class="col-lg-6">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label" for="banner-alt-{{ $key }}">Texte alternatif</label>
                                    <input type="text" class="form-control" id="banner-alt-{{ $key }}" name="alt_text" maxlength="255"
                                           value="{{ old('alt_text', $banner->alt_text) }}"
                                           placeholder="Ex. {{ $item['label'] }} avec Ajinsafro">
                                    <div class="form-text">Décrit l’image pour les lecteurs d’écran et les moteurs de recherche.</div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="banner-link-{{ $key }}">URL au clic <span class="text-muted">(optionnel)</span></label>
                                    <input type="url" class="form-control" id="banner-link-{{ $key }}" name="link_url" maxlength="2048"
                                           value="{{ old('link_url', $banner->link_url) }}" placeholder="https://…">
                                    <div class="form-text">Si renseignée, l’image devient cliquable.</div>
                                </div>
                                <div class="col-12">
                                    <div class="form-check form-switch">
                                        <input type="hidden" name="is_active" value="0">
                                        <input class="form-check-input" type="checkbox" role="switch" id="banner-active-{{ $key }}"
                                               name="is_active" value="1" @checked($banner->is_active) @disabled(! $imageUrl)>
                                        <label class="form-check-label" for="banner-active-{{ $key }}">Activer la bannière</label>
                                    </div>
                                    @unless ($imageUrl)
                                        <div class="form-text">L’activation sera possible dès qu’une image est enregistrée.</div>
                                    @endunless
                                </div>
                                <div class="col-12 d-flex justify-content-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bx bx-save"></i>
                                        <span>Enregistrer « {{ $item['label'] }} »</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Formulaire separe : la suppression ne doit pas envoyer les autres champs. --}}
        @if ($imageUrl)
            <form id="banner-delete-{{ $key }}" action="{{ route('admin.settings.page-banners.destroy-image', $key) }}" method="POST">
                @csrf
                @method('DELETE')
            </form>
        @endif
    @endforeach
@endsection

@push('scripts')
    <script>
        (function () {
            document.querySelectorAll('[data-banner-file-input]').forEach(function (input) {
                var key = input.getAttribute('data-banner-file-input');
                var label = document.querySelector('[data-banner-file-name="' + key + '"]');
                if (!label) { return; }

                input.addEventListener('change', function () {
                    var file = input.files && input.files[0];
                    label.textContent = file
                        ? file.name + ' (' + (file.size / 1048576).toFixed(2).replace('.', ',') + ' Mo) — enregistrez pour appliquer'
                        : 'Aucun fichier choisi';
                });
            });
        })();
    </script>
@endpush
