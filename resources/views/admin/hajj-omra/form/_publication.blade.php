{{-- Etape 8 : publication, SEO et recapitulatif avant mise en ligne. --}}
<div class="ho-panel" data-panel="publication">
    <section class="ho-card mb-0">
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="ho-eyebrow">Publication</div>

            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label" for="slug">Slug</label>
                    <div class="input-group">
                        <span class="input-group-text ho-mono">ajinsafro.ma/hajj-omra/</span>
                        <input type="text" id="slug" name="slug" class="form-control ho-mono @error('slug') is-invalid @enderror"
                               value="{{ old('slug', $package->slug) }}" placeholder="omra-ramadan-1448">
                    </div>
                    <div class="form-text">Laissez vide pour générer automatiquement depuis le titre français.</div>
                    @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="sort_order">Ordre d'affichage</label>
                    <input type="number" min="0" id="sort_order" name="sort_order" class="form-control"
                           value="{{ old('sort_order', $package->sort_order ?? 0) }}">
                </div>
            </div>

            <hr class="my-4">
            <div class="ho-eyebrow">Référencement (SEO)</div>

            @include('admin.hajj-omra.form._field', [
                'name' => 'meta_title_fr',
                'nameAr' => 'meta_title_ar',
                'label' => 'Meta title',
                'labelAr' => 'عنوان الميتا',
                'value' => $package->meta_title,
                'valueAr' => $package->meta_title_ar,
                'maxlength' => 255,
            ])

            @include('admin.hajj-omra.form._field', [
                'name' => 'meta_description_fr',
                'nameAr' => 'meta_description_ar',
                'label' => 'Meta description',
                'labelAr' => 'وصف الميتا',
                'value' => $package->meta_description,
                'valueAr' => $package->meta_description_ar,
                'type' => 'textarea',
                'rows' => 3,
                'maxlength' => 500,
            ])
            <div class="ho-serp mt-3">
                <div class="ho-eyebrow mb-2">Aperçu moteur de recherche</div>
                <div class="ho-serp__url">ajinsafro.ma › hajj-omra › {{ $package->slug ?: 'slug-a-generer' }}</div>
                <div class="ho-serp__title">{{ $package->meta_title ?: ($package->title ?: "Titre de l'offre") }}</div>
                <p class="ho-serp__desc">{{ \Illuminate\Support\Str::limit($package->meta_description ?: $package->short_description, 160) ?: 'Ajoutez une meta description pour contrôler ce texte.' }}</p>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="ho-eyebrow">Récapitulatif avant publication</div>

                    @if ($package->main_image_url)
                        <img src="{{ $package->main_image_url }}" alt="" class="img-fluid rounded mb-3" style="max-height:150px;object-fit:cover;width:100%;">
                    @else
                        <div class="ho-dropzone mb-3" style="cursor:default;">Aucune image principale</div>
                    @endif

                    <table class="table table-sm mb-0">
                        <tr><td class="text-muted">Titre</td><td class="text-end">{{ $package->title ?: '—' }}</td></tr>
                        <tr><td class="text-muted">Titre arabe</td><td class="text-end" dir="rtl">{{ $package->title_ar ?: '—' }}</td></tr>
                        <tr><td class="text-muted">Type</td><td class="text-end">{{ $package->exists ? $package->type_label : '—' }}</td></tr>
                        <tr><td class="text-muted">Durée</td><td class="text-end">{{ $package->duration_label ?: '—' }}</td></tr>
                        <tr>
                            <td class="text-muted">Dates</td>
                            <td class="text-end">
                                {{ optional($package->start_date)->format('d/m/Y') ?: '—' }}
                                @if ($package->return_date) → {{ $package->return_date->format('d/m/Y') }} @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Prix à partir de</td>
                            <td class="text-end fw-semibold">
                                @if ($package->exists && $package->price_from_value !== null)
                                    {{ number_format($package->price_from_value, 2, ',', ' ') }} {{ $package->currency }}
                                @else — @endif
                            </td>
                        </tr>
                        <tr><td class="text-muted">Places disponibles</td><td class="text-end">{{ $package->available_places ?? 0 }}</td></tr>
                        <tr><td class="text-muted">Nombre de départs</td><td class="text-end">{{ $package->exists ? $package->departures->count() : 0 }}</td></tr>
                        <tr><td class="text-muted">Nombre de tarifs</td><td class="text-end">{{ $package->exists ? $package->roomPrices->count() : 0 }}</td></tr>
                        <tr><td class="text-muted">Jours de programme</td><td class="text-end">{{ $package->exists ? $package->programDays->count() : 0 }}</td></tr>
                    </table>

                    @if ($package->exists && ! empty($missingArabic))
                        <div class="alert alert-warning border-0 small mt-3 mb-0">
                            <div class="fw-semibold mb-1">Traduction arabe non complétée</div>
                            Champs concernés : {{ implode(', ', $missingArabic) }}.
                            L'offre reste publiable ; l'arabe retombera sur le texte français.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
</div>
