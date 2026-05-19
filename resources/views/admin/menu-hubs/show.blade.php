@extends('layouts.admin-v6')

@section('title', $title)
@section('page_title', $title)

@push('styles')
<style>
    .aj-menu-hub {
        max-width: 1180px;
        margin: 0 auto;
        display: grid;
        gap: 18px;
    }
    .aj-menu-hub__hero,
    .aj-menu-hub__links {
        background: #fff;
        border: 1px solid #dce8f1;
        border-radius: 22px;
        box-shadow: 0 12px 35px rgba(15, 39, 66, 0.08);
    }
    .aj-menu-hub__hero {
        padding: 24px 26px;
        background:
            radial-gradient(circle at top right, rgba(255, 122, 26, 0.14), transparent 30%),
            linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
    }
    .aj-menu-hub__eyebrow {
        font-size: 11px;
        letter-spacing: .12em;
        text-transform: uppercase;
        color: #6b7f95;
        margin-bottom: 8px;
    }
    .aj-menu-hub__title {
        margin: 0;
        font-size: 30px;
        line-height: 1.08;
        color: #0f2742;
    }
    .aj-menu-hub__subtitle {
        margin: 10px 0 0;
        font-size: 14px;
        color: #5f7389;
        max-width: 760px;
    }
    .aj-menu-hub__status {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-top: 14px;
        padding: 8px 12px;
        border-radius: 999px;
        background: #fff7ed;
        color: #c2410c;
        font-size: 12px;
        font-weight: 700;
    }
    .aj-menu-hub__links {
        padding: 22px;
    }
    .aj-menu-hub__grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
    }
    .aj-menu-hub__card {
        display: grid;
        gap: 8px;
        padding: 18px;
        border: 1px solid #dce8f1;
        border-radius: 18px;
        text-decoration: none;
        background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
        color: inherit;
        transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
    }
    .aj-menu-hub__card:hover {
        transform: translateY(-2px);
        border-color: #9fd5f2;
        box-shadow: 0 10px 25px rgba(15, 95, 143, 0.10);
    }
    .aj-menu-hub__card-title {
        font-size: 17px;
        font-weight: 700;
        color: #0f2742;
    }
    .aj-menu-hub__card-text {
        font-size: 13px;
        line-height: 1.45;
        color: #6b7f95;
    }
    .aj-menu-hub__card-cta {
        font-size: 12px;
        font-weight: 700;
        color: #0f7ab6;
    }
    .aj-menu-hub__empty {
        padding: 18px;
        border-radius: 18px;
        background: #f8fbff;
        color: #6b7f95;
        font-size: 14px;
    }
    @media (max-width: 900px) {
        .aj-menu-hub__grid {
            grid-template-columns: 1fr;
        }
        .aj-menu-hub__hero,
        .aj-menu-hub__links {
            padding-left: 16px;
            padding-right: 16px;
        }
        .aj-menu-hub__title {
            font-size: 24px;
        }
    }
</style>
@endpush

@section('content')
<div class="aj-menu-hub">
    <section class="aj-menu-hub__hero">
        <div class="aj-menu-hub__eyebrow">Ajinsafro Admin</div>
        <h1 class="aj-menu-hub__title">{{ $title }}</h1>
        <p class="aj-menu-hub__subtitle">{{ $subtitle }}</p>
        @if(!empty($status))
            <div class="aj-menu-hub__status">{{ $status }}</div>
        @endif
    </section>

    <section class="aj-menu-hub__links">
        @if(!empty($links))
            <div class="aj-menu-hub__grid">
                @foreach($links as $link)
                    <a href="{{ $link['href'] }}" class="aj-menu-hub__card">
                        <div class="aj-menu-hub__card-title">{{ $link['label'] }}</div>
                        <div class="aj-menu-hub__card-text">{{ $link['description'] }}</div>
                        <div class="aj-menu-hub__card-cta">Ouvrir</div>
                    </a>
                @endforeach
            </div>
        @else
            <div class="aj-menu-hub__empty">Aucun lien metier n est encore disponible pour cette section.</div>
        @endif
    </section>
</div>
@endsection
