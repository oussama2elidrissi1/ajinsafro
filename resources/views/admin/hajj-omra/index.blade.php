@extends('layouts.master-ajinsafro')

@section('title', 'Hajj & Omra')

@section('content')
    <x-admin.page-header
        title="Hajj & Omra"
        subtitle="Gerez vos offres Omra, Hajj, Ramadan et premium depuis un seul espace admin."
        :breadcrumbs="[
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Produits & Services'],
            ['label' => 'Hajj & Omra'],
        ]"
    >
        <x-slot name="actions">
            <a href="{{ route('admin.hajj-omra.requests.index') }}" class="aj-btn aj-btn-soft">
                <i class="bx bx-message-square-detail"></i>
                <span>Demandes</span>
            </a>
            <a href="{{ route('admin.hajj-omra.create') }}" class="aj-btn aj-btn-primary">
                <i class="bx bx-plus"></i>
                <span>Nouvelle offre</span>
            </a>
        </x-slot>
    </x-admin.page-header>

    <x-admin.flash-messages />

    <x-admin.kpi-cards
        :kpis="[
            ['label' => 'Offres visibles', 'value' => number_format($totals['published'], 0, ',', ' '), 'icon' => 'bx bx-badge-check', 'color' => '-green', 'note' => 'Publiees'],
            ['label' => 'Offres filtrees', 'value' => number_format($totals['packages'], 0, ',', ' '), 'icon' => 'bx bx-kaaba', 'color' => '-blue', 'note' => 'Resultat courant'],
            ['label' => 'Mises en avant', 'value' => number_format($totals['featured'], 0, ',', ' '), 'icon' => 'bx bx-star', 'color' => '-orange', 'note' => 'Hero / priorite'],
            ['label' => 'Demandes total', 'value' => number_format($totals['requests'], 0, ',', ' '), 'icon' => 'bx bx-envelope-open', 'color' => '-violet', 'note' => 'Toutes offres'],
        ]"
    />

    <x-admin.filter-panel :action="route('admin.hajj-omra.index')" :reset-url="route('admin.hajj-omra.index')" grid-class="row g-3 align-items-end">
        <x-slot name="fields">
            <div class="col-md-4">
                <label class="form-label">Recherche</label>
                <input type="text" name="q" value="{{ $filters['q'] }}" class="form-control" placeholder="Titre, destination, ville de depart">
            </div>
            <div class="col-md-3">
                <label class="form-label">Destination</label>
                <input type="text" name="destination" value="{{ $filters['destination'] }}" class="form-control" placeholder="Makkah, Madinah, Jeddah">
            </div>
            <div class="col-md-2">
                <label class="form-label">Type</label>
                <select name="type" class="form-select">
                    <option value="">Tous</option>
                    @foreach($typeOptions as $value => $label)
                        <option value="{{ $value }}" @selected($filters['type'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Statut</label>
                <select name="status" class="form-select">
                    <option value="">Tous</option>
                    @foreach($statusOptions as $value => $label)
                        <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </x-slot>
    </x-admin.filter-panel>

    <section class="aj-panel">
        @if($packages->isEmpty())
            <x-admin.empty-state
                title="Aucune offre Hajj & Omra"
                message="Commencez par creer une offre puis ajoutez ses chambres, departs et programme."
                :action-url="route('admin.hajj-omra.create')"
                action-label="Creer une offre"
            />
        @else
            <div class="table-responsive" style="overflow-x:auto;">
                <table class="aj-table" style="width:100%;border-collapse:separate;border-spacing:0;">
                    <thead>
                    <tr>
                        <th>Image</th>
                        <th>Titre</th>
                        <th>Type</th>
                        <th>Ville de depart</th>
                        <th>Prix a partir de</th>
                        <th>Date de depart</th>
                        <th>Statut</th>
                        <th class="text-end">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($packages as $package)
                        @php($nextDeparture = $package->resolveUpcomingDeparture())
                        <tr>
                            <td><x-admin.image-thumb :src="$package->main_image_url" :alt="$package->title" size="sm" /></td>
                            <td>
                                <div style="font-weight:800;color:#102340;">{{ $package->title }}</div>
                                <div style="font-size:12px;font-weight:700;color:#7a879a;">{{ $package->destination ?: 'Destination non renseignee' }}</div>
                            </td>
                            <td>
                                <x-admin.badge type="info" :label="$package->type_label" />
                            </td>
                            <td>{{ $package->departure_city ?: '—' }}</td>
                            <td>
                                @if($package->price_from_value !== null)
                                    <strong>{{ number_format($package->price_from_value, 0, ',', ' ') }} {{ $package->currency }}</strong>
                                @else
                                    <span class="text-muted">Sur demande</span>
                                @endif
                            </td>
                            <td>{{ $nextDeparture?->departure_date?->format('d/m/Y') ?? ($package->start_date?->format('d/m/Y') ?: '—') }}</td>
                            <td>
                                <x-admin.badge
                                    :type="match($package->status){
                                        'published' => 'success',
                                        'full' => 'warning',
                                        'expired' => 'danger',
                                        default => 'neutral'
                                    }"
                                    :label="$package->status_label"
                                />
                            </td>
                            <td class="text-end">
                                <div class="aj-actions" style="display:flex;justify-content:flex-end;gap:8px;flex-wrap:wrap;">
                                    <a href="{{ route('admin.hajj-omra.show', $package) }}" class="aj-icon-btn" title="Voir" style="width:36px;height:36px;display:inline-flex;align-items:center;justify-content:center;border-radius:12px;border:1px solid var(--ajp-line);background:#fff;color:#31435c;text-decoration:none;">
                                        <i class="bx bx-show"></i>
                                    </a>
                                    <a href="{{ route('admin.hajj-omra.edit', $package) }}" class="aj-icon-btn" title="Modifier" style="width:36px;height:36px;display:inline-flex;align-items:center;justify-content:center;border-radius:12px;border:1px solid var(--ajp-line);background:#fff;color:#31435c;text-decoration:none;">
                                        <i class="bx bx-pencil"></i>
                                    </a>
                                    <form action="{{ route('admin.hajj-omra.destroy', $package) }}" method="POST" onsubmit="return confirm('Supprimer cette offre ?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="aj-icon-btn -danger" title="Supprimer" style="width:36px;height:36px;display:inline-flex;align-items:center;justify-content:center;border-radius:12px;border:1px solid var(--ajp-line);background:#fff;color:#31435c;text-decoration:none;">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <x-admin.pagination-footer :paginator="$packages" />
        @endif
    </section>
@endsection
