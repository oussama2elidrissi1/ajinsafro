@extends('layouts.partner')
@section('title', 'Catalogue voyages')

@section('content')
    <div class="row mb-3">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title mb-0 font-size-18">Catalogue voyages</h4>
            </div>
        </div>
    </div>
    <p class="text-muted">Voyages que vous pouvez proposer et vendre. Prix public et commission applicable selon les règles définies par Ajinsafro.</p>

    <div class="row">
        @forelse($voyages as $voyage)
            @php
                $rule = $ruleByVoyage[$voyage->id] ?? $globalRule;
                $commissionLabel = $rule
                    ? ($rule->type === \App\Models\PartnerCommissionRule::TYPE_PERCENT ? $rule->value . ' %' : number_format($rule->value, 0, ',', ' ') . ' DH')
                    : '—';
            @endphp
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="card-title">{{ $voyage->name }}</h6>
                        @if($voyage->destination)
                            <p class="text-muted small mb-1">{{ $voyage->destination }}</p>
                        @endif
                        <p class="mb-2">
                            <strong>Prix public :</strong> {{ $voyage->price_from ? number_format($voyage->price_from, 0, ',', ' ') . ' ' . ($voyage->currency_symbol ?? 'DH') : '—' }}
                        </p>
                        <p class="mb-0">
                            <strong>Commission :</strong> <span class="text-success">{{ $commissionLabel }}</span>
                        </p>
                    </div>
                    <div class="card-footer bg-transparent">
                        <a href="{{ route('partner.reservations.create') }}?tour_id={{ $voyage->id }}" class="btn btn-sm btn-primary">Réserver</a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info">Aucun voyage disponible pour le moment.</div>
            </div>
        @endforelse
    </div>
    @if(method_exists($voyages, 'links'))
        <div class="d-flex justify-content-center mt-3">{{ $voyages->links() }}</div>
    @endif
@endsection
