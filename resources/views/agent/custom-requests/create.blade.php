@extends('layouts.master-ajinsafro')

@section('title', 'Nouvelle demande Ã  la carte')

@push('styles')
    <link href="{{ URL::asset('css/agent-dashboard.css') }}" rel="stylesheet" type="text/css" />
    <style>
        .aj-dac-wizard {
            --dac-dark: #0c4a6e;
            --dac-blue: #0081bc;
            --dac-orange: #f97316;
            --dac-border: #e2e8f0;
            --dac-muted: #64748b;
            --dac-text: #0f172a;
            max-width: 1280px;
            margin: 0 auto;
            padding: 28px 32px 36px;
        }
        .aj-dac-hero {
            position: relative;
            overflow: hidden;
            background: var(--dac-dark);
            color: #fff;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 18px;
            box-shadow: 0 12px 28px rgba(12, 74, 110, .14);
        }
        .aj-dac-hero::after {
            content: "";
            position: absolute;
            top: -70px;
            right: -50px;
            width: 230px;
            height: 230px;
            border: 34px solid rgba(255, 255, 255, .08);
            border-radius: 999px;
        }
        .aj-dac-hero-main {
            position: relative;
            z-index: 1;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 18px;
            flex-wrap: wrap;
        }
        .aj-dac-kicker {
            display: flex;
            align-items: center;
            gap: 7px;
            color: #cbd5e1;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
            margin-bottom: 7px;
        }
        .aj-dac-hero h1 {
            margin: 0;
            font-size: 25px;
            font-weight: 800;
            letter-spacing: -.01em;
        }
        .aj-dac-hero p {
            color: #cbd5e1;
            margin: 7px 0 0;
            font-size: 13px;
            max-width: 620px;
        }
        .aj-dac-hero-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .aj-dac-status {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            border: 1px solid rgba(249, 115, 22, .35);
            background: rgba(249, 115, 22, .14);
            color: #fdba74;
            border-radius: 999px;
            padding: 6px 11px;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
        }
        .aj-dac-status::before {
            content: "";
            width: 7px;
            height: 7px;
            border-radius: 999px;
            background: var(--dac-orange);
        }
        .aj-dac-hero-link {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            border: 1px solid rgba(255,255,255,.16);
            background: rgba(255,255,255,.09);
            color: #fff;
            text-decoration: none;
            border-radius: 10px;
            padding: 9px 13px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
        }
        .aj-dac-summary-grid {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
            margin-top: 24px;
        }
        .aj-dac-summary-card {
            border: 1px solid rgba(255,255,255,.12);
            background: rgba(255,255,255,.06);
            border-radius: 12px;
            padding: 14px;
            min-width: 0;
        }
        .aj-dac-summary-card span {
            display: block;
            color: #94a3b8;
            font-size: 9px;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .aj-dac-summary-card strong {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-size: 14px;
            font-weight: 800;
        }
        .aj-dac-alert {
            border: 1px solid #fecaca;
            background: #fef2f2;
            color: #991b1b;
            border-radius: 12px;
            padding: 12px 14px;
            margin: 0 0 16px;
            font-weight: 700;
        }
        .aj-dac-stepper {
            display: flex;
            align-items: center;
            gap: 12px;
            overflow-x: auto;
            border: 1px solid var(--dac-border);
            background: #fff;
            border-radius: 16px;
            padding: 14px;
            margin-bottom: 18px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, .04), 0 6px 16px rgba(15, 23, 42, .05);
        }
        .aj-dac-stepper-label {
            flex: 0 0 auto;
            color: #94a3b8;
            font-size: 10px;
            font-weight: 900;
            letter-spacing: .08em;
            text-transform: uppercase;
        }
        .aj-dac-step-pill {
            flex: 0 0 auto;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid #eef2f7;
            background: #fff;
            color: #94a3b8;
            border-radius: 10px;
            padding: 9px 13px;
            font-size: 11px;
            font-weight: 900;
            transition: background .16s ease, color .16s ease, border-color .16s ease;
        }
        .aj-dac-step-pill span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 22px;
            height: 22px;
            border-radius: 999px;
            background: #f1f5f9;
            color: #64748b;
            font-size: 10px;
        }
        .aj-dac-step-pill.is-active {
            border-color: #fee2e2;
            background: #fef2f2;
            color: #b91c1c;
        }
        .aj-dac-step-pill.is-active span {
            background: #b91c1c;
            color: #fff;
        }
        .aj-dac-step-pill.is-done {
            border-color: #bbf7d0;
            background: #f0fdf4;
            color: #15803d;
        }
        .aj-dac-step-pill.is-done span {
            background: #16a34a;
            color: #fff;
        }
        .aj-dac-step-line {
            flex: 0 0 18px;
            height: 1px;
            background: #e2e8f0;
        }
        .aj-dac-card {
            background: #fff;
            border: 1px solid var(--dac-border);
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, .04), 0 8px 20px rgba(15, 23, 42, .06);
            overflow: hidden;
        }
        .aj-agent-dac-form {
            margin: 0;
        }
        .aj-dac-step-panel {
            display: none;
            padding: 28px;
            min-height: 520px;
            animation: ajDacFade .22s ease-out;
        }
        .aj-dac-step-panel.is-active {
            display: block;
        }
        @keyframes ajDacFade {
            from { opacity: 0; transform: translateY(5px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .aj-agent-dac-section {
            border: 0;
            border-radius: 0;
            box-shadow: none;
            overflow: visible;
            margin-bottom: 30px;
        }
        .aj-agent-dac-section:last-child {
            margin-bottom: 0;
        }
        .aj-agent-dac-section-head {
            display: block;
            border: 0;
            background: transparent;
            padding: 0 0 18px 14px;
            border-left: 4px solid var(--dac-blue);
        }
        .aj-agent-dac-section-head h2 {
            margin: 0;
            color: var(--dac-text);
            font-size: 13px;
            font-weight: 900;
            letter-spacing: .02em;
            text-transform: uppercase;
        }
        .aj-agent-dac-section-head span {
            display: block;
            color: var(--dac-muted);
            margin-top: 4px;
            font-size: 11px;
            font-weight: 700;
        }
        .aj-agent-dac-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
            padding: 0;
        }
        .aj-agent-dac-field {
            min-width: 0;
        }
        .aj-agent-dac-field-wide {
            grid-column: 1 / -1;
        }
        .aj-agent-dac-field label {
            display: block;
            color: #64748b;
            font-size: 10px;
            font-weight: 900;
            letter-spacing: .04em;
            text-transform: uppercase;
            margin-bottom: 7px;
        }
        .aj-agent-dac-field label span {
            color: #dc2626;
        }
        .aj-agent-dac-field input,
        .aj-agent-dac-field select,
        .aj-agent-dac-field textarea {
            width: 100%;
            min-height: 44px;
            border: 1px solid #dbe5ef;
            background: #f8fafc;
            color: #0f172a;
            border-radius: 10px;
            padding: 11px 12px;
            font-size: 13px;
            font-weight: 700;
            outline: none;
        }
        .aj-agent-dac-field textarea {
            min-height: 108px;
            display: block;
            box-sizing: border-box;
            width: 100%;
            line-height: 1.55;
            resize: vertical;
        }
        .aj-agent-dac-textarea-large {
            min-height: 220px !important;
            display: block !important;
            box-sizing: border-box !important;
            width: 100% !important;
            font-size: 15px !important;
            line-height: 1.7 !important;
            padding: 14px 16px !important;
            resize: vertical !important;
            overflow: auto !important;
        }
        .aj-agent-dac-field input:focus,
        .aj-agent-dac-field select:focus,
        .aj-agent-dac-field textarea:focus {
            border-color: var(--dac-blue);
            box-shadow: 0 0 0 3px rgba(0, 129, 188, .10);
            background: #fff;
        }
        .aj-agent-dac-field small {
            display: block;
            color: #dc2626;
            margin-top: 6px;
            font-weight: 700;
        }
        .aj-agent-dac-field .aj-agent-dac-hint {
            color: #64748b;
        }
        .aj-dac-step-panel[data-dac-step-panel="4"] .aj-agent-dac-section:not([hidden]) .aj-agent-dac-section-head span {
            display: none;
        }
        .aj-dac-step-panel[data-dac-step-panel="4"] .aj-agent-dac-section:not([hidden]) .aj-agent-dac-section-head h2::after {
            content: "Priorite, echeance et notes utiles au service quotation.";
            display: block;
            color: var(--dac-muted);
            margin-top: 4px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0;
            text-transform: none;
        }
        .aj-client-search-panel {
            margin-top: 10px;
            border: 1px solid #dbe5ef;
            border-radius: 12px;
            background: #fff;
            padding: 10px;
        }
        .aj-client-search-selected {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
            padding: 10px 12px;
            margin-bottom: 10px;
            border-radius: 10px;
            background: #fff7ed;
            color: #9a3412;
            font-size: 12px;
            font-weight: 800;
        }
        .aj-client-search-clear {
            border: 0;
            background: transparent;
            color: #ea580c;
            font: inherit;
            font-weight: 900;
            cursor: pointer;
        }
        .aj-client-search-results {
            display: grid;
            gap: 8px;
        }
        .aj-client-search-result {
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            color: #0f172a;
            border-radius: 10px;
            padding: 10px 12px;
            text-align: left;
            cursor: pointer;
            width: 100%;
        }
        .aj-client-search-result:hover,
        .aj-client-search-result.is-active {
            border-color: var(--dac-blue);
            background: #eff6ff;
        }
        .aj-client-search-result strong {
            display: block;
            font-size: 12px;
            font-weight: 900;
            margin-bottom: 4px;
        }
        .aj-client-search-result small {
            color: #64748b;
            margin: 0;
            font-weight: 700;
        }
        .aj-dac-client-type {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }
        .aj-dac-client-type label,
        .aj-agent-dac-services label {
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: 48px;
            border: 1px solid #e2e8f0;
            background: #fff;
            color: #475569;
            border-radius: 12px;
            padding: 11px 12px;
            font-size: 12px;
            font-weight: 900;
            cursor: pointer;
            transition: all .16s ease;
        }
        .aj-dac-client-type label:has(input:checked),
        .aj-agent-dac-services label:has(input:checked) {
            border-color: var(--dac-orange);
            background: #fff7ed;
            color: #9a3412;
            box-shadow: 0 8px 18px rgba(249, 115, 22, .09);
        }
        .aj-dac-client-type input,
        .aj-agent-dac-services input {
            width: 16px;
            height: 16px;
            flex: 0 0 auto;
            accent-color: var(--dac-orange);
        }
        .aj-agent-dac-services {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
            padding: 0;
        }
        .aj-dac-service-configs {
            display: grid;
            gap: 12px;
            margin-top: 18px;
        }
        .aj-dac-service-config {
            display: none;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            border-radius: 14px;
            padding: 16px;
            animation: ajDacFade .2s ease-out;
        }
        .aj-dac-service-config.is-visible {
            display: block;
        }
        .aj-dac-service-config-title {
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 11px;
            margin-bottom: 14px;
            color: #0f172a;
            font-size: 12px;
            font-weight: 900;
            text-transform: uppercase;
        }
        .aj-dac-service-config-title i {
            color: var(--dac-blue);
        }
        .aj-agent-dac-field-after {
            padding: 18px 0 0;
        }
        .aj-dac-actionbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
            border-top: 1px solid #e2e8f0;
            background: #f8fafc;
            padding: 18px;
        }
        .aj-dac-actionbar > div {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }
        .aj-dac-btn {
            border: 1px solid #dbe5ef;
            background: #fff;
            color: #475569;
            min-height: 42px;
            border-radius: 10px;
            padding: 10px 16px;
            font-size: 11px;
            font-weight: 900;
            letter-spacing: .06em;
            text-transform: uppercase;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
        }
        .aj-dac-btn:hover {
            border-color: var(--dac-blue);
            color: var(--dac-blue);
            background: #f0f9ff;
        }
        .aj-dac-btn-primary {
            border-color: var(--dac-orange);
            background: var(--dac-orange);
            color: #fff;
            box-shadow: 0 10px 20px rgba(249, 115, 22, .18);
        }
        .aj-dac-btn-primary:hover {
            border-color: #ea580c;
            background: #ea580c;
            color: #fff;
        }
        .aj-dac-btn-next {
            min-width: 160px;
        }
        .aj-dac-btn[hidden] {
            display: none;
        }
        @media (max-width: 1120px) {
            .aj-dac-summary-grid,
            .aj-agent-dac-grid,
            .aj-agent-dac-services {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
        @media (max-width: 720px) {
            .aj-dac-wizard {
                padding: 18px 14px 28px;
            }
            .aj-dac-hero {
                padding: 18px;
            }
            .aj-dac-summary-grid,
            .aj-agent-dac-grid,
            .aj-agent-dac-services,
            .aj-dac-client-type {
                grid-template-columns: 1fr;
            }
            .aj-dac-step-panel {
                padding: 18px;
                min-height: auto;
            }
            .aj-dac-actionbar {
                align-items: stretch;
                flex-direction: column;
            }
            .aj-dac-actionbar > div,
            .aj-dac-btn {
                width: 100%;
            }
        }
    </style>
@endpush

@section('content')
<div class="aj-dac-wizard" data-dac-wizard>
    <div class="aj-dac-hero">
        <div class="aj-dac-hero-main">
            <div>
                <div class="aj-dac-kicker"><i class="bx bx-layout"></i> Agent / Demandes Ã  la carte / CrÃ©ation</div>
                <h1>CrÃ©er une demande Ã  la carte</h1>
                <p>Renseignez la fiche client, choisissez les services Ã  chiffrer et transmettez une demande complÃ¨te au service quotation.</p>
            </div>
            <div class="aj-dac-hero-actions">
                <span class="aj-dac-status">Brouillon</span>
                <a href="{{ route('agent.custom-reservations.index') }}" class="aj-dac-hero-link"><i class="bx bx-list-ul"></i> Liste des demandes</a>
            </div>
        </div>
        <div class="aj-dac-summary-grid">
            <div class="aj-dac-summary-card"><span>Passagers</span><strong data-dac-summary="pax">1 adulte</strong></div>
            <div class="aj-dac-summary-card"><span>Services</span><strong data-dac-summary="services">0 sÃ©lectionnÃ©</strong></div>
            <div class="aj-dac-summary-card"><span>Destination</span><strong data-dac-summary="destination">Ã€ dÃ©finir</strong></div>
            <div class="aj-dac-summary-card"><span>Statut</span><strong data-dac-summary="status">Demande de devis</strong></div>
        </div>
    </div>

    @if($errors->any())
        <div class="aj-dac-alert">VÃ©rifiez les champs du formulaire. Les champs marquÃ©s d'une Ã©toile rouge sont obligatoires; l'email reste facultatif.</div>
    @endif

    <div class="aj-dac-stepper" aria-label="Parcours de crÃ©ation">
        <span class="aj-dac-stepper-label">Parcours</span>
        <button type="button" class="aj-dac-step-pill is-active" data-dac-step-button="1"><span>1</span> Informations gÃ©nÃ©rales</button>
        <div class="aj-dac-step-line"></div>
        <button type="button" class="aj-dac-step-pill" data-dac-step-button="2"><span>2</span> Offre commerciale</button>
        <div class="aj-dac-step-line"></div>
        <button type="button" class="aj-dac-step-pill" data-dac-step-button="3"><span>3</span> DÃ©tails de programme</button>
        <div class="aj-dac-step-line"></div>
        <button type="button" class="aj-dac-step-pill" data-dac-step-button="4"><span>4</span> Validation et suivi</button>
    </div>

    <div class="aj-dac-card">
        @include('agent.custom-requests.partials.form')
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const root = document.querySelector('[data-dac-wizard]');
        if (!root) return;

        let currentStep = 1;
        const panels = Array.from(root.querySelectorAll('[data-dac-step-panel]'));
        const buttons = Array.from(root.querySelectorAll('[data-dac-step-button]'));
        const prevButton = root.querySelector('[data-dac-prev]');
        const nextButton = root.querySelector('[data-dac-next]');
        const submitButtons = Array.from(root.querySelectorAll('[data-dac-submit]'));
        const clientTypeInputs = Array.from(root.querySelectorAll('input[name="customer_type"]'));
        const existingClientWrap = root.querySelector('[data-existing-client-wrap]');
        const existingClientId = root.querySelector('[data-existing-client-id]');
        const existingClientSearchInput = root.querySelector('[data-client-search-input]');
        const existingClientPanel = root.querySelector('[data-client-search-panel]');
        const existingClientResults = root.querySelector('[data-client-search-results]');
        const existingClientSelected = root.querySelector('[data-client-search-selected]');
        const existingClientClear = root.querySelector('[data-client-search-clear]');
        const clientSearchUrl = @json($clientSearchUrl ?? '');
        const serviceInputs = Array.from(root.querySelectorAll('input[name="services[]"]'));
        const serviceConfigs = Array.from(root.querySelectorAll('[data-service-config]'));
        const summaries = {
            pax: root.querySelector('[data-dac-summary="pax"]'),
            services: root.querySelector('[data-dac-summary="services"]'),
            destination: root.querySelector('[data-dac-summary="destination"]'),
            status: root.querySelector('[data-dac-summary="status"]'),
        };

        const getInput = function (name) {
            return root.querySelector('[name="' + name + '"]');
        };

        const plural = function (count, one, many) {
            return count > 1 ? many : one;
        };

        const escapeHtml = function (value) {
            return String(value ?? '').replace(/[&<>"']/g, function (char) {
                return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[char];
            });
        };

        const updateServiceConfigs = function () {
            const selected = serviceInputs.filter(function (input) { return input.checked; }).map(function (input) { return input.value; });
            serviceConfigs.forEach(function (config) {
                const services = (config.getAttribute('data-service-config') || '').split(',');
                config.classList.toggle('is-visible', services.some(function (service) { return selected.includes(service); }));
            });
        };

        const updateSummary = function () {
            const travelers = parseInt(getInput('travelers_count')?.value || '0', 10) || 0;
            const children = parseInt(getInput('children_count')?.value || '0', 10) || 0;
            const babies = parseInt(getInput('babies_count')?.value || '0', 10) || 0;
            const serviceCount = serviceInputs.filter(function (input) { return input.checked; }).length;
            const destination = (getInput('desired_destination')?.value || '').trim();
            const departureDate = (getInput('desired_departure_date')?.value || '').trim();
            const returnDate = (getInput('desired_return_date')?.value || '').trim();
            const durationInput = getInput('desired_duration');

            let durationText = '';
            if (departureDate && returnDate) {
                const start = new Date(departureDate + 'T00:00:00');
                const end = new Date(returnDate + 'T00:00:00');
                if (!Number.isNaN(start.getTime()) && !Number.isNaN(end.getTime()) && end >= start) {
                    const diffDays = Math.max(1, Math.round((end - start) / 86400000));
                    durationText = diffDays + ' ' + plural(diffDays, 'nuit', 'nuits');
                }
            }

            if (durationInput) {
                durationInput.value = durationText;
            }

            if (summaries.pax) {
                const details = [];
                if (travelers > 0) details.push(travelers + ' ' + plural(travelers, 'voyageur', 'voyageurs'));
                if (children > 0) details.push(children + ' ' + plural(children, 'enfant', 'enfants'));
                if (babies > 0) details.push(babies + ' ' + plural(babies, 'bÃ©bÃ©', 'bÃ©bÃ©s'));
                summaries.pax.textContent = details.length ? details.join(', ') : travelers + ' voyageur';
            }
            if (summaries.services) summaries.services.textContent = serviceCount + ' ' + plural(serviceCount, 'sÃ©lectionnÃ©', 'sÃ©lectionnÃ©s');
            if (summaries.destination) summaries.destination.textContent = destination || 'Ã€ dÃ©finir';
            if (summaries.status) summaries.status.textContent = 'Demande de devis';

            updateServiceConfigs();
        };

        const syncTravelerComposition = function () {
            const travelersInput = getInput('travelers_count');
            const childrenInput = getInput('children_count');
            const babiesInput = getInput('babies_count');
            const adultsInput = root.querySelector('[data-adults-count]');
            if (!travelersInput || !childrenInput || !babiesInput || !adultsInput) return;

            let travelers = Math.max(1, parseInt(travelersInput.value || '1', 10) || 1);
            const children = Math.max(0, parseInt(childrenInput.value || '0', 10) || 0);
            const babies = Math.max(0, parseInt(babiesInput.value || '0', 10) || 0);
            const minimumTotal = children + babies + 1;

            if (travelers < minimumTotal) {
                travelers = minimumTotal;
                travelersInput.value = travelers;
            }

            adultsInput.value = travelers - children - babies;
        };

        const autoGrow = function (textarea) {
            if (!textarea) return;
            textarea.style.height = 'auto';
            textarea.style.height = Math.max(textarea.scrollHeight, 220) + 'px';
        };

        const setClientField = function (name, value) {
            const field = root.querySelector('[data-client-input="' + name + '"]');
            if (!field) return;
            field.value = value || '';
        };

        const setSelectedClient = function (client) {
            if (!client || !existingClientId) return;
            existingClientId.value = client.id || '';
            if (existingClientSelected) {
                existingClientSelected.hidden = false;
                existingClientSelected.dataset.clientId = client.id || '';
                existingClientSelected.dataset.clientLabel = client.label || client.full_name || '';
                existingClientSelected.dataset.clientFullName = client.full_name || '';
                existingClientSelected.dataset.clientPhone = client.phone || '';
                existingClientSelected.dataset.clientEmail = client.email || '';
                existingClientSelected.dataset.clientCity = client.city || '';
                existingClientSelected.dataset.clientCountry = client.country || '';
                existingClientSelected.dataset.clientIdentity = client.identity || '';
                existingClientSelected.innerHTML = 'Client sÃ©lectionnÃ© : <strong>' + escapeHtml(client.label || client.full_name || '') + '</strong><button type="button" class="aj-client-search-clear" data-client-search-clear>Changer</button>';
            }
            setClientField('customer_full_name', client.full_name || '');
            setClientField('customer_phone', client.phone || '');
            setClientField('customer_email', client.email || '');
            setClientField('customer_city', client.city || '');
            setClientField('customer_country', client.country || '');
            setClientField('customer_identity', client.identity || '');
            if (existingClientSearchInput) {
                existingClientSearchInput.value = client.label || '';
            }
            if (existingClientResults) {
                existingClientResults.innerHTML = '';
            }
        };

        const clearSelectedClient = function () {
            if (existingClientId) existingClientId.value = '';
            if (existingClientSelected) existingClientSelected.hidden = true;
            if (existingClientSearchInput) existingClientSearchInput.value = '';
            if (existingClientResults) existingClientResults.innerHTML = '';
            setClientField('customer_full_name', '');
            setClientField('customer_phone', '');
            setClientField('customer_email', '');
            setClientField('customer_city', '');
            setClientField('customer_country', '');
            setClientField('customer_identity', '');
        };

        const renderClientResults = function (items) {
            if (!existingClientResults) return;
            if (!items.length) {
                existingClientResults.innerHTML = '<div style="color:#64748b;font-size:12px;font-weight:700;">Aucun client trouvÃ©.</div>';
                return;
            }
            existingClientResults.innerHTML = items.map(function (client) {
                const meta = [client.phone, client.email, client.city].filter(Boolean).join(' Â· ');
                return '<button type="button" class="aj-client-search-result" data-client-id="' + (client.id || '') + '" data-client-label="' + (client.label || '') + '" data-client-full-name="' + (client.full_name || '') + '" data-client-phone="' + (client.phone || '') + '" data-client-email="' + (client.email || '') + '" data-client-city="' + (client.city || '') + '" data-client-country="' + (client.country || '') + '" data-client-identity="' + (client.identity || '') + '">'
                    + '<strong>' + escapeHtml(client.label || '') + '</strong>'
                    + '<small>' + escapeHtml(meta) + '</small>'
                    + '</button>';
            }).join('');
        };

        const runClientSearch = function (term) {
            if (!existingClientPanel) return;
            const query = (term || '').trim();
            if (query.length < 2) {
                if (existingClientResults) existingClientResults.innerHTML = '';
                return;
            }

            existingClientPanel.hidden = false;
            if (existingClientResults) {
                existingClientResults.innerHTML = '<div style="color:#64748b;font-size:12px;font-weight:700;">Recherche en cours...</div>';
            }

            fetch(clientSearchUrl + '?q=' + encodeURIComponent(query), {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin',
            })
                .then(function (response) { return response.ok ? response.json() : Promise.reject(response); })
                .then(function (payload) {
                    renderClientResults(payload.items || []);
                })
                .catch(function () {
                    if (existingClientResults) {
                        existingClientResults.innerHTML = '<div style="color:#dc2626;font-size:12px;font-weight:700;">Recherche indisponible.</div>';
                    }
                });
        };

        const populateFromSelected = function () {
            if (!existingClientSelected || !existingClientSelected.dataset.clientId) {
                return;
            }

            setClientField('customer_full_name', existingClientSelected.dataset.clientFullName || '');
            setClientField('customer_phone', existingClientSelected.dataset.clientPhone || '');
            setClientField('customer_email', existingClientSelected.dataset.clientEmail || '');
            setClientField('customer_city', existingClientSelected.dataset.clientCity || '');
            setClientField('customer_country', existingClientSelected.dataset.clientCountry || '');
            setClientField('customer_identity', existingClientSelected.dataset.clientIdentity || '');
            if (existingClientSearchInput) {
                existingClientSearchInput.value = existingClientSelected.dataset.clientLabel || '';
            }
        };

        const updateClientMode = function () {
            const mode = clientTypeInputs.find(function (input) { return input.checked; })?.value || 'new_customer';
            if (existingClientWrap) {
                existingClientWrap.hidden = mode !== 'existing_customer';
            }
            if (mode === 'existing_customer') {
                populateFromSelected();
                if (existingClientPanel) {
                    existingClientPanel.hidden = false;
                }
            }
        };

        const buildGeneratedProgram = function () {
            const destination = (getInput('desired_destination')?.value || '').trim();
            const departureCity = (getInput('departure_city')?.value || '').trim();
            const travelTypeSelect = getInput('travel_type');
            const travelType = travelTypeSelect?.selectedOptions?.[0]?.textContent?.trim() || '';
            const departureDate = (getInput('desired_departure_date')?.value || '').trim();
            const returnDate = (getInput('desired_return_date')?.value || '').trim();
            const duration = (getInput('desired_duration')?.value || '').trim();
            const rhythmSelect = root.querySelector('[data-program-rhythm]');
            const styleSelect = root.querySelector('[data-program-style]');
            const rhythm = rhythmSelect?.selectedOptions?.[0]?.textContent?.trim() || 'Souple';
            const style = styleSelect?.selectedOptions?.[0]?.textContent?.trim() || 'Classique';
            const serviceLabels = serviceInputs.filter(function (input) { return input.checked; }).map(function (input) {
                const label = input.parentElement?.textContent?.trim() || input.value;
                return label;
            });
            const travelers = parseInt(getInput('travelers_count')?.value || '0', 10) || 0;
            const children = parseInt(getInput('children_count')?.value || '0', 10) || 0;
            const babies = parseInt(getInput('babies_count')?.value || '0', 10) || 0;
            const serviceText = serviceLabels.length ? serviceLabels.join(', ').toLowerCase() : 'les prestations demandÃ©es';
            const programType = root.querySelector('[data-program-type]')?.selectedOptions?.[0]?.textContent?.trim() || '';
            const lines = [];

            lines.push('Programme de voyage personnalisÃ©');
            if (programType) {
                lines.push('Type de programme : ' + programType);
            }
            if (destination) {
                lines.push('Destination : ' + destination);
            }
            if (departureCity) {
                lines.push('DÃ©part : ' + departureCity);
            }
            if (travelType) {
                lines.push('Type de voyage : ' + travelType);
            }
            if (departureDate || returnDate || duration) {
                const period = [
                    departureDate ? 'dÃ©part le ' + departureDate : '',
                    returnDate ? 'retour le ' + returnDate : '',
                    duration ? 'durÃ©e ' + duration : '',
                ].filter(Boolean).join(' | ');
                if (period) lines.push(period);
            }
            if (travelers || children || babies) {
                const travelerParts = [];
                if (travelers) travelerParts.push(travelers + ' voyageur(s)');
                if (children) travelerParts.push(children + ' enfant(s)');
                if (babies) travelerParts.push(babies + ' bebe(s)');
                lines.push('Voyageurs : ' + travelerParts.join(', '));
            }
            lines.push('Rythme souhaitÃ© : ' + rhythm.toLowerCase());
            lines.push('Style dâ€™expÃ©rience : ' + style.toLowerCase());
            lines.push('Services Ã  intÃ©grer : ' + serviceText + '.');
            lines.push('DÃ©roulÃ© suggÃ©rÃ© :');
            lines.push('Jour 1 : arrivÃ©e, accueil et installation.');
            lines.push('Jour 2 : dÃ©couverte principale et prestations sÃ©lectionnÃ©es.');
            lines.push('Jour 3 : activitÃ© / excursion / temps libre selon le rythme souhaitÃ©.');
            lines.push('Adapter le programme aux contraintes client, au budget et aux disponibilitÃ©s locales.');

            return lines.join('\n');
        };

        const renderStep = function () {
            panels.forEach(function (panel) {
                panel.classList.toggle('is-active', Number(panel.getAttribute('data-dac-step-panel')) === currentStep);
            });
            buttons.forEach(function (button) {
                const step = Number(button.getAttribute('data-dac-step-button'));
                button.classList.toggle('is-active', step === currentStep);
                button.classList.toggle('is-done', step < currentStep);
            });
            if (prevButton) prevButton.hidden = currentStep === 1;
            if (nextButton) nextButton.hidden = currentStep === panels.length;
            submitButtons.forEach(function (button) {
                button.hidden = currentStep !== panels.length;
            });
            updateSummary();
        };

        buttons.forEach(function (button) {
            button.addEventListener('click', function () {
                currentStep = Number(button.getAttribute('data-dac-step-button'));
                renderStep();
            });
        });
        prevButton?.addEventListener('click', function () {
            currentStep = Math.max(1, currentStep - 1);
            renderStep();
        });
        nextButton?.addEventListener('click', function () {
            currentStep = Math.min(panels.length, currentStep + 1);
            renderStep();
        });
        root.addEventListener('input', function () {
            syncTravelerComposition();
            updateSummary();
        });
        root.addEventListener('change', function () {
            syncTravelerComposition();
            updateSummary();
        });
        clientTypeInputs.forEach(function (input) {
            input.addEventListener('change', updateClientMode);
        });
        existingClientSearchInput?.addEventListener('input', function () {
            runClientSearch(existingClientSearchInput.value);
        });
        root.addEventListener('click', function (event) {
            const resultButton = event.target.closest('.aj-client-search-result');
            if (resultButton) {
                setSelectedClient({
                    id: resultButton.dataset.clientId || '',
                    label: resultButton.dataset.clientLabel || '',
                    full_name: resultButton.dataset.clientFullName || '',
                    phone: resultButton.dataset.clientPhone || '',
                    email: resultButton.dataset.clientEmail || '',
                    city: resultButton.dataset.clientCity || '',
                    country: resultButton.dataset.clientCountry || '',
                    identity: resultButton.dataset.clientIdentity || '',
                });
            }
            if (event.target.closest('[data-client-search-clear]')) {
                clearSelectedClient();
            }
        });
        root.querySelectorAll('textarea.aj-dac-textarea-large').forEach(function (textarea) {
            autoGrow(textarea);
            textarea.addEventListener('input', function () {
                autoGrow(textarea);
            });
        });
        const generateButton = root.querySelector('[data-generate-program]');
        const programTextarea = root.querySelector('textarea[name="requested_services_details"]');
        generateButton?.addEventListener('click', function () {
            if (!programTextarea) return;
            programTextarea.value = buildGeneratedProgram();
            autoGrow(programTextarea);
            programTextarea.focus();
            programTextarea.dispatchEvent(new Event('input', { bubbles: true }));
        });
        populateFromSelected();
        updateClientMode();
        syncTravelerComposition();
        renderStep();
    });
</script>
@endpush
