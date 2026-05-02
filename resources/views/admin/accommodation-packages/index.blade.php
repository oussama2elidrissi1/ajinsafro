@extends('layouts.master-ajinsafro')

@section('title', 'Packs hébergement')

@section('content')
    <x-admin.page-header
        title="Packs hébergement"
        subtitle="Gérez les packs d'hébergement affichés sur le site."
        :breadcrumbs="[
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Packs hébergement'],
        ]"
    >
        <x-slot name="actions">
            <a href="{{ route('admin.accommodation-packages.create') }}" class="aj-btn aj-btn-primary">
                <i class="bx bx-plus"></i>
                <span>Nouveau pack</span>
            </a>
        </x-slot>
    </x-admin.page-header>

    <x-admin.flash-messages />

    <x-admin.kpi-cards
        :kpis="[
            ['label' => 'Total packs', 'value' => number_format($packages->total(), 0, ',', ' '), 'icon' => 'bx bx-buildings', 'color' => '-blue', 'note' => 'Base complète'],
            ['label' => 'Actifs', 'value' => number_format($packages->where('is_active', true)->count(), 0, ',', ' '), 'icon' => 'bx bx-badge-check', 'color' => '-green', 'note' => 'Visibles sur le site'],
            ['label' => 'En vedette', 'value' => number_format($packages->where('is_featured', true)->count(), 0, ',', ' '), 'icon' => 'bx bx-star', 'color' => '-orange', 'note' => 'Mis en avant'],
            ['label' => 'Prix moyen', 'value' => number_format($packages->avg('price_from') ?? 0, 0, ',', ' ') . ' DH', 'icon' => 'bx bx-wallet', 'color' => '-violet', 'note' => 'Moyenne base'],
        ]"
    />

    <section class="aj-panel">
        @if($packages->isEmpty())
            <x-admin.empty-state
                title="Aucun pack hébergement"
                message="Créez votre premier pack pour l'afficher sur le site."
                :action-url="route('admin.accommodation-packages.create')"
                action-label="Nouveau pack"
            />
        @else
            <div class="table-responsive" style="overflow-x:auto;">
                <table class="aj-table" style="width:100%;border-collapse:separate;border-spacing:0;">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Titre</th>
                            <th>Destination</th>
                            <th>Durée</th>
                            <th>Pension</th>
                            <th>Type</th>
                            <th>Prix</th>
                            <th>Vedette</th>
                            <th>Statut</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($packages as $package)
                            <tr>
                                <td>
                                    <x-admin.image-thumb :src="$package->image_url" :alt="$package->title" size="sm" />
                                </td>
                                <td>
                                    <div style="font-weight:800;color:#102340;">{{ $package->title }}</div>
                                    <div style="font-size:12px;font-weight:700;color:#7a879a;">#{{ $package->id }}</div>
                                </td>
                                <td>
                                    <div style="font-weight:700;color:#253754;font-size:13px;">{{ $package->city ?? 'Ville non renseignée' }}</div>
                                    <div style="font-size:12px;font-weight:600;color:#7a879a;">{{ $package->country ?? '' }}</div>
                                </td>
                                <td>{{ $package->duration_days }}j / {{ $package->nights }}n</td>
                                <td>{{ $package->pension_type ?? '—' }}</td>
                                <td>{{ $package->accommodation_type ?? '—' }}</td>
                                <td>
                                    <span style="color:var(--ajp-ink);font-size:15px;font-weight:900;white-space:nowrap;">
                                        {{ number_format($package->price_from, 0, ',', ' ') }} {{ $package->currency }}
                                    </span>
                                </td>
                                <td>
                                    <x-admin.badge :type="$package->is_featured ? 'warning' : 'neutral'" :label="$package->is_featured ? 'Oui' : 'Non'" />
                                </td>
                                <td>
                                    <x-admin.badge :type="$package->is_active ? 'success' : 'neutral'" :label="$package->is_active ? 'Actif' : 'Inactif'" />
                                </td>
                                <td class="text-end">
                                    <x-admin.action-buttons
                                        :edit-url="route('admin.accommodation-packages.edit', $package)"
                                        :delete-url="route('admin.accommodation-packages.destroy', $package)"
                                        delete-confirm="Supprimer ce pack ?"
                                    />
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
