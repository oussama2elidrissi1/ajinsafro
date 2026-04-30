@csrf
@if($isEdit)
    @method('PUT')
@endif

@php
    $tiers = old('tiers', ($groupDeal->pricingTiers ?? collect())->map(fn ($tier) => [
        'min_participants' => $tier->min_participants,
        'max_people' => $tier->max_people,
        'price_per_person' => $tier->price_per_person,
        'label' => $tier->label,
        'sort_order' => $tier->sort_order,
    ])->values()->all());

    if (empty($tiers)) {
        $tiers = [
            ['min_participants' => 4, 'max_people' => 8, 'price_per_person' => 9000, 'label' => 'Lancement', 'sort_order' => 1],
            ['min_participants' => 9, 'max_people' => 14, 'price_per_person' => 8500, 'label' => 'Croissance', 'sort_order' => 2],
            ['min_participants' => 15, 'max_people' => 20, 'price_per_person' => 8000, 'label' => 'Meilleur prix', 'sort_order' => 3],
        ];
    }
@endphp

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h5 class="mb-0">Informations générales</h5></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Titre de l'offre</label>
                        <input type="text" name="title" class="form-control" value="{{ old('title', $groupDeal->title) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Slug</label>
                        <input type="text" name="slug" class="form-control" value="{{ old('slug', $groupDeal->slug) }}" placeholder="auto">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Destination</label>
                        <input type="text" name="destination" class="form-control" value="{{ old('destination', $groupDeal->destination) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Date de départ</label>
                        <input type="date" name="start_date" class="form-control" value="{{ old('start_date', optional($groupDeal->start_date)->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Date de retour</label>
                        <input type="date" name="end_date" class="form-control" value="{{ old('end_date', optional($groupDeal->end_date)->format('Y-m-d')) }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" rows="5" class="form-control">{{ old('description', $groupDeal->description) }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Programme</label>
                        <textarea name="program" rows="6" class="form-control">{{ old('program', $groupDeal->program) }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header"><h5 class="mb-0">Paliers de prix</h5></div>
            <div class="card-body">
                <p class="text-muted mb-3">Chaque palier définit la tranche de participants et le prix par personne. Les lignes sont entièrement modifiables depuis ce formulaire.</p>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead>
                        <tr>
                            <th>Min personnes</th>
                            <th>Max personnes</th>
                            <th>Prix / personne (DH)</th>
                            <th>Libellé</th>
                            <th>Ordre</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($tiers as $index => $tier)
                            <tr>
                                <td><input type="number" min="1" max="100000" class="form-control" name="tiers[{{ $index }}][min_participants]" value="{{ $tier['min_participants'] ?? '' }}" required></td>
                                <td><input type="number" min="1" max="100000" class="form-control" name="tiers[{{ $index }}][max_people]" value="{{ $tier['max_people'] ?? '' }}"></td>
                                <td><input type="number" min="0" step="0.01" class="form-control" name="tiers[{{ $index }}][price_per_person]" value="{{ $tier['price_per_person'] ?? '' }}" required></td>
                                <td><input type="text" class="form-control" name="tiers[{{ $index }}][label]" value="{{ $tier['label'] ?? '' }}"></td>
                                <td><input type="number" min="0" class="form-control" name="tiers[{{ $index }}][sort_order]" value="{{ $tier['sort_order'] ?? $index + 1 }}"></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="alert alert-info mb-0">Pour ajouter d’autres paliers, dupliquez une ligne ou utilisez ensuite la gestion inline sur la fiche détail.</div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h5 class="mb-0">Groupe et publication</h5></div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Statut</label>
                    <select name="status" class="form-select" required>
                        @foreach($statuses as $status)
                            <option value="{{ $status }}" @selected(old('status', $groupDeal->status) === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="row g-3">
                    <div class="col-sm-6">
                        <label class="form-label">Minimum garanti</label>
                        <input type="number" min="1" max="100000" name="min_participants" class="form-control" value="{{ old('min_participants', $groupDeal->min_participants) }}" required>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label">Maximum accepté</label>
                        <input type="number" min="1" max="100000" name="max_participants" class="form-control" value="{{ old('max_participants', $groupDeal->max_participants) }}" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Date limite d'inscription</label>
                        <input type="date" name="registration_deadline" class="form-control" value="{{ old('registration_deadline', optional($groupDeal->registration_deadline)->format('Y-m-d')) }}">
                    </div>
                </div>
                <div class="form-check form-switch mt-3">
                    <input class="form-check-input" type="checkbox" id="share_enabled" name="share_enabled" value="1" @checked(old('share_enabled', $groupDeal->share_enabled ?? true))>
                    <label class="form-check-label" for="share_enabled">Activer le partage client</label>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header"><h5 class="mb-0">Images et services</h5></div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Image principale</label>
                    <input type="file" name="image_file" class="form-control" accept="image/*">
                </div>
                <div class="mb-3">
                    <label class="form-label">ou URL / chemin image</label>
                    <input type="text" name="image" class="form-control" value="{{ old('image', $groupDeal->image) }}">
                </div>
                @if($groupDeal->image_url)
                    <div class="mb-3">
                        <img src="{{ $groupDeal->image_url }}" alt="" class="img-fluid rounded border">
                    </div>
                @endif
                <div class="mb-3">
                    <label class="form-label">Galerie images</label>
                    <textarea name="images_list" rows="4" class="form-control" placeholder="Une URL ou un chemin par ligne">{{ old('images_list', collect($groupDeal->images ?? [])->implode("\n")) }}</textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Services inclus</label>
                    <textarea name="services_included_list" rows="4" class="form-control" placeholder="Une ligne = un service">{{ old('services_included_list', collect($groupDeal->services_included ?? [])->implode("\n")) }}</textarea>
                </div>
                <div class="mb-0">
                    <label class="form-label">Services non inclus</label>
                    <textarea name="services_excluded_list" rows="4" class="form-control" placeholder="Une ligne = un service">{{ old('services_excluded_list', collect($groupDeal->services_excluded ?? [])->implode("\n")) }}</textarea>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2 mt-4">
            <button class="btn btn-primary flex-fill" type="submit">{{ $isEdit ? 'Mettre à jour' : 'Créer l\'offre' }}</button>
            <a href="{{ $isEdit ? route('admin.group-deals.show', $groupDeal) : route('admin.group-deals.index') }}" class="btn btn-light">Annuler</a>
        </div>
    </div>
</div>
