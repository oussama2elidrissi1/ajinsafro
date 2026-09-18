@extends('partner_v2.layouts.app')
@section('title', 'Wallet')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/partner-wallet.css') }}?v=1">
@endpush

@section('content')
@php
    use App\Models\PartnerWalletTransaction;

    $stats = $walletStats ?? ['pending_amount' => 0.0, 'pending_count' => 0, 'recharged_amount' => 0.0];

    /** Montant sans unite : l'unite est rendue a part, en plus discret. */
    $amount = fn ($value) => number_format((float) $value, 2, ',', ' ');

    $presets = [2000, 5000, 10000, 20000];
    $methods = PartnerWalletTransaction::paymentMethods();
    $selectedMethod = old('payment_method', 'cash');

    $steps = [
        ['n' => '1', 'title' => 'Vous envoyez la demande', 'text' => 'Montant, mode de paiement et justificatif du versement.'],
        ['n' => '2', 'title' => 'Ajinsafro vérifie', 'text' => 'Contrôle du justificatif par la comptabilité.'],
        ['n' => '3', 'title' => 'Le solde est crédité', 'text' => 'Le wallet est utilisable immédiatement pour réserver.'],
    ];
@endphp

<div class="pw-wallet">

    <div class="pw-head">
        <div style="min-width:0;">
            <div class="pw-crumbs">
                <a href="{{ route('partner.dashboard') }}">Portail partenaire</a>
                <span class="pw-sep">/</span>
                <span>Wallet</span>
            </div>
            <h1 class="pw-title">Wallet de l'agence</h1>
            <p class="pw-lead">Solde, demandes de recharge et justificatifs. Le solde n'est crédité qu'après validation Ajinsafro.</p>
        </div>
        <div class="pw-head-actions">
            <a href="#pw-recharge" class="pw-btn -accent">Demander une recharge</a>
        </div>
    </div>

    @if(session('success'))
        <div class="pw-flash">{{ session('success') }}</div>
    @endif

    <section class="pw-kpis">
        <div class="pw-kpi -balance">
            <span class="pw-kpi__label">Solde disponible</span>
            <span class="pw-kpi__value">
                <strong class="pw-mono">{{ $amount($partner->wallet_balance ?? 0) }}</strong>
                <span class="pw-kpi__unit">DH</span>
            </span>
            <span class="pw-kpi__tag">Réservable immédiatement</span>
        </div>
        <div class="pw-kpi -pending">
            <span class="pw-kpi__label">En attente de validation</span>
            <span class="pw-kpi__value">
                <strong class="pw-mono">{{ $amount($stats['pending_amount']) }}</strong>
                <span class="pw-kpi__unit">DH</span>
            </span>
            <p class="pw-kpi__note">
                @if($stats['pending_count'] > 0)
                    {{ $stats['pending_count'] }} demande{{ $stats['pending_count'] > 1 ? 's' : '' }} en cours de vérification.
                @else
                    Aucune demande en cours.
                @endif
            </p>
        </div>
        <div class="pw-kpi">
            <span class="pw-kpi__label">Total rechargé</span>
            <span class="pw-kpi__value">
                <strong class="pw-mono">{{ $amount($stats['recharged_amount']) }}</strong>
                <span class="pw-kpi__unit">DH</span>
            </span>
            <p class="pw-kpi__note">Recharges validées depuis l'ouverture du compte.</p>
        </div>
    </section>

    <div class="pw-layout">

        <section class="pw-panel" id="pw-recharge">
            <form class="pw-form" method="POST" action="{{ route('partner.wallet.recharge-request') }}" enctype="multipart/form-data">
                @csrf
                <div>
                    <h2>Demander une recharge</h2>
                    <p class="pw-form-intro">Joignez le justificatif de versement&nbsp;: la validation est plus rapide.</p>
                </div>

                <div class="pw-field">
                    <span>Montant</span>
                    <span class="pw-amount">
                        <label for="pw-amount" style="position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0);">Montant de la recharge</label>
                        <input id="pw-amount" class="pw-mono" type="number" name="amount" min="1" step="0.01"
                               value="{{ old('amount') }}" placeholder="0" required>
                        <span>DH</span>
                    </span>
                    @error('amount')<span class="pw-error">{{ $message }}</span>@enderror
                    <div class="pw-presets">
                        @foreach($presets as $preset)
                            <button type="button" class="pw-preset" data-preset="{{ $preset }}">+ {{ number_format($preset, 0, ',', ' ') }}</button>
                        @endforeach
                    </div>
                </div>

                <fieldset class="pw-field" style="border:0;padding:0;margin:0;">
                    <legend class="pw-legend" style="padding:0;">Mode de paiement</legend>
                    <div class="pw-methods">
                        @foreach($methods as $value => $label)
                            <label class="pw-method">
                                <input type="radio" name="payment_method" value="{{ $value }}" @checked($selectedMethod === $value) required>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('payment_method')<span class="pw-error">{{ $message }}</span>@enderror
                </fieldset>

                <div class="pw-field">
                    <span>Justificatif</span>
                    <div class="pw-drop">
                        <span class="pw-drop-icon" aria-hidden="true">&uarr;</span>
                        <span class="pw-drop-text">
                            <b>Reçu, virement ou capture</b>
                            <span>PDF, JPG, PNG ou WebP &middot; 5 Mo maximum</span>
                        </span>
                    </div>
                    <input type="file" name="proof" accept=".jpg,.jpeg,.png,.webp,.pdf">
                    @error('proof')<span class="pw-error">{{ $message }}</span>@enderror
                </div>

                <label class="pw-field">
                    <span class="pw-legend-row"><span>Note</span><span class="pw-optional">facultatif</span></span>
                    <textarea name="note" rows="3" placeholder="Référence du virement, agence de dépôt…">{{ old('note') }}</textarea>
                    @error('note')<span class="pw-error">{{ $message }}</span>@enderror
                </label>

                <div class="pw-recap">
                    <span>Demande de</span>
                    <b class="pw-mono" id="pw-recap-amount">{{ old('amount') ? $amount(old('amount')) : '0,00' }} DH</b>
                </div>

                <button type="submit" class="pw-btn -submit">Envoyer la demande</button>
            </form>
        </section>

        <section class="pw-panel">
            <div class="pw-panel-head">
                <div style="min-width:0;">
                    <h2>Historique des opérations</h2>
                    <p>Recharges, validations et débits de réservation.</p>
                </div>
                <div class="pw-tabs" role="group" aria-label="Filtrer les opérations">
                    <button type="button" class="pw-tab" data-tab="all" aria-pressed="true">Tout</button>
                    <button type="button" class="pw-tab" data-tab="in" aria-pressed="false">Recharges</button>
                    <button type="button" class="pw-tab" data-tab="out" aria-pressed="false">Débits</button>
                </div>
            </div>

            <div class="pw-thead" aria-hidden="true">
                <span class="pw-c-date">Date</span>
                <span class="pw-c-type">Type</span>
                <span class="pw-c-amount">Montant</span>
                <span class="pw-c-method">Paiement</span>
                <span class="pw-c-status">Statut</span>
                <span class="pw-c-doc">Justificatif</span>
            </div>

            <div id="pw-rows">
                @forelse($transactions as $transaction)
                    <div class="pw-row" data-flow="{{ $transaction->is_credit ? 'in' : 'out' }}">
                        <span class="pw-c-date pw-mono">{{ $transaction->created_at?->format('d/m/Y H:i') ?? '—' }}</span>
                        <span class="pw-c-type">{{ $transaction->type_label }}</span>
                        <span class="pw-c-amount pw-mono {{ $transaction->is_credit ? '-credit' : '-debit' }}">
                            {{ $transaction->is_credit ? '+' : '−' }}{{ $amount($transaction->amount) }}
                        </span>
                        <span class="pw-c-method">{{ $transaction->payment_method_label ?? '—' }}</span>
                        <span class="pw-c-status">
                            <span class="pw-status -{{ $transaction->status_tone }}">{{ $transaction->status_label }}</span>
                        </span>
                        <span class="pw-c-doc">
                            @if($transaction->proof_path)
                                <a href="{{ asset('storage/'.$transaction->proof_path) }}" target="_blank" rel="noopener">Voir</a>
                            @else
                                —
                            @endif
                        </span>
                    </div>
                @empty
                    <div class="pw-emptyzone">
                        <div class="pw-empty">
                            <strong>Aucune opération pour le moment</strong>
                            <p>Votre première recharge apparaîtra ici dès l'envoi de la demande, avec son statut de validation.</p>
                        </div>
                        <div class="pw-steps">
                            @foreach($steps as $step)
                                <div class="pw-step">
                                    <span class="pw-step-num pw-mono">{{ $step['n'] }}</span>
                                    <b>{{ $step['title'] }}</b>
                                    <p>{{ $step['text'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforelse
            </div>

            <div class="pw-emptyzone" id="pw-empty-filtered" hidden>
                <div class="pw-empty">
                    <strong>Aucune opération dans ce filtre</strong>
                    <p>Revenez à « Tout » pour afficher l'ensemble des mouvements du wallet.</p>
                </div>
            </div>

            <div class="pw-panel-foot">
                <span><b id="pw-shown">{{ $transactions->count() }}</b> opération{{ $transactions->count() > 1 ? 's' : '' }} affichée{{ $transactions->count() > 1 ? 's' : '' }} sur {{ $transactions->total() }}</span>
                <span style="margin-left:auto;">{{ $transactions->links('pagination::bootstrap-5') }}</span>
            </div>
        </section>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var form = document.querySelector('.pw-wallet form');
    if (!form) return;

    var amountEl = document.getElementById('pw-amount');
    var recapEl = document.getElementById('pw-recap-amount');
    var presets = Array.prototype.slice.call(document.querySelectorAll('.pw-preset'));

    function fmt(n) {
        return n.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
            .replace(/ | /g, ' ');
    }

    function refreshRecap() {
        if (!amountEl || !recapEl) return;
        var n = Number(amountEl.value);
        recapEl.textContent = (isNaN(n) || n <= 0 ? '0,00' : fmt(n)) + ' DH';
    }

    if (amountEl) amountEl.addEventListener('input', refreshRecap);

    // Les montants rapides s'ajoutent au montant saisi, comme une recharge cumulee.
    presets.forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (!amountEl) return;
            var current = Number(amountEl.value) || 0;
            amountEl.value = current + Number(btn.getAttribute('data-preset') || 0);
            refreshRecap();
        });
    });

    var rowsBox = document.getElementById('pw-rows');
    var rows = rowsBox ? Array.prototype.slice.call(rowsBox.querySelectorAll('.pw-row')) : [];
    var tabs = Array.prototype.slice.call(document.querySelectorAll('.pw-tab'));
    var shownEl = document.getElementById('pw-shown');
    var emptyBox = document.getElementById('pw-empty-filtered');
    var tab = 'all';

    function applyTab() {
        var visible = 0;
        rows.forEach(function (row) {
            var show = tab === 'all' || row.getAttribute('data-flow') === tab;
            row.hidden = !show;
            if (show) visible++;
        });
        if (shownEl) shownEl.textContent = String(visible);
        if (emptyBox) emptyBox.hidden = visible !== 0 || rows.length === 0;
    }

    tabs.forEach(function (btn) {
        btn.addEventListener('click', function () {
            tab = btn.getAttribute('data-tab') || 'all';
            tabs.forEach(function (b) { b.setAttribute('aria-pressed', b === btn ? 'true' : 'false'); });
            applyTab();
        });
    });

    refreshRecap();
    applyTab();
})();
</script>
@endpush
