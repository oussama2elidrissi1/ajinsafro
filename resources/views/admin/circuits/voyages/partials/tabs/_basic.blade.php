{{--
    Étape 1 · Infos générales — design « Voyage - Formulaire » (Espace Admin v2).
    Rend la colonne principale (.vf-col-main) et la colonne droite (.vf-col-side) du panneau #s-general.
    Champs persistés par l'étape : title, slug, excerpt, content, destination, duration_text,
    post_status, min_people, tour_price_by, is_featured, is_group_deal.
--}}
@php
    $vfTitle = (string) old('title', $voyage->post_title ?? '');
    $vfSlug = (string) old('slug', $voyage->post_name ?? '');
    $vfExcerpt = (string) old('excerpt', $voyage->post_excerpt ?? '');
    $vfExcerptLen = \Illuminate\Support\Str::length(trim(strip_tags($vfExcerpt)));
    $vfDestination = (string) old('destination', $veDestination ?? '');
    $vfDuration = (string) old('duration_text', data_get($laravelV ?? null, 'duration_text') ?? ($meta['duration_day'] ?? ''));
    $vfDays = preg_match('/^\d+$/', trim($vfDuration)) ? (int) $vfDuration : null;
    $vfNights = $vfDays !== null && $vfDays > 0 ? max(0, $vfDays - 1) : null;
    $vfStatus = (string) old('post_status', $voyage->post_status ?? 'draft');
    $vfMinPeople = (string) old('min_people', $meta['min_people'] ?? '');
    $vfPriceBy = (string) old('tour_price_by', $meta['tour_price_by'] ?? '');
    $vfFeatured = in_array((string) old('is_featured', $meta['is_featured'] ?? ''), ['on', '1'], true);
    $vfGroupDeal = (bool) old('is_group_deal', (int) data_get($laravelV ?? null, 'is_group_deal', 0));
    $vfHeroUrl = $heroImageUrl ?? null;
    $vfBase = rtrim((string) ($publicVoyagesBaseUrl ?? url('/voyages')), '/');
    $vfBaseDisplay = preg_replace('#^https?://#', '', $vfBase) . '/';
    $vfBrand = \App\Models\Setting::getValue('brand_name', 'Ajinsafro');
    $tourPriceByOpts = \App\Services\BusinessReferentialService::tourPriceByOptions();
    $vfGoogleTitle = ($vfTitle !== '' ? $vfTitle : 'Titre du voyage') . ' — ' . $vfBrand;
    $vfGoogleUrl = implode(' › ', array_filter(explode('/', rtrim($vfBaseDisplay, '/')))) . ' › ' . ($vfSlug !== '' ? $vfSlug : '…');
    $vfGoogleDesc = $vfExcerpt !== ''
        ? \Illuminate\Support\Str::limit(trim(strip_tags($vfExcerpt)), 160)
        : 'L’accroche courte sert de description dans les résultats de recherche.';
@endphp

