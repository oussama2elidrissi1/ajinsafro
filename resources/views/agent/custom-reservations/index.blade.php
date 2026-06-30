@extends('layouts.master-ajinsafro')

@section('title', 'Réservations à la carte')

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
            font-weight: 500;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, .74);
        }
        .aj-agent-hero-copy h1 {
            margin: 0;
            font-size: 34px;
            line-height: 1.05;
            font-weight: 600;
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
        .aj-agent-dashboard-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 18px;
        }
        .aj-agent-kpi-card {
            display: grid;
            grid-template-columns: 46px minmax(0, 1fr);
            gap: 12px;
            align-items: center;
            min-height: 94px;
            padding: 16px;
            border-radius: 18px;
        }
        .aj-agent-kpi-icon {
            display: grid;
            place-items: center;
            width: 46px;
            height: 46px;
            border-radius: 14px;
            background: #e8f4ff;
            color: #0676bc;
            font-size: 21px;
        }
        .aj-agent-kpi-card.is-green .aj-agent-kpi-icon { background: #e4f8ed; color: #12884d; }
        .aj-agent-kpi-card.is-orange .aj-agent-kpi-icon { background: #fff2df; color: #d96a00; }
        .aj-agent-kpi-card.is-violet .aj-agent-kpi-icon { background: #f0e9ff; color: #7048e8; }
        .aj-agent-kpi-label {
            display: block;
            color: #71849c;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: .05em;
            text-transform: uppercase;
        }
        .aj-agent-kpi-value {
            display: block;
            margin-top: 4px;
            color: #0f172a;
            font-size: 26px;
            font-weight: 600;
            line-height: 1;
        }
        .aj-agent-kpi-note {
            display: block;
            margin-top: 6px;
            color: #7a8da3;
            font-size: 12px;
        }
        .aj-agent-workspace-grid {
            display: grid;
            grid-template-columns: minmax(260px, .8fr) minmax(0, 1.4fr);
            gap: 18px;
            margin-bottom: 18px;
        }
        .aj-agent-workspace-card { padding: 18px; }
        .aj-agent-card-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
        }
        .aj-agent-card-title h2 {
            margin: 0;
            color: #123d60;
            font-size: 20px;
            font-weight: 600;
        }
        .aj-agent-card-title span {
            color: #71849c;
            font-size: 12px;
            font-weight: 500;
        }
        .aj-agent-account-line,
        .aj-agent-action-line {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            padding: 12px 0;
            border-top: 1px solid #edf3f9;
        }
        .aj-agent-account-line:first-of-type,
        .aj-agent-action-line:first-of-type { border-top: 0; padding-top: 0; }
        .aj-agent-account-line span,
        .aj-agent-action-line span {
            color: #6b7d93;
            font-size: 12px;
            font-weight: 500;
        }
        .aj-agent-account-line strong,
        .aj-agent-action-line strong {
            color: #172334;
            font-size: 14px;
            text-align: right;
        }
        .aj-agent-action-list {
            display: grid;
            gap: 10px;
        }
        .aj-agent-action-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 14px;
            align-items: center;
            padding: 13px;
            border: 1px solid #dbe6f2;
            border-radius: 15px;
            background: #fbfdff;
        }
        .aj-agent-action-row h3 {
            margin: 0;
            color: #10253b;
            font-size: 14px;
            font-weight: 600;
        }
        .aj-agent-action-row p {
            margin: 4px 0 0;
            color: #667b92;
            font-size: 12px;
        }
        .aj-agent-quick-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 14px;
        }
        .aj-agent-quick-filter {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            min-height: 34px;
            padding: 7px 11px;
            border: 1px solid #d6e2ef;
            border-radius: 999px;
            background: #fbfdff;
            color: #425871;
            font-size: 12px;
            font-weight: 500;
        }
        .aj-agent-quick-filter.is-active {
            border-color: #0b85cf;
            background: #e7f4ff;
            color: #0b75bd;
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
            font-weight: 600;
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
            grid-template-columns: minmax(190px, 1.25fr) minmax(180px, 1.15fr) minmax(150px, .8fr) minmax(150px, .8fr) minmax(150px, .8fr) auto auto;
            gap: 12px;
            align-items: end;
        }
        .aj-agent-field label {
            display: block;
            margin-bottom: 7px;
            color: #64748b;
            font-size: 11px;
            font-weight: 500;
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
            font-size: 18px;
            font-weight: 600;
            line-height: 1.25;
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
            font-weight: 500;
            line-height: 1;
            white-space: nowrap;
        }
        .aj-agent-pill-blue { background: #e7f2fe; color: #0b75bd; }
        .aj-agent-pill-slate { background: #eef3f8; color: #51657c; }
        .aj-agent-pill-green { background: #ddf7e8; color: #0f8a4b; }
        .aj-agent-pill-orange { background: #fff3df; color: #b45309; }
        .aj-agent-pill-red { background: #fee2e2; color: #b91c1c; }
        .aj-agent-status-board {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 18px;
        }
        .aj-agent-status-card {
            display: grid;
            gap: 10px;
            padding: 16px;
            border-left: 4px solid #0b85cf;
        }
        .aj-agent-status-card.is-orange { border-left-color: #d97706; }
        .aj-agent-status-card.is-violet { border-left-color: #7048e8; }
        .aj-agent-status-card.is-green { border-left-color: #16a34a; }
        .aj-agent-status-card span {
            color: #64748b;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: .04em;
            text-transform: uppercase;
        }
        .aj-agent-status-card strong {
            color: #0f172a;
            font-size: 26px;
            font-weight: 600;
            line-height: 1;
        }
        .aj-agent-status-card small {
            color: #64748b;
            font-size: 12px;
            font-weight: 600;
        }
        .aj-agent-priority-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #edf3f9;
        }
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
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: .04em;
        }
        .aj-agent-request-stat strong {
            display: block;
            margin-top: 8px;
            color: #172334;
            font-size: 16px;
            font-weight: 500;
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
        .aj-agent-request-footer-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }
        .aj-agent-request-footer-actions.is-left { justify-content: flex-start; margin-top: 14px; }
        .aj-agent-empty {
            padding: 40px 24px;
            text-align: center;
            border: 1px dashed #cdd9e6;
        }
        .aj-agent-empty h2 {
            margin: 0;
            color: #123d60;
            font-size: 24px;
            font-weight: 600;
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
            .aj-agent-dashboard-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .aj-agent-workspace-grid { grid-template-columns: 1fr; }
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
        /* Professional command-center refinement for the a-la-carte workspace. */
        .partner-v2.internal-v2-topbar-hidden .agent-portal-main {
            background: #f4f7fb;
        }
        .aj-agent-custom-page {
            max-width: 1680px;
            margin: 0 auto;
            padding: 22px 24px 36px;
        }
        .aj-agent-custom-page .aj-agent-panel {
            border-color: #d8e3ef;
            border-radius: 8px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .04), 0 12px 30px rgba(15, 23, 42, .06);
        }
        .aj-agent-page-hero {
            align-items: center;
            min-height: 118px;
            padding: 22px 28px;
            margin-bottom: 16px;
            border-radius: 8px;
            background: linear-gradient(135deg, #0b3959 0%, #0d628f 100%);
            box-shadow: 0 18px 36px rgba(8, 47, 73, .16);
        }
        .aj-agent-page-hero::before,
        .aj-agent-page-hero::after {
            display: none;
        }
        .aj-agent-hero-copy h1 {
            font-size: 28px;
            letter-spacing: 0;
        }
        .aj-agent-hero-copy p {
            max-width: 900px;
            margin-top: 8px;
            font-size: 13px;
        }
        .aj-agent-hero-kicker {
            margin-bottom: 6px;
            letter-spacing: .06em;
        }
        .aj-agent-hero-actions {
            min-width: auto;
        }
        .aj-agent-custom-page .aj-agent-primary-btn,
        .aj-agent-custom-page .aj-agent-action-btn {
            width: auto;
            min-height: 38px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            line-height: 1;
        }
        .aj-agent-custom-page .aj-agent-primary-btn {
            padding: 11px 16px;
            background: #0086c9;
            box-shadow: 0 8px 18px rgba(0, 120, 189, .18);
        }
        .aj-agent-custom-page .aj-agent-primary-btn:hover {
            background: #0077b5;
        }
        .aj-agent-custom-page .aj-agent-action-btn {
            padding: 10px 14px;
            color: #334155;
            border-color: #cfdce9;
            justify-content: center;
        }
        .aj-agent-dashboard-grid {
            gap: 12px;
            margin-bottom: 14px;
        }
        .aj-agent-kpi-card {
            min-height: 80px;
            grid-template-columns: 38px minmax(0, 1fr);
            gap: 12px;
            padding: 14px 16px;
            border-radius: 8px;
            box-shadow: none;
        }
        .aj-agent-kpi-card::before {
            height: 2px;
        }
        .aj-agent-kpi-icon {
            width: 38px;
            height: 38px;
            border-radius: 8px;
            font-size: 18px;
        }
        .aj-agent-kpi-label {
            font-size: 11px;
            letter-spacing: .03em;
        }
        .aj-agent-kpi-value {
            display: inline-block;
            margin-top: 3px;
            font-size: 24px;
        }
        .aj-agent-kpi-note {
            display: inline-block;
            margin: 3px 0 0 8px;
            font-size: 11px;
        }
        .aj-agent-workspace-grid {
            grid-template-columns: minmax(320px, .55fr) minmax(0, 1fr);
            gap: 14px;
            margin-bottom: 14px;
        }
        .aj-agent-workspace-card {
            padding: 16px 18px;
        }
        .aj-agent-card-title {
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e8eef5;
        }
        .aj-agent-card-title h2 {
            color: #0f3351;
            font-size: 16px;
            font-weight: 600;
        }
        .aj-agent-card-title span {
            font-size: 11px;
        }
        .aj-agent-account-line,
        .aj-agent-action-line {
            padding: 9px 0;
        }
        .aj-agent-action-list {
            gap: 8px;
        }
        .aj-agent-action-row {
            grid-template-columns: minmax(0, 1fr) auto;
            padding: 12px 12px;
            border-radius: 8px;
            background: #fbfdff;
        }
        .aj-agent-action-row h3 {
            font-size: 14px;
        }
        .aj-agent-request-footer-actions {
            flex-wrap: nowrap;
        }
        .aj-agent-filter-panel {
            padding: 16px;
            margin-bottom: 14px;
            border-radius: 8px;
        }
        .aj-agent-filter-head {
            margin-bottom: 12px;
        }
        .aj-agent-filter-title strong {
            font-size: 19px;
        }
        .aj-agent-filter-title span {
            margin-top: 4px;
            font-size: 12px;
        }
        .aj-agent-filter-grid {
            grid-template-columns: minmax(220px, 1.2fr) minmax(190px, 1fr) minmax(145px, .75fr) minmax(145px, .75fr) minmax(145px, .75fr) auto auto;
            gap: 10px;
        }
        .aj-agent-field label {
            margin-bottom: 6px;
            font-size: 10px;
            letter-spacing: .05em;
        }
        .aj-agent-field input,
        .aj-agent-field select {
            min-height: 40px;
            border-radius: 8px;
            background: #fff;
        }
        .aj-agent-filter-grid .aj-agent-primary-btn,
        .aj-agent-filter-grid .aj-agent-action-btn {
            min-height: 40px;
        }
        .aj-agent-quick-filters {
            margin-top: 12px;
            gap: 7px;
        }
        .aj-agent-quick-filter {
            min-height: 30px;
            padding: 6px 10px;
            border-radius: 8px;
            background: #fff;
        }
        .aj-agent-quick-filter.is-active {
            border-color: #0086c9;
            background: #eef8ff;
        }
        .aj-agent-table-panel {
            overflow: hidden;
        }
        .aj-agent-table-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 14px 16px;
            border-bottom: 1px solid #e4edf6;
            background: #fff;
        }
        .aj-agent-table-head h2 {
            margin: 0;
            color: #0f3351;
            font-size: 17px;
            font-weight: 600;
        }
        .aj-agent-table-head span {
            color: #64748b;
            font-size: 12px;
            font-weight: 500;
            white-space: nowrap;
        }
        .aj-agent-dossiers-table-wrap {
            overflow-x: auto;
            background: #fff;
        }
        .aj-agent-dossiers-table {
            width: 100%;
            min-width: 980px;
            border-collapse: collapse;
        }
        .aj-agent-dossiers-table th {
            padding: 12px 16px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            color: #64748b;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: .04em;
            text-align: left;
            text-transform: uppercase;
            white-space: nowrap;
        }
        .aj-agent-dossiers-table td {
            padding: 14px 16px;
            border-bottom: 1px solid #edf2f7;
            color: #334155;
            font-size: 13px;
            vertical-align: middle;
        }
        .aj-agent-dossiers-table tbody tr:hover {
            background: #f9fcff;
        }
        .aj-agent-dossiers-table tbody tr:last-child td {
            border-bottom: 0;
        }
        .aj-agent-client-main {
            display: block;
            color: #0f172a;
            font-size: 14px;
            font-weight: 600;
            line-height: 1.25;
        }
        .aj-agent-client-sub {
            display: block;
            margin-top: 4px;
            color: #64748b;
            font-size: 12px;
            font-weight: 600;
        }
        .aj-agent-table-strong {
            display: block;
            color: #172334;
            font-weight: 600;
        }
        .aj-agent-table-muted {
            display: block;
            margin-top: 4px;
            color: #64748b;
            font-size: 12px;
        }
        .aj-agent-table-actions-cell {
            text-align: right;
            white-space: nowrap;
        }
        .aj-agent-table-actions {
            display: inline-flex;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
            flex-wrap: nowrap;
        }
        .aj-agent-pill {
            border-radius: 8px;
            padding: 6px 9px;
            font-size: 11px;
            border: 1px solid transparent;
        }
        .aj-agent-pill-blue {
            border-color: #b9dcf5;
        }
        .aj-agent-pill-slate {
            border-color: #d8e1eb;
        }
        .aj-agent-pill-green {
            border-color: #bdebd1;
        }
        .aj-agent-pill-orange {
            border-color: #f7d39b;
        }
        .aj-agent-pill-red {
            border-color: #fecaca;
        }
        .aj-agent-status-board {
            gap: 12px;
            margin-bottom: 14px;
        }
        .aj-agent-status-card {
            border-radius: 8px;
            box-shadow: none;
        }
        .aj-agent-empty {
            border-radius: 8px;
        }
        @media (max-width: 1180px) {
            .aj-agent-status-board {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            .aj-agent-filter-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
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
            .aj-agent-dashboard-grid,
            .aj-agent-status-board,
            .aj-agent-request-meta {
                grid-template-columns: 1fr;
            }
            .aj-agent-action-row { grid-template-columns: 1fr; }
            .aj-agent-account-line,
            .aj-agent-action-line {
                align-items: flex-start;
                flex-direction: column;
            }
            .aj-agent-account-line strong,
            .aj-agent-action-line strong { text-align: left; }
            .aj-agent-hero-copy h1 { font-size: 28px; }
            .aj-agent-filter-title strong { font-size: 22px; }
            .aj-agent-filter-head .aj-agent-primary-btn,
            .aj-agent-hero-actions .aj-agent-primary-btn,
            .aj-agent-request-actions .aj-agent-action-btn {
                width: 100%;
                justify-content: center;
            }
            .aj-agent-status-stack { justify-content: flex-start; }
            .aj-agent-custom-page {
                padding: 14px 12px 26px;
            }
            .aj-agent-page-hero,
            .aj-agent-filter-panel,
            .aj-agent-custom-page .aj-agent-panel {
                border-radius: 8px;
            }
            .aj-agent-hero-copy h1 {
                font-size: 23px;
            }
            .aj-agent-dashboard-grid,
            .aj-agent-status-board,
            .aj-agent-workspace-grid,
            .aj-agent-filter-grid {
                grid-template-columns: 1fr;
            }
            .aj-agent-kpi-note {
                display: block;
                margin-left: 0;
            }
            .aj-agent-request-footer-actions,
            .aj-agent-table-actions {
                flex-wrap: wrap;
                justify-content: flex-start;
            }
            .aj-agent-custom-page .aj-agent-primary-btn,
            .aj-agent-custom-page .aj-agent-action-btn {
                width: 100%;
            }
            .aj-agent-table-head {
                align-items: flex-start;
                flex-direction: column;
            }
        }

        /* Nouvelle peau visuelle inspirée de la proposition fournie, sans changer la logique Blade. */
        .partner-v2.internal-v2-topbar-hidden .agent-portal-main {
            background: #f8fafc;
        }
        .aj-agent-custom-page {
            --agent-primary: #0284c7;
            --agent-primary-dark: #075985;
            --agent-primary-soft: #e0f2fe;
            --agent-success: #16a34a;
            --agent-success-soft: #dcfce7;
            --agent-warning: #f97316;
            --agent-warning-soft: #ffedd5;
            --agent-purple: #7c3aed;
            --agent-purple-soft: #ede9fe;
            --agent-danger: #ef4444;
            --agent-danger-soft: #fee2e2;
            --agent-card: #ffffff;
            --agent-text: #0f172a;
            --agent-muted: #64748b;
            --agent-light: #94a3b8;
            --agent-border: #e6edf5;
            --agent-border-soft: #eef2f7;
            max-width: 1500px;
            padding: 28px;
            color: var(--agent-text);
            font-size: 14px;
            font-weight: 400;
        }
        .aj-agent-custom-page * {
            letter-spacing: 0;
        }
        .aj-agent-custom-page .aj-agent-panel {
            background: var(--agent-card);
            border: 1px solid var(--agent-border);
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .05);
        }
        .aj-agent-page-hero {
            min-height: auto;
            margin-bottom: 20px;
            padding: 24px 28px;
            border-color: var(--agent-border);
            border-radius: 20px;
            background: #fff;
            color: var(--agent-text);
            box-shadow: 0 8px 24px rgba(15, 23, 42, .05);
        }
        .aj-agent-hero-kicker {
            color: var(--agent-primary);
            font-size: 12px;
            font-weight: 500;
            letter-spacing: .04em;
        }
        .aj-agent-hero-copy h1 {
            color: var(--agent-text);
            font-size: 26px;
            line-height: 1.2;
            font-weight: 600;
        }
        .aj-agent-hero-copy p {
            color: var(--agent-muted);
            font-size: 14px;
            font-weight: 400;
            line-height: 1.55;
        }
        .aj-agent-custom-page .aj-agent-primary-btn,
        .aj-agent-custom-page .aj-agent-action-btn {
            min-height: 38px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 500;
            gap: 7px;
        }
        .aj-agent-custom-page .aj-agent-primary-btn {
            background: var(--agent-primary);
            box-shadow: 0 8px 18px rgba(2, 132, 199, .22);
        }
        .aj-agent-custom-page .aj-agent-primary-btn:hover {
            background: var(--agent-primary-dark);
        }
        .aj-agent-custom-page .aj-agent-action-btn {
            background: #fff;
            color: #334155;
            border-color: var(--agent-border);
        }
        .aj-agent-dashboard-grid {
            gap: 16px;
            margin-bottom: 20px;
        }
        .aj-agent-kpi-card {
            min-height: 92px;
            grid-template-columns: 38px minmax(0, 1fr);
            gap: 14px;
            align-items: flex-start;
            padding: 18px;
            border-radius: 16px;
        }
        .aj-agent-kpi-icon {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            background: var(--agent-primary-soft);
            color: var(--agent-primary);
        }
        .aj-agent-kpi-card.is-orange .aj-agent-kpi-icon { background: var(--agent-warning-soft); color: var(--agent-warning); }
        .aj-agent-kpi-card.is-violet .aj-agent-kpi-icon { background: var(--agent-purple-soft); color: var(--agent-purple); }
        .aj-agent-kpi-card.is-green .aj-agent-kpi-icon { background: var(--agent-success-soft); color: var(--agent-success); }
        .aj-agent-kpi-label,
        .aj-agent-status-card span,
        .aj-agent-field label,
        .aj-agent-dossiers-table th {
            color: var(--agent-muted);
            font-weight: 500;
            letter-spacing: .02em;
        }
        .aj-agent-kpi-value,
        .aj-agent-status-card strong {
            color: var(--agent-text);
            font-size: 28px;
            font-weight: 600;
        }
        .aj-agent-kpi-note,
        .aj-agent-status-card small {
            color: var(--agent-light);
            font-size: 12px;
            font-weight: 400;
        }
        .aj-agent-status-board,
        .aj-agent-workspace-grid,
        .aj-agent-filter-panel {
            margin-bottom: 20px;
        }
        .aj-agent-status-card {
            padding: 18px;
            border-left-width: 3px;
            border-radius: 16px;
        }
        .aj-agent-workspace-grid {
            grid-template-columns: minmax(300px, .8fr) minmax(0, 1.4fr);
            gap: 16px;
        }
        .aj-agent-workspace-card,
        .aj-agent-filter-panel {
            padding: 20px;
        }
        .aj-agent-card-title,
        .aj-agent-table-head {
            border-bottom-color: var(--agent-border-soft);
        }
        .aj-agent-card-title h2,
        .aj-agent-filter-title strong,
        .aj-agent-table-head h2,
        .aj-agent-empty h2 {
            color: #123d60;
            font-weight: 600;
        }
        .aj-agent-card-title h2,
        .aj-agent-table-head h2 {
            font-size: 17px;
        }
        .aj-agent-card-title span,
        .aj-agent-table-head span {
            color: var(--agent-muted);
            font-weight: 400;
        }
        .aj-agent-account-line span,
        .aj-agent-action-line span {
            color: var(--agent-muted);
            font-weight: 500;
        }
        .aj-agent-account-line strong,
        .aj-agent-action-line strong,
        .aj-agent-action-row h3,
        .aj-agent-client-main,
        .aj-agent-table-strong {
            color: var(--agent-text);
            font-weight: 500;
        }
        .aj-agent-action-row {
            border-color: var(--agent-border);
            border-radius: 12px;
            background: #fbfdff;
        }
        .aj-agent-filter-grid {
            grid-template-columns: minmax(190px, 2fr) minmax(180px, 1.4fr) minmax(140px, 1fr) minmax(140px, 1fr) minmax(140px, 1fr) auto auto;
        }
        .aj-agent-field input,
        .aj-agent-field select {
            min-height: 40px;
            border-color: #dbe5f0;
            border-radius: 10px;
            background: #fff;
            font-weight: 400;
        }
        .aj-agent-quick-filter,
        .aj-agent-pill {
            border-radius: 999px;
            font-weight: 500;
        }
        .aj-agent-quick-filter {
            background: #fff;
        }
        .aj-agent-table-head {
            padding: 18px 20px;
            background: #fff;
        }
        .aj-agent-dossiers-table {
            min-width: 1050px;
        }
        .aj-agent-dossiers-table th {
            padding: 12px 14px;
            background: #f8fafc;
            font-size: 12px;
        }
        .aj-agent-dossiers-table td {
            padding: 14px;
            color: #334155;
            font-weight: 400;
        }
        .aj-agent-client-sub,
        .aj-agent-table-muted,
        .aj-agent-action-row p,
        .aj-agent-filter-title span {
            color: var(--agent-muted);
            font-weight: 400;
        }
        .aj-agent-pill-blue { background: var(--agent-primary-soft); color: var(--agent-primary-dark); border-color: #bae6fd; }
        .aj-agent-pill-slate { background: #f1f5f9; color: #475569; border-color: #d8e1eb; }
        .aj-agent-pill-green { background: var(--agent-success-soft); color: #15803d; border-color: #bbf7d0; }
        .aj-agent-pill-orange { background: var(--agent-warning-soft); color: #c2410c; border-color: #fed7aa; }
        .aj-agent-pill-red { background: var(--agent-danger-soft); color: #b91c1c; border-color: #fecaca; }
        @media (max-width: 1180px) {
            .aj-agent-workspace-grid,
            .aj-agent-filter-grid {
                grid-template-columns: 1fr;
            }
        }
        @media (max-width: 640px) {
            .aj-agent-custom-page {
                padding: 18px;
            }
            .aj-agent-page-hero {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
@endpush

@section('content')
@php
    $dashboard = $dashboard ?? [];
    $account = $dashboard['account'] ?? [];
    $actionRequests = $dashboard['action_requests'] ?? collect();
    $currentStatus = $filters['status'] ?? '';
    $currentPriority = $filters['priority'] ?? '';
    $statusCounts = $dashboard['status_counts'] ?? [];
    $priorityCounts = $dashboard['priority_counts'] ?? [];
    $statusGroups = $dashboard['status_groups'] ?? [];
    $quoteUser = auth()->user();
    $statusOverview = [
        [
            'label' => 'En attente',
            'count' => $statusGroups['pending'] ?? 0,
            'note' => 'Brouillon, nouvelle ou assignée',
            'class' => 'is-orange',
            'url' => route('agent.custom-reservations.index', ['status' => \App\Models\CustomRequest::STATUS_NEW]),
        ],
        [
            'label' => 'En traitement',
            'count' => $statusGroups['processing'] ?? 0,
            'note' => 'Pris en charge ou devis en préparation',
            'class' => 'is-violet',
            'url' => route('agent.custom-reservations.index', ['status' => \App\Models\CustomRequest::STATUS_PROCESSING]),
        ],
        [
            'label' => 'Devis envoyés',
            'count' => $statusGroups['quote_sent'] ?? 0,
            'note' => 'En attente du retour agent/client',
            'class' => '',
            'url' => route('agent.custom-reservations.index', ['status' => \App\Models\CustomRequest::STATUS_QUOTE_SENT]),
        ],
        [
            'label' => 'Confirmées',
            'count' => $statusGroups['confirmed'] ?? 0,
            'note' => 'Dossiers validés',
            'class' => 'is-green',
            'url' => route('agent.custom-reservations.index', ['status' => \App\Models\CustomRequest::STATUS_CONFIRMED]),
        ],
    ];
@endphp
<div class="aj-agent-custom-page">
    <section class="aj-agent-page-hero">
        <div class="aj-agent-hero-copy">
            <p class="aj-agent-hero-kicker">Agent / demandes personnalisées</p>
            <h1>Réservations à la carte</h1>
            <p>Suivez les demandes clients, préparez les quotations et envoyez les devis depuis le même espace.</p>
        </div>
        @if($canCreateRequest ?? false)
            <div class="aj-agent-hero-actions">
                <a href="{{ route('agent.custom-reservations.create') }}" class="aj-agent-primary-btn">
                    <i class="bx bx-plus-circle"></i>
                    <span>Créer une réservation</span>
                </a>
            </div>
        @endif
    </section>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <section class="aj-agent-dashboard-grid" aria-label="Dashboard réservations à la carte">
        <article class="aj-agent-panel aj-agent-kpi-card">
            <span class="aj-agent-kpi-icon"><i class="bx bx-folder-open"></i></span>
            <div>
                <span class="aj-agent-kpi-label">Demandes visibles</span>
                <strong class="aj-agent-kpi-value">{{ $dashboard['total'] ?? 0 }}</strong>
                <span class="aj-agent-kpi-note">{{ $dashboard['today'] ?? 0 }} créée(s) aujourd'hui</span>
            </div>
        </article>
        <article class="aj-agent-panel aj-agent-kpi-card is-orange">
            <span class="aj-agent-kpi-icon"><i class="bx bx-bell"></i></span>
            <div>
                <span class="aj-agent-kpi-label">Nouvelles</span>
                <strong class="aj-agent-kpi-value">{{ $dashboard['new'] ?? 0 }}</strong>
                <span class="aj-agent-kpi-note">{{ $dashboard['urgent'] ?? 0 }} urgente(s)</span>
            </div>
        </article>
        <article class="aj-agent-panel aj-agent-kpi-card is-violet">
            <span class="aj-agent-kpi-icon"><i class="bx bx-loader-circle"></i></span>
            <div>
                <span class="aj-agent-kpi-label">En traitement</span>
                <strong class="aj-agent-kpi-value">{{ $dashboard['in_progress'] ?? 0 }}</strong>
                <span class="aj-agent-kpi-note">{{ $dashboard['assigned_to_me'] ?? 0 }} assignée(s) à moi</span>
            </div>
        </article>
        <article class="aj-agent-panel aj-agent-kpi-card is-green">
            <span class="aj-agent-kpi-icon"><i class="bx bx-check-shield"></i></span>
            <div>
                <span class="aj-agent-kpi-label">Confirmées</span>
                <strong class="aj-agent-kpi-value">{{ $dashboard['confirmed'] ?? 0 }}</strong>
                <span class="aj-agent-kpi-note">{{ $dashboard['quote_sent'] ?? 0 }} devis envoyé(s)</span>
            </div>
        </article>
    </section>

    <section class="aj-agent-status-board" aria-label="Statuts des demandes à la carte">
        @foreach($statusOverview as $overview)
            <a href="{{ $overview['url'] }}" class="aj-agent-panel aj-agent-status-card {{ $overview['class'] }}">
                <span>{{ $overview['label'] }}</span>
                <strong>{{ $overview['count'] }}</strong>
                <small>{{ $overview['note'] }}</small>
            </a>
        @endforeach
    </section>

    <section class="aj-agent-workspace-grid">
        <article class="aj-agent-panel aj-agent-workspace-card">
            <div class="aj-agent-card-title">
                <h2>Mon compte</h2>
                <span>Gestion</span>
            </div>
            <div class="aj-agent-account-line">
                <span>Utilisateur</span>
                <strong>{{ $account['name'] ?? auth()->user()?->name }}</strong>
            </div>
            <div class="aj-agent-account-line">
                <span>Rôle</span>
                <strong>{{ $account['role'] ?? '-' }}</strong>
            </div>
            <div class="aj-agent-account-line">
                <span>Agence</span>
                <strong>{{ $account['branch'] ?? 'Non rattaché' }}</strong>
            </div>
            <div class="aj-agent-account-line">
                <span>Droits</span>
                <strong>{{ ! empty($account['can_quote']) ? 'Quotation autorisée' : 'Consultation / création' }}</strong>
            </div>
            <div class="aj-agent-request-footer-actions is-left">
                <a href="{{ route('agent.profile') }}" class="aj-agent-action-btn"><i class="bx bx-user"></i> Profil</a>
                @if($canCreateRequest ?? false)
                    <a href="{{ route('agent.custom-reservations.create') }}" class="aj-agent-primary-btn"><i class="bx bx-plus"></i> Nouvelle demande</a>
                @endif
            </div>
        </article>

        <article class="aj-agent-panel aj-agent-workspace-card">
            <div class="aj-agent-card-title">
                <h2>Actions prioritaires</h2>
                <span>{{ $dashboard['upcoming_departures'] ?? 0 }} départ(s) sous 7 jours</span>
            </div>
            @if($actionRequests->count())
                <div class="aj-agent-action-list">
                    @foreach($actionRequests as $actionRequest)
                        <div class="aj-agent-action-row">
                            <div>
                                <h3>{{ $actionRequest->customer_full_name ?: $actionRequest->request_number }}</h3>
                                <p>{{ $actionRequest->request_number }} - {{ $actionRequest->desired_destination ?: 'Destination a confirmer' }} - {{ $actionRequest->statusLabel() }}</p>
                            </div>
                            <div class="aj-agent-request-footer-actions">
                                @if($quoteUser && $actionRequest->canBeQuotedBy($quoteUser))
                                    @if((int) ($actionRequest->assigned_to ?? 0) === (int) $quoteUser->id)
                                        <a href="{{ route('agent.custom-reservations.quote', $actionRequest) }}" class="aj-agent-primary-btn"><i class="bx bx-check-circle"></i> Traiter</a>
                                    @else
                                        <form method="POST" action="{{ route('agent.custom-reservations.take', $actionRequest) }}">
                                            @csrf
                                            <button type="submit" class="aj-agent-primary-btn">
                                                <i class="bx bx-user-check"></i>
                                                {{ (int) ($actionRequest->created_by ?? 0) === (int) $quoteUser->id ? 'Traiter' : 'Prendre en charge' }}
                                            </button>
                                        </form>
                                    @endif
                                @endif
                                <a href="{{ route('agent.custom-reservations.show', $actionRequest) }}" class="aj-agent-action-btn"><i class="bx bx-show"></i> Ouvrir</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="aj-agent-action-line">
                    <span>Suivi</span>
                    <strong>Aucune action prioritaire pour le moment</strong>
                </div>
            @endif
        </article>
    </section>

    <form method="GET" action="{{ route('agent.custom-reservations.index') }}" class="aj-agent-panel aj-agent-filter-panel">
        <div class="aj-agent-filter-head">
            <div class="aj-agent-filter-title">
                <strong>Recherche et dossiers</strong>
                <span>Affinez la recherche par client, destination, statut ou date de départ souhaitée.</span>
            </div>
        </div>
        <div class="aj-agent-filter-grid">
            <div class="aj-agent-field">
                <label for="client">Client</label>
                <input id="client" type="text" name="client" value="{{ $filters['client'] ?? '' }}" placeholder="Nom, téléphone, référence...">
            </div>
            <div class="aj-agent-field">
                <label for="destination">Destination</label>
                <input id="destination" type="text" name="destination" value="{{ $filters['destination'] ?? '' }}" placeholder="Destination souhaitée">
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
                <label for="priority">Priorité</label>
                <select id="priority" name="priority">
                    <option value="">Toutes</option>
                    @foreach($priorityOptions as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['priority'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="aj-agent-field">
                <label for="date">Date</label>
                <input id="date" type="date" name="date" value="{{ $filters['date'] ?? '' }}">
            </div>
            <button type="submit" class="aj-agent-primary-btn"><i class="bx bx-filter-alt"></i> Filtrer</button>
            <a href="{{ route('agent.custom-reservations.index') }}" class="aj-agent-action-btn"><i class="bx bx-reset"></i> Réinitialiser</a>
        </div>
        <div class="aj-agent-quick-filters">
            <a class="aj-agent-quick-filter {{ $currentStatus === '' && $currentPriority === '' ? 'is-active' : '' }}" href="{{ route('agent.custom-reservations.index') }}">
                Tous <strong>{{ $dashboard['total'] ?? 0 }}</strong>
            </a>
            @foreach($statusOptions as $value => $label)
                @if(($statusCounts[$value] ?? 0) > 0)
                    <a class="aj-agent-quick-filter {{ $currentStatus === $value ? 'is-active' : '' }}" href="{{ route('agent.custom-reservations.index', ['status' => $value]) }}">
                        {{ $label }} <strong>{{ $statusCounts[$value] }}</strong>
                    </a>
                @endif
            @endforeach
        </div>
        <div class="aj-agent-priority-filters">
            @foreach($priorityOptions as $value => $label)
                @if(($priorityCounts[$value] ?? 0) > 0)
                    <a class="aj-agent-quick-filter {{ $currentPriority === $value ? 'is-active' : '' }}" href="{{ route('agent.custom-reservations.index', ['priority' => $value]) }}">
                        {{ $label }} <strong>{{ $priorityCounts[$value] }}</strong>
                    </a>
                @endif
            @endforeach
        </div>
    </form>

    @if($requests->count())
        <section class="aj-agent-panel aj-agent-table-panel">
            <div class="aj-agent-table-head">
                <h2>Dossiers à la carte</h2>
                <span>{{ $requests->total() }} dossier(s) trouvé(s)</span>
            </div>
            <div class="aj-agent-dossiers-table-wrap">
                <table class="aj-agent-dossiers-table">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Destination</th>
                            <th>Date</th>
                            <th>Statut</th>
                            <th>Agent</th>
                            <th>Dernier devis</th>
                            <th class="aj-agent-table-actions-cell">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($requests as $requestRow)
                            @php
                                $priorityPillClass = match ($requestRow->priority) {
                                    'very_urgent' => 'aj-agent-pill-red',
                                    'urgent' => 'aj-agent-pill-orange',
                                    default => 'aj-agent-pill-slate',
                                };
                                $statusPillClass = match ($requestRow->status) {
                                    \App\Models\CustomRequest::STATUS_CONFIRMED => 'aj-agent-pill-green',
                                    \App\Models\CustomRequest::STATUS_CANCELLED, \App\Models\CustomRequest::STATUS_REFUSED => 'aj-agent-pill-slate',
                                    \App\Models\CustomRequest::STATUS_MODIFICATION_REQUESTED, \App\Models\CustomRequest::STATUS_MISSING_INFO => 'aj-agent-pill-orange',
                                    default => 'aj-agent-pill-blue',
                                };
                            @endphp
                            <tr>
                                <td>
                                    <span class="aj-agent-client-main">{{ $requestRow->customer_full_name }}</span>
                                    <span class="aj-agent-client-sub">{{ $requestRow->request_number }} - {{ $requestRow->travelers_count }} voyageur(s)</span>
                                </td>
                                <td>
                                    <span class="aj-agent-table-strong">{{ $requestRow->desired_destination ?: 'Destination a confirmer' }}</span>
                                    <span class="aj-agent-table-muted">Dossier DAC</span>
                                </td>
                                <td>
                                    <span class="aj-agent-table-strong">{{ $requestRow->desired_departure_date ? $requestRow->desired_departure_date->format('d/m/Y') : 'Flexible' }}</span>
                                </td>
                                <td>
                                    <div class="aj-agent-status-stack">
                                        <span class="aj-agent-pill {{ $statusPillClass }}">{{ $requestRow->statusLabel() }}</span>
                                        <span class="aj-agent-pill {{ $priorityPillClass }}">{{ $requestRow->priorityLabel() }}</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="aj-agent-table-strong">{{ $requestRow->assignedAgent?->name ?: 'En attente' }}</span>
                                </td>
                                <td>
                                    <span class="aj-agent-table-strong">{{ $requestRow->latestQuote ? number_format((float) $requestRow->latestQuote->total_sale, 2, ',', ' ') . ' ' . $requestRow->latestQuote->currency : '-' }}</span>
                                </td>
                                <td class="aj-agent-table-actions-cell">
                                    <div class="aj-agent-table-actions">
                                        @if($quoteUser && $requestRow->canBeQuotedBy($quoteUser))
                                            @if((int) ($requestRow->assigned_to ?? 0) === (int) $quoteUser->id)
                                                <a href="{{ route('agent.custom-reservations.quote', $requestRow) }}" class="aj-agent-primary-btn"><i class="bx bx-check-circle"></i> Traiter</a>
                                            @else
                                                <form method="POST" action="{{ route('agent.custom-reservations.take', $requestRow) }}">
                                                    @csrf
                                                    <button type="submit" class="aj-agent-primary-btn">
                                                        <i class="bx bx-user-check"></i>
                                                        {{ (int) ($requestRow->created_by ?? 0) === (int) $quoteUser->id ? 'Traiter' : 'Prendre en charge' }}
                                                    </button>
                                                </form>
                                            @endif
                                        @endif
                                        @if($requestRow->latestQuote?->pdf_path)
                                            <a href="{{ route('agent.custom-reservations.quote.download', [$requestRow, $requestRow->latestQuote]) }}" class="aj-agent-action-btn"><i class="bx bx-file"></i> PDF</a>
                                        @endif
                                        <a href="{{ route('agent.custom-reservations.show', $requestRow) }}" class="aj-agent-action-btn"><i class="bx bx-show"></i> Détail</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <div class="aj-agent-pagination">{{ $requests->links() }}</div>
    @else
        <div class="aj-agent-panel aj-agent-empty">
            <h2>Aucune demande à la carte</h2>
            <p>Les demandes personnalisées créées par votre compte apparaîtront ici dès qu'un dossier sera enregistré.</p>
            @if($canCreateRequest ?? false)
                <a href="{{ route('agent.custom-reservations.create') }}" class="aj-agent-primary-btn">Créer une demande</a>
            @endif
        </div>
    @endif
</div>
@endsection
