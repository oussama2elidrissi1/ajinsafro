@extends('layouts.admin-v6')

@section('title', 'Group Deals')

@section('content')
    <x-admin.page-header
        title="Offres de voyage de groupe"
        subtitle="Créez des offres autonomes avec progression, garantie et prix par paliers."
        :breadcrumbs="[
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Group Deals'],
        ]"
    >
        <x-slot name="actions">
            <a href="{{ route('admin.group-deals.create') }}" class="aj-btn aj-btn-primary">
                <i class="bx bx-plus"></i>
                <span>Nouvelle offre</span>
            </a>
        </x-slot>
    </x-admin.page-header>

    <x-admin.flash-messages />

    <x-admin.kpi-cards
        :kpis="[
            ['label' => 'Total offres', 'value' => number_format($groupDeals->total(), 0, ',', ' '), 'icon' => 'bx bx-group', 'color' => '-blue', 'note' => 'Base complète'],
            ['label' => 'Actives', 'value' => number_format($groupDeals->where('status', 'active')->count(), 0, ',', ' '), 'icon' => 'bx bx-badge-check', 'color' => '-green', 'note' => 'En cours'],
            ['label' => 'Complètes', 'value' => number_format($groupDeals->where('status', 'completed')->count(), 0, ',', ' '), 'icon' => 'bx bx-check-double', 'color' => '-orange', 'note' => 'Garanties'],
            ['label' => 'En cours', 'value' => number_format($groupDeals->where('status', 'in_progress')->count(), 0, ',', ' '), 'icon' => 'bx bx-time', 'color' => '-violet', 'note' => 'Recrutement'],
        ]"
    />

    <x-admin.filter-panel :reset-url="route('admin.group-deals.index')">
        <x-slot name="fields">
            <div class="aj-field aj-search-wrap">
                <span class="aj-search-icon"><i class="bx bx-search"></i></span>
                <input type="text" name="q" class="aj-control" value="{{ $filters['q'] ?? '' }}" placeholder="Titre ou destination">
            </div>
            <div class="aj-field">
                <select name="status" class="aj-control">
                    <option value="">Tous les statuts</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
        </x-slot>
    </x-admin.filter-panel>

    <section class="aj-panel">
        @if($groupDeals->isEmpty())
            <x-admin.empty-state
                title="Aucune offre Group Deal"
                message="Créez votre première offre pour lancer un voyage de groupe."
                :action-url="route('admin.group-deals.create')"
                action-label="Nouvelle offre"
            />
        @else
            <div class="table-responsive" style="overflow-x:auto;">
                <table class="aj-table" style="width:100%;border-collapse:separate;border-spacing:0;">
                    <thead>
                        <tr>
                            <th>Visuel</th>
                            <th>Offre</th>
                            <th>Dates</th>
                            <th>Progression</th>
                            <th>Prix actuel</th>
                            <th>Statut</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($groupDeals as $deal)
                            <tr>
                                <td>
                                    <x-admin.image-thumb :src="$deal->image_url" :alt="$deal->title" size="sm" />
                                </td>
                                <td>
                                    <div style="font-weight:800;color:#102340;">{{ $deal->title }}</div>
                                    <div style="font-size:12px;font-weight:700;color:#7a879a;">{{ $deal->destination ?: 'Destination non renseignée' }}</div>
                                </td>
                                <td>
                                    <div style="font-weight:700;color:#253754;font-size:13px;">{{ optional($deal->start_date)->format('d/m/Y') ?: 'N/A' }}</div>
                                    @if($deal->end_date)
                                        <div style="font-size:12px;font-weight:600;color:#7a879a;">�?' {{ $deal->end_date->format('d/m/Y') }}</div>
                                    @endif
                                </td>
                                <td>
                                    <div style="font-size:13px;font-weight:700;color:#31435c;">{{ $deal->current_participants }}/{{ $deal->max_participants }} inscrits</div>
                                    <div class="progress mt-2" style="height:8px;">
                                        <div class="progress-bar bg-success" style="width: {{ $deal->progress_percent }}%"></div>
                                    </div>
                                    <div style="font-size:12px;font-weight:600;color:#7a879a;margin-top:4px;">
                                        @if($deal->remaining_to_guarantee > 0)
                                            Il reste {{ $deal->remaining_to_guarantee }} personne(s) pour garantir
                                        @else
                                            Voyage garanti
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <span style="color:var(--ajp-ink);font-size:15px;font-weight:900;white-space:nowrap;">
                                        {{ $deal->current_price ? number_format((float) $deal->current_price, 0, ',', ' ') . ' DH' : 'N/A' }}
                                    </span>
                                </td>
                                <td>
                                    <x-admin.badge type="neutral" :label="$deal->status_label" />
                                </td>
                                <td class="text-end">
                                    <x-admin.action-buttons
                                        :view-url="route('admin.group-deals.show', $deal)"
                                        :edit-url="route('admin.group-deals.edit', $deal)"
                                    />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <x-admin.pagination-footer :paginator="$groupDeals" />
        @endif
    </section>
@endsection


