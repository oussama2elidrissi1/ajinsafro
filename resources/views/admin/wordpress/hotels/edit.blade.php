@extends('layouts.admin-v6')

@section('title')
    Modifier l'hébergement
@endsection

@push('styles')
    <link href="{{ URL::asset('css/admin-hotel-editor.css') }}" rel="stylesheet" type="text/css" />
@endpush

@php
    $steps = $editorSteps ?? [];
    $countedSteps = array_values(array_filter($steps, static fn ($s) => empty($s['pending'])));
    $doneSteps = array_values(array_filter($countedSteps, static fn ($s) => ! empty($s['done'])));
    $stepTotal = count($countedSteps);
    $stepDone = count($doneSteps);
    $stepPercent = $stepTotal > 0 ? (int) round($stepDone / $stepTotal * 100) : 0;

    $remaining = array_values(array_map(
        static fn ($s) => $s['label'],
        array_filter($countedSteps, static fn ($s) => empty($s['done'])),
    ));

    $isPublished = ($hotel->post_status ?? '') === 'publish';
    $modifiedAt = $hotel->post_modified ? \Carbon\Carbon::parse($hotel->post_modified) : null;
    $publicUrl = ! empty($wpSiteUrl) ? $wpSiteUrl.'/?post_type=st_hotel&p='.$hotel->ID : null;
@endphp

