{{--
    Coque du module « Finance & contrôle » : rail des modules + colonne de contenu.

    S'appuie sur la coque unique Espace Admin v2 (header, méga-menus, pied de page).
    Les vues du module étendent ce layout et remplissent @section('finance_content').
--}}
@extends('layouts.espace-admin-v2')

@push('page-css')
    <link href="{{ URL::asset('css/finance-controle.css') }}?v=1" rel="stylesheet">
@endpush

@section('content')
<div class="fc-shell">
    @include('admin.finance.control.partials._rail')

    <div class="fc-main">
        @include('admin.finance.control.partials._flash')
        @yield('finance_content')
    </div>
</div>
@endsection
