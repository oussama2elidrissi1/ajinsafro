{{--
    Icone du sous-domaine booking.ajinsafro.net : la meme que la page d'accueil
    ajinsafro.net. Les fichiers sont copies dans public/images/brand/, sans appel
    au domaine public, pour que l'icone reste affichee si WordPress est indisponible.
    Le parametre de version force le navigateur a oublier l'icone precedente.
--}}
@php
    $faviconPath = public_path('favicon.ico');
    $faviconVersion = is_file($faviconPath) ? filemtime($faviconPath) : 1;
@endphp
<link rel="icon" href="{{ asset('favicon.ico') }}?v={{ $faviconVersion }}" sizes="any">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/brand/ajinsafro-32x32.png') }}?v={{ $faviconVersion }}">
<link rel="icon" type="image/png" sizes="192x192" href="{{ asset('images/brand/ajinsafro-192x192.png') }}?v={{ $faviconVersion }}">
<link rel="apple-touch-icon" href="{{ asset('images/brand/ajinsafro-180x180.png') }}?v={{ $faviconVersion }}">
