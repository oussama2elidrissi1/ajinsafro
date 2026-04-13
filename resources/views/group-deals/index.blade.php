@php
    $listingUrl   = url('/group-deals');
    $f            = $filters ?? [];
    $publicBase   = rtrim((string) config('app.public_url', config('app.url', '')), '/');

    // Hero image from settings (fallback to abstract geometric SVG inline)
    $heroImagePath = \App\Models\Setting::getValue('hero_image');
    $heroImageUrl  = $heroImagePath
        ? \App\Models\Setting::storageUrl($heroImagePath)
        : asset('front/images/hero.jpg');

    // Helper: build voyage public URL
    $voyageUrl = function (string $slug) use ($publicBase): string {
        if ($publicBase !== '') {
            return $publicBase . '/voyages/' . $slug;
        }
        return url('/voyages/' . $slug);
    };

    // Helper: format price with proper fallback
    $priceLabel = function ($price, $currency) {
        $p = (float) ($price ?? 0);
        if ($p <= 0) {
            return null; // caller renders "Devis sur demande"
        }
        $sym = $currency ?: 'MAD';
        return number_format($p, 0, ',', ' ') . ' ' . $sym;
    };

    // Storage URL helper for featured image
    $imageUrl = function ($path) {
        if (!$path) return null;
        return \Illuminate\Support\Facades\Storage::disk('public')->exists($path)
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($path)
            : null;
    };
@endphp
@extends('layouts.front')

@section('title', 'Group Deals – Voyages organisés en groupe · AjiNsafro')

