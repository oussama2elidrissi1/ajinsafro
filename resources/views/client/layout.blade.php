<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>@yield('title', 'Espace client') · Ajinsafro</title>
    <link rel="shortcut icon" href="{{ URL::asset('build/images/favicon.ico') }}">
    <link href="{{ URL::asset('build/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ URL::asset('build/css/icons.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ URL::asset('build/css/app.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --aj-bg: #F5F3EE;
            --aj-card: #FFFFFF;
            --aj-navy: #1A3A5C;
            --aj-navy-light: #234d7a;
            --aj-gold: #C9913D;
            --aj-gold-light: #f0c97a;
            --aj-text: #1C1C2E;
            --aj-muted: #6B7280;
            --aj-border: #E5E0D5;
            --aj-shadow: 0 2px 16px rgba(26,58,92,0.08);
            --aj-shadow-hover: 0 8px 32px rgba(26,58,92,0.14);
            --aj-radius: 14px;
            --aj-radius-sm: 8px;
        }

        body {
            background-color: var(--aj-bg) !important;
            font-family: 'Outfit', sans-serif !important;
            color: var(--aj-text) !important;
            min-height: 100vh;
        }

        /* ── Portal shell ── */
        .aj-portal-wrap {
            min-height: 100vh;
            background: var(--aj-bg);
        }

        /* ── Header (kept structurally identical, styled here) ── */
        .aj-header {
            background: var(--aj-navy);
            border-bottom: 2px solid rgba(201,145,61,0.3);
            padding: 0 2rem;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 20px rgba(26,58,92,0.25);
        }
        .aj-header-inner {
            max-width: 1280px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.85rem 0;
            flex-wrap: wrap;
        }
        .aj-brand .brand-sub {
            font-family: 'Outfit', sans-serif;
            font-size: 0.65rem;
            font-weight: 500;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--aj-gold);
            line-height: 1;
        }
        .aj-brand h4 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 1.45rem;
            font-weight: 600;
            color: #fff;
            margin: 0;
            line-height: 1.2;
            letter-spacing: 0.01em;
        }
        .aj-nav {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            flex-wrap: wrap;
        }
        .aj-nav .btn-nav {
            font-family: 'Outfit', sans-serif;
            font-size: 0.8rem;
            font-weight: 500;
            letter-spacing: 0.03em;
            padding: 0.42rem 1rem;
            border-radius: 50px;
            border: 1.5px solid rgba(255,255,255,0.22);
            color: rgba(255,255,255,0.82);
            background: transparent;
            text-decoration: none;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            cursor: pointer;
        }
        .aj-nav .btn-nav:hover,
        .aj-nav .btn-nav.active {
            background: rgba(201,145,61,0.18);
            border-color: var(--aj-gold);
            color: var(--aj-gold-light);
        }
        .aj-nav .btn-nav-danger {
            border-color: rgba(220,38,38,0.4);
            color: rgba(252,165,165,0.9);
        }
        .aj-nav .btn-nav-danger:hover {
            background: rgba(220,38,38,0.18);
            border-color: #ef4444;
            color: #fca5a5;
        }
        .aj-nav form { margin: 0; }

        /* ── Main content area ── */
        .aj-content {
            max-width: 1280px;
            margin: 0 auto;
            padding: 2.5rem 1.5rem 4rem;
        }

        /* ── Alerts ── */
        .aj-alert {
            border-radius: var(--aj-radius-sm);
            border: none;
            font-size: 0.875rem;
            padding: 0.85rem 1.2rem;
        }

        /* ── Cards ── */
        .aj-card {
            background: var(--aj-card);
            border-radius: var(--aj-radius);
            box-shadow: var(--aj-shadow);
            border: 1px solid var(--aj-border);
            overflow: hidden;
            transition: box-shadow 0.25s ease;
        }
        .aj-card:hover { box-shadow: var(--aj-shadow-hover); }
        .aj-card-body { padding: 1.75rem; }
        .aj-card-header {
            padding: 1.25rem 1.75rem 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }
        .aj-card-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--aj-navy);
            margin: 0;
            letter-spacing: 0.01em;
        }

        /* ── Status badges ── */
        .aj-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            font-family: 'Outfit', sans-serif;
            font-size: 0.72rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            padding: 0.3rem 0.75rem;
            border-radius: 50px;
        }
        .aj-badge::before {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: currentColor;
            opacity: 0.7;
        }
        .aj-badge-confirmed  { background: #ECFDF5; color: #059669; }
        .aj-badge-pending    { background: #FFFBEB; color: #B45309; }
        .aj-badge-cancelled  { background: #FEF2F2; color: #DC2626; }
        .aj-badge-default    { background: #F3F4F6; color: #4B5563; }
        .aj-badge-info       { background: #EFF6FF; color: #2563EB; }

        /* ── Divider ── */
        .aj-divider {
            height: 1px;
            background: var(--aj-border);
            margin: 1.25rem 0;
        }

        /* ── Empty state ── */
        .aj-empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3.5rem 2rem;
            text-align: center;
            gap: 0.75rem;
        }
        .aj-empty-icon {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: #F0EDE6;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--aj-gold);
            font-size: 1.5rem;
        }
        .aj-empty p {
            font-size: 0.9rem;
            color: var(--aj-muted);
            margin: 0;
        }

        /* ── Buttons ── */
        .btn-aj-primary {
            background: var(--aj-navy);
            color: #fff;
            border: none;
            border-radius: 50px;
            padding: 0.55rem 1.5rem;
            font-family: 'Outfit', sans-serif;
            font-size: 0.82rem;
            font-weight: 500;
            letter-spacing: 0.03em;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }
        .btn-aj-primary:hover {
            background: var(--aj-navy-light);
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(26,58,92,0.25);
        }
        .btn-aj-outline {
            background: transparent;
            color: var(--aj-navy);
            border: 1.5px solid var(--aj-border);
            border-radius: 50px;
            padding: 0.5rem 1.25rem;
            font-family: 'Outfit', sans-serif;
            font-size: 0.82rem;
            font-weight: 500;
            letter-spacing: 0.03em;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }
        .btn-aj-outline:hover {
            border-color: var(--aj-navy);
            color: var(--aj-navy);
            background: #F0EDE6;
        }
        .btn-aj-gold {
            background: var(--aj-gold);
            color: #fff;
            border: none;
            border-radius: 50px;
            padding: 0.65rem 2rem;
            font-family: 'Outfit', sans-serif;
            font-size: 0.88rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            transition: all 0.2s;
        }
        .btn-aj-gold:hover {
            background: #b07e30;
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 4px 14px rgba(201,145,61,0.35);
        }

        /* ── Info rows ── */
        .aj-info-row {
            display: flex;
            gap: 0.5rem;
            align-items: flex-start;
            padding: 0.6rem 0;
            border-bottom: 1px solid var(--aj-border);
        }
        .aj-info-row:last-child { border-bottom: none; }
        .aj-info-label {
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--aj-muted);
            min-width: 110px;
            padding-top: 1px;
        }
        .aj-info-value {
            font-size: 0.9rem;
            color: var(--aj-text);
            font-weight: 400;
        }

        /* ── Table ── */
        .aj-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        .aj-table thead tr th {
            font-family: 'Outfit', sans-serif;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--aj-muted);
            padding: 0 1.5rem 1rem;
            border-bottom: 1px solid var(--aj-border);
        }
        .aj-table tbody tr td {
            padding: 1rem 1.5rem;
            font-size: 0.88rem;
            color: var(--aj-text);
            border-bottom: 1px solid #F2EFE9;
            vertical-align: middle;
        }
        .aj-table tbody tr:last-child td { border-bottom: none; }
        .aj-table tbody tr:hover td { background: #FAFAF7; }

        /* ── Section heading ── */
        .aj-section-heading {
            font-family: 'Cormorant Garamond', serif;
            font-size: 1.75rem;
            font-weight: 600;
            color: var(--aj-navy);
            letter-spacing: 0.01em;
            margin: 0;
        }
        .aj-section-sub {
            font-size: 0.82rem;
            color: var(--aj-muted);
            margin-top: 0.2rem;
        }

        /* Responsive */
        @media (max-width: 576px) {
            .aj-content { padding: 1.5rem 1rem 3rem; }
            .aj-card-body { padding: 1.25rem; }
            .aj-header { padding: 0 1rem; }
        }
    </style>
    @yield('page_styles')
</head>
<body data-sidebar="dark">
<div class="aj-portal-wrap">

    {{-- ── HEADER (structure kept identical) ── --}}
    <header class="aj-header">
        <div class="aj-header-inner">
            <div class="aj-brand">
                <div class="brand-sub">Ajinsafro</div>
                <h4>@yield('page_title', 'Espace client')</h4>
            </div>
            <nav class="aj-nav">
                <a class="btn-nav @if(request()->routeIs('client.dashboard')) active @endif" href="{{ route('client.dashboard') }}">
                    <i class="ri-home-3-line"></i> Dashboard
                </a>
                <a class="btn-nav @if(request()->routeIs('client.reservations.*')) active @endif" href="{{ route('client.reservations.index') }}">
                    <i class="ri-calendar-check-line"></i> Réservations
                </a>
                <a class="btn-nav @if(request()->routeIs('client.profile.*')) active @endif" href="{{ route('client.profile.edit') }}">
                    <i class="ri-user-line"></i> Profil
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn-nav btn-nav-danger">
                        <i class="ri-logout-box-r-line"></i> Déconnexion
                    </button>
                </form>
            </nav>
        </div>
    </header>

    <main class="aj-content">

        @if (session('success'))
            <div class="alert aj-alert alert-success alert-dismissible fade show mb-3" role="alert">
                <i class="ri-checkbox-circle-line me-1"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert aj-alert alert-danger alert-dismissible fade show mb-3" role="alert">
                <i class="ri-error-warning-line me-1"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @yield('content')
    </main>
</div>

<script src="{{ URL::asset('build/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
@include('support.reclamations._floating_button')
@yield('page_scripts')
</body>
</html>
