{{--
    Editeur d'offre Hajj & Omra — 8 etapes.

    Le formulaire est unique : les etapes ne sont que des panneaux affiches tour a tour,
    donc un seul enregistrement suffit et rien n'est perdu en naviguant entre elles.
--}}
@php
    $steps = [
        'offre' => 'Offre',
        'tarifs' => 'Tarifs',
        'departs' => 'Départs',
        'hebergement' => 'Hébergement',
        'programme' => 'Programme',
        'prestations' => 'Prestations',
        'medias' => 'Médias',
        'publication' => 'Publication',
    ];
@endphp

@include('admin.hajj-omra.form._styles')

<div class="ho-editor"
     data-ho-editor
     data-active-tab="{{ $activeTab }}"
     data-upload-url="{{ route('admin.local-media.upload') }}">

    @if (session('success'))
        <div class="alert alert-success border-0 shadow-sm">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger border-0 shadow-sm">
            <div class="fw-semibold mb-1">Vérifiez la saisie :</div>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    @if ($package->exists && ! empty($missingArabic))
        <div class="alert alert-warning border-0 shadow-sm d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <span class="fw-semibold">Traduction arabe non complétée</span>
                <span class="text-muted small ms-2">{{ count($missingArabic) }} champ(s) sans version arabe.</span>
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-lang-switch="ar">Passer en العربية</button>
        </div>
    @endif

    <form method="POST"
          action="{{ $package->exists ? route('admin.hajj-omra.update', $package) : route('admin.hajj-omra.store') }}">
        @csrf
        @if ($package->exists) @method('PUT') @endif
        <input type="hidden" name="active_tab" value="{{ $activeTab }}" data-role="active-tab">

        <div class="card border-0 shadow-sm">
            <div class="card-body">

                {{-- Navigation par etapes + bascule de langue --}}
                <div class="d-flex justify-content-between align-items-end flex-wrap gap-2">
                    <div class="ho-steps flex-grow-1">
                        @foreach ($steps as $key => $label)
                            <button type="button" class="ho-step" data-step="{{ $key }}">
                                <span class="ho-step-num">{{ $loop->iteration }}</span>
                                <span>{{ $label }}</span>
                            </button>
                        @endforeach
                    </div>
                    <div class="btn-group btn-group-sm ms-auto mb-2" role="group" aria-label="Langue de saisie">
                        <button type="button" class="btn btn-primary" data-lang-switch="fr">Français</button>
                        <button type="button" class="btn btn-outline-secondary" data-lang-switch="ar" lang="ar">العربية</button>
                    </div>
                </div>

                @include('admin.hajj-omra.form._offre')
                @include('admin.hajj-omra.form._tarifs')
                @include('admin.hajj-omra.form._departs')
                @include('admin.hajj-omra.form._hebergement')
                @include('admin.hajj-omra.form._programme')
                @include('admin.hajj-omra.form._prestations')
                @include('admin.hajj-omra.form._medias')
                @include('admin.hajj-omra.form._publication')

            </div>
        </div>

        {{-- Barre d'actions : toujours accessible, sans avoir a derouler la page --}}
        <div class="ho-actionbar">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <a href="{{ route('admin.hajj-omra.index') }}" class="btn btn-outline-secondary">Annuler</a>

                <div class="d-flex gap-2">
                    @if ($package->exists)
                        <a href="{{ route('admin.hajj-omra.preview', $package) }}" target="_blank" rel="noopener"
                           class="btn btn-outline-secondary">Prévisualiser</a>
                    @endif
                    <button type="button" class="btn btn-outline-primary" data-role="save-draft">Enregistrer le brouillon</button>
                    <button type="submit" class="btn btn-secondary">Enregistrer</button>
                    <button type="button" class="btn btn-primary" data-role="publish">Publier l'offre</button>
                </div>
            </div>
        </div>
    </form>

    {{-- Generation serveur des jours : formulaire distinct, hors du formulaire principal --}}
    @if ($package->exists)
        <form method="POST" action="{{ route('admin.hajj-omra.generate-program', $package) }}" class="d-none" id="hoGenerateProgram">
            @csrf
            <input type="hidden" name="duration_days" value="{{ $package->duration_days }}">
        </form>
    @endif
</div>

@include('admin.hajj-omra.form._scripts')