@push('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
  :root {
    --gold:       #C49A3E;
    --gold-light: #E8C46A;
    --gold-dark:  #9A7830;
    --navy:       #0B1829;
    --navy-mid:   #162035;
    --slate:      #F7F5F1;
    --card-bg:    #FFFFFF;
  }

  body { font-family: 'DM Sans', sans-serif; background: var(--slate); }

  .gd-serif { font-family: 'Cormorant Garamond', serif; }

  /* ── Hero ─────────────────────────────────────────────────────────────── */
  .gd-hero {
    position: relative;
    min-height: 340px;
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    overflow: hidden;
  }
  @media (min-width: 768px) { .gd-hero { min-height: 420px; } }

  .gd-hero-bg {
    position: absolute; inset: 0;
    background-size: cover; background-position: center;
    transform: scale(1.04);
    transition: transform 8s ease;
  }
  .gd-hero:hover .gd-hero-bg { transform: scale(1.00); }

  .gd-hero-overlay {
    position: absolute; inset: 0;
    background: linear-gradient(
      170deg,
      rgba(11,24,41,0.55) 0%,
      rgba(11,24,41,0.72) 50%,
      rgba(11,24,41,0.88) 100%
    );
  }

  .gd-hero-content {
    position: relative; z-index: 10;
    padding: 3.5rem 1.5rem 3rem;
    max-width: 72rem;
    margin: 0 auto;
    width: 100%;
  }
  @media (min-width: 1024px) { .gd-hero-content { padding: 4.5rem 2rem 3.5rem; } }

  .gd-hero-badge {
    display: inline-flex; align-items: center; gap: 0.5rem;
    background: rgba(196,154,62,0.18);
    border: 1px solid rgba(196,154,62,0.45);
    color: var(--gold-light);
    font-size: 0.7rem; font-weight: 600;
    letter-spacing: 0.12em; text-transform: uppercase;
    padding: 0.35rem 0.85rem; border-radius: 99px;
    backdrop-filter: blur(4px);
    margin-bottom: 1.1rem;
  }

  .gd-hero-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(2.6rem, 6vw, 4.2rem);
    font-weight: 700;
    color: #FFFFFF;
    line-height: 1.08;
    letter-spacing: -0.01em;
    margin-bottom: 1rem;
  }

  .gd-hero-title span {
    color: var(--gold-light);
    font-style: italic;
  }

  .gd-hero-sub {
    font-size: 1.05rem; color: rgba(255,255,255,0.82);
    line-height: 1.65; max-width: 38rem;
    font-weight: 300;
    margin-bottom: 1.8rem;
  }

  .gd-hero-stats {
    display: flex; flex-wrap: wrap; gap: 2rem;
  }

  .gd-hero-stat {
    display: flex; flex-direction: column;
    color: white;
  }
  .gd-hero-stat strong {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.9rem; font-weight: 700;
    line-height: 1;
    color: var(--gold-light);
  }
  .gd-hero-stat span {
    font-size: 0.72rem; letter-spacing: 0.08em; text-transform: uppercase;
    color: rgba(255,255,255,0.6); margin-top: 0.2rem;
  }

  .gd-breadcrumb {
    font-size: 0.78rem; color: rgba(255,255,255,0.55);
    display: flex; align-items: center; gap: 0.5rem;
    margin-bottom: 1.4rem;
  }
  .gd-breadcrumb a { color: rgba(255,255,255,0.7); text-decoration: none; }
  .gd-breadcrumb a:hover { color: var(--gold-light); }
  .gd-breadcrumb-sep { color: rgba(255,255,255,0.3); }

  /* ── Gold separator bar ───────────────────────────────────────────────── */
  .gd-gold-bar {
    height: 3px;
    background: linear-gradient(90deg, var(--gold) 0%, var(--gold-light) 50%, transparent 100%);
    width: 100%;
  }

  /* ── Layout ───────────────────────────────────────────────────────────── */
  .gd-page-wrap {
    max-width: 80rem; margin: 0 auto;
    padding: 2.5rem 1.25rem 5rem;
  }
  @media (min-width: 640px)  { .gd-page-wrap { padding: 2.5rem 1.5rem 5rem; } }
  @media (min-width: 1024px) { .gd-page-wrap { padding: 3rem 2rem 5rem; display: flex; gap: 2rem; align-items: flex-start; } }

  /* ── Filters sidebar ─────────────────────────────────────────────────── */
  .gd-filters {
    width: 100%;
    background: var(--card-bg);
    border: 1px solid rgba(0,0,0,0.07);
    border-radius: 1rem;
    padding: 1.6rem;
    box-shadow: 0 2px 12px rgba(0,0,0,0.05);
  }
  @media (min-width: 1024px) {
    .gd-filters {
      width: 19rem; min-width: 19rem; flex-shrink: 0;
      position: sticky; top: 5.5rem; z-index: 10;
    }
  }

  .gd-filters-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.2rem; font-weight: 700;
    color: var(--navy);
    margin-bottom: 1.35rem;
    padding-bottom: 0.85rem;
    border-bottom: 1px solid rgba(0,0,0,0.07);
    display: flex; align-items: center; gap: 0.5rem;
  }
  .gd-filters-title::before {
    content: '';
    display: inline-block;
    width: 3px; height: 1.1rem;
    background: var(--gold);
    border-radius: 2px;
    flex-shrink: 0;
  }

  .gd-field-label {
    display: block;
    font-size: 0.72rem; font-weight: 600;
    letter-spacing: 0.07em; text-transform: uppercase;
    color: #6B7280;
    margin-bottom: 0.45rem;
  }

  .gd-field-input {
    width: 100%;
    border: 1.5px solid #E5E7EB;
    border-radius: 0.6rem;
    padding: 0.65rem 0.85rem;
    font-size: 0.875rem;
    color: var(--navy);
    background: #FAFAFA;
    outline: none;
    transition: border-color 0.15s, box-shadow 0.15s;
    appearance: none;
    -webkit-appearance: none;
  }
  .gd-field-input:focus {
    border-color: var(--gold);
    box-shadow: 0 0 0 3px rgba(196,154,62,0.12);
    background: #FFF;
  }

  .gd-btn-primary {
    display: block; width: 100%;
    background: var(--navy);
    color: #FFFFFF;
    font-size: 0.82rem; font-weight: 600;
    letter-spacing: 0.06em; text-transform: uppercase;
    padding: 0.85rem 1rem;
    border-radius: 0.6rem;
    border: none; cursor: pointer;
    transition: background 0.18s, transform 0.1s;
    text-align: center; text-decoration: none;
  }
  .gd-btn-primary:hover { background: var(--navy-mid); transform: translateY(-1px); }

  .gd-btn-ghost {
    display: block; width: 100%;
    background: transparent;
    color: #6B7280;
    font-size: 0.82rem; font-weight: 500;
    padding: 0.65rem 1rem;
    border-radius: 0.6rem;
    border: 1.5px solid #E5E7EB;
    cursor: pointer;
    transition: border-color 0.15s, color 0.15s;
    text-align: center; text-decoration: none;
  }
  .gd-btn-ghost:hover { border-color: #9CA3AF; color: var(--navy); }

  /* ── Cards grid ─────────────────────────────────────────────────────── */
  .gd-main { flex: 1; min-width: 0; }

  .gd-results-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 1.75rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid rgba(0,0,0,0.07);
  }
  .gd-results-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.45rem; font-weight: 700;
    color: var(--navy);
  }
  .gd-results-count {
    font-size: 0.8rem; color: #9CA3AF;
    background: #F3F4F6;
    padding: 0.3rem 0.8rem; border-radius: 99px;
    font-weight: 500;
  }

  .gd-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.5rem;
  }
  @media (min-width: 640px)  { .gd-grid { grid-template-columns: repeat(2, 1fr); } }
  @media (min-width: 1024px) { .gd-grid { grid-template-columns: repeat(2, 1fr); } }
  @media (min-width: 1280px) { .gd-grid { grid-template-columns: repeat(3, 1fr); } }

  /* ── Card ────────────────────────────────────────────────────────────── */
  .gd-card {
    background: var(--card-bg);
    border-radius: 1rem;
    overflow: hidden;
    border: 1px solid rgba(0,0,0,0.07);
    box-shadow: 0 2px 12px rgba(0,0,0,0.05);
    display: flex; flex-direction: column;
    transition: transform 0.22s ease, box-shadow 0.22s ease;
  }
  .gd-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 40px rgba(0,0,0,0.11);
  }

  .gd-card-img-wrap {
    position: relative;
    aspect-ratio: 16 / 10;
    overflow: hidden;
    background: var(--navy-mid);
  }

  .gd-card-img {
    width: 100%; height: 100%;
    object-fit: cover;
    transition: transform 0.55s ease;
  }
  .gd-card:hover .gd-card-img { transform: scale(1.06); }

  .gd-card-img-placeholder {
    width: 100%; height: 100%;
    background: linear-gradient(135deg, var(--navy) 0%, #1E3A5F 60%, #2A4A72 100%);
    display: flex; align-items: center; justify-content: center;
  }

  .gd-card-img-overlay {
    position: absolute; inset: 0;
    background: linear-gradient(
      to bottom,
      rgba(0,0,0,0) 40%,
      rgba(11,24,41,0.62) 100%
    );
  }

  .gd-card-badge {
    position: absolute; top: 0.85rem; left: 0.85rem;
    background: var(--gold);
    color: var(--navy);
    font-size: 0.62rem; font-weight: 700;
    letter-spacing: 0.1em; text-transform: uppercase;
    padding: 0.28rem 0.65rem;
    border-radius: 99px;
    box-shadow: 0 2px 8px rgba(196,154,62,0.4);
  }

  .gd-card-price-tag {
    position: absolute; bottom: 0.85rem; right: 0.85rem;
    background: rgba(11,24,41,0.75);
    backdrop-filter: blur(6px);
    border: 1px solid rgba(196,154,62,0.35);
    color: white;
    border-radius: 0.5rem;
    padding: 0.35rem 0.7rem;
    text-align: right;
    min-width: 6rem;
  }
  .gd-card-price-from {
    font-size: 0.6rem; color: rgba(255,255,255,0.6);
    letter-spacing: 0.06em; text-transform: uppercase;
    display: block; line-height: 1;
    margin-bottom: 0.15rem;
  }
  .gd-card-price-value {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.2rem; font-weight: 700;
    color: var(--gold-light);
    line-height: 1;
  }
  .gd-card-price-ondemand {
    font-size: 0.65rem; font-weight: 500;
    color: rgba(255,255,255,0.75);
    line-height: 1.2;
  }

  .gd-card-body {
    padding: 1.2rem 1.3rem 1.4rem;
    display: flex; flex-direction: column; flex: 1;
  }

  .gd-card-dest {
    font-size: 0.68rem; font-weight: 600;
    letter-spacing: 0.1em; text-transform: uppercase;
    color: var(--gold-dark);
    margin-bottom: 0.35rem;
    display: flex; align-items: center; gap: 0.3rem;
  }
  .gd-card-dest-dot {
    width: 4px; height: 4px; background: var(--gold);
    border-radius: 50%; display: inline-block;
  }

  .gd-card-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.18rem; font-weight: 700;
    color: var(--navy);
    line-height: 1.25;
    margin-bottom: 0.5rem;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }

  .gd-card-meta {
    display: flex; align-items: center; gap: 1rem;
    margin-bottom: 0.75rem;
  }
  .gd-card-meta-item {
    font-size: 0.75rem; color: #6B7280;
    display: flex; align-items: center; gap: 0.3rem;
  }
  .gd-card-meta-icon { width: 13px; height: 13px; color: var(--gold-dark); flex-shrink: 0; }

  .gd-card-desc {
    font-size: 0.82rem; color: #6B7280;
    line-height: 1.55;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    flex: 1;
    margin-bottom: 1rem;
  }

  .gd-card-cta {
    display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem;
    background: var(--navy);
    color: #FFFFFF;
    font-size: 0.78rem; font-weight: 600;
    letter-spacing: 0.05em; text-transform: uppercase;
    padding: 0.75rem 1.25rem;
    border-radius: 0.6rem;
    text-decoration: none;
    transition: background 0.18s, gap 0.18s;
    margin-top: auto;
  }
  .gd-card-cta:hover {
    background: var(--gold-dark);
    gap: 0.75rem;
  }
  .gd-card-cta svg {
    width: 14px; height: 14px;
    transition: transform 0.18s;
  }
  .gd-card-cta:hover svg { transform: translateX(3px); }

  .gd-card-no-link {
    display: inline-flex; align-items: center; justify-content: center; gap: 0.4rem;
    background: #F3F4F6;
    color: #9CA3AF;
    font-size: 0.78rem; font-weight: 500;
    padding: 0.7rem 1.25rem;
    border-radius: 0.6rem;
    text-decoration: none;
    margin-top: auto;
    cursor: default;
  }

  /* ── Empty state ─────────────────────────────────────────────────────── */
  .gd-empty {
    text-align: center;
    padding: 5rem 1.5rem;
    background: var(--card-bg);
    border-radius: 1rem;
    border: 1px dashed #D1D5DB;
  }
  .gd-empty-icon {
    width: 64px; height: 64px; margin: 0 auto 1.5rem;
    background: #F3F4F6;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
  }
  .gd-empty-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.45rem; font-weight: 700;
    color: var(--navy); margin-bottom: 0.5rem;
  }
  .gd-empty-sub { font-size: 0.88rem; color: #9CA3AF; margin-bottom: 1.75rem; }

  /* ── Pagination ──────────────────────────────────────────────────────── */
  .gd-pagination { margin-top: 2.5rem; padding-top: 1.5rem; border-top: 1px solid #E5E7EB; display: flex; justify-content: center; }
  .gd-pagination nav { display: flex; gap: 0.4rem; }
  .gd-pagination .page-link,
  .gd-pagination span[aria-current] {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 2.2rem; height: 2.2rem; padding: 0 0.6rem;
    border-radius: 0.5rem;
    font-size: 0.82rem; font-weight: 500;
    border: 1.5px solid #E5E7EB;
    color: var(--navy);
    text-decoration: none;
    transition: all 0.15s;
  }
  .gd-pagination .page-link:hover { background: var(--navy); color: white; border-color: var(--navy); }
  .gd-pagination span[aria-current] { background: var(--navy); color: white; border-color: var(--navy); }

  /* ── Active filters strip ────────────────────────────────────────────── */
  .gd-active-filters {
    display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1.25rem;
  }
  .gd-filter-chip {
    display: inline-flex; align-items: center; gap: 0.35rem;
    background: rgba(196,154,62,0.1);
    border: 1px solid rgba(196,154,62,0.3);
    color: var(--gold-dark);
    font-size: 0.72rem; font-weight: 600;
    padding: 0.3rem 0.65rem;
    border-radius: 99px;
  }
  .gd-filter-chip a { color: inherit; text-decoration: none; opacity: 0.7; }
  .gd-filter-chip a:hover { opacity: 1; }
</style>
@endpush

@section('content')
<x-front.navbar />

{{-- ── HERO ──────────────────────────────────────────────────────────────────── --}}
<section class="gd-hero">
    <div class="gd-hero-bg" style="background-image: url('{{ $heroImageUrl }}');" aria-hidden="true"></div>
    <div class="gd-hero-overlay" aria-hidden="true"></div>

    <div class="gd-hero-content">
        {{-- Breadcrumb --}}
        <nav class="gd-breadcrumb" aria-label="Fil d'Ariane">
            <a href="{{ url('/') }}">Accueil</a>
            <span class="gd-breadcrumb-sep" aria-hidden="true">›</span>
            <span style="color: var(--gold-light);">Group Deals</span>
        </nav>

        {{-- Badge --}}
        <div class="gd-hero-badge">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            Offres exclusives groupe
        </div>

        {{-- Title --}}
        <h1 class="gd-hero-title">
            Voyagez ensemble,<br>
            <span>payez moins cher.</span>
        </h1>

        {{-- Subtitle --}}
        <p class="gd-hero-sub">
            Nos Group Deals sont conçus pour les groupes qui veulent vivre une expérience mémorable.
            Plus votre groupe est grand, plus le tarif est avantageux.
        </p>

        {{-- Stats --}}
        @if($deals->total() > 0)
        <div class="gd-hero-stats">
            <div class="gd-hero-stat">
                <strong>{{ $deals->total() }}</strong>
                <span>Offre{{ $deals->total() > 1 ? 's' : '' }} disponible{{ $deals->total() > 1 ? 's' : '' }}</span>
            </div>
            @if($destinations->count() > 0)
            <div class="gd-hero-stat">
                <strong>{{ $destinations->count() }}</strong>
                <span>Destination{{ $destinations->count() > 1 ? 's' : '' }}</span>
            </div>
            @endif
        </div>
        @endif
    </div>
</section>
<div class="gd-gold-bar"></div>

{{-- ── MAIN CONTENT ─────────────────────────────────────────────────────────── --}}
<main>
<div class="gd-page-wrap">

    {{-- ── Filters sidebar ───────────────────────────────────────────────────── --}}
    <aside>
        <form method="get" action="{{ $listingUrl }}" class="gd-filters">
            <h2 class="gd-filters-title">Filtrer les offres</h2>

            <div style="display:flex; flex-direction:column; gap:1.1rem;">
                {{-- Search --}}
                <div>
                    <label for="filter-q" class="gd-field-label">Recherche libre</label>
                    <input
                        type="search"
                        name="q"
                        id="filter-q"
                        value="{{ $f['q'] ?? '' }}"
                        class="gd-field-input"
                        placeholder="Nom du voyage, destination…"
                        autocomplete="off"
                    >
                </div>

                {{-- Destination --}}
                <div>
                    <label for="filter-destination" class="gd-field-label">Destination</label>
                    <div style="position:relative;">
                        <select name="destination" id="filter-destination" class="gd-field-input" style="padding-right:2.25rem; cursor:pointer;">
                            <option value="">Toutes les destinations</option>
                            @foreach ($destinations as $dest)
                                <option value="{{ $dest }}" @selected(($f['destination'] ?? '') === $dest)>{{ $dest }}</option>
                            @endforeach
                        </select>
                        <span style="position:absolute;right:0.75rem;top:50%;transform:translateY(-50%);pointer-events:none;color:#9CA3AF;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                        </span>
                    </div>
                </div>

                {{-- Group size --}}
                <div>
                    <label for="filter-group-size" class="gd-field-label">Taille du groupe (pers.)</label>
                    <input
                        type="number"
                        min="2"
                        max="200"
                        id="filter-group-size"
                        name="group_size"
                        value="{{ $f['group_size'] ?? 6 }}"
                        class="gd-field-input"
                        placeholder="ex: 10"
                    >
                </div>

                {{-- Actions --}}
                <div style="display:flex; flex-direction:column; gap:0.65rem; padding-top:0.35rem;">
                    <button type="submit" class="gd-btn-primary">
                        Appliquer les filtres
                    </button>
                    @if(!empty($f['q']) || !empty($f['destination']))
                        <a href="{{ $listingUrl }}" class="gd-btn-ghost">
                            Réinitialiser les filtres
                        </a>
                    @else
                        <a href="{{ $listingUrl }}" class="gd-btn-ghost" style="opacity:0.5; pointer-events:none;">
                            Réinitialiser
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </aside>

    {{-- ── Cards area ─────────────────────────────────────────────────────────── --}}
    <div class="gd-main">

        {{-- Results header --}}
        <div class="gd-results-header">
            <h2 class="gd-results-title">Offres Group Deals</h2>
            <span class="gd-results-count">
                {{ $deals->total() }} résultat{{ $deals->total() > 1 ? 's' : '' }}
            </span>
        </div>

        {{-- Active filter chips --}}
        @php
            $hasFilters = !empty(trim($f['q'] ?? '')) || !empty(trim($f['destination'] ?? ''));
        @endphp
        @if($hasFilters)
        <div class="gd-active-filters">
            @if(!empty(trim($f['q'] ?? '')))
                <span class="gd-filter-chip">
                    Recherche : "{{ $f['q'] }}"
                    <a href="{{ $listingUrl . '?' . http_build_query(array_merge($f, ['q' => ''])) }}" title="Retirer">✕</a>
                </span>
            @endif
            @if(!empty(trim($f['destination'] ?? '')))
                <span class="gd-filter-chip">
                    {{ $f['destination'] }}
                    <a href="{{ $listingUrl . '?' . http_build_query(array_merge($f, ['destination' => ''])) }}" title="Retirer">✕</a>
                </span>
            @endif
        </div>
        @endif

        @if($deals->isEmpty())
            {{-- ── Empty state ── --}}
            <div class="gd-empty">
                <div class="gd-empty-icon">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#9CA3AF" stroke-width="1.5">
                        <circle cx="11" cy="11" r="8"/>
                        <path d="m21 21-4.35-4.35"/>
                        <path d="M11 8v6M8 11h6" stroke-linecap="round"/>
                    </svg>
                </div>
                <p class="gd-empty-title">Aucune offre trouvée</p>
                <p class="gd-empty-sub">
                    @if($hasFilters)
                        Aucun voyage Group Deal ne correspond à vos critères de recherche.
                    @else
                        Aucun voyage Group Deal n'est disponible pour le moment.
                        Revenez bientôt pour découvrir nos prochaines offres.
                    @endif
                </p>
                @if($hasFilters)
                    <a href="{{ $listingUrl }}" class="gd-btn-primary" style="display:inline-flex;width:auto;padding:0.8rem 2rem;">
                        Voir toutes les offres
                    </a>
                @endif
            </div>

        @else
            {{-- ── Grid ── --}}
            <div class="gd-grid">
                @foreach($deals as $deal)
                    @php
                        $imgSrc      = $imageUrl($deal->featured_image ?? null);
                        $formattedPrice = $priceLabel($deal->price_from, $deal->currency);
                        $hasSlug     = !empty(trim($deal->slug ?? ''));
                        $circuitUrl  = $hasSlug ? $voyageUrl($deal->slug) : null;
                        $dest        = trim($deal->destination ?? '');
                        $duration    = trim($deal->duration_text ?? '');
                        $summary     = trim(strip_tags($deal->accroche ?? ''));
                        $minPeople   = (int) ($deal->min_people ?? 0);
                        $maxPeople   = (int) ($deal->max_people ?? 0);
                    @endphp

                    <article class="gd-card">
                        {{-- Image / placeholder --}}
                        <div class="gd-card-img-wrap">
                            @if($imgSrc)
                                <img src="{{ $imgSrc }}" alt="{{ $deal->name }}" class="gd-card-img" loading="lazy">
                            @else
                                <div class="gd-card-img-placeholder">
                                    <svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.15)" stroke-width="1">
                                        <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                                        <polyline points="9 22 9 12 15 12 15 22"/>
                                    </svg>
                                </div>
                            @endif
                            <div class="gd-card-img-overlay" aria-hidden="true"></div>

                            {{-- Badge --}}
                            <span class="gd-card-badge">Group Deal</span>

                            {{-- Price overlay --}}
                            <div class="gd-card-price-tag">
                                @if($formattedPrice)
                                    <span class="gd-card-price-from">À partir de</span>
                                    <span class="gd-card-price-value">{{ $formattedPrice }}</span>
                                @else
                                    <span class="gd-card-price-ondemand">Devis<br>sur demande</span>
                                @endif
                            </div>
                        </div>

                        {{-- Card body --}}
                        <div class="gd-card-body">
                            {{-- Destination --}}
                            @if($dest)
                                <div class="gd-card-dest">
                                    <span class="gd-card-dest-dot"></span>
                                    {{ $dest }}
                                </div>
                            @endif

                            {{-- Title --}}
                            <h3 class="gd-card-title">{{ $deal->name }}</h3>

                            {{-- Meta: duration + group size --}}
                            @if($duration || $minPeople > 0)
                            <div class="gd-card-meta">
                                @if($duration)
                                <span class="gd-card-meta-item">
                                    <svg class="gd-card-meta-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                    {{ $duration }}
                                </span>
                                @endif
                                @if($minPeople > 0)
                                <span class="gd-card-meta-item">
                                    <svg class="gd-card-meta-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                    {{ $minPeople }}{{ $maxPeople > 0 && $maxPeople !== $minPeople ? '–' . $maxPeople : '+' }} pers.
                                </span>
                                @endif
                            </div>
                            @endif

                            {{-- Short description --}}
                            @if($summary)
                                <p class="gd-card-desc">{{ $summary }}</p>
                            @else
                                <p class="gd-card-desc" style="color:#D1D5DB; font-style:italic; font-size:0.78rem;">
                                    Voyage en groupe avec tarifs dégressifs selon le nombre de participants.
                                </p>
                            @endif

                            {{-- CTA --}}
                            @if($circuitUrl)
                                <a href="{{ $circuitUrl }}" class="gd-card-cta" target="_blank" rel="noopener">
                                    Voir le circuit
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                                </a>
                            @else
                                <span class="gd-card-no-link">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.08 9.81a19.79 19.79 0 01-3.07-8.67A2 2 0 012 1h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.91 8.92a16 16 0 006.18 6.17l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>
                                    Nous contacter
                                </span>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>

            {{-- Pagination --}}
            @if($deals->hasPages())
                <div class="gd-pagination">
                    {{ $deals->links() }}
                </div>
            @endif
        @endif

    </div>{{-- end .gd-main --}}
</div>{{-- end .gd-page-wrap --}}
</main>
@endsection
