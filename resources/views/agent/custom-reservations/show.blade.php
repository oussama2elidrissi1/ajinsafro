@extends('layouts.master-ajinsafro')

@section('title', 'Détail demande à la carte')

@push('styles')
    <link href="{{ URL::asset('css/agent-dashboard.css') }}" rel="stylesheet" type="text/css" />
@endpush

@section('content')
<div class="aj-agent-dashboard">
    <div class="aj-agent-page-head">
        <div class="aj-agent-page-title">
            <h1>Détail demande à la carte</h1>
            <p>{{ $customRequest->request_number }}</p>
        </div>
        <a href="{{ route('agent.custom-reservations.index') }}" class="aj-agent-action-btn">Retour</a>
    </div>

    <section class="aj-agent-content-grid">
        <div class="aj-agent-panel aj-agent-panel-wide">
            <div class="aj-agent-panel-body">
                <div class="aj-agent-today-item"><span>Client</span><small>{{ $customRequest->customer_full_name }}</small></div>
                <div class="aj-agent-today-item"><span>Téléphone</span><small>{{ $customRequest->customer_phone }}</small></div>
                <div class="aj-agent-today-item"><span>Destination</span><small>{{ $customRequest->desired_destination }}</small></div>
                <div class="aj-agent-today-item"><span>Date souhaitée</span><small>{{ $customRequest->desired_departure_date ? $customRequest->desired_departure_date->format('d/m/Y') : 'Flexible' }}</small></div>
                <div class="aj-agent-today-item"><span>Voyageurs</span><small>{{ $customRequest->travelers_count }} total, {{ $customRequest->adults_count }} adultes, {{ $customRequest->children_count }} enfants, {{ $customRequest->babies_count }} bébés</small></div>
                <div class="aj-agent-today-item"><span>Statut</span><small>{{ $customRequest->statusLabel() }}</small></div>
                <div class="aj-agent-alert-box">{{ $customRequest->customer_notes ?: 'Aucune note client.' }}</div>

                @if($customRequest->latestQuote)
                    <div class="aj-agent-alert-box" style="margin-top:14px;">
                        <strong>Dernier devis:</strong>
                        {{ $customRequest->latestQuote->quote_number }} v{{ $customRequest->latestQuote->version }}
                        - {{ number_format((float) $customRequest->latestQuote->total_sale, 2, ',', ' ') }} {{ $customRequest->latestQuote->currency }}
                        @if($customRequest->latestQuote->pdf_path)
                            <div style="margin-top:10px;">
                                <a class="aj-agent-primary-btn" href="{{ route('admin.custom-requests.quote.download', [$customRequest, $customRequest->latestQuote]) }}">Télécharger le devis PDF</a>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </section>
</div>
@endsection
