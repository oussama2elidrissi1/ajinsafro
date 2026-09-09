@extends('layouts.admin-v6')

@section('title', 'Nouvelle offre Hajj & Omra')

@section('content')
    <x-admin.page-header
        title="Nouvelle offre Hajj &amp; Omra"
        subtitle="Renseignez l'offre étape par étape : tarifs, départs, hébergement, programme et médias."
        :breadcrumbs="[
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Hajj & Omra', 'url' => route('admin.hajj-omra.index')],
            ['label' => 'Création'],
        ]"
    />

    {{-- Le formulaire est porte par le partial : pas de balise <form> ici, sous peine d'imbrication. --}}
    @include('admin.hajj-omra._form')
@endsection
