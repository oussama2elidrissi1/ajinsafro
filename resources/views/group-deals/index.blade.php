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

    // Get all unique durations for tabs
    $allDestinations = $destinations->toArray();
@endphp
@extends('layouts.front')

@section('title', 'Group Deals – Voyages organisés en groupe · AjiNsafro')

@push('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
  :root {
    --gold:       #C49A3E;
    --gold-light: #E8C46A;
    --gold-dark:  #9A7830;
    --navy:       #0B1829;
    --navy-mid:   #162035;
    --slate:      #F7F5F1;
    --card-bg:    #FFFFFF;
    --success:    #10B981;
  }

  body { font-family: 'DM Sans', sans-serif; background: var(--slate); }
  .gd-serif { font-family: 'Cormorant Garamond', serif; }

  /* ── HERO SECTION - MODERN & IMPACTFUL ─────────────────────────────────── */
  .gd-hero {
    position: relative;
    min-height: 520px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    overflow: hidden;
  }
  @media (min-width: 768px) { .gd-hero { min-height: 600px; } }

  .gd-hero-bg {
    position: absolute; inset: 0;
    background-size: cover; background-position: center;
    transform: scale(1.05);
    animation: heroZoom 20s ease infinite alternate;
  }
  @keyframes heroZoom {
    from { transform: scale(1.05); }
    to { transform: scale(1.00); }
  }

  .gd-hero-overlay {
    position: absolute; inset: 0;
    background: linear-gradient(
      125deg,
      rgba(11,24,41,0.65) 0%,
      rgba(11,24,41,0.75) 35%,
      rgba(11,24,41,0.82) 70%,
      rgba(11,24,41,0.88) 100%
    );
  }

  .gd-hero-content {
    position: relative; z-index: 10;
    padding: 2.5rem 1.5rem 3.5rem;
    max-width: 90rem;
    margin: 0 auto;
    width: 100%;
  }
  @media (min-width: 768px) { .gd-hero-content { padding: 5rem 2.5rem 4rem; } }

  .gd-hero-badge {
    display: inline-flex; align-items: center; gap: 0.6rem;
    background: rgba(196,154,62,0.25);
    border: 1px solid rgba(196,154,62,0.55);
    color: var(--gold-light);
    font-size: 0.68rem; font-weight: 700;
    letter-spacing: 0.12em; text-transform: uppercase;
    padding: 0.5rem 1rem;
    border-radius: 99px;
    backdrop-filter: blur(8px);
    margin-bottom: 1.5rem;
    animation: badgeFloat 6s ease-in-out infinite;
  }
  @keyframes badgeFloat {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-4px); }
  }

  .gd-hero-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(2.2rem, 7.5vw, 5rem);
    font-weight: 700;
    color: #FFFFFF;
    line-height: 1.05;
    letter-spacing: -0.02em;
    margin-bottom: 0.8rem;
    max-width: 56rem;
  }

  .gd-hero-title span {
    color: var(--gold-light);
    font-style: italic;
    text-shadow: 0 4px 12px rgba(196,154,62,0.2);
  }

  .gd-hero-sub {
    font-size: clamp(0.95rem, 2vw, 1.15rem);
    color: rgba(255,255,255,0.85);
    line-height: 1.7;
    max-width: 42rem;
    font-weight: 300;
    margin-bottom: 2.2rem;
  }

  .gd-hero-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 1.8rem;
    margin-top: 2.5rem;
    max-width: 45rem;
  }

  .gd-hero-stat {
    display: flex; flex-direction: column;
    color: white;
    background: rgba(11,24,41,0.3);
    backdrop-filter: blur(10px);
    padding: 1.3rem;
    border-radius: 0.8rem;
    border: 1px solid rgba(196,154,62,0.25);
  }
  .gd-hero-stat strong {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(1.6rem, 4vw, 2.2rem);
    font-weight: 700;
    line-height: 1;
    color: var(--gold-light);
    margin-bottom: 0.3rem;
  }
  .gd-hero-stat span {
    font-size: 0.68rem; letter-spacing: 0.08em; text-transform: uppercase;
    color: rgba(255,255,255,0.65);
  }

  .gd-breadcrumb {
    font-size: 0.76rem; color: rgba(255,255,255,0.55);
    display: flex; align-items: center; gap: 0.5rem;
    margin-bottom: 1.2rem;
  }
  .gd-breadcrumb a { color: rgba(255,255,255,0.7); text-decoration: none; transition: color 0.2s; }
  .gd-breadcrumb a:hover { color: var(--gold-light); }
  .gd-breadcrumb-sep { color: rgba(255,255,255,0.3); }

  /* ── SEPARATOR ─────────────────────────────────────────────────────── */
  .gd-gold-bar {
    height: 4px;
    background: linear-gradient(90deg, var(--gold) 0%, var(--gold-light) 40%, transparent 100%);
    width: 100%;
  }

  /* ── MAIN LAYOUT ───────────────────────────────────────────────────── */
  .gd-page-wrap {
    max-width: 90rem; margin: 0 auto;
    padding: 3rem 1.25rem 5rem;
  }
  @media (min-width: 640px)  { .gd-page-wrap { padding: 3rem 1.5rem 5rem; } }
  @media (min-width: 1024px) { .gd-page-wrap { padding: 3.5rem 2rem 5rem; display: grid; grid-template-columns: 280px 1fr; gap: 3rem; } }

  /* ── FILTERS SECTION - MODERN ──────────────────────────────────────── */
  .gd-filters {
    width: 100%;
    background: var(--card-bg);
    border: 1px solid rgba(0,0,0,0.08);
    border-radius: 1.2rem;
    padding: 2rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.06);
    margin-bottom: 2rem;
  }
  @media (min-width: 1024px) {
    .gd-filters {
      width: 100%; max-width: 280px;
      position: sticky; top: 6rem; z-index: 10;
      margin-bottom: 0;
      border-radius: 1.2rem;
      padding: 1.8rem;
    }
  }

  .gd-filters-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.3rem; font-weight: 700;
    color: var(--navy);
    margin-bottom: 1.6rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid rgba(0,0,0,0.08);
    display: flex; align-items: center; gap: 0.6rem;
  }
  .gd-filters-title::before {
    content: '';
    display: inline-block;
    width: 4px; height: 1.3rem;
    background: linear-gradient(180deg, var(--gold), var(--gold-light));
    border-radius: 2px;
    flex-shrink: 0;
  }

  .gd-field-label {
    display: block;
    font-size: 0.7rem; font-weight: 700;
    letter-spacing: 0.1em; text-transform: uppercase;
    color: #475569;
    margin-bottom: 0.6rem;
  }

  .gd-field-input {
    width: 100%;
    border: 1.5px solid #E2E8F0;
    border-radius: 0.7rem;
    padding: 0.7rem 1rem;
    font-size: 0.875rem;
    color: var(--navy);
    background: #F9FAFB;
    outline: none;
    transition: all 0.2s;
    appearance: none;
    -webkit-appearance: none;
    margin-bottom: 1.2rem;
  }
  .gd-field-input:focus {
    border-color: var(--gold);
    box-shadow: 0 0 0 4px rgba(196,154,62,0.1);
    background: #FFFFFF;
  }

  .gd-btn-primary {
    display: block; width: 100%;
    background: linear-gradient(135deg, var(--navy), var(--navy-mid));
    color: #FFFFFF;
    font-size: 0.8rem; font-weight: 700;
    letter-spacing: 0.07em; text-transform: uppercase;
    padding: 0.9rem 1.2rem;
    border-radius: 0.7rem;
    border: none; cursor: pointer;
    transition: all 0.2s;
    text-align: center; text-decoration: none;
    margin-bottom: 0.7rem;
    box-shadow: 0 4px 12px rgba(11,24,41,0.15);
  }
  .gd-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(11,24,41,0.2);
  }
  .gd-btn-primary:active { transform: translateY(0); }

  .gd-btn-ghost {
    display: block; width: 100%;
    background: transparent;
    color: #6B7280;
    font-size: 0.8rem; font-weight: 600;
    padding: 0.75rem 1rem;
    border-radius: 0.7rem;
    border: 1.5px solid #E5E7EB;
    cursor: pointer;
    transition: all 0.2s;
    text-align: center; text-decoration: none;
  }
  .gd-btn-ghost:hover {
    border-color: var(--gold);
    color: var(--gold-dark);
    background: rgba(196,154,62,0.05);
  }

  /* ── MAIN CONTENT AREA ─────────────────────────────────────────────── */
  .gd-main { width: 100%; }

  .gd-results-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 2rem;
    padding-bottom: 1.2rem;
    border-bottom: 2px solid rgba(0,0,0,0.08);
    flex-wrap: wrap;
    gap: 1rem;
  }
  .gd-results-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(1.3rem, 3vw, 1.8rem);
    font-weight: 700;
    color: var(--navy);
  }
  .gd-results-count {
    font-size: 0.8rem; color: #FFFFFF;
    background: linear-gradient(135deg, var(--gold), var(--gold-light));
    padding: 0.5rem 1rem; border-radius: 99px;
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(196,154,62,0.2);
  }

  .gd-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 2rem;
  }
  @media (min-width: 640px)  { .gd-grid { grid-template-columns: repeat(2, 1fr); gap: 2rem; } }
  @media (min-width: 1024px) { .gd-grid { grid-template-columns: repeat(2, 1fr); gap: 2rem; } }
  @media (min-width: 1280px) { .gd-grid { grid-template-columns: repeat(3, 1fr); gap: 2rem; } }

  /* ── CARD - PREMIUM DESIGN ─────────────────────────────────────────── */
  .gd-card {
    background: var(--card-bg);
    border-radius: 1.2rem;
    overflow: hidden;
    border: 1px solid rgba(0,0,0,0.08);
    box-shadow: 0 4px 20px rgba(0,0,0,0.07);
    display: flex; flex-direction: column;
    transition: all 0.35s cubic-bezier(0.23, 1, 0.320, 1);
  }
  .gd-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 50px rgba(0,0,0,0.14);
    border-color: rgba(196,154,62,0.3);
  }

  .gd-card-img-wrap {
    position: relative;
    aspect-ratio: 16 / 9;
    overflow: hidden;
    background: var(--navy-mid);
  }

  .gd-card-img {
    width: 100%; height: 100%;
    object-fit: cover;
    transition: transform 0.7s cubic-bezier(0.23, 1, 0.320, 1);
  }
  .gd-card:hover .gd-card-img { transform: scale(1.08); }

  .gd-card-img-placeholder {
    width: 100%; height: 100%;
    background: linear-gradient(135deg, #0F172A 0%, #1E3A5F 50%, #2A5A8F 100%);
    display: flex; align-items: center; justify-content: center;
    position: relative;
    overflow: hidden;
  }
  .gd-card-img-placeholder::before {
    content: '';
    position: absolute;
    width: 200%; height: 200%;
    background: radial-gradient(circle, rgba(196,154,62,0.15) 1px, transparent 1px);
    background-size: 50px 50px;
    animation: bgShift 20s linear infinite;
  }
  @keyframes bgShift {
    0% { transform: translate(0, 0); }
    100% { transform: translate(50px, 50px); }
  }

  .gd-card-img-overlay {
    position: absolute; inset: 0;
    background: linear-gradient(
      to bottom,
      rgba(0,0,0,0) 30%,
      rgba(11,24,41,0.4) 70%,
      rgba(11,24,41,0.7) 100%
    );
  }

  .gd-card-badge {
    position: absolute; top: 1rem; left: 1rem;
    background: linear-gradient(135deg, var(--gold), var(--gold-light));
    color: var(--navy);
    font-size: 0.6rem; font-weight: 800;
    letter-spacing: 0.12em; text-transform: uppercase;
    padding: 0.4rem 0.85rem;
    border-radius: 99px;
    box-shadow: 0 4px 16px rgba(196,154,62,0.35);
    z-index: 5;
  }

  .gd-card-price-tag {
    position: absolute; bottom: 1rem; right: 1rem;
    background: rgba(11,24,41,0.85);
    backdrop-filter: blur(10px);
    border: 1.5px solid rgba(196,154,62,0.4);
    color: white;
    border-radius: 0.8rem;
    padding: 0.5rem 0.9rem;
    text-align: right;
    min-width: 7rem;
    z-index: 5;
    box-shadow: 0 8px 24px rgba(0,0,0,0.2);
  }
  .gd-card-price-from {
    font-size: 0.58rem; color: rgba(255,255,255,0.65);
    letter-spacing: 0.07em; text-transform: uppercase;
    display: block; line-height: 1.2;
    margin-bottom: 0.2rem;
  }
  .gd-card-price-value {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.35rem; font-weight: 700;
    color: var(--gold-light);
    line-height: 1;
  }
  .gd-card-price-ondemand {
    font-size: 0.65rem; font-weight: 600;
    color: rgba(255,255,255,0.8);
    line-height: 1.3;
  }

  .gd-card-body {
    padding: 1.5rem 1.6rem 1.8rem;
    display: flex; flex-direction: column; flex: 1;
  }

  .gd-card-dest {
    font-size: 0.65rem; font-weight: 700;
    letter-spacing: 0.1em; text-transform: uppercase;
    color: var(--gold);
    margin-bottom: 0.5rem;
    display: flex; align-items: center; gap: 0.4rem;
  }
  .gd-card-dest-dot {
    width: 5px; height: 5px; background: var(--gold-light);
    border-radius: 50%; display: inline-block;
  }

  .gd-card-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.25rem; font-weight: 700;
    color: var(--navy);
    line-height: 1.3;
    margin-bottom: 0.8rem;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }

  .gd-card-meta {
    display: flex; align-items: center; gap: 1.2rem;
    margin-bottom: 1rem;
    padding: 0.8rem 0;
    border-top: 1px solid rgba(0,0,0,0.05);
    border-bottom: 1px solid rgba(0,0,0,0.05);
  }
  .gd-card-meta-item {
    font-size: 0.73rem; color: #64748B;
    display: flex; align-items: center; gap: 0.35rem;
    font-weight: 500;
  }
  .gd-card-meta-icon { width: 16px; height: 16px; color: var(--gold); flex-shrink: 0; }

  .gd-card-desc {
    font-size: 0.83rem; color: #64748B;
    line-height: 1.6;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    flex: 1;
    margin-bottom: 1.3rem;
  }

  .gd-card-cta {
    display: inline-flex; align-items: center; justify-content: center; gap: 0.6rem;
    background: linear-gradient(135deg, var(--navy), var(--navy-mid));
    color: #FFFFFF;
    font-size: 0.77rem; font-weight: 700;
    letter-spacing: 0.06em; text-transform: uppercase;
    padding: 0.85rem 1.4rem;
    border-radius: 0.7rem;
    text-decoration: none;
    transition: all 0.2s;
    border: 1px solid transparent;
    box-shadow: 0 4px 12px rgba(11,24,41,0.15);
    margin-top: auto;
  }
  .gd-card-cta:hover {
    background: linear-gradient(135deg, var(--gold-dark), var(--gold));
    box-shadow: 0 6px 20px rgba(196,154,62,0.25);
    transform: translateY(-2px);
  }
  .gd-card-cta svg {
    width: 15px; height: 15px;
    transition: transform 0.2s;
  }
  .gd-card-cta:hover svg { transform: translateX(3px); }

  .gd-card-no-link {
    display: inline-flex; align-items: center; justify-content: center; gap: 0.4rem;
    background: #F1F5F9;
    color: #94A3B8;
    font-size: 0.77rem; font-weight: 600;
    padding: 0.8rem 1.4rem;
    border-radius: 0.7rem;
    text-decoration: none;
    margin-top: auto;
    cursor: default;
    border: 1px solid rgba(0,0,0,0.05);
  }

  /* ── EMPTY STATE ───────────────────────────────────────────────────── */
  .gd-empty {
    text-align: center;
    padding: 5rem 2rem;
    background: var(--card-bg);
    border-radius: 1.2rem;
    border: 2px dashed #E2E8F0;
    grid-column: 1 / -1;
  }
  .gd-empty-icon {
    width: 80px; height: 80px; margin: 0 auto 1.8rem;
    background: linear-gradient(135deg, rgba(196,154,62,0.1), rgba(196,154,62,0.05));
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
  }
  .gd-empty-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.6rem; font-weight: 700;
    color: var(--navy); margin-bottom: 0.6rem;
  }
  .gd-empty-sub { font-size: 0.9rem; color: #9CA3AF; margin-bottom: 2rem; }

  /* ── PAGINATION ────────────────────────────────────────────────────── */
  .gd-pagination { margin-top: 3rem; padding-top: 2rem; border-top: 2px solid rgba(0,0,0,0.08); display: flex; justify-content: center; }
  .gd-pagination nav { display: flex; gap: 0.5rem; }
  .gd-pagination .page-link,
  .gd-pagination span[aria-current] {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 2.4rem; height: 2.4rem; padding: 0 0.7rem;
    border-radius: 0.6rem;
    font-size: 0.82rem; font-weight: 600;
    border: 1.5px solid #E5E7EB;
    color: var(--navy);
    text-decoration: none;
    transition: all 0.2s;
  }
  .gd-pagination .page-link:hover { background: rgba(196,154,62,0.1); border-color: var(--gold); color: var(--gold-dark); }
  .gd-pagination span[aria-current] { background: var(--navy); color: white; border-color: var(--navy); }

  /* ── ACTIVE FILTERS ────────────────────────────────────────────────── */
  .gd-active-filters {
    display: flex; flex-wrap: wrap; gap: 0.7rem; margin-bottom: 1.5rem;
  }
  .gd-filter-chip {
    display: inline-flex; align-items: center; gap: 0.4rem;
    background: rgba(196,154,62,0.12);
    border: 1.5px solid rgba(196,154,62,0.35);
    color: var(--gold-dark);
    font-size: 0.73rem; font-weight: 600;
    padding: 0.4rem 0.8rem;
    border-radius: 99px;
  }
  .gd-filter-chip a { color: inherit; text-decoration: none; opacity: 0.7; font-weight: 700; cursor: pointer; }
  .gd-filter-chip a:hover { opacity: 1; }

  /* ── PROGRESSION PARTICIPANTS ──────────────────────────────────────── */
  .gd-participation-block {
    display: flex; flex-direction: column; gap: 0.8rem;
    padding: 1rem;
    background: rgba(196,154,62,0.03);
    border: 1px solid rgba(196,154,62,0.15);
    border-radius: 0.8rem;
  }

  .gd-participation-header {
    display: flex; align-items: center; justify-content: space-between;
    gap: 0.8rem;
  }

  .gd-participants-count {
    font-size: 0.85rem; font-weight: 700;
    color: var(--navy);
    font-family: 'Cormorant Garamond', serif;
    letter-spacing: 0.05em;
  }

  .gd-participation-badge {
    display: inline-flex; align-items: center; gap: 0.3rem;
    padding: 0.3rem 0.7rem;
    border-radius: 99px;
    font-size: 0.65rem; font-weight: 700;
    letter-spacing: 0.08em; text-transform: uppercase;
    white-space: nowrap;
  }

  .gd-participation-badge.guaranteed {
    background: rgba(16,185,129,0.15);
    color: #059669;
    border: 1px solid rgba(16,185,129,0.3);
  }

  .gd-participation-badge.full {
    background: rgba(239,68,68,0.15);
    color: #DC2626;
    border: 1px solid rgba(239,68,68,0.3);
  }

  .gd-participation-badge.almost-full {
    background: rgba(217,119,6,0.15);
    color: #B45309;
    border: 1px solid rgba(217,119,6,0.3);
  }

  .gd-participation-badge.pending {
    background: rgba(196,154,62,0.15);
    color: var(--gold-dark);
    border: 1px solid rgba(196,154,62,0.35);
  }

  .gd-progress-bar-wrap {
    position: relative;
    width: 100%;
    height: 6px;
    background: rgba(0,0,0,0.05);
    border-radius: 99px;
    overflow: hidden;
  }

  .gd-progress-bar-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--gold), var(--gold-light));
    transition: width 0.6s cubic-bezier(0.23, 1, 0.320, 1);
    border-radius: 99px;
    box-shadow: 0 0 8px rgba(196,154,62,0.4);
  }

  .gd-participation-footer {
    font-size: 0.73rem;
    color: #64748B;
    line-height: 1.4;
    font-weight: 500;
  }

  .gd-participation-footer.guaranteed {
    color: #059669;
  }

  .gd-participation-footer.full {
    color: #DC2626;
  }

  .gd-participation-footer.almost-full {
    color: #B45309;
  }

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
            <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            Voyagez en groupe, économisez plus
        </div>

        {{-- Title --}}
        <h1 class="gd-hero-title">
            Voyagez ensemble,<br>
            <span>payez moins cher.</span>
        </h1>

        {{-- Subtitle --}}
        <p class="gd-hero-sub">
            Découvrez nos offres exclusives pour groupes. Plus vous êtes nombreux, plus les tarifs sont avantageux. 
            Des voyages mémorables à des prix qui ravissent vos portefeuilles.
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
            <h2 class="gd-filters-title">Filtrer</h2>

            <div style="display:flex; flex-direction:column; gap:1.3rem;">
                {{-- Search --}}
                <div>
                    <label for="filter-q" class="gd-field-label">Recherche</label>
                    <input
                        type="search"
                        name="q"
                        id="filter-q"
                        value="{{ $f['q'] ?? '' }}"
                        class="gd-field-input"
                        placeholder="Voyage, destination…"
                        autocomplete="off"
                    >
                </div>

                {{-- Destination --}}
                <div>
                    <label for="filter-destination" class="gd-field-label">Destination</label>
                    <div style="position:relative;">
                        <select name="destination" id="filter-destination" class="gd-field-input" style="padding-right:2.5rem; cursor:pointer;">
                            <option value="">Toutes les destinations</option>
                            @foreach ($destinations as $dest)
                                <option value="{{ $dest }}" @selected(($f['destination'] ?? '') === $dest)>{{ $dest }}</option>
                            @endforeach
                        </select>
                        <span style="position:absolute;right:0.9rem;top:50%;transform:translateY(-50%);pointer-events:none;color:#CBD5E1;">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                        </span>
                    </div>
                </div>

                {{-- Group size --}}
                <div>
                    <label for="filter-group-size" class="gd-field-label">Groupe (personnes)</label>
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
                <div style="display:flex; flex-direction:column; gap:0.65rem; padding-top:0.5rem;">
                    <button type="submit" class="gd-btn-primary">
                        Filtrer
                    </button>
                    @if(!empty($f['q']) || !empty($f['destination']))
                        <a href="{{ $listingUrl }}" class="gd-btn-ghost">
                            Réinitialiser
                        </a>
                    @else
                        <a href="{{ $listingUrl }}" class="gd-btn-ghost" style="opacity:0.4; cursor:not-allowed;">
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
            <h2 class="gd-results-title">Nos offres</h2>
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
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <circle cx="11" cy="11" r="8"/>
                        <path d="m21 21-4.35-4.35"/>
                        <path d="M11 8v6M8 11h6" stroke-linecap="round"/>
                    </svg>
                </div>
                <p class="gd-empty-title">Aucune offre trouvée</p>
                <p class="gd-empty-sub">
                    @if($hasFilters)
                        Aucun voyage Group Deal ne correspond à vos critères de recherche. Essayez de modifier vos filtres.
                    @else
                        Aucun voyage Group Deal n'est disponible pour le moment. Revenez bientôt pour découvrir nos prochaines offres.
                    @endif
                </p>
                @if($hasFilters)
                    <a href="{{ $listingUrl }}" class="gd-btn-primary" style="display:inline-flex;width:auto;padding:0.9rem 2.2rem;">
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
                                    <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.2)" stroke-width="1.2">
                                        <rect x="3" y="3" width="18" height="18" rx="2"/>
                                        <circle cx="8.5" cy="8.5" r="1.5"/>
                                        <path d="M21 15l-5-5L5 21"/>
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
                                <p class="gd-card-desc" style="color:#CBD5E1;">
                                    Voyage en groupe avec tarifs dégressifs selon le nombre de participants.
                                </p>
                            @endif

                            {{-- PROGRESSION PARTICIPANTS (Group Deal) --}}
                            @if(($metrics = $deal->groupDealMetrics ?? null) && $metrics['total_capacity'] > 0)
                            <div class="gd-participation-block">
                                {{-- Header: Count + Badge --}}
                                <div class="gd-participation-header">
                                    <span class="gd-participants-count">
                                        {{ $metrics['confirmed_count'] }}/{{ $metrics['total_capacity'] }} participants
                                    </span>
                                    @if($metrics['is_full'])
                                        <span class="gd-participation-badge full">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M20 6L9 17l-5-5"/></svg>
                                            Complet
                                        </span>
                                    @elseif($metrics['is_guaranteed'])
                                        <span class="gd-participation-badge guaranteed">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M20 6L9 17l-5-5"/></svg>
                                            Garanti
                                        </span>
                                    @elseif($metrics['is_almost_full'])
                                        <span class="gd-participation-badge almost-full">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                            Presque complet
                                        </span>
                                    @else
                                        <span class="gd-participation-badge pending">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                            En attente
                                        </span>
                                    @endif
                                </div>

                                {{-- Progress bar --}}
                                <div class="gd-progress-bar-wrap">
                                    <div class="gd-progress-bar-fill" style="width: {{ $metrics['progress_percent'] }}%;" aria-valuenow="{{ $metrics['progress_percent'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>

                                {{-- Footer text --}}
                                @if($metrics['is_full'])
                                    <p class="gd-participation-footer full">
                                        ✓ Départ complet - Aucune place disponible
                                    </p>
                                @elseif($metrics['is_guaranteed'])
                                    <p class="gd-participation-footer guaranteed">
                                        ✓ Départ garanti - {{ $metrics['remaining_places'] }} place{{ $metrics['remaining_places'] !== 1 ? 's' : '' }} restante{{ $metrics['remaining_places'] !== 1 ? 's' : '' }}
                                    </p>
                                @elseif($metrics['is_almost_full'])
                                    <p class="gd-participation-footer almost-full">
                                        ⚠ {{ $metrics['remaining_places'] }} place{{ $metrics['remaining_places'] !== 1 ? 's' : '' }} seulement
                                    </p>
                                @elseif($metrics['missing_to_guarantee'] > 0)
                                    <p class="gd-participation-footer">
                                        +{{ $metrics['missing_to_guarantee'] }} personne{{ $metrics['missing_to_guarantee'] !== 1 ? 's' : '' }} pour garantir le départ
                                    </p>
                                @else
                                    <p class="gd-participation-footer">
                                        {{ $metrics['remaining_places'] }} place{{ $metrics['remaining_places'] !== 1 ? 's' : '' }} disponible{{ $metrics['remaining_places'] !== 1 ? 's' : '' }}
                                    </p>
                                @endif
                            </div>
                            @endif

                            {{-- CTA --}}
                            @if($circuitUrl)
                                <a href="{{ $circuitUrl }}" class="gd-card-cta" target="_blank" rel="noopener">
                                    Découvrir
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                                </a>
                            @else
                                <span class="gd-card-no-link">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.08 9.81a19.79 19.79 0 01-3.07-8.67A2 2 0 012 1h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.91 8.92a16 16 0 006.18 6.17l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>
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
