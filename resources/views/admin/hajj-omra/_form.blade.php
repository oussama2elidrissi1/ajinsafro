{{--
    Editeur d'offre Hajj & Omra — 8 etapes.

    Le formulaire est unique : les etapes ne sont que des panneaux affiches tour a tour,
    donc un seul enregistrement suffit et rien n'est perdu en naviguant entre elles.
--}}
@php
    $steps = $editorSteps;
    $doneCount = collect($steps)->where('done', true)->count();
@endphp

@include('admin.hajj-omra.form._styles')

<div class="ho-editor"
     data-ho-editor
     data-active-tab="{{ $activeTab }}"
     data-upload-url="{{ route('admin.local-media.upload') }}">

    @if ($package->exists)
        <div class="ho-head">
            <div class="ho-head__main">
                <div class="ho-head__facts">
                    <span>{{ $package->duration_label ?: 'Durée à définir' }}</span><span class="ho-sep">·</span>
                    <span>{{ $package->departures->count() }} départ(s)</span><span class="ho-sep">·</span>
                    <span>{{ $package->roomPrices->count() }} tarif(s)</span><span class="ho-sep">·</span>
                    <span class="text-nowrap">À partir de
                        <b class="ho-mono">{{ $package->price_from_value !== null ? number_format($package->price_from_value, 0, ',', ' ').' '.$package->currency : 'sur demande' }}</b>
                    </span>
                </div>
            </div>
            <div class="ho-head__side">
                <div class="d-flex align-items-baseline justify-content-between gap-2 mb-1">
                    <span class="ho-eyebrow mb-0">Complétude</span>
                    <span class="small text-muted"><b class="ho-mono">{{ $doneCount }}</b> / {{ count($steps) }} étapes</span>
                </div>
                <div class="ho-progress"><span style="width: {{ (int) round($doneCount / max(1, count($steps)) * 100) }}%"></span></div>
                @if (! empty($missingArabic))
                    <div class="d-flex align-items-center gap-2 mt-2 small text-muted">
                        <span class="ho-dot"></span>
                        <span>Traduction arabe incomplète — {{ count($missingArabic) }} champ(s)</span>
                        <button type="button" class="btn btn-link btn-sm p-0 ms-auto fw-semibold" data-lang-switch="ar">Corriger</button>
                    </div>
                @endif
            </div>
        </div>
    @endif

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

    <form method="POST"
          action="{{ $package->exists ? route('admin.hajj-omra.update', $package) : route('admin.hajj-omra.store') }}">
        @csrf
        @if ($package->exists) @method('PUT') @endif
        <input type="hidden" name="active_tab" value="{{ $activeTab }}" data-role="active-tab">

        <div class="ho-card">
            <div>

                {{-- Navigation par etapes + bascule de langue --}}
                <div class="d-flex justify-content-between align-items-end flex-wrap gap-2">
                    <div class="ho-steps flex-grow-1">
                        @foreach ($steps as $key => $step)
                            <button type="button" @class(['ho-step', 'is-done' => $step['done']]) data-step="{{ $key }}">
                                <span class="ho-step-num">{{ $step['done'] ? '✓' : $loop->iteration }}</span>
                                <span>{{ $step['label'] }}</span>
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
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <a href="{{ route('admin.hajj-omra.index') }}" class="btn btn-outline-secondary">Annuler</a>
                    @if ($package->exists && $package->updated_at)
                        <span class="small text-muted">Dernier enregistrement <b class="ho-mono">{{ $package->updated_at->locale('fr')->diffForHumans() }}</b></span>
                    @endif
                </div>

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