<div class="vf-col-main">

    <section id="sec-fiche" class="vf-card">
        <div class="vf-card__head">
            <span class="vf-card__num">01</span>
            <div style="min-width:0">
                <h2 class="vf-card__title">Fiche commerciale</h2>
                <p class="vf-card__desc">Ce que le client voit en premier : titre, accroche et prix affiché.</p>
            </div>
        </div>
        <div class="vf-card__body">
            <label class="vf-field">
                <span class="vf-label">Titre du voyage <em>obligatoire</em></span>
                <input type="text" class="vf-input @error('title') is-invalid @enderror" id="title" name="title" value="{{ $vfTitle }}" required data-v2-error="title">
            </label>

            <div class="vf-row">
                <div class="vf-field vf-field--grow">
                    <span class="vf-label">Prix de base affiché</span>
                    <div class="vf-readout">
                        <span class="vf-readout__value {{ $vePriceLabel ? '' : 'is-empty' }}">{{ $vePriceLabel ?: 'À définir' }}</span>
                        <button type="button" class="vf-readout__link" data-v2-nav="s-pricing">Modifier dans Tarifs</button>
                    </div>
                </div>
                <label class="vf-field vf-field--grow">
                    <span class="vf-label">Durée</span>
                    <div class="vf-duo">
                        <input type="text" inputmode="numeric" class="vf-input vf-input--mono" id="duration_text" name="duration_text" value="{{ $vfDuration }}" placeholder="5" maxlength="100" data-v2-error="duration_text">
                        <input type="text" class="vf-input vf-input--mono" value="{{ $vfNights ?? '' }}" placeholder="4" readonly tabindex="-1" aria-label="Nuits (calculées)" data-vf-nights>
                    </div>
                    <span class="vf-hint">jours / nuits</span>
                    {{-- Champ technique : nombre de jours du programme. Alimenté par l'étape Programme et
                         lu par les listes « Jour » des étapes Vols, Hôtels et Transferts. --}}
                    <input type="hidden" id="duration_day" name="duration_day" value="{{ old('duration_day', $meta['duration_day'] ?? ($vfDays ?: 1)) }}">
                </label>
                <label class="vf-field vf-field--grow">
                    <span class="vf-label">Destination</span>
                    <input type="text" class="vf-input" id="destination" name="destination" value="{{ $vfDestination }}" placeholder="Barcelone, Espagne" maxlength="255" data-v2-error="destination">
                </label>
            </div>

            <label class="vf-field">
                <span class="vf-label">Accroche courte <em class="vf-count {{ $vfExcerptLen > 160 ? 'is-over' : '' }}" data-v3-counter-for="excerpt">{{ $vfExcerptLen }} / 160</em></span>
                <textarea class="vf-input {{ $vfExcerptLen > 160 ? 'is-missing' : '' }}" id="excerpt" name="excerpt" rows="3" data-v2-error="excerpt">{{ $vfExcerpt }}</textarea>
                <span class="vf-hint" data-vf-excerpt-hint>Affichée dans les listes et résultats de recherche.</span>
            </label>
        </div>
    </section>

    <section id="sec-seo" class="vf-card">
        <div class="vf-card__head">
            <span class="vf-card__num">02</span>
            <div style="min-width:0">
                <h2 class="vf-card__title">SEO &amp; URL</h2>
                <p class="vf-card__desc">Adresse publique et rendu dans les résultats de recherche.</p>
            </div>
        </div>
        <div class="vf-card__body">
            <label class="vf-field">
                <span class="vf-label">Slug de l'URL</span>
                <div class="vf-input-group">
                    <span class="vf-input-group__prefix">{{ $vfBaseDisplay }}</span>
                    <input type="text" class="vf-input vf-input--bare vf-input--mono" id="slug" name="slug" value="{{ $vfSlug }}" placeholder="genere-depuis-le-titre" maxlength="255" data-v2-error="slug">
                    <button type="button" class="vf-input-group__action" data-v3-copy-slug>Copier</button>
                </div>
                <span class="vf-hint">Laissez vide pour générer le slug depuis le titre. <span data-v3-slug-preview class="d-none">{{ $vfBase . '/' . ltrim($vfSlug, '/') }}</span></span>
            </label>
            <div class="vf-google" aria-live="polite">
                <div class="vf-kicker">Aperçu Google</div>
                <div class="vf-google__title" data-vf-google-title>{{ $vfGoogleTitle }}</div>
                <div class="vf-google__url" data-vf-google-url>{{ $vfGoogleUrl }}</div>
                <div class="vf-google__desc" data-vf-google-desc>{{ $vfGoogleDesc }}</div>
            </div>
        </div>
    </section>

    <section id="sec-presentation" class="vf-card">
        <div class="vf-card__head">
            <span class="vf-card__num">03</span>
            <div style="min-width:0">
                <h2 class="vf-card__title">Présentation détaillée</h2>
                <p class="vf-card__desc">Descriptif long affiché sur la page du voyage.</p>
            </div>
        </div>
        <div class="vf-editor" style="margin-top:20px">
            <textarea class="vf-input rich-editor" id="content" name="content" rows="9" placeholder="Décrivez l'expérience : ambiance, points forts, ce qui rend ce séjour différent…" data-v2-error="content">{{ old('content', $voyage->post_content ?? '') }}</textarea>
        </div>
        <div class="vf-chips">
            <span class="vf-chips__label">Suggestions :</span>
            <button type="button" class="vf-chip" data-vf-suggest="Points forts du séjour">Points forts du séjour</button>
            <button type="button" class="vf-chip" data-vf-suggest="À savoir avant de partir">À savoir avant de partir</button>
        </div>
    </section>

    <section id="sec-medias" class="vf-card">
        <div class="vf-card__head">
            <span class="vf-card__num">04</span>
            <div style="min-width:0">
                <h2 class="vf-card__title">Médias</h2>
                <p class="vf-card__desc">Visuel principal et galerie. JPG ou WebP, 1600×900 minimum. Gérés à l'étape Médias.</p>
            </div>
        </div>
        <div class="vf-media">
            <div class="vf-media__thumb {{ $vfHeroUrl ? 'has-image' : '' }}">
                @if($vfHeroUrl)
                    <img src="{{ $vfHeroUrl }}" alt="Visuel principal du voyage">
                @else
                    <span class="vf-media__thumb-label">visuel principal</span>
                    <span class="vf-media__thumb-hint">1600 × 900</span>
                @endif
            </div>
            <button type="button" class="vf-media__drop" data-v2-nav="s-media">
                <span class="vf-media__plus" aria-hidden="true">+</span>
                <span class="vf-media__drop-title">{{ $vfHeroUrl ? 'Gérer les images' : 'Ajouter des images' }}</span>
                <span class="vf-media__drop-hint">ouvre l'étape Médias</span>
            </button>
        </div>
    </section>
