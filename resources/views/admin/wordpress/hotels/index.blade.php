@extends('layouts.master-ajinsafro')

@php
    use Illuminate\Support\Str;

    $pageTitle = 'Catalogue Hébergements';
    $currentHotels = $hotels->getCollection();
    $totalHotels = $hotels->total();
    $publishedCount = $currentHotels->where('post_status', 'publish')->count();
    $draftCount = $currentHotels->where('post_status', 'draft')->count();
    $featuredCount = $currentHotels->filter(fn ($hotel) => optional($hotel->stHotel)->is_featured === 'on')->count();
    $activeFilterCount = collect([
        filled($filters['search'] ?? null),
        filled($filters['status'] ?? null),
        filled($filters['star'] ?? null),
        filled($filters['featured'] ?? null),
    ])->filter()->count();

    $activeFilters = [];
    if (filled($filters['search'] ?? null)) {
        $activeFilters[] = 'Recherche : '.Str::limit($filters['search'], 28);
    }
    if (($filters['status'] ?? '') === 'publish') {
        $activeFilters[] = 'Statut : Publiés';
    } elseif (($filters['status'] ?? '') === 'draft') {
        $activeFilters[] = 'Statut : Brouillons';
    }
    if (filled($filters['star'] ?? null)) {
        $activeFilters[] = 'Étoiles : '.(int) $filters['star'];
    }
    if (($filters['featured'] ?? '') === '1') {
        $activeFilters[] = 'Sélection : À la une';
    }
@endphp

@section('title', $pageTitle)

@push('styles')
    <style>
        .hotel-catalog-page {
            --aj-primary: #0468c8;
            --aj-primary-dark: #084d9a;
            --aj-primary-soft: #edf6ff;
            --aj-accent: #ff7f1f;
            --aj-ink: #09203f;
            --aj-muted: #667085;
            --aj-line: #e5edf6;
            --aj-card: #ffffff;
            --aj-soft: #f6f9fc;
            --aj-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
            --aj-shadow-sm: 0 10px 24px rgba(15, 23, 42, 0.05);
        }

        .hotel-catalog-page .aj-shell {
            padding: 28px 22px 34px;
        }

        .hotel-catalog-page .aj-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 24px;
            padding: 16px 20px;
            border: 1px solid rgba(255, 255, 255, 0.45);
            border-radius: 24px;
            background:
                radial-gradient(circle at top right, rgba(4, 104, 200, 0.11), transparent 34%),
                linear-gradient(180deg, rgba(255, 255, 255, 0.92), rgba(248, 251, 255, 0.9));
            box-shadow: var(--aj-shadow-sm);
            backdrop-filter: blur(14px);
        }

        .hotel-catalog-page .aj-contact-row,
        .hotel-catalog-page .aj-topbar-actions,
        .hotel-catalog-page .aj-page-head,
        .hotel-catalog-page .aj-toolbar,
        .hotel-catalog-page .aj-result-meta,
        .hotel-catalog-page .aj-footer {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .hotel-catalog-page .aj-contact-row {
            flex-wrap: wrap;
            color: #3c4b64;
            font-weight: 700;
        }

        .hotel-catalog-page .aj-contact-pill,
        .hotel-catalog-page .aj-profile-chip,
        .hotel-catalog-page .aj-badge,
        .hotel-catalog-page .aj-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border-radius: 999px;
            font-weight: 800;
            white-space: nowrap;
        }

        .hotel-catalog-page .aj-contact-pill {
            padding: 9px 12px;
            background: #fff;
            border: 1px solid var(--aj-line);
            font-size: 13px;
        }

        .hotel-catalog-page .aj-profile-chip {
            padding: 6px 12px 6px 8px;
            background: linear-gradient(135deg, #eef6ff, #ffffff);
            border: 1px solid var(--aj-line);
            color: #22334d;
        }

        .hotel-catalog-page .aj-avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            background: linear-gradient(135deg, #0b8bd9, #0550a7);
            color: #fff;
            font-size: 13px;
            font-weight: 900;
            box-shadow: 0 10px 22px rgba(4, 104, 200, 0.22);
        }

        .hotel-catalog-page .aj-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 44px;
            padding: 0 16px;
            border-radius: 14px;
            border: 1px solid transparent;
            font-weight: 800;
            text-decoration: none;
            transition: .18s ease;
        }

        .hotel-catalog-page .aj-btn:hover {
            transform: translateY(-1px);
        }

        .hotel-catalog-page .aj-btn-primary {
            color: #fff;
            background: linear-gradient(135deg, var(--aj-primary), var(--aj-primary-dark));
            box-shadow: 0 16px 28px rgba(4, 104, 200, 0.25);
        }

        .hotel-catalog-page .aj-btn-soft {
            color: #21344f;
            background: #fff;
            border-color: var(--aj-line);
        }

        .hotel-catalog-page .aj-page-head {
            justify-content: space-between;
            align-items: flex-start;
            gap: 18px;
            margin-bottom: 22px;
        }

        .hotel-catalog-page .aj-title {
            margin: 0;
            color: var(--aj-ink);
            font-size: clamp(1.9rem, 2.2vw, 2.6rem);
            font-weight: 800;
            letter-spacing: -0.04em;
        }

        .hotel-catalog-page .aj-subtitle {
            margin: 8px 0 0;
            color: var(--aj-muted);
            font-weight: 500;
            max-width: 720px;
        }

        .hotel-catalog-page .aj-breadcrumb {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 14px;
            color: #718198;
            font-size: 13px;
            font-weight: 700;
        }

        .hotel-catalog-page .aj-kpis {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 22px;
        }

        .hotel-catalog-page .aj-kpi {
            position: relative;
            overflow: hidden;
            min-height: 118px;
            padding: 20px;
            border-radius: 24px;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.97), rgba(247, 250, 255, 0.96));
            border: 1px solid rgba(228, 237, 246, 0.95);
            box-shadow: var(--aj-shadow-sm);
        }

        .hotel-catalog-page .aj-kpi::after {
            content: "";
            position: absolute;
            top: -42px;
            right: -38px;
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: rgba(4, 104, 200, 0.06);
        }

        .hotel-catalog-page .aj-kpi-head {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .hotel-catalog-page .aj-kpi-icon {
            width: 56px;
            height: 56px;
            border-radius: 18px;
            display: grid;
            place-items: center;
            font-size: 22px;
            font-weight: 900;
        }

        .hotel-catalog-page .aj-kpi-icon.-blue { background: #edf6ff; color: #0468c8; }
        .hotel-catalog-page .aj-kpi-icon.-green { background: #edfdf3; color: #12b76a; }
        .hotel-catalog-page .aj-kpi-icon.-orange { background: #fff6ea; color: #f79009; }
        .hotel-catalog-page .aj-kpi-icon.-violet { background: #f3f0ff; color: #7a5af8; }

        .hotel-catalog-page .aj-kpi-label {
            display: block;
            margin-bottom: 6px;
            color: var(--aj-muted);
            font-size: 13px;
            font-weight: 700;
        }

        .hotel-catalog-page .aj-kpi-value {
            display: block;
            color: var(--aj-ink);
            font-size: 2rem;
            font-weight: 800;
            letter-spacing: -0.04em;
            line-height: 1;
        }

        .hotel-catalog-page .aj-kpi-note {
            display: block;
            margin-top: 6px;
            color: #7a879a;
            font-size: 12px;
            font-weight: 600;
        }

        .hotel-catalog-page .aj-panel {
            margin-bottom: 20px;
            padding: 20px;
            border-radius: 24px;
            background: rgba(255, 255, 255, 0.96);
            border: 1px solid var(--aj-line);
            box-shadow: var(--aj-shadow);
        }

        .hotel-catalog-page .aj-filter-grid {
            display: grid;
            grid-template-columns: minmax(240px, 1.65fr) repeat(3, minmax(0, .8fr)) minmax(180px, auto) auto;
            gap: 12px;
            align-items: end;
        }

        .hotel-catalog-page .aj-field label {
            display: block;
            margin: 0 0 8px 4px;
            color: #62738c;
            font-size: 12px;
            font-weight: 800;
        }

        .hotel-catalog-page .aj-control,
        .hotel-catalog-page .aj-mini-select {
            width: 100%;
            min-height: 46px;
            padding: 0 14px;
            border-radius: 14px;
            border: 1px solid #dbe5f0;
            background: #fff;
            color: #152843;
            font-weight: 600;
            box-shadow: none;
        }

        .hotel-catalog-page .aj-control:focus,
        .hotel-catalog-page .aj-mini-select:focus {
            border-color: rgba(4, 104, 200, 0.55);
            box-shadow: 0 0 0 4px rgba(4, 104, 200, 0.08);
            outline: none;
        }

        .hotel-catalog-page .aj-search-wrap {
            position: relative;
        }

        .hotel-catalog-page .aj-search-wrap .aj-control {
            padding-left: 42px;
        }

        .hotel-catalog-page .aj-search-icon {
            position: absolute;
            top: 50%;
            left: 15px;
            transform: translateY(-50%);
            color: #7a879a;
            font-size: 15px;
        }

        .hotel-catalog-page .aj-filter-chips {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 16px;
            color: var(--aj-muted);
            font-size: 13px;
            font-weight: 700;
        }

        .hotel-catalog-page .aj-chip {
            padding: 8px 11px;
            background: var(--aj-primary-soft);
            border: 1px solid #cae3ff;
            color: var(--aj-primary-dark);
        }

        .hotel-catalog-page .aj-toolbar {
            justify-content: space-between;
            flex-wrap: wrap;
            margin-bottom: 16px;
        }

        .hotel-catalog-page .aj-result-meta {
            flex-wrap: wrap;
            color: #5f6f85;
            font-size: 13px;
            font-weight: 700;
        }

        .hotel-catalog-page .aj-mini-btn {
            min-height: 40px;
            padding: 0 14px;
            border-radius: 12px;
            border: 1px solid var(--aj-line);
            background: #fff;
            color: #31435c;
            font-weight: 800;
        }

        .hotel-catalog-page .aj-view-toggle {
            display: inline-flex;
            padding: 4px;
            border-radius: 14px;
            border: 1px solid var(--aj-line);
            background: #fff;
        }

        .hotel-catalog-page .aj-view-toggle button {
            min-width: 42px;
            height: 38px;
            border: 0;
            border-radius: 10px;
            background: transparent;
            color: #516278;
            font-weight: 900;
        }

        .hotel-catalog-page .aj-view-toggle button.is-active {
            color: #fff;
            background: linear-gradient(135deg, var(--aj-primary), var(--aj-primary-dark));
            box-shadow: 0 10px 18px rgba(4, 104, 200, 0.2);
        }

        .hotel-catalog-page .aj-table-wrap {
            overflow-x: auto;
        }

        .hotel-catalog-page .aj-table {
            width: 100%;
            min-width: 1080px;
            border-collapse: separate;
            border-spacing: 0;
        }

        .hotel-catalog-page .aj-table thead th {
            padding: 14px 12px;
            color: #344054;
            font-size: 11px;
            font-weight: 900;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            background: #f7fbff;
            border-bottom: 1px solid var(--aj-line);
        }

        .hotel-catalog-page .aj-table tbody td {
            padding: 16px 12px;
            color: #24364f;
            font-size: 14px;
            font-weight: 600;
            border-bottom: 1px solid #eef3f8;
            vertical-align: middle;
        }

        .hotel-catalog-page .aj-table tbody tr:hover {
            background: #fbfdff;
        }

        .hotel-catalog-page .aj-thumb {
            width: 64px;
            height: 52px;
            border-radius: 14px;
            overflow: hidden;
            background: linear-gradient(135deg, #e8f3ff, #fff5eb);
            border: 1px solid #dbe5f0;
            box-shadow: 0 8px 16px rgba(15, 23, 42, 0.07);
        }

        .hotel-catalog-page .aj-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .hotel-catalog-page .aj-thumb-placeholder {
            width: 100%;
            height: 100%;
            display: grid;
            place-items: center;
            color: #6f7f95;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .hotel-catalog-page .aj-hotel-title {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 3px;
        }

        .hotel-catalog-page .aj-hotel-title a {
            color: #102340;
            font-weight: 800;
            text-decoration: none;
        }

        .hotel-catalog-page .aj-hotel-title a:hover {
            color: var(--aj-primary);
        }

        .hotel-catalog-page .aj-meta-text {
            color: #7a879a;
            font-size: 12px;
            font-weight: 700;
        }

        .hotel-catalog-page .aj-location strong,
        .hotel-catalog-page .aj-date {
            display: block;
            color: #253754;
            font-size: 13px;
            font-weight: 700;
        }

        .hotel-catalog-page .aj-location span,
        .hotel-catalog-page .aj-date small {
            display: block;
            margin-top: 3px;
            color: #7a879a;
            font-size: 12px;
            font-weight: 600;
        }

        .hotel-catalog-page .aj-badge {
            min-height: 28px;
            padding: 0 11px;
            font-size: 12px;
        }

        .hotel-catalog-page .aj-badge.-success { background: #ecfdf3; color: #067647; }
        .hotel-catalog-page .aj-badge.-warning { background: #fff7e8; color: #b54708; }
        .hotel-catalog-page .aj-badge.-neutral { background: #f2f4f7; color: #475467; }
        .hotel-catalog-page .aj-badge.-info { background: #edf6ff; color: #0550a7; }

        .hotel-catalog-page .aj-stars {
            color: #f59e0b;
            letter-spacing: 0.18em;
            font-size: 13px;
            font-weight: 900;
        }

        .hotel-catalog-page .aj-stars span {
            margin-left: 6px;
            color: #253754;
            letter-spacing: 0;
        }

        .hotel-catalog-page .aj-price {
            color: var(--aj-ink);
            font-size: 15px;
            font-weight: 900;
            white-space: nowrap;
        }

        .hotel-catalog-page .aj-actions {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            flex-wrap: wrap;
        }

        .hotel-catalog-page .aj-icon-btn {
            width: 36px;
            height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            border: 1px solid var(--aj-line);
            background: #fff;
            color: #31435c;
            text-decoration: none;
            transition: .16s ease;
        }

        .hotel-catalog-page .aj-icon-btn:hover {
            color: var(--aj-primary);
            border-color: #c9d8e9;
            background: #f7fbff;
        }

        .hotel-catalog-page .aj-icon-btn.-danger:hover {
            color: #d92d20;
            background: #fff2f0;
            border-color: #ffd2cc;
        }

        .hotel-catalog-page .aj-grid {
            display: none;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
        }

        .hotel-catalog-page .aj-grid.is-active {
            display: grid;
        }

        .hotel-catalog-page .aj-card {
            overflow: hidden;
            border-radius: 22px;
            background: #fff;
            border: 1px solid var(--aj-line);
            box-shadow: var(--aj-shadow-sm);
        }

        .hotel-catalog-page .aj-card-cover {
            height: 170px;
            background: linear-gradient(135deg, #e8f3ff, #fff4e7);
        }

        .hotel-catalog-page .aj-card-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .hotel-catalog-page .aj-card-body {
            padding: 18px;
        }

        .hotel-catalog-page .aj-card-title {
            margin: 0 0 6px;
            color: #102340;
            font-size: 16px;
            font-weight: 800;
        }

        .hotel-catalog-page .aj-card-title a {
            color: inherit;
            text-decoration: none;
        }

        .hotel-catalog-page .aj-card-title a:hover {
            color: var(--aj-primary);
        }

        .hotel-catalog-page .aj-card-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            margin-top: 16px;
        }

        .hotel-catalog-page .aj-pagination-wrap nav > div:first-child,
        .hotel-catalog-page .aj-pagination-wrap nav > div:last-child > div:first-child {
            display: none;
        }

        .hotel-catalog-page .aj-pagination-wrap nav svg {
            width: 16px;
            height: 16px;
        }

        .hotel-catalog-page .aj-empty {
            padding: 34px 22px;
            border-radius: 24px;
            border: 1px dashed #cfdbe7;
            background: linear-gradient(180deg, #fbfdff, #f7fbff);
            text-align: center;
        }

        .hotel-catalog-page .aj-footer {
            justify-content: space-between;
            flex-wrap: wrap;
            margin-top: 16px;
            padding-top: 18px;
            border-top: 1px solid #eef3f8;
            color: #7a879a;
            font-size: 13px;
            font-weight: 600;
        }

        @media (max-width: 1399.98px) {
            .hotel-catalog-page .aj-kpis {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .hotel-catalog-page .aj-filter-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .hotel-catalog-page .aj-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 991.98px) {
            .hotel-catalog-page .aj-shell {
                padding-inline: 14px;
            }

            .hotel-catalog-page .aj-topbar,
            .hotel-catalog-page .aj-page-head,
            .hotel-catalog-page .aj-toolbar,
            .hotel-catalog-page .aj-footer {
                flex-direction: column;
                align-items: stretch;
            }

            .hotel-catalog-page .aj-breadcrumb {
                justify-content: flex-start;
            }

            .hotel-catalog-page .aj-kpis,
            .hotel-catalog-page .aj-filter-grid,
            .hotel-catalog-page .aj-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@section('content')
    <div class="hotel-catalog-page">
        <div class="aj-shell">
            <div class="aj-page-head">
                <div>
                    <h1 class="aj-title">{{ $pageTitle }}</h1>
                    <p class="aj-subtitle">Gérez, filtrez et consultez les hébergements WordPress synchronisés sans modifier la logique métier existante.</p>
                </div>
                <div>
                    <div class="aj-breadcrumb">
                        <span>Admin</span>
                        <span>/</span>
                        <span>Hébergements</span>
                        <span>/</span>
                        <strong style="color:#0b1f3a">Catalogue</strong>
                    </div>
                    <a href="{{ route('admin.wordpress.hotels.create') }}" class="aj-btn aj-btn-primary">
                        <i class="bx bx-plus"></i>
                        <span>Créer un hébergement</span>
                    </a>
                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <section class="aj-kpis">
                <article class="aj-kpi">
                    <div class="aj-kpi-head">
                        <div class="aj-kpi-icon -blue"><i class="bx bx-buildings"></i></div>
                        <div>
                            <span class="aj-kpi-label">Total hébergements</span>
                            <strong class="aj-kpi-value">{{ number_format($totalHotels, 0, ',', ' ') }}</strong>
                            <span class="aj-kpi-note">Résultats sur le catalogue courant</span>
                        </div>
                    </div>
                </article>
                <article class="aj-kpi">
                    <div class="aj-kpi-head">
                        <div class="aj-kpi-icon -green"><i class="bx bx-badge-check"></i></div>
                        <div>
                            <span class="aj-kpi-label">Publiés</span>
                            <strong class="aj-kpi-value">{{ $publishedCount }}</strong>
                            <span class="aj-kpi-note">Sur la page affichée</span>
                        </div>
                    </div>
                </article>
                <article class="aj-kpi">
                    <div class="aj-kpi-head">
                        <div class="aj-kpi-icon -orange"><i class="bx bx-edit-alt"></i></div>
                        <div>
                            <span class="aj-kpi-label">Brouillons</span>
                            <strong class="aj-kpi-value">{{ $draftCount }}</strong>
                            <span class="aj-kpi-note">À compléter ou publier</span>
                        </div>
                    </div>
                </article>
                <article class="aj-kpi">
                    <div class="aj-kpi-head">
                        <div class="aj-kpi-icon -violet"><i class="bx bx-star"></i></div>
                        <div>
                            <span class="aj-kpi-label">À la une</span>
                            <strong class="aj-kpi-value">{{ $featuredCount }}</strong>
                            <span class="aj-kpi-note">Mis en avant dans cette vue</span>
                        </div>
                    </div>
                </article>
            </section>

            <section class="aj-panel">
                <form method="GET">
                    <div class="aj-filter-grid">
                        <div class="aj-field aj-search-wrap">
                            <label for="search">Recherche</label>
                            <span class="aj-search-icon"><i class="bx bx-search"></i></span>
                            <input id="search" type="text" name="search" class="aj-control" value="{{ $filters['search'] ?? '' }}" placeholder="Nom, slug, résumé ou adresse">
                        </div>
                        <div class="aj-field">
                            <label for="status">Statut</label>
                            <select id="status" name="status" class="aj-control">
                                <option value="">Tous les statuts</option>
                                <option value="publish" @selected(($filters['status'] ?? '') === 'publish')>Publié</option>
                                <option value="draft" @selected(($filters['status'] ?? '') === 'draft')>Brouillon</option>
                            </select>
                        </div>
                        <div class="aj-field">
                            <label for="hotel_star">Étoiles</label>
                            <select id="hotel_star" name="hotel_star" class="aj-control">
                                <option value="">Toutes les étoiles</option>
                                @for ($i = 1; $i <= 5; $i++)
                                    <option value="{{ $i }}" @selected((string) ($filters['star'] ?? '') === (string) $i)>{{ $i }} étoile(s)</option>
                                @endfor
                            </select>
                        </div>
                        <div class="aj-field">
                            <label for="featured">Sélection</label>
                            <select id="featured" name="featured" class="aj-control">
                                <option value="">Tous les hébergements</option>
                                <option value="1" @selected(($filters['featured'] ?? '') === '1')>À la une</option>
                            </select>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="submit" class="aj-btn aj-btn-primary w-100">
                                <i class="bx bx-filter-alt"></i>
                                <span>Filtrer</span>
                            </button>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ route('admin.wordpress.hotels.index') }}" class="aj-btn aj-btn-soft w-100">
                                <i class="bx bx-reset"></i>
                                <span>Réinitialiser</span>
                            </a>
                        </div>
                    </div>
                </form>

                <div class="aj-filter-chips">
                    <span>Filtres actifs :</span>
                    @forelse ($activeFilters as $filterLabel)
                        <span class="aj-chip">{{ $filterLabel }}</span>
                    @empty
                        <span class="text-muted">Aucun filtre actif.</span>
                    @endforelse
                    @if ($activeFilterCount > 0)
                        <a href="{{ route('admin.wordpress.hotels.index') }}" class="ms-auto fw-bold text-decoration-none" style="color:#0468c8;">Tout effacer</a>
                    @endif
                </div>
            </section>

            <section class="aj-panel">
                <div class="aj-toolbar">
                    <div class="aj-result-meta">
                        <div class="d-flex align-items-center gap-2">
                            <label for="hotelSortSelect" class="mb-0">Trier par :</label>
                            <select id="hotelSortSelect" class="aj-mini-btn aj-mini-select">
                                <option value="recent">Plus récents</option>
                                <option value="price_asc">Prix croissant</option>
                                <option value="price_desc">Prix décroissant</option>
                                <option value="title_asc">Titre A-Z</option>
                            </select>
                        </div>
                        <button type="button" class="aj-mini-btn" id="hotelExportBtn">
                            <i class="bx bx-export"></i>
                            <span>Exporter la vue</span>
                        </button>
                        <span>{{ $hotels->firstItem() ?? 0 }} - {{ $hotels->lastItem() ?? 0 }} sur {{ $totalHotels }} hébergements</span>
                    </div>
                    <div class="aj-result-meta">
                        <span>Vue :</span>
                        <div class="aj-view-toggle" role="tablist" aria-label="Changer la vue">
                            <button type="button" class="is-active" data-view="table" aria-pressed="true"><i class="bx bx-list-ul"></i></button>
                            <button type="button" data-view="grid" aria-pressed="false"><i class="bx bx-grid-alt"></i></button>
                        </div>
                    </div>
                </div>

                @if($hotels->isEmpty())
                    <div class="aj-empty">
                        <h5 class="mb-2">Aucun hébergement trouvé</h5>
                        <p class="text-muted mb-3">Ajustez vos filtres ou créez un nouvel hébergement pour alimenter le catalogue.</p>
                        <a href="{{ route('admin.wordpress.hotels.create') }}" class="aj-btn aj-btn-primary">
                            <i class="bx bx-plus"></i>
                            <span>Créer un hébergement</span>
                        </a>
                    </div>
                @else
                    <div class="aj-table-wrap" data-hotel-view="table">
                        <table class="aj-table">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Hébergement</th>
                                    <th>Localisation</th>
                                    <th>Statut</th>
                                    <th>Étoiles</th>
                                    <th>Prix min</th>
                                    <th>Modifié le</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($hotels as $hotel)
                                    @php
                                        $thumbUrl = $media->getFeaturedImageUrlVerified($hotel->ID);
                                        $stHotel = $hotel->stHotel;
                                        $isPublished = $hotel->post_status === 'publish';
                                        $isFeatured = optional($stHotel)->is_featured === 'on';
                                        $stars = (int) ($stHotel->hotel_star ?? 0);
                                        $price = $stHotel->min_price;
                                        $address = trim((string) ($stHotel->address ?? ''));
                                    @endphp
                                    <tr
                                        data-title="{{ Str::lower($hotel->post_title) }}"
                                        data-price="{{ is_numeric($price) ? (float) $price : 0 }}"
                                        data-modified="{{ $hotel->post_modified ? \Carbon\Carbon::parse($hotel->post_modified)->timestamp : 0 }}"
                                    >
                                        <td>
                                            <div class="aj-thumb">
                                                @if($thumbUrl)
                                                    <img src="{{ $thumbUrl }}" alt="{{ $hotel->post_title }}" onerror="this.remove(); this.parentElement.querySelector('.aj-thumb-placeholder').style.display='grid';">
                                                @endif
                                                <div class="aj-thumb-placeholder" style="{{ $thumbUrl ? 'display:none;' : '' }}">Ajinsafro</div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="aj-hotel-title">
                                                <a href="{{ route('admin.wordpress.hotels.edit', $hotel) }}">{{ $hotel->post_title }}</a>
                                                @if($isFeatured)
                                                    <span class="aj-badge -info">À la une</span>
                                                @endif
                                            </div>
                                            <div class="aj-meta-text">ID #{{ $hotel->ID }}</div>
                                            @if($hotel->post_excerpt)
                                                <div class="aj-meta-text mt-1">{{ Str::limit(strip_tags($hotel->post_excerpt), 70) }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="aj-location">
                                                <strong>{{ $address !== '' ? $address : 'Adresse non renseignée' }}</strong>
                                                <span>{{ $hotel->post_name ?: 'Slug non renseigné' }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            @if($isPublished)
                                                <span class="aj-badge -success">Publié</span>
                                            @else
                                                <span class="aj-badge -warning">Brouillon</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($stars > 0)
                                                <span class="aj-stars">{{ str_repeat('★', $stars) }}<span>{{ $stars }}</span></span>
                                            @else
                                                <span class="aj-meta-text">Non renseigné</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="aj-price">
                                                {{ is_numeric($price) ? number_format((float) $price, 0, ',', ' ') . ' DH' : '—' }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="aj-date">
                                                {{ $hotel->post_modified ? \Carbon\Carbon::parse($hotel->post_modified)->format('d/m/Y') : '—' }}
                                                <small>{{ $hotel->post_modified ? \Carbon\Carbon::parse($hotel->post_modified)->format('H:i') : '' }}</small>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <div class="aj-actions">
                                                <a href="{{ route('admin.wordpress.hotels.edit', $hotel) }}" class="aj-icon-btn" title="Voir">
                                                    <i class="bx bx-show"></i>
                                                </a>
                                                <a href="{{ route('admin.wordpress.hotels.edit', $hotel) }}" class="aj-icon-btn" title="Modifier">
                                                    <i class="bx bx-pencil"></i>
                                                </a>
                                                <form action="{{ route('admin.wordpress.hotels.destroy', $hotel) }}" method="POST" class="d-inline" onsubmit="return confirm('Déplacer cet hôtel dans la corbeille ?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="aj-icon-btn -danger" title="Supprimer">
                                                        <i class="bx bx-trash"></i>
                                                    </button>
                                                </form>
                                                <a href="{{ route('admin.wordpress.hotels.edit', $hotel) }}" class="aj-icon-btn" title="Plus">
                                                    <i class="bx bx-dots-horizontal-rounded"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="aj-grid" data-hotel-view="grid">
                        @foreach($hotels as $hotel)
                            @php
                                $thumbUrl = $media->getFeaturedImageUrlVerified($hotel->ID);
                                $stHotel = $hotel->stHotel;
                                $isPublished = $hotel->post_status === 'publish';
                                $isFeatured = optional($stHotel)->is_featured === 'on';
                                $stars = (int) ($stHotel->hotel_star ?? 0);
                                $price = $stHotel->min_price;
                            @endphp
                            <article
                                class="aj-card"
                                data-title="{{ Str::lower($hotel->post_title) }}"
                                data-price="{{ is_numeric($price) ? (float) $price : 0 }}"
                                data-modified="{{ $hotel->post_modified ? \Carbon\Carbon::parse($hotel->post_modified)->timestamp : 0 }}"
                            >
                                <div class="aj-card-cover">
                                    @if($thumbUrl)
                                        <img src="{{ $thumbUrl }}" alt="{{ $hotel->post_title }}">
                                    @endif
                                </div>
                                <div class="aj-card-body">
                                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                        <div>
                                            <h4 class="aj-card-title"><a href="{{ route('admin.wordpress.hotels.edit', $hotel) }}">{{ $hotel->post_title }}</a></h4>
                                            <div class="aj-meta-text">ID #{{ $hotel->ID }}</div>
                                        </div>
                                        @if($isFeatured)
                                            <span class="aj-badge -info">À la une</span>
                                        @endif
                                    </div>

                                    <div class="aj-meta-text mb-3">{{ trim((string) ($stHotel->address ?? '')) !== '' ? $stHotel->address : 'Adresse non renseignée' }}</div>

                                    <div class="d-flex flex-wrap gap-2 mb-3">
                                        @if($isPublished)
                                            <span class="aj-badge -success">Publié</span>
                                        @else
                                            <span class="aj-badge -warning">Brouillon</span>
                                        @endif
                                        @if($stars > 0)
                                            <span class="aj-badge -neutral">{{ $stars }} étoile(s)</span>
                                        @endif
                                    </div>

                                    <div class="aj-card-actions">
                                        <span class="aj-price">{{ is_numeric($price) ? number_format((float) $price, 0, ',', ' ') . ' DH' : '—' }}</span>
                                        <div class="aj-actions">
                                            <a href="{{ route('admin.wordpress.hotels.edit', $hotel) }}" class="aj-icon-btn" title="Voir">
                                                <i class="bx bx-show"></i>
                                            </a>
                                            <a href="{{ route('admin.wordpress.hotels.edit', $hotel) }}" class="aj-icon-btn" title="Modifier">
                                                <i class="bx bx-pencil"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <div class="aj-footer">
                        <div>
                            Affichage de {{ $hotels->firstItem() ?? 0 }} à {{ $hotels->lastItem() ?? 0 }} sur {{ $totalHotels }} résultats
                        </div>
                        <div class="aj-pagination-wrap">
                            {{ $hotels->links() }}
                        </div>
                    </div>
                @endif
            </section>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const toggleButtons = document.querySelectorAll('.aj-view-toggle button');
            const tableView = document.querySelector('[data-hotel-view="table"]');
            const gridView = document.querySelector('[data-hotel-view="grid"]');
            const exportBtn = document.getElementById('hotelExportBtn');
            const sortSelect = document.getElementById('hotelSortSelect');

            function setView(mode) {
                toggleButtons.forEach((button) => {
                    const isActive = button.dataset.view === mode;
                    button.classList.toggle('is-active', isActive);
                    button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                });

                if (tableView) {
                    tableView.style.display = mode === 'table' ? 'block' : 'none';
                }

                if (gridView) {
                    gridView.classList.toggle('is-active', mode === 'grid');
                }
            }

            function compareNodes(mode) {
                return function (a, b) {
                    const titleA = a.dataset.title || '';
                    const titleB = b.dataset.title || '';
                    const priceA = Number(a.dataset.price || 0);
                    const priceB = Number(b.dataset.price || 0);
                    const modifiedA = Number(a.dataset.modified || 0);
                    const modifiedB = Number(b.dataset.modified || 0);

                    if (mode === 'price_asc') {
                        return priceA - priceB;
                    }

                    if (mode === 'price_desc') {
                        return priceB - priceA;
                    }

                    if (mode === 'title_asc') {
                        return titleA.localeCompare(titleB, 'fr');
                    }

                    return modifiedB - modifiedA;
                };
            }

            function sortCurrentView(mode) {
                const rowContainer = tableView ? tableView.querySelector('tbody') : null;
                const cardContainer = gridView;

                if (rowContainer) {
                    [...rowContainer.querySelectorAll('tr')]
                        .sort(compareNodes(mode))
                        .forEach((row) => rowContainer.appendChild(row));
                }

                if (cardContainer) {
                    [...cardContainer.querySelectorAll('.aj-card')]
                        .sort(compareNodes(mode))
                        .forEach((card) => cardContainer.appendChild(card));
                }
            }

            toggleButtons.forEach((button) => {
                button.addEventListener('click', function () {
                    setView(this.dataset.view || 'table');
                });
            });

            if (sortSelect) {
                sortSelect.addEventListener('change', function () {
                    sortCurrentView(this.value || 'recent');
                });
            }

            if (exportBtn) {
                exportBtn.addEventListener('click', function () {
                    window.print();
                });
            }

            setView('table');
            sortCurrentView(sortSelect ? sortSelect.value : 'recent');
        });
    </script>
@endpush