@section('content')
    <div class="aj-hotel-editor">

        <div class="aje-head">
            <div style="min-width:0;">
                <div class="aje-crumbs">
                    <a href="{{ route('admin.wordpress.hotels.index') }}">Catalogue hébergements</a>
                    <span class="aje-sep">/</span>
                    <span>{{ $hotel->post_title }}</span>
                </div>
                <h1 class="aje-title">Modifier l'hébergement</h1>
                <div class="aje-head-meta">
                    <span class="aje-chip {{ $isPublished ? '-published' : '-draft' }}">{{ $isPublished ? 'Publié' : 'Brouillon' }}</span>
                    <span>
                        @if($modifiedAt)
                            Modifié {{ $modifiedAt->diffForHumans() }} ·
                        @endif
                        ID {{ $hotel->ID }}
                    </span>
                </div>
            </div>
            <div class="aje-head-actions">
                @if($publicUrl)
                    <a href="{{ $publicUrl }}" target="_blank" rel="noopener" class="aje-btn -ghost">Voir la fiche publique</a>
                @endif
                <a href="{{ route('admin.wordpress.hotels.index') }}" class="aje-btn -ghost">Retour à la liste</a>
            </div>
        </div>

        <x-admin.flash-messages />

        <form action="{{ route('admin.wordpress.hotels.update', $hotel) }}" method="POST" enctype="multipart/form-data" id="hotel-edit-form">
            @csrf
            @method('PUT')
            {{-- Renseigne par « Enregistrer et continuer » : identifiant du panneau a rouvrir. --}}
            <input type="hidden" name="save_and_continue" id="save_and_continue" value="">

            <div class="aje-layout">

                <aside class="aje-steps">
                    <div class="nav flex-column" id="hotel-edit-tabs" role="tablist">
                        @foreach($steps as $index => $step)
                            <a class="aje-step nav-link{{ $index === 0 ? ' active' : '' }}"
                               id="tab-{{ $step['key'] }}"
                               data-bs-toggle="pill"
                               href="#{{ $step['pane'] }}"
                               role="tab">
                                <span class="aje-step-num">{{ $index + 1 }}</span>
                                <span class="aje-step-label">{{ $step['label'] }}</span>
                                @if(! empty($step['pending']))
                                    <span class="aje-step-state -pending" title="Étape à venir">à venir</span>
                                @elseif(! empty($step['done']))
                                    <span class="aje-step-state -done" title="Étape renseignée" aria-label="Étape renseignée">&#10003;</span>
                                @endif
                            </a>
                        @endforeach
                    </div>

                    @if($stepTotal > 0)
                        <div class="aje-progress">
                            <div class="aje-progress-top">
                                <span>Fiche complétée</span>
                                <b>{{ $stepDone }} / {{ $stepTotal }}</b>
                            </div>
                            <div class="aje-progress-bar" role="progressbar" aria-valuenow="{{ $stepPercent }}" aria-valuemin="0" aria-valuemax="100">
                                <span style="width:{{ $stepPercent }}%"></span>
                            </div>
                            <span class="aje-progress-note">
                                @if($remaining === [])
                                    Toutes les sections disponibles sont renseignées.
                                @else
                                    {{ implode(', ', $remaining) }} {{ count($remaining) > 1 ? 'restent' : 'reste' }} à renseigner.
                                @endif
                            </span>
                        </div>
                    @endif
                </aside>

                <div class="aje-panels tab-content" id="hotel-edit-panes">

                    <section class="aje-panel tab-pane fade show active" id="pane-location" role="tabpanel">
                        <div class="aje-panel-head">
                            <h2>Général et localisation</h2>
                            <p>Nom affiché sur le site public, description commerciale et coordonnées de l'établissement.</p>
                        </div>
                        <div class="aje-panel-body">
                            @include('admin.wordpress.hotels._tab_general', ['hotel' => $hotel, 'stHotel' => $stHotel, 'featuredUrl' => $featuredUrl ?? null])
                        </div>
                        @include('admin.wordpress.hotels._editor_actions')
                    </section>

                    <section class="aje-panel tab-pane fade" id="pane-hotel-detail" role="tabpanel">
                        <div class="aje-panel-head">
                            <h2>Détails de l'hôtel</h2>
                            <p>Logo, galerie photo, mise en page de la fiche et réservation externe.</p>
                        </div>
                        <div class="aje-panel-body">
                            @include('admin.wordpress.hotels._tab_hotel_detail', [
                                'hotelDetailMeta' => $hotelDetailMeta ?? [],
                                'logoUrl' => $logoUrl ?? null,
                                'galleryUrls' => $galleryUrls ?? [],
                            ])
                        </div>
                        @include('admin.wordpress.hotels._editor_actions')
                    </section>

                    <section class="aje-panel tab-pane fade" id="pane-contact" role="tabpanel">
                        <div class="aje-panel-head">
                            <h2>Contact</h2>
                            <p>Téléphone et adresse e-mail transmis aux voyageurs et aux conseillers.</p>
                        </div>
                        <div class="aje-panel-body">
                            @include('admin.wordpress.hotels._tab_contact', ['meta' => $meta ?? []])
                        </div>
                        @include('admin.wordpress.hotels._editor_actions')
                    </section>

                    <section class="aje-panel tab-pane fade" id="pane-price" role="tabpanel">
                        <div class="aje-panel-head">
                            <h2>Tarifs</h2>
                            <p>Le prix minimum par nuit se saisit à l'étape « Général et localisation » : il alimente le prix affiché sur le catalogue.</p>
                        </div>
                        <div class="aje-panel-body">
                            <div class="aje-todo">
                                <h3>Grille tarifaire détaillée</h3>
                                <p>Les tarifs par saison et par type de chambre ne sont pas encore gérés depuis cet écran. En attendant, renseignez le prix minimum à la première étape.</p>
                            </div>
                        </div>
                        @include('admin.wordpress.hotels._editor_actions')
                    </section>

                    <section class="aje-panel tab-pane fade" id="pane-checkinout" role="tabpanel">
                        <div class="aje-panel-head">
                            <h2>Arrivée et départ</h2>
                            <p>Horaires d'arrivée et de départ communiqués au voyageur.</p>
                        </div>
                        <div class="aje-panel-body">
                            <div class="aje-todo">
                                <h3>Étape à venir</h3>
                                <p>Les horaires d'arrivée et de départ ne sont pas encore éditables ici. Cette étape n'entre pas dans le compteur d'avancement.</p>
                            </div>
                        </div>
                        @include('admin.wordpress.hotels._editor_actions')
                    </section>

                    <section class="aje-panel tab-pane fade" id="pane-other" role="tabpanel">
                        <div class="aje-panel-head">
                            <h2>Équipements et options</h2>
                            <p>Services proposés par l'établissement, affichés sur la fiche publique.</p>
                        </div>
                        <div class="aje-panel-body">
                            @include('admin.wordpress.hotels._tab_other', ['meta' => $meta ?? []])
                        </div>
                        @include('admin.wordpress.hotels._editor_actions')
                    </section>

                    <section class="aje-panel tab-pane fade" id="pane-policy" role="tabpanel">
                        <div class="aje-panel-head">
                            <h2>Conditions de réservation</h2>
                            <p>Règles d'annulation, dépôt de garantie et conditions particulières.</p>
                        </div>
                        <div class="aje-panel-body">
                            <div class="aje-todo">
                                <h3>Conditions détaillées</h3>
                                <p>Les conditions se saisissent pour l'instant dans le champ « Politiques » de l'étape « Équipements et options ».</p>
                            </div>
                        </div>
                        @include('admin.wordpress.hotels._editor_actions')
                    </section>

                    <section class="aje-panel tab-pane fade" id="pane-inventory" role="tabpanel">
                        <div class="aje-panel-head">
                            <h2>Inventaire et chambres</h2>
                            <p>Types de chambres, capacités et disponibilités.</p>
                        </div>
                        <div class="aje-panel-body">
                            <div class="aje-todo">
                                <h3>Étape à venir</h3>
                                <p>L'inventaire des chambres n'est pas encore géré depuis cet écran. Cette étape n'entre pas dans le compteur d'avancement.</p>
                            </div>
                        </div>
                        @include('admin.wordpress.hotels._editor_actions')
                    </section>

                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Lien de reservation externe : visible seulement quand la case est cochee
        var cb = document.getElementById('_external_booking');
        var wrap = document.getElementById('external-booking-link-wrap');
        if (cb && wrap) {
            function toggle() { wrap.classList.toggle('d-none', cb.value !== '1' && !cb.checked); }
            cb.addEventListener('change', function() { wrap.classList.toggle('d-none', !this.checked); });
            toggle();
        }
        // Retrait du logo
        var removeBtn = document.getElementById('hotel-logo-remove');
        var removeInput = document.getElementById('hotel_logo_remove_input');
        if (removeBtn && removeInput) {
            removeBtn.addEventListener('click', function() {
                removeInput.value = '1';
                document.getElementById('hotel-logo-preview').innerHTML = '<span class="text-muted">Logo supprimé (enregistrez pour confirmer).</span>';
            });
        }
        // Retrait d'une photo de la galerie
        document.querySelectorAll('.gallery-remove').forEach(function(btn) {
            btn.addEventListener('click', function() { this.closest('.gallery-item').remove(); });
        });

        // « Enregistrer et continuer » : on renseigne l'etape suivante dans un champ
        // cache, le controleur renvoie vers l'editeur sur cette etape.
        var continueField = document.getElementById('save_and_continue');
        var tabs = Array.prototype.slice.call(document.querySelectorAll('#hotel-edit-tabs .aje-step'));
        document.querySelectorAll('[data-save-continue]').forEach(function(btn) {
            btn.addEventListener('click', function() {
                if (!continueField) { return; }
                var panel = btn.closest('.aje-panel');
                var current = panel ? document.querySelector('#hotel-edit-tabs [href="#' + panel.id + '"]') : null;
                var next = current ? tabs[tabs.indexOf(current) + 1] : null;
                continueField.value = next ? next.getAttribute('href').slice(1) : '';
            });
        });

        // Reouvre l'etape ciblee par l'ancre apres un enregistrement.
        if (window.location.hash) {
            var target = document.querySelector('#hotel-edit-tabs [href="' + window.location.hash + '"]');
            if (target && window.bootstrap && window.bootstrap.Tab) {
                new window.bootstrap.Tab(target).show();
            }
        }
    });
    </script>
@endpush
