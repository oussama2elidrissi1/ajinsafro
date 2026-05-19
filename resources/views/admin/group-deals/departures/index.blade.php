@extends('layouts.admin-v6')
@section('title') Group Deals â€” DÃ©parts @endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="page-title mb-0 font-size-18">Group Deals â€” DÃ©parts</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.group-deals.trips.index') }}">Group Deals</a></li>
                    <li class="breadcrumb-item active">DÃ©parts</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        {{-- Filtres --}}
        <form method="get" class="row g-2 align-items-end mb-4">
            <div class="col-md-3">
                <label class="form-label small mb-1">Voyage</label>
                <select name="voyage_id" class="form-select form-select-sm">
                    <option value="">Tous les voyages</option>
                    @foreach($voyageOptions as $v)
                        <option value="{{ $v->id }}" @selected(request('voyage_id') == $v->id)>{{ $v->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">Statut dÃ©part</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">Tous</option>
                    <option value="open"    @selected(request('status') === 'open')>Ouvert</option>
                    <option value="limited" @selected(request('status') === 'limited')>LimitÃ©</option>
                    <option value="full"    @selected(request('status') === 'full')>Complet</option>
                    <option value="closed"  @selected(request('status') === 'closed')>FermÃ©</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">Garanti</label>
                <select name="guaranteed" class="form-select form-select-sm">
                    <option value="">Tous</option>
                    <option value="1" @selected(request('guaranteed') === '1')>Oui</option>
                    <option value="0" @selected(request('guaranteed') === '0')>Non</option>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-sm">Filtrer</button>
                <a href="{{ route('admin.group-deals.departures.index') }}" class="btn btn-outline-secondary btn-sm ms-1">RÃ©initialiser</a>
            </div>
        </form>

        <h5 class="mb-3">{{ $departures->total() }} dÃ©part(s)</h5>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Voyage</th>
                        <th>Date</th>
                        <th class="text-center">Participants</th>
                        <th class="text-center">Seuil garanti</th>
                        <th>Prix actif</th>
                        <th>Garanti</th>
                        <th>Statut</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($departures as $dep)
                    <tr>
                        <td>
                            <a href="{{ route('admin.group-deals.trips.show', $dep->voyage) }}"
                               class="text-body fw-semibold">{{ $dep->voyage?->name ?? 'â€”' }}</a>
                            @if($dep->voyage?->destination)
                                <br><small class="text-muted">{{ $dep->voyage->destination }}</small>
                            @endif
                        </td>
                        <td class="small">
                            {{ $dep->start_date?->format('d/m/Y') ?? 'â€”' }}
                            @if($dep->end_date)
                                <br><span class="text-muted">â†’ {{ $dep->end_date->format('d/m/Y') }}</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @php $pct = $dep->guaranteed_threshold > 0 ? min(100, round(($dep->confirmed_count / $dep->guaranteed_threshold) * 100)) : 0; @endphp
                            <strong>{{ $dep->confirmed_count }}</strong>
                            <div class="progress mt-1" style="height:4px;width:60px;margin:0 auto;">
                                <div class="progress-bar bg-{{ $dep->is_guaranteed ? 'success' : 'warning' }}"
                                     style="width:{{ $pct }}%"></div>
                            </div>
                        </td>
                        <td class="text-center text-muted small">{{ $dep->guaranteed_threshold }}</td>
                        <td>
                            @if($dep->active_tier_price)
                                <span class="text-success fw-semibold">{{ number_format($dep->active_tier_price, 0, ',', ' ') }} â‚¬</span>
                            @elseif($dep->sale_price)
                                {{ number_format($dep->sale_price, 0, ',', ' ') }} â‚¬
                            @else
                                â€”
                            @endif
                        </td>
                        <td>
                            @if($dep->is_guaranteed)
                                <span class="badge bg-success">Oui</span>
                                @if($dep->guaranteed_at)
                                    <br><small class="text-muted">{{ $dep->guaranteed_at->format('d/m/Y') }}</small>
                                @endif
                            @else
                                <span class="badge bg-light text-dark border">Non</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-{{ match($dep->status) {
                                'open'    => 'success',
                                'limited' => 'warning text-dark',
                                'full'    => 'danger',
                                'closed'  => 'secondary',
                                default   => 'secondary'
                            } }}">{{ $dep->status_label }}</span>
                        </td>
                        <td>
                            <a href="{{ route('admin.group-deals.departures.show', $dep) }}"
                               class="btn btn-sm btn-outline-primary">DÃ©tail</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            Aucun dÃ©part Group Deal trouvÃ©.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if($departures->hasPages())
            <div class="mt-3">{{ $departures->links() }}</div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush

