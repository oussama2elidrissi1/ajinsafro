@php
    $now      = \Carbon\Carbon::now('Africa/Casablanca')->locale('fr');
    $dateLong = ucfirst($now->translatedFormat('l d F Y'));
    $dateTime = ucfirst($now->translatedFormat('l d F Y · H:i'));
    $rsTotal  = max(1, (int)($stats['reservations_total'] ?? 0));
    $payments = $stats['payment_labels'] ?? [];
    $paySer   = $stats['payment_series'] ?? [];
    $payMax   = (count($paySer) > 0) ? max(max($paySer), 1) : 1;

    $user      = auth()->user();
    $userName  = $user?->name ?? 'Admin';
    $userRole  = $user?->getRoleNames()->first() ?? 'Administrateur';
    $initials  = strtoupper(collect(preg_split('/\s+/', trim($userName)))->filter()->take(2)->map(fn($s) => mb_substr($s, 0, 1))->implode(''));
    if ($initials === '') { $initials = 'AD'; }
    $brandLogoDark = \App\Models\Setting::brandLogoUrl('dark');
    $brandLogoLight = \App\Models\Setting::brandLogoUrl('light');
    $brandName = \App\Models\Setting::getValue('brand_name', 'Ajinsafro');
    $avatarUrl = $user?->avatar_url;
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dashboard V2 — {{ $brandName }} Admin</title>

    <link rel="shortcut icon" href="{{ URL::asset('build/images/favicon.ico') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <link href="{{ URL::asset('build/css/icons.min.css') }}" rel="stylesheet" type="text/css">

    <style>
        :root {
            --blue: #005792;
            --blue-dark: #06345c;
            --blue-soft: #eaf4fb;
            --orange: #ff6b00;
            --green: #19b982;
            --red: #ef4d45;
            --yellow: #f7b500;
            --purple: #8b5cf6;
            --text: #172b4d;
            --muted: #71829a;
            --border: #e6edf5;
            --bg: #f6f9fc;
            --white: #fff;
            --shadow: 0 12px 35px rgba(15, 45, 75, 0.08);
            --radius: 18px;
            --sidebar: 280px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            font-size: 14px;
        }

        a { text-decoration: none; color: inherit; }
        button, input, select { font-family: inherit; }

        .admin-layout { display: flex; min-height: 100vh; }

        /* ─── SIDEBAR ─── */
        .sidebar {
            width: var(--sidebar);
            background: #fff;
            border-right: 1px solid var(--border);
            position: fixed;
            inset: 0 auto 0 0;
            overflow-y: auto;
            z-index: 50;
            padding: 22px 22px 32px;
        }

        .brand {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 30px;
            padding-bottom: 18px;
            border-bottom: 1px solid #edf2f7;
        }
        .brand-logo-link {
            display: inline-flex;
            align-items: center;
            justify-content: flex-start;
            max-width: 100%;
        }
        .brand-logo-img,
        .logo-img {
            height: 42px;
            width: auto;
            max-width: 100%;
            object-fit: contain;
            object-position: left center;
            display: block;
        }
        .brand-subtitle { font-size: 11px; color: var(--muted); font-weight: 700; margin-top: 2px; }

        .menu-section { margin-bottom: 22px; }
        .menu-label {
            font-size: 11px; color: #8796aa; font-weight: 900;
            text-transform: uppercase; letter-spacing: 0.06em;
            margin-bottom: 10px; padding-left: 10px;
        }
        .menu { display: grid; gap: 4px; }
        .menu-item {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 14px; border-radius: 13px;
            color: #40546d; font-weight: 700;
            transition: 0.18s ease; font-size: 13px;
        }
        .menu-item:hover, .menu-item.active {
            background: var(--blue-soft);
            color: var(--blue);
        }
        .menu-item.danger { color: var(--red); }
        .menu-item.danger:hover { background: #fff0ef; color: var(--red); }
        .menu-icon { width: 22px; text-align: center; font-size: 17px; flex-shrink: 0; }

        .help-box {
            margin-top: 30px;
            background: linear-gradient(135deg, #eaf4ff, #f7fbff);
            border: 1px solid #d8ebff;
            border-radius: 16px;
            padding: 18px;
        }
        .help-box h4 { color: var(--blue-dark); font-size: 14px; margin-bottom: 6px; font-weight: 900; }
        .help-box p { color: var(--muted); font-size: 12px; line-height: 1.5; margin-bottom: 12px; }
        .help-box a {
            display: inline-block; background: var(--blue); color: #fff;
            padding: 9px 14px; border-radius: 11px; font-weight: 800; font-size: 12px;
        }

        /* ─── MAIN ─── */
        .main {
            margin-left: var(--sidebar);
            width: calc(100% - var(--sidebar));
            min-height: 100vh;
        }

        /* ─── TOPBAR ─── */
        .topbar {
            min-height: 78px;
            background: linear-gradient(135deg, var(--blue), #064b7f);
            box-shadow: 0 12px 28px rgba(6, 52, 92, 0.18);
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 34px;
            position: sticky; top: 0; z-index: 30;
        }
        .topbar-left {
            display: flex;
            align-items: center;
            gap: 18px;
            flex: 1;
            min-width: 0;
        }
        .topbar-brand-group { display: flex; align-items: center; gap: 14px; flex-shrink: 0; }
        .topbar-logo {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 140px;
            padding: 10px 14px;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.14);
        }
        .topbar-logo-img { height: 38px; }
        .hamburger {
            border: 1px solid rgba(255, 255, 255, 0.18);
            background: rgba(255, 255, 255, 0.12);
            width: 42px; height: 42px; border-radius: 12px;
            font-size: 20px; cursor: pointer; color: #fff;
            display: none;
        }
        .search { width: min(520px, 100%); position: relative; }
        .search input {
            width: 100%; height: 44px;
            border: 1px solid rgba(255, 255, 255, 0.18); border-radius: 14px;
            background: rgba(255, 255, 255, 0.14); padding: 0 48px 0 46px;
            outline: none; color: #fff; font-weight: 600;
            transition: 0.2s;
        }
        .search input::placeholder { color: rgba(255, 255, 255, 0.78); }
        .search input:focus {
            border-color: rgba(255, 255, 255, 0.42);
            box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.12);
        }
        .search .icon { position: absolute; top: 50%; left: 16px; transform: translateY(-50%); color: rgba(255, 255, 255, 0.82); }
        .search .shortcut {
            position: absolute; top: 50%; right: 14px; transform: translateY(-50%);
            background: rgba(255, 255, 255, 0.16); padding: 4px 8px; border-radius: 8px;
            font-size: 11px; font-weight: 800; color: rgba(255, 255, 255, 0.78);
        }

        .topbar-actions { display: flex; align-items: center; gap: 12px; flex-shrink: 0; }
        .notif {
            width: 42px; height: 42px; border-radius: 13px;
            display: grid; place-items: center;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.14);
            position: relative; color: #fff;
            font-size: 17px; cursor: pointer; transition: 0.2s;
        }
        .notif:hover { background: rgba(255, 255, 255, 0.2); border-color: rgba(255, 255, 255, 0.3); }
        .notif b {
            position: absolute; top: -5px; right: -5px;
            background: var(--orange); color: #fff;
            min-width: 18px; height: 18px; padding: 0 4px; border-radius: 999px;
            display: grid; place-items: center; font-size: 10px; font-weight: 900;
            box-shadow: 0 6px 14px rgba(0, 0, 0, 0.14);
        }

        .profile {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            padding: 8px 10px 8px 8px;
            border: 1px solid rgba(255, 255, 255, 0.16);
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            cursor: pointer;
        }
        .profile-avatar-shell {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            overflow: hidden;
            position: relative;
            flex-shrink: 0;
        }
        .profile-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(255,255,255,.35);
            display: block;
            background: rgba(255, 255, 255, 0.18);
        }
        .avatar-fallback {
            position: absolute;
            inset: 0;
            display: none;
            place-items: center;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.18);
            color: #fff;
            font-weight: 900;
        }
        .profile-info { display: flex; flex-direction: column; }
        .profile-name { font-weight: 900; color: #fff; font-size: 14px; line-height: 1.2; }
        .profile-role { color: rgba(255, 255, 255, 0.72); font-size: 11px; font-weight: 700; }
        .profile-caret {
            width: 24px;
            height: 24px;
            display: grid;
            place-items: center;
            color: rgba(255, 255, 255, 0.92);
            font-size: 18px;
        }

        /* ─── CONTENT ─── */
        .content { padding: 30px 34px; }

        .page-head {
            display: flex; justify-content: space-between; align-items: flex-start;
            gap: 20px; margin-bottom: 26px; flex-wrap: wrap;
        }
        .page-title { display: flex; align-items: flex-start; gap: 14px; }
        .page-title-icon {
            width: 48px; height: 48px; border-radius: 14px;
            background: var(--blue-soft); color: var(--blue);
            display: grid; place-items: center; font-size: 22px;
        }
        .page-title h1 { font-size: 30px; font-weight: 900; color: var(--text); margin-bottom: 4px; line-height: 1.1; }
        .page-title p { color: var(--muted); font-weight: 600; font-size: 13px; }

        .page-actions { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        .control-btn {
            height: 42px; padding: 0 16px; border-radius: 13px;
            border: 1px solid var(--border); background: #fff;
            color: var(--blue-dark); font-weight: 800; cursor: pointer;
            box-shadow: 0 6px 16px rgba(15, 45, 75, 0.04);
            font-size: 13px; display: inline-flex; align-items: center; gap: 8px;
            text-decoration: none; transition: 0.2s;
        }
        .control-btn:hover { background: #f7fbff; color: var(--blue); }
        .control-btn.primary { background: var(--blue); color: #fff; border-color: var(--blue); }
        .control-btn.primary:hover { background: var(--blue-dark); color: #fff; }

        /* ─── CARDS / GRID ─── */
        .card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }

        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 22px;
        }
        .kpi-card {
            padding: 24px; min-height: 166px;
            display: grid; align-content: space-between;
            position: relative; transition: transform 0.22s, box-shadow 0.22s;
        }
        .kpi-card:hover { transform: translateY(-3px); box-shadow: 0 18px 45px rgba(15, 45, 75, 0.12); }
        .kpi-card a.kpi-stretched { position: absolute; inset: 0; z-index: 1; border-radius: inherit; }
        .kpi-icon { width: 58px; height: 58px; border-radius: 17px; display: grid; place-items: center; font-size: 24px; }
        .kpi-icon.blue   { background: #e8f1ff; color: var(--blue); }
        .kpi-icon.green  { background: #e7fff4; color: var(--green); }
        .kpi-icon.orange { background: #fff0df; color: var(--orange); }
        .kpi-icon.purple { background: #f1eaff; color: var(--purple); }
        .kpi-label {
            color: #61728a; font-size: 12px; font-weight: 900;
            text-transform: uppercase; letter-spacing: .04em; margin-top: 18px;
        }
        .kpi-value { font-size: 34px; font-weight: 900; color: var(--text); margin: 8px 0; line-height: 1.05; }
        .kpi-footer {
            display: flex; justify-content: space-between; align-items: center;
            color: var(--muted); font-weight: 700; padding-top: 14px;
            border-top: 1px solid #edf2f7; font-size: 13px;
        }
        .arrow { color: var(--blue); font-weight: 900; }
        .badge-red, .badge-green {
            padding: 5px 10px; border-radius: 999px;
            font-size: 11px; font-weight: 900;
            display: inline-flex; align-items: center; gap: 4px; margin-top: 6px;
        }
        .badge-red   { background: #fff0ef; color: var(--red); }
        .badge-green { background: #e8fff4; color: var(--green); }

        .top-widgets {
            display: grid;
            grid-template-columns: 1fr 1fr 1.3fr;
            gap: 20px; margin-bottom: 22px;
        }
        .widget { padding: 24px; }
        .widget-head { display: flex; align-items: center; gap: 10px; margin-bottom: 18px; }
        .widget-head h3 { font-size: 17px; font-weight: 900; color: var(--text); }
        .widget-icon {
            width: 34px; height: 34px; border-radius: 11px;
            display: grid; place-items: center;
            background: var(--blue-soft); color: var(--blue);
        }

        .metric-list { display: grid; gap: 12px; margin-bottom: 18px; }
        .metric-row {
            display: flex; justify-content: space-between;
            color: var(--muted); font-weight: 700;
            padding-bottom: 12px; border-bottom: 1px solid #f0f3f7;
        }
        .metric-row:last-child { border-bottom: none; padding-bottom: 0; }
        .metric-row strong { color: var(--text); font-weight: 900; }

        .detail-link {
            display: flex; justify-content: space-between; align-items: center;
            border: 1px solid var(--border); border-radius: 13px;
            padding: 13px 15px; color: var(--blue-dark);
            font-weight: 800; background: #fff; font-size: 13px;
            transition: 0.2s;
        }
        .detail-link:hover { background: var(--blue-soft); border-color: var(--blue); color: var(--blue); }

        .revenue-value { font-size: 30px; color: var(--blue); font-weight: 900; margin-bottom: 8px; line-height: 1.1; }
        .small-green, .small-red {
            display: inline-flex; padding: 6px 10px; border-radius: 999px;
            font-size: 12px; font-weight: 900; margin-bottom: 18px;
            align-items: center; gap: 4px;
        }
        .small-green { background: #e9fff4; color: var(--green); }
        .small-red   { background: #fff0ef; color: var(--red); }

        .messages-box { display: flex; align-items: center; justify-content: space-between; gap: 20px; }
        .message-number { font-size: 32px; font-weight: 900; margin: 6px 0 18px; color: var(--text); }
        .blue-btn {
            border: none; background: var(--blue); color: #fff;
            padding: 12px 16px; border-radius: 13px; font-weight: 900; cursor: pointer;
            display: inline-flex; align-items: center; gap: 8px; font-size: 13px;
            text-decoration: none; transition: 0.2s;
        }
        .blue-btn:hover { background: var(--blue-dark); color: #fff; }
        .message-illu { font-size: 80px; opacity: .09; line-height: 1; color: var(--blue); }

        .status-card { padding: 24px; margin-bottom: 22px; }
        .status-card h3 { font-size: 17px; font-weight: 900; margin-bottom: 18px; color: var(--text); }
        .status-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; }
        .status-item { display: grid; gap: 9px; }
        .status-line { display: flex; justify-content: space-between; font-weight: 800; color: var(--muted); font-size: 13px; }
        .dot { display: inline-block; width: 9px; height: 9px; border-radius: 50%; margin-right: 8px; }
        .dot.yellow { background: var(--yellow); }
        .dot.green  { background: var(--green); }
        .dot.red    { background: var(--red); }
        .dot.gray   { background: #aab6c5; }
        .progress {
            height: 8px; border-radius: 999px;
            background: #edf2f7; overflow: hidden;
        }
        .progress > span { display: block; height: 100%; border-radius: inherit; transition: width 0.5s; }

        .charts-grid {
            display: grid; grid-template-columns: 1.55fr .9fr;
            gap: 20px; margin-bottom: 22px;
        }
        .chart-card { padding: 24px; min-height: 395px; }
        .card-head { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 18px; }
        .card-head h3 { font-size: 17px; font-weight: 900; color: var(--text); }
        .pill-select {
            border: none; background: var(--blue-soft); color: var(--blue);
            font-weight: 900; padding: 8px 14px; border-radius: 12px;
            font-size: 12px; cursor: pointer; text-decoration: none;
            display: inline-flex; align-items: center; gap: 6px; transition: 0.2s;
        }
        .pill-select:hover { background: #d8e9ff; color: var(--blue-dark); }

        .chart-area { width: 100%; min-height: 290px; }

        .lower-grid {
            display: grid; grid-template-columns: .75fr 1.55fr;
            gap: 20px; margin-bottom: 22px;
        }
        .payments-chart { display: grid; gap: 18px; margin-top: 12px; }
        .pay-row {
            display: grid; grid-template-columns: 110px 1fr 30px;
            align-items: center; gap: 12px;
            color: var(--muted); font-weight: 800; font-size: 13px;
        }
        .pay-row strong { color: var(--text); text-align: right; font-weight: 900; }
        .pay-bar { height: 14px; background: #edf2f7; border-radius: 999px; overflow: hidden; }
        .pay-bar > span {
            display: block; height: 100%;
            background: linear-gradient(90deg, #294c99, #2f7df4);
            border-radius: inherit;
        }

        .table-card { padding: 0; overflow: hidden; }
        .table-card .card-head { padding: 22px 22px 16px; }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th {
            text-align: left; padding: 12px 16px;
            background: #f7fbff; color: #66758a;
            font-size: 11px; text-transform: uppercase; letter-spacing: .05em;
            font-weight: 900; border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }
        td { padding: 13px 16px; border-bottom: 1px solid #edf2f7; color: #42556d; font-weight: 700; vertical-align: middle; }
        tbody tr:hover { background: #fafdff; }
        tbody tr:last-child td { border-bottom: none; }
        .client-name { font-weight: 900; color: var(--text); display: block; }
        .client-email {
            color: var(--muted); font-size: 11px; display: block;
            max-width: 180px; white-space: nowrap; overflow: hidden;
            text-overflow: ellipsis; font-weight: 600;
        }
        .tag { display: inline-flex; padding: 5px 10px; border-radius: 999px; font-size: 11px; font-weight: 900; white-space: nowrap; }
        .tag.pending { background: #fff4d8; color: #9a6b00; }
        .tag.valid   { background: #e8fff4; color: var(--green); }
        .tag.cancel  { background: #fff0ef; color: var(--red); }
        .view-btn {
            width: 32px; height: 32px; border-radius: 10px;
            border: 1px solid #dce8f3; background: #fff;
            color: var(--blue); cursor: pointer; font-weight: 900;
            display: inline-grid; place-items: center; text-decoration: none;
            transition: 0.2s;
        }
        .view-btn:hover { background: var(--blue-soft); border-color: var(--blue); }

        .bottom-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .trip-list, .agency-list { padding: 0 22px 22px; display: grid; gap: 12px; }
        .trip-item, .agency-item {
            display: grid; grid-template-columns: 56px 1fr auto;
            gap: 14px; align-items: center;
            padding: 11px 0; border-bottom: 1px solid #edf2f7;
        }
        .trip-item:last-child, .agency-item:last-child { border-bottom: 0; }
        .trip-img {
            width: 56px; height: 44px; border-radius: 12px; object-fit: cover;
            background: var(--blue-soft); display: grid; place-items: center;
            color: var(--blue); font-size: 18px;
        }
        .trip-title, .agency-title { color: var(--text); font-weight: 800; line-height: 1.35; font-size: 13px; }
        .trip-count { color: var(--blue); font-weight: 900; font-size: 16px; }
        .agency-action {
            color: var(--blue); font-weight: 900; font-size: 16px;
            width: 30px; height: 30px; border-radius: 8px;
            display: grid; place-items: center; transition: 0.2s;
        }
        .agency-action:hover { background: var(--blue-soft); }
        .agency-icon {
            width: 46px; height: 46px; border-radius: 14px;
            background: #e8fff4; color: var(--green);
            display: grid; place-items: center; font-size: 20px;
        }
        .agency-city { color: var(--muted); font-size: 11px; font-weight: 700; margin-top: 3px; }

        .empty-row { padding: 30px; color: var(--muted); text-align: center; font-weight: 700; }

        .footer {
            padding: 24px 34px;
            display: flex; justify-content: space-between; align-items: center;
            color: var(--muted); font-size: 13px; font-weight: 700;
            border-top: 1px solid var(--border); margin-top: 20px;
            background: #fff;
        }

        /* ─── ANIMATIONS ─── */
        .fade-in { animation: fadeIn 0.45s ease both; }
        .fade-in.d1 { animation-delay: 0.05s; }
        .fade-in.d2 { animation-delay: 0.10s; }
        .fade-in.d3 { animation-delay: 0.15s; }
        .fade-in.d4 { animation-delay: 0.20s; }
        .fade-in.d5 { animation-delay: 0.25s; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ─── RESPONSIVE ─── */
        @media (max-width: 1400px) {
            .top-widgets { grid-template-columns: 1fr 1fr; }
            .top-widgets > :nth-child(3) { grid-column: 1 / -1; }
            .lower-grid  { grid-template-columns: 1fr; }
        }

        @media (max-width: 1200px) {
            :root { --sidebar: 240px; }
            .kpi-grid    { grid-template-columns: repeat(2, 1fr); }
            .top-widgets { grid-template-columns: 1fr; }
            .top-widgets > :nth-child(3) { grid-column: auto; }
            .charts-grid { grid-template-columns: 1fr; }
            .bottom-grid { grid-template-columns: 1fr; }
            .status-grid { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 900px) {
            .sidebar { transform: translateX(-100%); transition: 0.25s; }
            .sidebar.open { transform: translateX(0); box-shadow: 6px 0 30px rgba(0, 0, 0, 0.18); }
            .main { margin-left: 0; width: 100%; }
            .hamburger { display: grid; place-items: center; }
            .topbar { padding: 0 18px; }
            .topbar-logo { min-width: 122px; padding-inline: 12px; }
            .topbar-logo-img { height: 34px; }
            .profile-info { display: none; }
            .profile { padding-right: 8px; }
            .content { padding: 22px 18px; }
            .page-head { flex-direction: column; align-items: stretch; }
            .kpi-grid { grid-template-columns: 1fr; }
            .status-grid { grid-template-columns: 1fr; }
            .search { display: none; }
            table { min-width: 750px; }
            .footer { flex-direction: column; gap: 8px; padding: 20px 18px; }
        }

        /* ─── SIDEBAR OVERLAY (mobile) ─── */
        .sidebar-overlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(15, 45, 75, 0.45);
            z-index: 49;
        }
        .sidebar-overlay.open { display: block; }
    </style>
</head>
<body>

<div class="admin-layout">

    {{-- ═════════════ SIDEBAR ═════════════ --}}
    <aside class="sidebar" id="dashv2-sidebar">
        <div class="brand">
            <a href="{{ route('admin.dashboard.v2') }}" class="brand-logo-link" aria-label="{{ $brandName }}">
                <img src="{{ $brandLogoDark }}" alt="{{ $brandName }}" class="brand-logo-img">
            </a>
            <div class="brand-subtitle">Voyagez en toute confiance</div>
        </div>

        <div class="menu-section">
            <div class="menu">
                <a href="{{ route('admin.dashboard.v2') }}" class="menu-item active">
                    <span class="menu-icon"><i class="bx bx-home-alt"></i></span>
                    <span>Tableau de bord</span>
                </a>
                @if(\Illuminate\Support\Facades\Route::has('admin.dashboard.vue-globale'))
                    <a href="{{ route('admin.dashboard.vue-globale') }}" class="menu-item">
                        <span class="menu-icon"><i class="bx bx-arrow-back"></i></span>
                        <span>Retour Dashboard V1</span>
                    </a>
                @endif
            </div>
        </div>

        <div class="menu-section">
            <div class="menu-label">Réservations</div>
            <div class="menu">
                @if(\Illuminate\Support\Facades\Route::has('admin.reservations.index'))
                    <a href="{{ route('admin.reservations.index') }}" class="menu-item">
                        <span class="menu-icon"><i class="bx bx-calendar-check"></i></span>Réservations
                    </a>
                @endif
                @if(\Illuminate\Support\Facades\Route::has('admin.messagerie.index'))
                    <a href="{{ route('admin.messagerie.index') }}" class="menu-item">
                        <span class="menu-icon"><i class="bx bx-envelope"></i></span>Messagerie
                    </a>
                @endif
            </div>
        </div>

        <div class="menu-section">
            <div class="menu-label">Gestion</div>
            <div class="menu">
                @if(\Illuminate\Support\Facades\Route::has('admin.customers.clients.index'))
                    <a href="{{ route('admin.customers.clients.index') }}" class="menu-item">
                        <span class="menu-icon"><i class="bx bx-group"></i></span>Clients
                    </a>
                @endif
                @if(\Illuminate\Support\Facades\Route::has('admin.branches.index'))
                    <a href="{{ route('admin.branches.index') }}" class="menu-item">
                        <span class="menu-icon"><i class="bx bx-buildings"></i></span>Agences
                    </a>
                @endif
                @if(\Illuminate\Support\Facades\Route::has('admin.circuits.voyages.index'))
                    <a href="{{ route('admin.circuits.voyages.index') }}" class="menu-item">
                        <span class="menu-icon"><i class="bx bx-map-alt"></i></span>Voyages
                    </a>
                @endif
            </div>
        </div>

        <div class="menu-section">
            <div class="menu-label">Compte</div>
            <div class="menu">
                @if(\Illuminate\Support\Facades\Route::has('admin.profile.edit'))
                    <a href="{{ route('admin.profile.edit') }}" class="menu-item">
                        <span class="menu-icon"><i class="bx bx-user-circle"></i></span>Mon profil
                    </a>
                @endif
                @if(\Illuminate\Support\Facades\Route::has('logout.get'))
                    <a href="{{ route('logout.get') }}" class="menu-item danger">
                        <span class="menu-icon"><i class="bx bx-power-off"></i></span>Déconnexion
                    </a>
                @endif
            </div>
        </div>

        <div class="help-box">
            <h4>Besoin d'aide ?</h4>
            <p>Consultez notre centre d'aide ou contactez le support.</p>
            <a href="mailto:contact@ajinsafro.ma">Centre d'aide</a>
        </div>
    </aside>

    <div class="sidebar-overlay" id="dashv2-overlay"></div>

    {{-- ═════════════ MAIN ═════════════ --}}
    <main class="main">

        {{-- ─── TOPBAR ─── --}}
        <header class="topbar">
            <div class="topbar-left">
                <div class="topbar-brand-group">
                    <button type="button" class="hamburger" id="dashv2-hamburger" aria-label="Menu">
                        <i class="bx bx-menu"></i>
                    </button>

                    <a href="{{ route('admin.dashboard.v2') }}" class="topbar-logo" aria-label="{{ $brandName }}">
                        <img src="{{ $brandLogoLight }}" alt="{{ $brandName }}" class="logo-img topbar-logo-img">
                    </a>
                </div>

                <div class="search">
                    <span class="icon"><i class="bx bx-search"></i></span>
                    <input type="text" placeholder="Rechercher (voyage, agence, réservation...)">
                    <span class="shortcut">⌘ K</span>
                </div>
            </div>

            <div class="topbar-actions">
                @if(\Illuminate\Support\Facades\Route::has('admin.reservations.index'))
                    <a href="{{ route('admin.reservations.index') }}" class="notif" title="Notifications">
                        <i class="bx bx-bell"></i>
                        @if(($stats['reservations_today'] ?? 0) > 0)
                            <b>{{ min((int) ($stats['reservations_today'] ?? 0), 99) }}</b>
                        @endif
                    </a>
                @endif

                @if(\Illuminate\Support\Facades\Route::has('admin.messagerie.index'))
                    <a href="{{ route('admin.messagerie.index') }}" class="notif" title="Messages">
                        <i class="bx bx-message-rounded-dots"></i>
                        @if(($stats['messages_count'] ?? 0) > 0)
                            <b>{{ min((int) ($stats['messages_count'] ?? 0), 99) }}</b>
                        @endif
                    </a>
                @endif

                <button type="button" class="profile" aria-label="Profil administrateur">
                    <span class="profile-avatar-shell">
                        <img
                            src="{{ $avatarUrl }}"
                            alt="{{ $userName }}"
                            class="profile-avatar"
                            onerror="this.style.display='none'; this.nextElementSibling.style.display='grid';"
                        >
                        <span class="avatar-fallback">{{ $initials }}</span>
                    </span>
                    <div class="profile-info">
                        <div class="profile-name">{{ $userName }}</div>
                        <div class="profile-role">{{ $userRole }}</div>
                    </div>
                    <span class="profile-caret"><i class="bx bx-chevron-down"></i></span>
                </button>
            </div>
        </header>

        {{-- ─── CONTENT ─── --}}
        <section class="content">

            {{-- Page Head --}}
            <div class="page-head fade-in d1">
                <div class="page-title">
                    <div class="page-title-icon"><i class="bx bx-grid-alt"></i></div>
                    <div>
                        <h1>Tableau de bord</h1>
                        <p>Vue d'ensemble de votre activité — {{ $dateTime }}</p>
                    </div>
                </div>

                <div class="page-actions">
                    <span class="control-btn">
                        <i class="bx bx-calendar"></i> {{ $dateLong }}
                    </span>
                    @if(\Illuminate\Support\Facades\Route::has('admin.reservations.index'))
                        <a href="{{ route('admin.reservations.index') }}" class="control-btn primary">
                            <i class="bx bx-list-ul"></i> Réservations
                        </a>
                    @endif
                </div>
            </div>

            {{-- KPI Grid --}}
            <div class="kpi-grid">

                <div class="card kpi-card fade-in d1">
                    <div>
                        <div class="kpi-icon blue"><i class="bx bx-map-alt"></i></div>
                        <div class="kpi-label">Voyages</div>
                        <div class="kpi-value">{{ $stats['voyages_count'] ?? 0 }}</div>
                    </div>
                    <div class="kpi-footer">
                        <span>Tous les voyages</span>
                        <span class="arrow">→</span>
                    </div>
                    @if(\Illuminate\Support\Facades\Route::has('admin.circuits.voyages.index'))
                        <a href="{{ route('admin.circuits.voyages.index') }}" class="kpi-stretched"></a>
                    @endif
                </div>

                <div class="card kpi-card fade-in d2">
                    <div>
                        <div class="kpi-icon green"><i class="bx bx-buildings"></i></div>
                        <div class="kpi-label">Agences</div>
                        <div class="kpi-value">{{ $stats['branches_count'] ?? 0 }}</div>
                    </div>
                    <div class="kpi-footer">
                        <span>{{ $stats['branches_active'] ?? 0 }} actives</span>
                        <span class="arrow">→</span>
                    </div>
                    @if(\Illuminate\Support\Facades\Route::has('admin.branches.index'))
                        <a href="{{ route('admin.branches.index') }}" class="kpi-stretched"></a>
                    @endif
                </div>

                <div class="card kpi-card fade-in d3">
                    <div>
                        <div class="kpi-icon orange"><i class="bx bx-calendar-event"></i></div>
                        <div class="kpi-label">Réservations</div>
                        <div class="kpi-value">{{ $stats['reservations_total'] ?? 0 }}</div>
                        @php $evo = $stats['reservations_month_evolution'] ?? 0; @endphp
                        @if($evo < 0)
                            <span class="badge-red">↓ {{ $evo }}% ce mois</span>
                        @elseif($evo > 0)
                            <span class="badge-green">↑ +{{ $evo }}% ce mois</span>
                        @endif
                    </div>
                    <div class="kpi-footer">
                        <span>Total enregistré</span>
                        <span class="arrow">→</span>
                    </div>
                    @if(\Illuminate\Support\Facades\Route::has('admin.reservations.index'))
                        <a href="{{ route('admin.reservations.index') }}" class="kpi-stretched"></a>
                    @endif
                </div>

                <div class="card kpi-card fade-in d4">
                    <div>
                        <div class="kpi-icon purple"><i class="bx bx-group"></i></div>
                        <div class="kpi-label">Clients</div>
                        <div class="kpi-value">{{ $stats['clients_count'] ?? 0 }}</div>
                    </div>
                    <div class="kpi-footer">
                        <span>Clients enregistrés</span>
                        <span class="arrow">→</span>
                    </div>
                    @if(\Illuminate\Support\Facades\Route::has('admin.customers.clients.index'))
                        <a href="{{ route('admin.customers.clients.index') }}" class="kpi-stretched"></a>
                    @endif
                </div>

            </div>

            {{-- Top Widgets --}}
            <div class="top-widgets">

                <div class="card widget fade-in d2">
                    <div class="widget-head">
                        <div class="widget-icon"><i class="bx bx-time-five"></i></div>
                        <h3>Activité récente</h3>
                    </div>
                    <div class="metric-list">
                        <div class="metric-row"><span>Aujourd'hui</span><strong>{{ $stats['reservations_today'] ?? 0 }} résa</strong></div>
                        <div class="metric-row"><span>Cette semaine</span><strong>{{ $stats['reservations_this_week'] ?? 0 }} résa</strong></div>
                        <div class="metric-row"><span>Ce mois</span><strong>{{ $stats['reservations_this_month'] ?? 0 }} réservations</strong></div>
                    </div>
                    @if(\Illuminate\Support\Facades\Route::has('admin.reservations.index'))
                        <a href="{{ route('admin.reservations.index') }}" class="detail-link">Voir le détail <span>→</span></a>
                    @endif
                </div>

                <div class="card widget fade-in d3">
                    <div class="widget-head">
                        <div class="widget-icon"><i class="bx bx-euro"></i></div>
                        <h3>Chiffre d'affaires</h3>
                    </div>
                    <p style="color:var(--muted);font-weight:700;margin:0 0 4px;font-size:12px;">Total validé</p>
                    <div class="revenue-value">{{ number_format($stats['revenue_total'] ?? 0, 0, ',', ' ') }} €</div>
                    <p style="color:var(--muted);font-weight:700;margin:0 0 8px;font-size:12px;">
                        Ce mois : <strong style="color:var(--text);">{{ number_format($stats['revenue_this_month'] ?? 0, 0, ',', ' ') }} €</strong>
                    </p>
                    @php $revEvo = $stats['revenue_month_evolution'] ?? 0; @endphp
                    @if($revEvo >= 0)
                        <span class="small-green">↑ +{{ $revEvo }}% vs mois dernier</span>
                    @else
                        <span class="small-red">↓ {{ $revEvo }}% vs mois dernier</span>
                    @endif
                    @if(\Illuminate\Support\Facades\Route::has('admin.reservations.index'))
                        <a href="{{ route('admin.reservations.index') }}" class="detail-link">Voir le détail <span>→</span></a>
                    @endif
                </div>

                <div class="card widget fade-in d4">
                    <div class="messages-box">
                        <div style="flex:1;">
                            <div class="widget-head">
                                <div class="widget-icon"><i class="bx bx-envelope"></i></div>
                                <h3>Messages</h3>
                            </div>
                            <p style="color:var(--muted);font-weight:700;margin:0;font-size:13px;">Boîte Réservations</p>
                            <div class="message-number">{{ $stats['messages_count'] ?? 0 }}</div>
                            @if(\Illuminate\Support\Facades\Route::has('admin.messagerie.index'))
                                <a href="{{ route('admin.messagerie.index') }}" class="blue-btn">
                                    <i class="bx bx-envelope"></i> Ouvrir la messagerie
                                </a>
                            @endif
                        </div>
                        <div class="message-illu"><i class="bx bx-envelope"></i></div>
                    </div>
                </div>

            </div>

            {{-- Status Card --}}
            <div class="card status-card fade-in d3">
                <h3>Répartition des réservations</h3>
                <div class="status-grid">
                    @php
                        $waiting = (int)($stats['reservations_en_cours'] ?? 0);
                        $valid   = (int)($stats['reservations_validees'] ?? 0);
                        $cancel  = (int)($stats['reservations_annulees'] ?? 0);
                        $total   = (int)($stats['reservations_total'] ?? 0);
                    @endphp
                    <div class="status-item">
                        <div class="status-line"><span><i class="dot yellow"></i>En attente</span><strong>{{ $waiting }}</strong></div>
                        <div class="progress"><span style="background:var(--yellow);width:{{ ($waiting / $rsTotal) * 100 }}%;"></span></div>
                    </div>
                    <div class="status-item">
                        <div class="status-line"><span><i class="dot green"></i>Validées</span><strong>{{ $valid }}</strong></div>
                        <div class="progress"><span style="background:var(--green);width:{{ ($valid / $rsTotal) * 100 }}%;"></span></div>
                    </div>
                    <div class="status-item">
                        <div class="status-line"><span><i class="dot red"></i>Annulées</span><strong>{{ $cancel }}</strong></div>
                        <div class="progress"><span style="background:var(--red);width:{{ ($cancel / $rsTotal) * 100 }}%;"></span></div>
                    </div>
                    <div class="status-item">
                        <div class="status-line"><span><i class="dot gray"></i>Total</span><strong>{{ $total }}</strong></div>
                        <div class="progress"><span style="background:#aab6c5;width:100%;"></span></div>
                    </div>
                </div>
            </div>

            {{-- Charts Grid --}}
            <div class="charts-grid">
                <div class="card chart-card fade-in d4">
                    <div class="card-head">
                        <h3>Évolution des réservations &amp; du chiffre d'affaires</h3>
                        <span class="pill-select">6 derniers mois</span>
                    </div>
                    <div id="dashv2-chart-line" class="chart-area"></div>
                </div>
                <div class="card chart-card fade-in d5">
                    <div class="card-head">
                        <h3>Statut des réservations</h3>
                    </div>
                    <div id="dashv2-chart-donut" class="chart-area"></div>
                </div>
            </div>

            {{-- Lower Grid --}}
            <div class="lower-grid">

                <div class="card chart-card fade-in d4">
                    <div class="card-head"><h3>Paiements validés</h3></div>
                    <div class="payments-chart">
                        @forelse($payments as $i => $label)
                            <div class="pay-row">
                                <span>{{ $label }}</span>
                                <div class="pay-bar"><span style="width:{{ ($paySer[$i] / $payMax) * 100 }}%;"></span></div>
                                <strong>{{ $paySer[$i] }}</strong>
                            </div>
                        @empty
                            <div class="empty-row">Aucun paiement enregistré.</div>
                        @endforelse
                    </div>
                    <div style="margin-top:auto;padding-top:24px;">
                        @if(\Illuminate\Support\Facades\Route::has('admin.reservations.index'))
                            <a href="{{ route('admin.reservations.index') }}" class="detail-link">Voir le détail <span>→</span></a>
                        @endif
                    </div>
                </div>

                <div class="card table-card fade-in d5">
                    <div class="card-head">
                        <h3>Dernières réservations</h3>
                        @if(\Illuminate\Support\Facades\Route::has('admin.reservations.index'))
                            <a href="{{ route('admin.reservations.index') }}" class="pill-select">Voir toutes →</a>
                        @endif
                    </div>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>#</th><th>Client</th><th>Voyage</th>
                                    <th>Statut</th><th>Paiement</th><th>Montant</th>
                                    <th>Date</th><th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($lastReservations as $r)
                                    <tr>
                                        <td><span style="color:var(--muted);">#{{ $r->id }}</span></td>
                                        <td>
                                            <span class="client-name">{{ trim(($r->client_first_name ?? '').' '.($r->client_last_name ?? '')) ?: '—' }}</span>
                                            @if($r->client_email)
                                                <span class="client-email">{{ $r->client_email }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($r->tour)
                                                {{ \Illuminate\Support\Str::limit($r->tour->name, 30) }}
                                            @else
                                                <span style="color:var(--muted);">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $st = strtolower($r->status ?? '');
                                                $cls = match(true) {
                                                    in_array($st, ['valide','validee','validée','validated','confirmed']) => 'valid',
                                                    in_array($st, ['annule','annulee','annulée','cancelled','canceled']) => 'cancel',
                                                    default => 'pending',
                                                };
                                                $stLabel = match($cls) {
                                                    'valid'  => 'Validée',
                                                    'cancel' => 'Annulée',
                                                    default  => 'En cours',
                                                };
                                            @endphp
                                            <span class="tag {{ $cls }}">{{ $stLabel }}</span>
                                        </td>
                                        <td>{{ strtoupper($r->payment_type ?? '—') }}</td>
                                        <td>
                                            @if(!empty($r->base_price))
                                                {{ number_format(($r->base_price ?? 0) + ($r->room_supplement_total ?? 0), 0, ',', ' ') }} €
                                            @else
                                                <span style="color:var(--muted);">—</span>
                                            @endif
                                        </td>
                                        <td style="white-space:nowrap;font-size:12px;">
                                            {{ $r->created_at ? $r->created_at->timezone('Africa/Casablanca')->format('d/m/Y H:i') : '—' }}
                                        </td>
                                        <td>
                                            @if(\Illuminate\Support\Facades\Route::has('admin.reservations.edit'))
                                                <a href="{{ route('admin.reservations.edit', $r) }}" class="view-btn" title="Voir"><i class="bx bx-show"></i></a>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="8" class="empty-row">Aucune réservation récente.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            {{-- Bottom Grid --}}
            <div class="bottom-grid">

                <div class="card table-card fade-in d4">
                    <div class="card-head">
                        <h3>Voyages les plus réservés</h3>
                        @if(\Illuminate\Support\Facades\Route::has('admin.circuits.voyages.index'))
                            <a href="{{ route('admin.circuits.voyages.index') }}" class="pill-select">Voir tous</a>
                        @endif
                    </div>
                    <div class="trip-list">
                        @forelse($topVoyages as $tv)
                            @php
                                $voyage = $tv['voyage'];
                                $count  = $tv['total'];
                                $img    = $voyage->main_image_url ?? null;
                            @endphp
                            <div class="trip-item">
                                @if($img)
                                    <img class="trip-img" src="{{ $img }}" alt="">
                                @else
                                    <div class="trip-img"><i class="bx bx-map"></i></div>
                                @endif
                                <div class="trip-title">{{ \Illuminate\Support\Str::limit($voyage->name ?? 'Voyage', 50) }}</div>
                                <strong class="trip-count">{{ $count }}</strong>
                            </div>
                        @empty
                            <div class="empty-row">Aucun voyage réservé pour le moment.</div>
                        @endforelse
                    </div>
                </div>

                <div class="card table-card fade-in d5">
                    <div class="card-head">
                        <h3>Agences actives</h3>
                        @if(\Illuminate\Support\Facades\Route::has('admin.branches.index'))
                            <a href="{{ route('admin.branches.index') }}" class="pill-select">Voir toutes</a>
                        @endif
                    </div>
                    <div class="agency-list">
                        @forelse($recentBranches as $b)
                            <div class="agency-item">
                                <div class="agency-icon"><i class="bx bx-buildings"></i></div>
                                <div>
                                    <div class="agency-title">{{ $b->name }}</div>
                                    @if($b->city || $b->code)
                                        <div class="agency-city">{{ $b->city }}{{ $b->code ? ' • '.$b->code : '' }}</div>
                                    @endif
                                </div>
                                @if(\Illuminate\Support\Facades\Route::has('admin.branches.edit'))
                                    <a href="{{ route('admin.branches.edit', $b) }}" class="agency-action" title="Voir">→</a>
                                @endif
                            </div>
                        @empty
                            <div class="empty-row">Aucune agence à afficher.</div>
                        @endforelse
                    </div>
                </div>

            </div>

        </section>

        <footer class="footer">
            <div>© {{ now()->year }} {{ $brandName }}.ma — Tous droits réservés.</div>
            <div>Dashboard V2 — Design &amp; Développement Ajinsafro</div>
        </footer>

    </main>
</div>

{{-- ═════════════ JS ═════════════ --}}
<script src="{{ URL::asset('build/libs/jquery/jquery.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/apexcharts/apexcharts.min.js') }}"></script>
<script>
(function () {
    // Mobile sidebar toggle
    var hamburger = document.getElementById('dashv2-hamburger');
    var sidebar   = document.getElementById('dashv2-sidebar');
    var overlay   = document.getElementById('dashv2-overlay');
    if (hamburger && sidebar && overlay) {
        hamburger.addEventListener('click', function () {
            sidebar.classList.toggle('open');
            overlay.classList.toggle('open');
        });
        overlay.addEventListener('click', function () {
            sidebar.classList.remove('open');
            overlay.classList.remove('open');
        });
    }

    if (typeof ApexCharts === 'undefined') return;

    // ─── Combo : Réservations + CA ───
    var elLine = document.querySelector('#dashv2-chart-line');
    if (elLine) {
        new ApexCharts(elLine, {
            series: [
                { name: 'Réservations',           type: 'column', data: @json($stats['chart_reservations'] ?? []) },
                { name: "Chiffre d'affaires (€)", type: 'line',   data: @json($stats['chart_revenue'] ?? []) }
            ],
            chart: {
                height: 320, type: 'line',
                toolbar: { show: false }, zoom: { enabled: false },
                animations: { enabled: true, speed: 600 },
                fontFamily: 'Inter, sans-serif'
            },
            colors: ['#0b68d1', '#ff8a00'],
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: [0, 3] },
            fill: { type: ['solid', 'solid'], opacity: [0.95, 1] },
            xaxis: {
                categories: @json($stats['chart_months'] ?? []),
                labels: { style: { colors: '#8c9aad', fontWeight: 700, fontSize: '11px' } },
                axisBorder: { color: '#edf2f7' },
                axisTicks: { color: '#edf2f7' }
            },
            yaxis: [
                {
                    title: { text: 'Réservations', style: { color: '#8c9aad', fontWeight: 700 } },
                    labels: { style: { colors: '#8c9aad' }, formatter: function (v) { return Math.round(v); } }
                },
                {
                    opposite: true,
                    title: { text: 'CA (€)', style: { color: '#8c9aad', fontWeight: 700 } },
                    labels: {
                        style: { colors: '#8c9aad' },
                        formatter: function (v) { return v >= 1000 ? (v / 1000).toFixed(0) + 'k' : v; }
                    }
                }
            ],
            legend: { position: 'top', horizontalAlign: 'right', fontWeight: 700, fontFamily: 'Inter, sans-serif' },
            plotOptions: { bar: { columnWidth: '42%', borderRadius: 6 } },
            grid: { borderColor: '#edf2f7', strokeDashArray: 4 }
        }).render();
    }

    // ─── Donut ───
    var elDonut = document.querySelector('#dashv2-chart-donut');
    if (elDonut) {
        new ApexCharts(elDonut, {
            series: @json($stats['donut_series'] ?? [0, 0, 0]),
            chart: {
                height: 320, type: 'donut',
                animations: { enabled: true, speed: 600 },
                fontFamily: 'Inter, sans-serif'
            },
            labels: @json($stats['donut_labels'] ?? ['En cours','Validées','Annulées']),
            plotOptions: {
                pie: {
                    donut: {
                        size: '68%',
                        labels: {
                            show: true,
                            name: { fontWeight: 700, color: '#71829a' },
                            value: { fontSize: '26px', fontWeight: 900, color: '#172b4d' },
                            total: {
                                show: true, label: 'Total',
                                fontWeight: 700, color: '#71829a',
                                formatter: function (w) {
                                    return w.globals.seriesTotals.reduce(function (a, b) { return a + b; }, 0);
                                }
                            }
                        }
                    }
                }
            },
            legend: { position: 'bottom', fontWeight: 700, fontFamily: 'Inter, sans-serif' },
            colors: ['#f7b500', '#19b982', '#ef4d45'],
            stroke: { width: 0 },
            dataLabels: { enabled: false }
        }).render();
    }
})();
</script>

</body>
</html>
