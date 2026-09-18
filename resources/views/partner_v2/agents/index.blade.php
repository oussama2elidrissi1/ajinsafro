@extends('partner_v2.layouts.app')
@section('title', 'Mes agents')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/partner-agents.css') }}?v=1">
@endpush

@section('content')
@php
    $total = $agents->total();
    $activeCount = $agents->getCollection()->where('is_active', true)->count();
    $offCount = $agents->getCollection()->count() - $activeCount;

    /** Initiales de l'agent, deux lettres au plus. */
    $initials = static function (string $name): string {
        $parts = array_values(array_filter(preg_split('/\s+/u', trim($name))));
        $letters = array_map(static fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)), array_slice($parts, 0, 2));

        return implode('', $letters) ?: '?';
    };
@endphp

<div class="pa-agents">

    <div class="pa-head">
        <div style="min-width:0;">
            <div class="pa-crumbs">
                <a href="{{ route('partner.dashboard') }}">Portail partenaire</a>
                <span class="pa-sep">/</span>
                <span>Mes agents</span>
            </div>
            <h1 class="pa-title">Mes agents</h1>
            <p class="pa-lead">Comptes rattachés à {{ $partner->display_name }}. Chaque agent réserve sous le wallet de l'agence.</p>
        </div>
        <div class="pa-head-actions">
            <button type="button" class="pa-btn -accent" id="pa-focus-form">Ajouter un agent</button>
        </div>
    </div>

    @if(session('success'))
        <div class="pa-flash">{{ session('success') }}</div>
    @endif

    <div class="pa-layout">

        <section class="pa-panel">
            <div class="pa-panel-head">
                <div style="min-width:0;">
                    <h2>Agents rattachés</h2>
                    <p>{{ $activeCount }} actif{{ $activeCount > 1 ? 's' : '' }} sur {{ $agents->count() }} compte{{ $agents->count() > 1 ? 's' : '' }} affiché{{ $agents->count() > 1 ? 's' : '' }}</p>
                </div>
                <div class="pa-head-tools">
                    <div class="pa-search">
                        <span class="pa-search-dot" aria-hidden="true"></span>
                        <label for="pa-search" style="position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0);">Rechercher un agent</label>
                        <input id="pa-search" type="search" placeholder="Nom ou email…" autocomplete="off">
                    </div>
                    <div class="pa-tabs" role="group" aria-label="Filtrer par état du compte">
                        <button type="button" class="pa-tab" data-tab="all" aria-pressed="true">Tous<b class="pa-mono">{{ $agents->count() }}</b></button>
                        <button type="button" class="pa-tab" data-tab="on" aria-pressed="false">Actifs<b class="pa-mono">{{ $activeCount }}</b></button>
                        <button type="button" class="pa-tab" data-tab="off" aria-pressed="false">Désactivés<b class="pa-mono">{{ $offCount }}</b></button>
                    </div>
                </div>
            </div>

            <div class="pa-thead" aria-hidden="true">
                <span class="pa-col-agent">Agent</span>
                <span class="pa-col-seen">Dernière activité</span>
                <span class="pa-col-status">Statut</span>
                <span class="pa-col-actions">Actions</span>
            </div>

            <div id="pa-rows">
                @forelse($agents as $agent)
                    @php
                        $isActive = (bool) $agent->is_active;
                        $bookings = (int) ($agent->reservations_count ?? 0);
                    @endphp
                    <div class="pa-row"
                         data-state="{{ $isActive ? 'on' : 'off' }}"
                         data-search="{{ Str::lower(trim($agent->name.' '.$agent->email)) }}">
                        <div class="pa-col-agent">
                            <div class="pa-identity">
                                <span class="pa-avatar" aria-hidden="true">{{ $initials((string) $agent->name) }}</span>
                                <div style="min-width:0;">
                                    <div class="pa-name">{{ $agent->name }}</div>
                                    <div class="pa-email">{{ $agent->email }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="pa-col-seen">
                            <div class="pa-seen pa-mono">{{ $agent->last_login_at ? $agent->last_login_at->format('d/m/Y') : 'Jamais connecté' }}</div>
                            <div class="pa-bookings">{{ $bookings }} réservation{{ $bookings > 1 ? 's' : '' }}</div>
                        </div>
                        <div class="pa-col-status">
                            <span class="pa-status {{ $isActive ? '-on' : '-off' }}"><b aria-hidden="true"></b>{{ $isActive ? 'Actif' : 'Désactivé' }}</span>
                        </div>
                        <div class="pa-col-actions">
                            <a href="{{ route('partner.agents.edit', $agent) }}" class="pa-rowbtn">Modifier</a>
                            @if($isActive)
                                <form action="{{ route('partner.agents.disable', $agent) }}" method="POST" style="display:contents;">
                                    @csrf
                                    <button type="submit" class="pa-rowbtn -danger">Désactiver</button>
                                </form>
                            @else
                                <form action="{{ route('partner.agents.enable', $agent) }}" method="POST" style="display:contents;">
                                    @csrf
                                    <button type="submit" class="pa-rowbtn -ok">Réactiver</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="pa-empty">
                        <strong>Aucun agent rattaché</strong>
                        <p>Créez un premier compte depuis le formulaire pour qu'il puisse réserver au nom de l'agence.</p>
                    </div>
                @endforelse
            </div>

            <div class="pa-empty" id="pa-empty-filtered" hidden>
                <strong>Aucun agent ne correspond</strong>
                <p>Modifiez la recherche ou créez un nouvel agent depuis le formulaire.</p>
            </div>

            <div class="pa-panel-foot">
                <span><b id="pa-shown">{{ $agents->count() }}</b> agent{{ $agents->count() > 1 ? 's' : '' }} affiché{{ $agents->count() > 1 ? 's' : '' }} sur {{ $total }}</span>
                <span style="margin-left:auto;">{{ $agents->links('pagination::bootstrap-5') }}</span>
            </div>
        </section>

        <section class="pa-panel">
            <form class="pa-form" method="POST" action="{{ route('partner.agents.store') }}" id="pa-create-form">
                @csrf
                <div>
                    <h2>Ajouter un agent</h2>
                    <p class="pa-form-intro">L'agent recevra ses identifiants par email et pourra réserver au nom de l'agence.</p>
                </div>

                <div class="pa-fields">
                    <label class="pa-field">
                        <span>Nom complet</span>
                        <input type="text" name="name" value="{{ old('name') }}" placeholder="ex. Yassine Bennani" required>
                        @error('name')<span class="pa-error">{{ $message }}</span>@enderror
                    </label>
                    <label class="pa-field">
                        <span>Email professionnel</span>
                        <input type="email" name="email" value="{{ old('email') }}" placeholder="prenom.nom@agence.ma" required>
                        @error('email')<span class="pa-error">{{ $message }}</span>@enderror
                    </label>
                    <label class="pa-field">
                        <span>Téléphone</span>
                        <input type="tel" name="phone" value="{{ old('phone') }}" class="pa-mono" placeholder="+212 6 00 00 00 00">
                        @error('phone')<span class="pa-error">{{ $message }}</span>@enderror
                    </label>
                    <div class="pa-field-pair">
                        <label class="pa-field">
                            <span>Mot de passe</span>
                            <input type="password" name="password" placeholder="8 caractères min." required>
                            @error('password')<span class="pa-error">{{ $message }}</span>@enderror
                        </label>
                        <label class="pa-field">
                            <span>Confirmation</span>
                            <input type="password" name="password_confirmation" placeholder="Répétez le mot de passe" required>
                        </label>
                    </div>
                </div>

                <button type="submit" class="pa-btn -brand">Créer l'agent</button>
            </form>
        </section>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var rowsBox = document.getElementById('pa-rows');
    if (!rowsBox) return;

    var rows = Array.prototype.slice.call(rowsBox.querySelectorAll('.pa-row'));
    var searchEl = document.getElementById('pa-search');
    var tabs = Array.prototype.slice.call(document.querySelectorAll('.pa-tab'));
    var shownEl = document.getElementById('pa-shown');
    var emptyBox = document.getElementById('pa-empty-filtered');
    var focusBtn = document.getElementById('pa-focus-form');
    var form = document.getElementById('pa-create-form');
    var tab = 'all';

    function apply() {
        var q = searchEl ? String(searchEl.value || '').toLowerCase().trim() : '';
        var visible = 0;

        rows.forEach(function (row) {
            var show = true;
            if (tab !== 'all') {
                show = show && row.getAttribute('data-state') === tab;
            }
            if (q) {
                show = show && String(row.getAttribute('data-search') || '').indexOf(q) !== -1;
            }
            row.hidden = !show;
            if (show) visible++;
        });

        if (shownEl) shownEl.textContent = String(visible);
        if (emptyBox) emptyBox.hidden = visible !== 0 || rows.length === 0;
    }

    if (searchEl) searchEl.addEventListener('input', apply);

    tabs.forEach(function (btn) {
        btn.addEventListener('click', function () {
            tab = btn.getAttribute('data-tab') || 'all';
            tabs.forEach(function (b) { b.setAttribute('aria-pressed', b === btn ? 'true' : 'false'); });
            apply();
        });
    });

    // « Ajouter un agent » amene au formulaire et place le curseur sur le nom.
    if (focusBtn && form) {
        focusBtn.addEventListener('click', function () {
            form.scrollIntoView({ behavior: 'smooth', block: 'start' });
            var first = form.querySelector('input[name="name"]');
            if (first) first.focus({ preventScroll: true });
        });
    }

    apply();
})();
</script>
@endpush