</div>

<div class="vf-col-side">

    <section id="sec-reglages" class="vf-side-card">
        <h2 class="vf-side-card__title">Réglages de publication</h2>
        <p class="vf-side-card__sub">Visibilité et règles de vente</p>
        <div style="display:flex; flex-direction:column; gap:16px;">
            <label class="vf-field">
                <span class="vf-label">Statut</span>
                <select class="vf-input vf-input--sm" id="post_status" name="post_status" data-v2-error="post_status">
                    <option value="publish" @selected($vfStatus === 'publish')>Publié</option>
                    <option value="draft" @selected($vfStatus === 'draft')>Brouillon</option>
                    <option value="pending" @selected($vfStatus === 'pending')>En attente</option>
                    <option value="private" @selected($vfStatus === 'private')>Archivé</option>
                </select>
            </label>
            <div class="vf-row vf-row--tight">
                <label class="vf-field" style="flex:1 1 110px">
                    <span class="vf-label">Min. personnes</span>
                    <input type="number" class="vf-input vf-input--sm vf-input--mono {{ $vfMinPeople === '' ? 'is-missing' : '' }}" id="min_people" name="min_people" value="{{ $vfMinPeople }}" min="1" placeholder="2" data-v2-error="min_people">
                </label>
                <label class="vf-field" style="flex:1 1 110px">
                    <span class="vf-label">Tarification par</span>
                    <select class="vf-input vf-input--sm {{ $vfPriceBy === '' ? 'is-missing' : '' }}" id="tour_price_by" name="tour_price_by" data-v2-error="tour_price_by">
                        <option value="">— Sélectionner —</option>
                        @foreach($tourPriceByOpts as $opt)
                            <option value="{{ $opt['value'] }}" @selected($vfPriceBy === (string) $opt['value'])>{{ $opt['label'] }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
            <div class="vf-toggles">
                <label class="vf-toggle" for="is_featured">
                    <span style="min-width:0">
                        <span class="vf-toggle__title d-block">Mettre en avant</span>
                        <span class="vf-toggle__sub d-block">Page d'accueil et newsletters</span>
                    </span>
                    <input type="checkbox" id="is_featured" name="is_featured" value="1" @checked($vfFeatured)>
                    <span class="vf-switch" aria-hidden="true"></span>
                </label>
                <input type="hidden" name="is_group_deal" value="0">
                <label class="vf-toggle" for="is_group_deal">
                    <span style="min-width:0">
                        <span class="vf-toggle__title d-block">Afficher dans Group Deals</span>
                        <span class="vf-toggle__sub d-block">Offres groupées</span>
                    </span>
                    <input type="checkbox" id="is_group_deal" name="is_group_deal" value="1" @checked($vfGroupDeal)>
                    <span class="vf-switch" aria-hidden="true"></span>
                </label>
            </div>
        </div>
    </section>

    <section class="vf-side-card vf-side-card--navy">
        <h2 class="vf-side-card__title">Vue rapide</h2>
        <p class="vf-side-card__sub">État du produit</p>
        <div class="vf-quick">
            <div class="vf-quick__row"><span class="vf-quick__label">Réf. voyage</span><span class="vf-quick__value" id="v2-rail-id">{{ $isCreate ? 'nouveau' : '#' . $veWpId }}</span></div>
            <div class="vf-quick__row"><span class="vf-quick__label">Statut</span><span class="vf-quick__value is-text" id="v2-rail-status">{{ $statusLabel }}</span></div>
            <div class="vf-quick__row"><span class="vf-quick__label">Départs programmés</span><span class="vf-quick__value {{ $veDatesCount > 0 ? '' : 'is-accent' }}">{{ $veDatesCount }}</span></div>
            <div class="vf-quick__row"><span class="vf-quick__label">Prix de base</span><span class="vf-quick__value">{{ $vePriceLabel ?: '—' }}</span></div>
            <div class="vf-quick__row"><span class="vf-quick__label">Destination</span><span class="vf-quick__value is-text" id="v2-rail-destination">{{ $vfDestination !== '' ? $vfDestination : '—' }}</span></div>
        </div>
        @if($veDatesCount === 0)
            <div class="vf-quick__note">Aucun départ programmé : le voyage reste invisible à la réservation.</div>
        @endif
    </section>
</div>
