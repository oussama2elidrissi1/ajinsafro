@extends('layouts.admin-v6')
@section('title') Bannière page Voyages @endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Bannière page Voyages</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.settings.index') }}">Paramètres</a></li>
                        <li class="breadcrumb-item active">Bannière page Voyages</li>
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

    <form action="{{ route('admin.settings.page-banners.voyages.update') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <x-admin.form-section title="Image de la bannière" subtitle="Remplace le bandeau bleu en haut de la liste des voyages. Affichée entière, sans recadrage.">
            <div class="row g-4 align-items-start">
                <div class="col-lg-7">
                    @if ($imageUrl)
                        <div class="border rounded-3 p-2 bg-light">
                            <img src="{{ $imageUrl }}" alt="{{ $banner->alt_text }}"
                                 style="display:block;width:100%;height:auto;border-radius:10px;">
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-2 mt-3">
                            <label class="btn btn-outline-primary mb-0" for="banner-image-file">Remplacer</label>
                            <button type="submit" class="btn btn-outline-danger" form="banner-image-delete-form"
                                    onclick="return confirm('Supprimer l’image ? La page Voyages reprendra son bandeau par défaut.');">
                                Supprimer
                            </button>
                            <span class="text-muted small" data-banner-file-name>Aucun nouveau fichier choisi</span>
                        </div>
                    @else
                        <div class="border border-2 rounded-3 p-4 text-center bg-light">
                            <p class="mb-1 fw-semibold">Aucune image pour le moment</p>
                            <p class="text-muted small mb-3">Tant qu’aucune image n’est définie, le front affiche le bandeau bleu par défaut.</p>
                            <label class="btn btn-primary mb-0" for="banner-image-file">Choisir une image</label>
                            <div class="text-muted small mt-2" data-banner-file-name>Aucun fichier choisi</div>
                        </div>
                    @endif

                    <input type="file" class="d-none" id="banner-image-file" name="image_file"
                           accept=".jpg,.jpeg,.png,.webp" data-banner-file-input>
                </div>

                <div class="col-lg-5">
                    <div class="p-3 rounded-3 bg-light h-100">
                        <div class="fw-semibold mb-2">Recommandations</div>
                        <ul class="text-muted small mb-0 ps-3">
                            <li>Dimensions recommandées : <strong>2073 × 758 px</strong> (ratio d’origine conservé, aucun recadrage).</li>
                            <li>Formats acceptés : JPG, PNG, WebP.</li>
                            <li>Poids maximum : <strong>{{ $maxUploadMb }} Mo</strong>.</li>
                            <li>Le formulaire de recherche vient chevaucher le bas de l’image : évitez d’y placer du texte important.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </x-admin.form-section>

        <x-admin.form-section title="Texte, lien et affichage">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="banner-alt">Texte alternatif</label>
                    <input type="text" class="form-control" id="banner-alt" name="alt_text" maxlength="255"
                           value="{{ old('alt_text', $banner->alt_text) }}"
                           placeholder="Ex. Voyages, séjours et circuits Ajinsafro">
                    <div class="form-text">Décrit l’image pour les lecteurs d’écran et les moteurs de recherche.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="banner-link">URL au clic <span class="text-muted">(optionnel)</span></label>
                    <input type="url" class="form-control" id="banner-link" name="link_url" maxlength="2048"
                           value="{{ old('link_url', $banner->link_url) }}" placeholder="https://…">
                    <div class="form-text">Si renseignée, l’image devient cliquable.</div>
                </div>
                <div class="col-12">
                    <div class="form-check form-switch">
                        <input type="hidden" name="is_active" value="0">
                        <input class="form-check-input" type="checkbox" role="switch" id="banner-active"
                               name="is_active" value="1" @checked(old('is_active', $banner->is_active))
                               @disabled(! $imageUrl)>
                        <label class="form-check-label" for="banner-active">Activer la bannière</label>
                    </div>
                    @unless ($imageUrl)
                        <div class="form-text">L’activation sera possible dès qu’une image est enregistrée.</div>
                    @endunless
                </div>
            </div>
        </x-admin.form-section>

        <div class="d-flex justify-content-end gap-2 mb-4">
            <a href="{{ route('admin.settings.index') }}" class="btn btn-light">Annuler</a>
            <button type="submit" class="btn btn-primary">
                <i class="bx bx-save"></i>
                <span>Enregistrer</span>
            </button>
        </div>
    </form>

    {{-- Formulaire separe : la suppression ne doit pas envoyer les autres champs. --}}
    @if ($imageUrl)
        <form id="banner-image-delete-form" action="{{ route('admin.settings.page-banners.voyages.destroy-image') }}" method="POST">
            @csrf
            @method('DELETE')
        </form>
    @endif
@endsection

@push('scripts')
    <script>
        (function () {
            var input = document.querySelector('[data-banner-file-input]');
            var label = document.querySelector('[data-banner-file-name]');
            if (!input || !label) { return; }

            input.addEventListener('change', function () {
                var file = input.files && input.files[0];
                label.textContent = file
                    ? file.name + ' (' + (file.size / 1048576).toFixed(2).replace('.', ',') + ' Mo) — enregistrez pour appliquer'
                    : 'Aucun fichier choisi';
            });
        })();
    </script>
@endpush
