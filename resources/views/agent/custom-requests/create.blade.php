@extends('layouts.master-ajinsafro')

@section('title', 'Nouvelle demande à la carte')

@push('styles')
    <link href="{{ URL::asset('css/agent-dashboard.css') }}" rel="stylesheet" type="text/css" />
    <style>
        .aj-agent-dac-page {
            padding: 28px 32px 34px;
            max-width: 1280px;
            margin: 0 auto;
        }
        .aj-agent-dac-alert {
            border: 1px solid #fecaca;
            background: #fef2f2;
            color: #991b1b;
            border-radius: 10px;
            padding: 12px 14px;
            margin-bottom: 18px;
            font-weight: 600;
        }
        .aj-agent-dac-form {
            display: grid;
            gap: 18px;
        }
        .aj-agent-dac-section {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, .04), 0 6px 16px rgba(15, 23, 42, .05);
            overflow: hidden;
        }
        .aj-agent-dac-section-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 16px 20px;
            border-bottom: 1px solid #e2e8f0;
            background: #fbfdff;
        }
        .aj-agent-dac-section-head h2 {
            margin: 0;
            color: #0f172a;
            font-size: 16px;
            font-weight: 700;
        }
        .aj-agent-dac-section-head span {
            color: #64748b;
            font-size: 12px;
            font-weight: 600;
        }
        .aj-agent-dac-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
            padding: 20px;
        }
        .aj-agent-dac-field {
            min-width: 0;
        }
        .aj-agent-dac-field-wide {
            grid-column: 1 / -1;
        }
        .aj-agent-dac-field-after {
            padding: 0 20px 20px;
        }
        .aj-agent-dac-field label {
            display: block;
            color: #334155;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 7px;
        }
        .aj-agent-dac-field label span {
            color: #dc2626;
        }
        .aj-agent-dac-field input,
        .aj-agent-dac-field select,
        .aj-agent-dac-field textarea {
            width: 100%;
            border: 1px solid #dbe5ef;
            background: #fff;
            color: #0f172a;
            border-radius: 10px;
            padding: 11px 12px;
            min-height: 44px;
            font-size: 14px;
            font-weight: 500;
            outline: none;
        }
        .aj-agent-dac-field textarea {
            min-height: 104px;
            resize: vertical;
        }
        .aj-agent-dac-field input:focus,
        .aj-agent-dac-field select:focus,
        .aj-agent-dac-field textarea:focus {
            border-color: #0078bd;
            box-shadow: 0 0 0 3px rgba(0, 120, 189, .12);
        }
        .aj-agent-dac-field small {
            display: block;
            color: #dc2626;
            margin-top: 6px;
            font-weight: 600;
        }
        .aj-agent-dac-services {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
            padding: 20px;
        }
        .aj-agent-dac-services label {
            display: flex;
            align-items: center;
            gap: 9px;
            border: 1px solid #dbe5ef;
            border-radius: 12px;
            background: #f8fafc;
            color: #0f172a;
            min-height: 44px;
            padding: 10px 12px;
            font-weight: 650;
            font-size: 13px;
        }
        .aj-agent-dac-services input {
            width: 16px;
            height: 16px;
            flex: 0 0 auto;
        }
        .aj-agent-dac-actions {
            position: sticky;
            bottom: 0;
            z-index: 4;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            border: 1px solid #dbe5ef;
            border-radius: 16px;
            background: rgba(255, 255, 255, .96);
            box-shadow: 0 -8px 20px rgba(15, 23, 42, .06);
            padding: 14px;
        }
        .aj-agent-dac-actions > div {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }
        .aj-agent-dac-actions .aj-agent-action-btn {
            width: auto;
            min-height: 42px;
        }
        .aj-agent-dac-secondary {
            border: 1px solid #dbe5ef;
            background: #fff;
            color: #334155;
            border-radius: 10px;
            min-height: 42px;
            padding: 10px 16px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }
        @media (max-width: 1120px) {
            .aj-agent-dac-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            .aj-agent-dac-services {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
        @media (max-width: 720px) {
            .aj-agent-dac-page {
                padding: 20px 14px 28px;
            }
            .aj-agent-dac-grid,
            .aj-agent-dac-services {
                grid-template-columns: 1fr;
                padding: 16px;
            }
            .aj-agent-dac-section-head,
            .aj-agent-dac-actions {
                align-items: stretch;
                flex-direction: column;
            }
            .aj-agent-dac-actions > div,
            .aj-agent-dac-actions .aj-agent-action-btn,
            .aj-agent-dac-actions button {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
@endpush

@section('content')
<div class="aj-agent-dac-page">
    <div class="aj-agent-page-head">
        <div class="aj-agent-page-title">
            <h1>Nouvelle demande à la carte</h1>
            <p>Création complète d'une demande personnalisée pour cotation.</p>
        </div>
        <a href="{{ route('agent.custom-reservations.index') }}" class="aj-agent-action-btn">
            <i class="bx bx-list-ul"></i>
            <span>Liste des demandes</span>
        </a>
    </div>

    @if($errors->any())
        <div class="aj-agent-dac-alert">Vérifiez les champs du formulaire. Les champs marqués d'une étoile rouge sont obligatoires; l'email reste facultatif.</div>
    @endif

    @include('agent.custom-requests.partials.form')
</div>
@endsection
