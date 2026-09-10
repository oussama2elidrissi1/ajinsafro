@extends('layouts.finance-control')

@section('title', 'Fiche financière projet')

@php
    $money = fn ($value) => number_format((float) $value, 2, ',', ' ').' DH';
    $pct = fn ($value) => number_format((float) $value, 1, ',', ' ').' %';

    $tabs = [
        'overview' => ['label' => "Vue d'ensemble", 'count' => null],
        'collections' => ['label' => 'Encaissements clients', 'count' => $reservations->count()],
        'charges' => ['label' => 'Charges', 'count' => $charges->count()],
        'suppliers' => ['label' => 'Fournisseurs', 'count' => $suppliers->count()],
        'documents' => ['label' => 'Justificatifs', 'count' => $documents->count()],
        'result' => ['label' => 'Résultat', 'count' => null],
    ];
    $activeTab = array_key_exists((string) request('tab'), $tabs) ? (string) request('tab') : 'overview';

    $states = [
        'profitable' => ['label' => 'Rentable', 'class' => 'is-ok'],
        'deficit' => ['label' => 'Déficitaire', 'class' => 'is-bad'],
        'neutral' => ['label' => "À l'équilibre", 'class' => 'is-warn'],
        'empty' => ['label' => 'À compléter', 'class' => 'is-warn'],
    ];
    $state = $states[$summary['state']] ?? $states['empty'];

    $sold = (float) $summary['sold_amount'];
    $marginClass = match ($summary['state']) {
        'profitable' => 'fc-value-green',
        'deficit' => 'fc-value-red',
        default => 'fc-value-muted',
    };

    // Cascade de lecture : chaque etape est mesuree par rapport au CA vendu.
    $bar = fn ($value) => $sold > 0 ? max(0, min(100, round(abs((float) $value) / $sold * 100, 1))).'%' : '0%';
    $waterfall = [
        ['label' => 'CA vendu', 'value' => $summary['sold_amount'], 'width' => $sold > 0 ? '100%' : '0%', 'color' => '', 'text' => ''],
        ['label' => '− Reste à encaisser', 'value' => $summary['client_remaining'], 'width' => $bar($summary['client_remaining']), 'color' => 'is-light', 'text' => 'fc-value-orange'],
        ['label' => '= Encaissé', 'value' => $summary['collected_amount'], 'width' => $bar($summary['collected_amount']), 'color' => 'is-green', 'text' => 'fc-value-green'],
        ['label' => '− Charges réelles', 'value' => $summary['real_charges'], 'width' => $bar($summary['real_charges']), 'color' => 'is-accent', 'text' => 'fc-value-orange'],
        ['label' => '= Marge réelle', 'value' => $summary['real_margin'], 'width' => $summary['real_margin'] > 0 ? $bar($summary['real_margin']) : '0%', 'color' => 'is-dark', 'text' => $marginClass],
    ];

    $canManageExpenses = auth()->user()?->can('finance.expenses.manage');
    $tabHref = fn (string $key) => route('admin.finance.control.travel-projects.show', [$departure, 'tab' => $key]);
@endphp

