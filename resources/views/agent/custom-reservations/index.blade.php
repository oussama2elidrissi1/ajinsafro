@extends('layouts.master-ajinsafro')

@section('title', 'Reservations a la carte')

@push('styles')
    <link href="{{ URL::asset('css/agent-dashboard.css') }}" rel="stylesheet" type="text/css" />
    <style>
        .aj-agent-custom-page { padding: 0 20px 32px; }
        .aj-agent-page-hero {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 20px;
            padding: 26px 28px;
            margin-bottom: 18px;
            background: linear-gradient(135deg, #0e3a5a 0%, #135882 58%, #1773a7 100%);
            border: 1px solid rgba(14, 58, 90, .18);
            border-radius: 22px;
            box-shadow: 0 14px 30px rgba(15, 23, 42, .10);
            color: #fff;
            overflow: hidden;
            position: relative;
        }
        .aj-agent-page-hero::after {
            content: "";
            position: absolute;
            right: -38px;
            top: -42px;
            width: 220px;
            height: 220px;
            border-radius: 50%;
            background: rgba(255, 255, 255, .07);
        }
        .aj-agent-page-hero::before {
            content: "";
            position: absolute;
            right: 56px;
            top: 22px;
            width: 138px;
            height: 138px;
            border-radius: 50%;
            border: 20px solid rgba(255, 255, 255, .07);
        }
        .aj-agent-page-hero > * { position: relative; z-index: 1; }
        .aj-agent-hero-copy { max-width: 760px; }
        .aj-agent-hero-kicker {
            margin: 0 0 8px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, .74);
        }
        .aj-agent-hero-copy h1 {
            margin: 0;
            font-size: 34px;
            line-height: 1.05;
            font-weight: 800;
            color: #fff;
        }
        .aj-agent-hero-copy p {
            margin: 10px 0 0;
            max-width: 760px;
            color: rgba(255, 255, 255, .84);
            font-size: 14px;
            line-height: 1.6;
        }
        .aj-agent-hero-actions {
            display: flex;
            align-items: flex-start;
            justify-content: flex-end;
            min-width: 260px;
        }
        .aj-agent-hero-actions .aj-agent-primary-btn {
            min-height: 46px;
            padding-inline: 18px;
            box-shadow: 0 14px 24px rgba(15, 23, 42, .18);
        }
        .aj-agent-panel {
            background: #fff;
            border: 1px solid #dbe6f2;
            border-radius: 22px;
            box-shadow: 0 10px 28px rgba(15, 23, 42, .06);
        }
        .aj-agent-filter-panel {
            margin-bottom: 18px;
            padding: 18px 18px 16px;
        }
        .aj-agent-filter-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 16px;
        }
        .aj-agent-filter-title strong {
            display: block;
            color: #123d60;
            font-size: 24px;
            font-weight: 800;
            line-height: 1.1;
        }
        .aj-agent-filter-title span {
            display: block;
            margin-top: 6px;
            color: #6b7d93;
            font-size: 13px;
        }
        .aj-agent-filter-grid {
            display: grid;
            grid-template-columns: minmax(190px, 1.25fr) minmax(180px, 1.15fr) minmax(160px, .85fr) minmax(160px, .85fr) auto auto;
            gap: 12px;
            align-items: end;
        }
        .aj-agent-field label {
            display: block;
            margin-bottom: 7px;
            color: #64748b;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
        }
        .aj-agent-field input,
        .aj-agent-field select {
            width: 100%;
            min-height: 46px;
            padding: 11px 14px;
            border: 1px solid #d6e2ef;
            border-radius: 13px;
            background: #fbfdff;
            color: #0f172a;
            font-size: 13px;
            transition: border-color .2s ease, box-shadow .2s ease, background .2s ease;
        }
        .aj-agent-field input:focus,
        .aj-agent-field select:focus {
            outline: none;
            border-color: #1d8ccf;
            box-shadow: 0 0 0 4px rgba(29, 140, 207, .12);
            background: #fff;
        }
        .aj-agent-filter-grid .aj-agent-primary-btn,
        .aj-agent-filter-grid .aj-agent-action-btn {
            min-height: 46px;
            justify-content: center;
            padding-inline: 16px;
        }
        .aj-agent-request-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }
        .aj-agent-request-card {
            padding: 18px;
            border-radius: 22px;
        }
        .aj-agent-request-card-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 16px;
        }
        .aj-agent-request-card h2 {
            margin: 0;
            color: #123d60;
            font-size: 28px;
            font-weight: 800;
            line-height: 1.05;
        }
        .aj-agent-request-ref {
            display: block;
            margin-top: 6px;
            color: #7c8ea4;
            font-size: 12px;
            font-weight: 600;
        }
        .aj-agent-status-stack {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: flex-end;
        }
        .aj-agent-pill {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 7px 11px;
            font-size: 12px;
            font-weight: 700;
            line-height: 1;
            white-space: nowrap;
        }
        .aj-agent-pill-blue { background: #e7f2fe; color: #0b75bd; }
        .aj-agent-pill-slate { background: #eef3f8; color: #51657c; }
        .aj-agent-pill-green { background: #ddf7e8; color: #0f8a4b; }
        .aj-agent-request-meta {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 16px;
        }
        .aj-agent-request-stat {
            min-height: 82px;
            padding: 14px;
            background: linear-gradient(180deg, #fcfeff 0%, #f6faff 100%);
            border: 1px solid #d7e3f0;
            border-radius: 16px;
        }
        .aj-agent-request-stat span {
            display: block;
            color: #71849c;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
        }
        .aj-agent-request-stat strong {
            display: block;
            margin-top: 8px;
            color: #172334;
            font-size: 16px;
            font-weight: 700;
            line-height: 1.35;
        }
        .aj-agent-request-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding-top: 4px;
        }
        .aj-agent-request-actions .aj-agent-action-btn {
            min-width: 118px;
            justify-content: center;
        }
        .aj-agent-empty {
            padding: 40px 24px;
            text-align: center;
            border: 1px dashed #cdd9e6;
        }
        .aj-agent-empty h2 {
            margin: 0;
            color: #123d60;
            font-size: 24px;
            font-weight: 800;
        }
        .aj-agent-empty p {
            margin: 8px auto 0;
            max-width: 520px;
            color: #6b7d93;
        }
        .aj-agent-empty .aj-agent-primary-btn {
            margin-top: 16px;
        }
        .aj-agent-pagination { margin-top: 18px; }
        @media (max-width: 1180px) {
            .aj-agent-filter-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
        @media (max-width: 1080px) {
            .aj-agent-request-grid { grid-template-columns: 1fr; }
            .aj-agent-page-hero { flex-direction: column; }
            .aj-agent-hero-actions {
                width: 100%;
                justify-content: flex-start;
            }
        }
        @media (max-width: 640px) {
            .aj-agent-custom-page { padding: 0 12px 24px; }
            .aj-agent-page-hero,
            .aj-agent-filter-panel,
            .aj-agent-request-card { border-radius: 18px; }
            .aj-agent-filter-head,
            .aj-agent-request-card-head,
            .aj-agent-request-actions {
                flex-direction: column;
                align-items: stretch;
            }
            .aj-agent-filter-grid,
            .aj-agent-request-meta {
                grid-template-columns: 1fr;
            }
            .aj-agent-hero-copy h1 { font-size: 28px; }
            .aj-agent-filter-title strong { font-size: 22px; }
            .aj-agent-filter-head .aj-agent-primary-btn,
            .aj-agent-hero-actions .aj-agent-primary-btn,
            .aj-agent-request-actions .aj-agent-action-btn {
                width: 100%;
                justify-content: center;
            }
            .aj-agent-status-stack { justify-content: flex-start; }
        }
    </style>
@endpush

@section('content')
<div class="aj-agent-custom-page">
    <section class="aj-agent-page-hero">
        <div class="aj-agent-hero-copy">
            <p class="aj-agent-hero-kicker">Agent / demandes personnalisees</p>
            <h1>Reservations a la carte</h1>
            <p>Consultez les dossiers transmis, filtrez rapidement les demandes en cours et ouvrez une nouvelle reservation a la carte depuis le meme espace.</p>
        </div>
        @if($canCreateRequest ?? false)
            <div class="aj-agent-hero-actions">
                <a href="{{ route('agent.custom-reservations.create') }}" class="aj-agent-primary-btn">
                    <i class="bx bx-plus-circle"></i>
                    <span>Creer une reservation</span>
                </a>
            </div>
        @endif
    </section>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="GET" action="{{ route('agent.custom-reservations.index') }}" class="aj-agent-panel aj-agent-filter-panel">
        <div class="aj-agent-filter-head">
            <div class="aj-agent-filter-title">
                <strong>Liste des demandes</strong>
                <span>Affinez la recherche par client, destination, statut ou date de depart souhaitee.</span>
            </div>
        </div>
        <div class="aj-agent-filter-grid">
            <div class="aj-agent-field">
                <label for="client">Client</label>
                <input id="client" type="text" name="client" value="{{ $filters['client'] ?? '' }}" placeholder="Nom, telephone, reference...">
            </div>
            <div class="aj-agent-field">
                <label for="destination">Destination</label>
                <input id="destination" type="text" name="destination" value="{{ $filters['destination'] ?? '' }}" placeholder="Destination souhaitee">
            </div>
            <div class="aj-agent-field">
                <label for="status">Statut</label>
                <select id="status" name="status">
                    <option value="">Tous</option>
                    @foreach($statusOptions as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="aj-agent-field">
                <label for="date">Date</label>
                <input id="date" type="date" name="date" value="{{ $filters['date'] ?? '' }}">
            </div>
            <button type="submit" class="aj-agent-primary-btn">Filtrer</button>
            <a href="{{ route('agent.custom-reservations.index') }}" class="aj-agent-action-btn">Reinitialiser</a>
        </div>
    </form>

    @if($requests->count())
        <div class="aj-agent-request-grid">
            @foreach($requests as $requestRow)
                <article class="aj-agent-panel aj-agent-request-card">
                    <div class="aj-agent-request-card-head">
                        <div>
                            <h2>{{ $requestRow->customer_full_name }}</h2>
                            <span class="aj-agent-request-ref">{{ $requestRow->request_number }}</span>
                        </div>
                        <div class="aj-agent-status-stack">
                            <span class="aj-agent-pill aj-agent-pill-blue">{{ $requestRow->statusLabel() }}</span>
                            <span class="aj-agent-pill aj-agent-pill-slate">{{ $requestRow->priorityLabel() }}</span>
                            <span class="aj-agent-pill aj-agent-pill-green">{{ $requestRow->travelers_count }} voyageur(s)</span>
                        </div>
                    </div>

                    <div class="aj-agent-request-meta">
                        <div class="aj-agent-request-stat">
                            <span>Destination souhaitee</span>
                            <strong>{{ $requestRow->desired_destination }}</strong>
                        </div>
                        <div class="aj-agent-request-stat">
                            <span>Date souhaitee</span>
                            <strong>{{ $requestRow->desired_departure_date ? $requestRow->desired_departure_date->format('d/m/Y') : 'Flexible' }}</strong>
                        </div>
                        <div class="aj-agent-request-stat">
                            <span>Voyageurs</span>
                            <strong>{{ $requestRow->travelers_count }} personne(s)</strong>
                        </div>
                        <div class="aj-agent-request-stat">
                            <span>Agent offline</span>
                            <strong>{{ $requestRow->assignedAgent?->name ?: 'En attente' }}</strong>
                        </div>
                        <div class="aj-agent-request-stat">
                            <span>Dernier devis</span>
                            <strong>{{ $requestRow->latestQuote ? number_format((float) $requestRow->latestQuote->total_sale, 2, ',', ' ') . ' ' . $requestRow->latestQuote->currency : '-' }}</strong>
                        </div>
                        <div class="aj-agent-request-stat">
                            <span>Suivi</span>
                            <strong>{{ $requestRow->statusLabel() }}</strong>
                        </div>
                    </div>

                    <div class="aj-agent-request-actions">
                        <span class="aj-agent-pill aj-agent-pill-slate">Dossier en consultation</span>
                        <a href="{{ route('agent.custom-reservations.show', $requestRow) }}" class="aj-agent-action-btn">Voir le detail</a>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="aj-agent-pagination">{{ $requests->links() }}</div>
    @else
        <div class="aj-agent-panel aj-agent-empty">
            <h2>Aucune demande a la carte</h2>
            <p>Les demandes personnalisees creees par votre compte apparaitront ici des qu'un dossier sera enregistre.</p>
            @if($canCreateRequest ?? false)
                <a href="{{ route('agent.custom-reservations.create') }}" class="aj-agent-primary-btn">Creer une demande</a>
            @endif
        </div>
    @endif
</div>
@endsection