@section('finance_content')

    <div class="fc-head">
        <div style="min-width:0;">
            <div class="fc-crumb">
                <span>Administration</span><i>/</i>
                <a href="{{ route('admin.finance.control.dashboard') }}">Finance &amp; contrôle</a><i>/</i>
                <a href="{{ route('admin.finance.control.travel-projects.index') }}">Projets de voyage</a><i>/</i>
                <b>Détail projet</b>
            </div>
            <h1 class="fc-head-title">Détail du projet</h1>
            <p class="fc-head-sub">Cycle client, cycle fournisseur et résultat pour un départ précis.</p>
        </div>
    </div>

    @include('admin.finance.control.partials._tabs', ['current' => 'detail', 'departure' => $departure])

    <section class="ea-card">
        <div class="fc-head">
            <div style="min-width:0;">
                <div class="fc-badges">
                    <span class="fc-ref">DEP-{{ $departure->id }}</span>
                    <span class="fc-state {{ $state['class'] }}">{{ $state['label'] }}</span>
                </div>
                <h2 class="fc-detail-title">{{ $departure->voyage?->name ?: 'Voyage non renseigné' }}</h2>
                <p class="fc-detail-sub">
                    Départ du {{ $departure->start_date?->format('d/m/Y') ?: '—' }}
                    @if ($departure->end_date) au {{ $departure->end_date->format('d/m/Y') }} @endif
                    · {{ $summary['reservations_count'] }} dossier{{ $summary['reservations_count'] > 1 ? 's' : '' }}
                    · {{ $summary['travelers_count'] }} voyageur{{ $summary['travelers_count'] > 1 ? 's' : '' }}
                </p>
            </div>
            <div class="fc-head-actions">
                <a href="{{ route('admin.finance.control.travel-projects.index') }}" class="fc-btn-ghost" style="border-radius:999px;">← Retour</a>
                @if ($canManageExpenses)
                    <a href="{{ route('admin.finance.control.travel-expenses.create', ['departure_id' => $departure->id]) }}" class="ea-btn-accent">+ Ajouter une charge</a>
                @endif
            </div>
        </div>

        @if ($summary['state'] === 'empty')
            <div class="fc-notice" style="margin-top:20px;">
                <span class="fc-notice-mark" aria-hidden="true">!</span>
                <div class="fc-notice-body">
                    <div class="fc-notice-title">Projet vide : 3 étapes pour le rendre exploitable</div>
                    <div class="fc-steps">
                        <span class="fc-step"><b>1.</b>Saisir les charges prévues fournisseurs (hôtel, vol, transferts)</span>
                        <span class="fc-step"><b>2.</b>Rattacher les réservations du départ pour alimenter le CA</span>
                        <span class="fc-step"><b>3.</b>Joindre les justificatifs à chaque charge payée</span>
                    </div>
                </div>
            </div>
        @endif
    </section>

    <section class="ea-card">
        <h2 class="ea-card-title">Du chiffre d'affaires au résultat</h2>
        <p class="ea-card-sub">Lecture en cascade : ce qui est vendu, ce qui reste à encaisser, ce qui part en charges.</p>
        <div class="fc-fall">
            @foreach ($waterfall as $step)
                <div class="fc-fall-row">
                    <span class="fc-fall-label">{{ $step['label'] }}</span>
                    <div class="fc-fall-track"><span class="{{ $step['color'] }}" style="width:{{ $step['width'] }};"></span></div>
                    <span class="fc-fall-value {{ $step['text'] }}">{{ $money($step['value']) }}</span>
                </div>
            @endforeach
        </div>
        <div class="fc-hint" style="margin-top:18px;">
            L'encaissé n'est pas un bénéfice : il contient les sommes destinées aux fournisseurs.
            La rentabilité se lit sur la marge réelle.
        </div>
    </section>

    <section class="fc-panel">
        <nav class="fc-subtabs" aria-label="Sections de la fiche projet">
            @foreach ($tabs as $key => $tab)
                <a href="{{ $tabHref($key) }}" class="fc-subtab {{ $activeTab === $key ? 'is-active' : '' }}">
                    {{ $tab['label'] }}@if ($tab['count'] !== null)<b>{{ $tab['count'] }}</b>@endif
                </a>
            @endforeach
        </nav>

        <div class="fc-panel-body">

            {{-- 1. Vue d'ensemble --}}
            @if ($activeTab === 'overview')
                <div class="fc-duo">
                    <div class="fc-mini" style="padding:16px 18px; border-radius:14px;">
                        <div class="fc-mini-title">Cycle client</div>
                        <div class="fc-lines">
                            <div class="fc-line"><span>CA vendu</span><b>{{ $money($summary['sold_amount']) }}</b></div>
                            <div class="fc-line"><span>Encaissé</span><b>{{ $money($summary['collected_amount']) }}</b></div>
                            <div class="fc-line"><span>Reste à encaisser</span><b class="{{ $summary['client_remaining'] > 0 ? 'is-orange' : '' }}">{{ $money($summary['client_remaining']) }}</b></div>
                            <div class="fc-line"><span>Dossiers / voyageurs</span><b>{{ $summary['reservations_count'] }} / {{ $summary['travelers_count'] }}</b></div>
                        </div>
                    </div>
                    <div class="fc-mini" style="padding:16px 18px; border-radius:14px;">
                        <div class="fc-mini-title is-orange">Cycle fournisseur</div>
                        <div class="fc-lines">
                            <div class="fc-line"><span>Charges prévues</span><b>{{ $money($summary['planned_charges']) }}</b></div>
                            <div class="fc-line"><span>Charges réelles</span><b>{{ $money($summary['real_charges']) }}</b></div>
                            <div class="fc-line"><span>Charges payées</span><b>{{ $money($summary['paid_charges']) }}</b></div>
                            <div class="fc-line"><span>Reste dû fournisseurs</span><b class="{{ $summary['supplier_remaining'] > 0 ? 'is-orange' : '' }}">{{ $money($summary['supplier_remaining']) }}</b></div>
                        </div>
                    </div>
                </div>

                @if ($charges->isEmpty())
                    <div class="fc-empty-box" style="margin-top:16px;">
                        <span class="fc-empty-mark" aria-hidden="true">＋</span>
                        <div class="fc-empty-title">Aucune charge enregistrée</div>
                        <p class="fc-empty-text">
                            Ajoutez les charges fournisseurs de ce départ pour calculer la marge réelle et le taux de marge.
                        </p>
                        @if ($canManageExpenses)
                            <a href="{{ route('admin.finance.control.travel-expenses.create', ['departure_id' => $departure->id]) }}" class="ea-btn-accent" style="margin-top:2px;">Ajouter une charge</a>
                        @endif
                    </div>
                @else
                    <div class="fc-hint" style="margin-top:16px;">
                        Le montant encaissé n'est pas un bénéfice : il inclut des sommes destinées à payer les fournisseurs.
                        La rentabilité du projet se lit sur la marge réelle, onglet Résultat.
                    </div>
                @endif
            @endif

            {{-- 2. Encaissements clients (lecture seule des paiements existants) --}}
            @if ($activeTab === 'collections')
                <div class="fc-table-wrap">
                    <table class="fc-table">
                        <thead>
                            <tr>
                                <th>Réservation</th>
                                <th>Client</th>
                                <th class="is-num">Total dossier</th>
                                <th class="is-num">Encaissé</th>
                                <th class="is-num">Reste</th>
                                <th>Agence</th>
                                <th>Détail des paiements</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($reservations as $reservation)
                                @php
                                    $paid = round((float) $reservation->payments->sum('amount'), 2);
                                    $total = (float) ($reservation->total_amount ?? 0);
                                @endphp
                                <tr>
                                    <td class="is-nowrap">{{ $reservation->dossier_number ?: 'RES-'.$reservation->id }}</td>
                                    <td>{{ trim($reservation->client_first_name.' '.$reservation->client_last_name) ?: '—' }}</td>
                                    <td class="is-num">{{ $money($total) }}</td>
                                    <td class="is-num">{{ $money($paid) }}</td>
                                    <td class="is-num {{ $total - $paid > 0 ? 'fc-value-orange' : '' }}">{{ $money($total - $paid) }}</td>
                                    <td>{{ $reservation->branch?->name ?: '—' }}</td>
                                    <td>
                                        @forelse ($reservation->payments as $payment)
                                            <div class="fc-payment">
                                                <span class="ea-mono">{{ $payment->payment_date?->format('d/m/Y') ?: '—' }}</span>
                                                <b class="ea-mono">{{ $money($payment->amount) }}</b>
                                                <span class="ea-tag is-blue">{{ $payment->payment_method }}</span>
                                                <span style="color:var(--ea-muted); font-size:11.5px;">par {{ $payment->creator?->name ?: 'n/c' }}</span>
                                                @if ($payment->proof_file || $payment->financialDocuments->isNotEmpty())
                                                    <span class="ea-tag is-green">Justificatif</span>
                                                @else
                                                    <span class="ea-tag is-orange">Sans justificatif</span>
                                                @endif
                                            </div>
                                        @empty
                                            <span style="color:var(--ea-muted);">Aucun paiement enregistré</span>
                                        @endforelse
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="fc-table-empty">Aucune réservation valide sur ce départ.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <p class="fc-hint" style="margin-top:16px;">
                    Ces paiements proviennent du workspace réservations. Ils sont affichés ici en lecture seule, sans ressaisie.
                </p>
            @endif

            {{-- 3. Charges du voyage --}}
            @if ($activeTab === 'charges')
                <div class="fc-table-wrap">
                    <table class="fc-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Catégorie</th>
                                <th>Libellé</th>
                                <th>Fournisseur</th>
                                <th class="is-num">Prévu</th>
                                <th class="is-num">Réel</th>
                                <th class="is-num">Payé</th>
                                <th class="is-num">Reste</th>
                                <th>Échéance</th>
                                <th>Statut</th>
                                <th>Justif.</th>
                                <th class="is-num">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($charges as $charge)
                                <tr class="{{ $charge->status === \App\Models\DepartureCharge::STATUS_CANCELLED ? 'is-cancelled' : '' }}">
                                    <td class="is-nowrap">{{ $charge->charge_date?->format('d/m/Y') ?: '—' }}</td>
                                    <td>{{ $charge->type?->name ?: '—' }}</td>
                                    <td>{{ $charge->title }}</td>
                                    <td>{{ $charge->supplier?->name ?: ($charge->supplier_name ?: '—') }}</td>
                                    <td class="is-num">{{ $money($charge->effective_planned_amount) }}</td>
                                    <td class="is-num">{{ $money($charge->amount) }}</td>
                                    <td class="is-num">{{ $money($charge->paid_amount) }}</td>
                                    <td class="is-num {{ $charge->remaining_amount > 0 ? 'fc-value-orange' : '' }}">{{ $money($charge->remaining_amount) }}</td>
                                    <td class="is-nowrap">{{ $charge->due_date?->format('d/m/Y') ?: '—' }}</td>
                                    <td><span class="ea-tag is-blue">{{ $charge->status_label }}</span></td>
                                    <td>
                                        @if ($charge->attachment || $charge->documents->isNotEmpty())
                                            <span class="ea-tag is-green">Oui</span>
                                        @else
                                            <span class="ea-tag is-orange">Non</span>
                                        @endif
                                    </td>
                                    <td>
                                        @can('finance.expenses.manage')
                                            <div class="fc-actions">
                                                <a href="{{ route('admin.finance.control.travel-expenses.edit', $charge) }}" class="fc-mini-link">Modifier</a>
                                                @if ($charge->status !== \App\Models\DepartureCharge::STATUS_CANCELLED)
                                                    <form method="POST" action="{{ route('admin.finance.control.travel-expenses.cancel', $charge) }}"
                                                          onsubmit="return confirm('Annuler cette charge ? Elle sera conservée dans l\'historique.');">
                                                        @csrf
                                                        <button type="submit" class="fc-mini-link is-danger">Annuler</button>
                                                    </form>
                                                @endif
                                            </div>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="12" class="fc-table-empty">Aucune charge saisie sur ce projet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif

            {{-- 4. Fournisseurs --}}
            @if ($activeTab === 'suppliers')
                <div class="fc-table-wrap">
                    <table class="fc-table">
                        <thead>
                            <tr>
                                <th>Fournisseur</th>
                                <th class="is-num">Nb charges</th>
                                <th class="is-num">Total facturé</th>
                                <th class="is-num">Total payé</th>
                                <th class="is-num">Reste dû</th>
                                <th class="is-num">Justificatifs</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($suppliers as $line)
                                <tr>
                                    <td>{{ $line['supplier'] }}</td>
                                    <td class="is-num">{{ $line['charges_count'] }}</td>
                                    <td class="is-num">{{ $money($line['billed']) }}</td>
                                    <td class="is-num">{{ $money($line['paid']) }}</td>
                                    <td class="is-num {{ $line['remaining'] > 0 ? 'fc-value-orange' : '' }}">{{ $money($line['remaining']) }}</td>
                                    <td class="is-num">{{ $line['documents_count'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="fc-table-empty">Aucun fournisseur rattaché à ce projet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif

            {{-- 5. Justificatifs --}}
            @if ($activeTab === 'documents')
                <div class="fc-table-wrap">
                    <table class="fc-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Référence</th>
                                <th class="is-num">Montant</th>
                                <th>Fournisseur / client</th>
                                <th>Agence</th>
                                <th>Statut</th>
                                <th class="is-num">Fichier</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($documents as $document)
                                <tr>
                                    <td class="is-nowrap">{{ $document->document_date?->format('d/m/Y') ?: '—' }}</td>
                                    <td>{{ $document->type_label }}</td>
                                    <td>{{ $document->reference ?: '—' }}</td>
                                    <td class="is-num">{{ $document->amount !== null ? $money($document->amount) : '—' }}</td>
                                    <td>{{ $document->supplier?->name ?: ($document->client_name ?: '—') }}</td>
                                    <td>{{ $document->branch?->name ?: '—' }}</td>
                                    <td><span class="ea-tag is-blue">{{ $document->status_label }}</span></td>
                                    <td>
                                        @if ($document->file_path)
                                            <div class="fc-actions">
                                                <a href="{{ route('admin.finance.control.documents.download', $document) }}" class="fc-mini-link">Télécharger</a>
                                            </div>
                                        @else
                                            <div class="fc-actions" style="color:var(--ea-muted);">—</div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="fc-table-empty">Aucun justificatif rattaché à ce projet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div style="margin-top:16px;">
                    <a href="{{ route('admin.finance.control.documents.index', ['departure_id' => $departure->id]) }}" class="ea-btn-outline">Ouvrir le centre de justificatifs</a>
                </div>
            @endif

            {{-- 6. Resultat --}}
            @if ($activeTab === 'result')
                <div class="fc-duo">
                    <div class="fc-mini" style="padding:16px 18px; border-radius:14px;">
                        <div class="fc-mini-title is-green">Résultat du projet</div>
                        <div class="fc-lines">
                            <div class="fc-line"><span>CA vendu (réservations valides)</span><b>{{ $money($summary['sold_amount']) }}</b></div>
                            <div class="fc-line"><span>− Charges réelles du projet</span><b>{{ $money($summary['real_charges']) }}</b></div>
                            <div class="fc-line"><span>Marge du projet</span><b class="{{ $marginClass }}">{{ $money($summary['real_margin']) }}</b></div>
                            <div class="fc-line"><span>Taux de marge</span><b>{{ $sold > 0 ? $pct($summary['margin_rate']) : '—' }}</b></div>
                        </div>
                    </div>
                    <div class="fc-hint">
                        <b style="display:block; color:var(--ea-text); margin-bottom:6px;">Lecture de la marge</b>
                        La marge compare la vente aux charges réelles du projet. Elle ne dépend ni du rythme d'encaissement
                        client ni du rythme de paiement fournisseur : ces deux flux sont suivis séparément dans la trésorerie.
                        Les charges de structure de l'agence ne sont pas imputées ici.
                    </div>
                </div>
            @endif

        </div>
    </section>

@endsection
